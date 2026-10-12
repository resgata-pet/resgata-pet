<?php
/**
 * Processamento dos webhooks da Stripe.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Sponsorship;

use Stripe\Exception\SignatureVerificationException;
use Stripe\WebhookSignature;

defined( 'ABSPATH' ) || exit;

/**
 * Confere a assinatura de cada evento e atualiza os padrinhos e os pagamentos.
 *
 * Tudo o que o site sabe sobre uma assinatura vem daqui: a página de retorno
 * do Checkout só agradece. Os e-mails do módulo (`Notifications`) também saem
 * daqui, quando o evento muda a situação gravada. Cada evento é processado uma única vez (`Events`) e
 * eventos fora de ordem não desfazem uma situação mais recente.
 */
final class WebhookHandler {

	/**
	 * Diferença máxima, em segundos, entre a hora do evento e a do servidor.
	 */
	private const TOLERANCE = 300;

	/**
	 * Eventos tratados e o método que cuida de cada um.
	 */
	private const HANDLERS = array(
		'checkout.session.completed'    => 'checkout_completed',
		'customer.subscription.created' => 'subscription_changed',
		'customer.subscription.updated' => 'subscription_changed',
		'customer.subscription.deleted' => 'subscription_changed',
		'invoice.paid'                  => 'invoice_paid',
		'invoice.payment_failed'        => 'invoice_payment_failed',
		'invoice_payment.paid'          => 'invoice_payment_paid',
		'charge.refunded'               => 'charge_refunded',
		'charge.dispute.created'        => 'dispute_changed',
		'charge.dispute.updated'        => 'dispute_changed',
		'charge.dispute.closed'         => 'dispute_changed',
		'customer.updated'              => 'customer_updated',
	);

	/**
	 * Tipos de evento que o endpoint precisa receber da Stripe.
	 *
	 * @return string[]
	 */
	public static function event_types(): array {
		return array_keys( self::HANDLERS );
	}

	/**
	 * Processa uma entrega de webhook.
	 *
	 * @param string $payload   Corpo da requisição, exatamente como chegou.
	 * @param string $signature Cabeçalho `Stripe-Signature`.
	 * @param string $secret    Segredo de assinatura do endpoint.
	 * @return array{status: int, result: string} Código HTTP da resposta e o que aconteceu.
	 */
	public static function handle( string $payload, string $signature, string $secret ): array {
		if ( '' === $secret ) {
			return self::response( 503, 'not_configured' );
		}

		try {
			WebhookSignature::verifyHeader( $payload, $signature, $secret, self::TOLERANCE );
		} catch ( SignatureVerificationException $error ) {
			return self::response( 400, 'invalid_signature' );
		}

		$event = json_decode( $payload, true );

		if ( ! is_array( $event ) || empty( $event['id'] ) || empty( $event['type'] ) || ! is_string( $event['id'] ) || ! is_string( $event['type'] ) || ! is_array( $event['data']['object'] ?? null ) ) {
			return self::response( 400, 'invalid_payload' );
		}

		if ( ! isset( self::HANDLERS[ $event['type'] ] ) ) {
			return self::response( 200, 'ignored' );
		}

		if ( ! Events::claim( $event['id'], $event['type'] ) ) {
			return self::response( 200, 'duplicate' );
		}

		try {
			$saved = call_user_func( array( self::class, self::HANDLERS[ $event['type'] ] ), $event['data']['object'], absint( $event['created'] ?? 0 ), $event['type'] );
		} catch ( \Throwable $error ) {
			$saved = false;
		}

		if ( ! $saved ) {
			// Sem a reserva, a próxima tentativa da Stripe processa o evento de novo.
			Events::release( $event['id'] );

			return self::response( 500, 'failed' );
		}

		return self::response( 200, 'processed' );
	}

	/**
	 * Monta a resposta.
	 *
	 * @param int    $status Código HTTP.
	 * @param string $result O que aconteceu com o evento.
	 * @return array{status: int, result: string}
	 */
	private static function response( int $status, string $result ): array {
		return array(
			'status' => $status,
			'result' => $result,
		);
	}

	/**
	 * ID de um objeto da Stripe que pode vir como texto ou expandido.
	 *
	 * @param mixed $value Valor do campo.
	 */
	private static function id( $value ): string {
		if ( is_array( $value ) ) {
			$value = $value['id'] ?? '';
		}

		return is_string( $value ) ? $value : '';
	}

	/**
	 * `checkout.session.completed`: a pessoa concluiu o Checkout. Guarda nome e e-mail.
	 *
	 * @param array<string, mixed> $session Sessão de Checkout.
	 */
	private static function checkout_completed( array $session ): bool {
		$customer_id = self::id( $session['customer'] ?? '' );

		// Só assinaturas criadas por este módulo. As doações avulsas (`mode: payment`) entram aqui no futuro.
		if ( 'subscription' !== ( $session['mode'] ?? '' ) || '' === $customer_id || Gateway::ORIGIN_VALUE !== ( $session['metadata'][ Gateway::ORIGIN_KEY ] ?? '' ) ) {
			return true;
		}

		$details = is_array( $session['customer_details'] ?? null ) ? $session['customer_details'] : array();
		$fields  = array(
			'name'  => (string) ( $details['name'] ?? '' ),
			'email' => (string) ( $details['email'] ?? '' ),
		);

		$existing = Sponsors::find_by_customer( $customer_id );

		// A situação e o plano chegam pelos eventos da assinatura, que podem vir antes ou depois deste.
		if ( ! $existing || '' === $existing['subscription_id'] ) {
			$fields['subscription_id'] = self::id( $session['subscription'] ?? '' );
		}

		if ( ! Sponsors::upsert( $customer_id, $fields ) ) {
			return false;
		}

		Notifications::maybe_welcome( $customer_id );

		return true;
	}

	/**
	 * `customer.subscription.*`: situação e plano da assinatura.
	 *
	 * @param array<string, mixed> $subscription Assinatura.
	 * @param int                  $created      Momento em que o evento foi gerado.
	 * @param string               $type         Tipo do evento.
	 */
	private static function subscription_changed( array $subscription, int $created, string $type ): bool {
		$customer_id     = self::id( $subscription['customer'] ?? '' );
		$subscription_id = self::id( $subscription );
		$price_id        = self::id( $subscription['items']['data'][0]['price'] ?? '' );
		$status          = sanitize_key( (string) ( $subscription['status'] ?? '' ) );
		$existing        = Sponsors::find_by_customer( $customer_id );

		if ( '' === $customer_id || '' === $subscription_id || '' === $status ) {
			return true;
		}

		// Assinatura de outro produto da mesma conta Stripe: não é apadrinhamento.
		if ( ! $existing && ! Plans::find( $price_id ) ) {
			return true;
		}

		if ( $existing ) {
			$last = (int) $existing['last_event_at'];

			// Evento antigo entregue com atraso: a situação gravada é mais recente.
			if ( $created < $last || ( 'customer.subscription.created' === $type && $last > 0 && $created <= $last ) ) {
				return true;
			}

			// Outra assinatura do mesmo cliente: só a que está gravada conta.
			if ( '' !== $existing['subscription_id'] && $existing['subscription_id'] !== $subscription_id && 'customer.subscription.created' !== $type ) {
				return true;
			}
		}

		// Versões recentes da API trazem o período no item da assinatura; as antigas, na própria assinatura.
		$period_end = absint( $subscription['items']['data'][0]['current_period_end'] ?? ( $subscription['current_period_end'] ?? 0 ) );
		// Cancelamento pedido: a assinatura segue ativa até esta data.
		$cancel_at = absint( $subscription['cancel_at'] ?? 0 );
		if ( ! $cancel_at && ! empty( $subscription['cancel_at_period_end'] ) ) {
			$cancel_at = $period_end;
		}

		$fields = array(
			'subscription_id' => $subscription_id,
			'price_id'        => $price_id,
			'status'          => 'customer.subscription.deleted' === $type ? 'canceled' : $status,
			'last_event_at'   => $created,
			'period_end'      => $period_end ? gmdate( 'Y-m-d H:i:s', $period_end ) : null,
			'cancel_at'       => $cancel_at ? gmdate( 'Y-m-d H:i:s', $cancel_at ) : null,
		);

		if ( Sponsors::STATUS_ACTIVE === $fields['status'] ) {
			$fields['payment_failed'] = 0;
		}

		if ( ! Sponsors::upsert( $customer_id, $fields ) ) {
			return false;
		}

		Plans::refresh_counter();
		Notifications::maybe_welcome( $customer_id );

		// A protetora é avisada uma vez por cancelamento: no pedido ou, sem pedido, no encerramento.
		// Quem nunca chegou a ficar ativo (pagamento não concluído) não gera aviso.
		if ( $existing && ! empty( $existing['welcomed_at'] ) && 'canceled' !== $existing['status'] && empty( $existing['cancel_at'] ) ) {
			$sponsor = array_merge( $existing, $fields );

			if ( 'canceled' === $fields['status'] ) {
				Notifications::canceled( $sponsor );
			} elseif ( $cancel_at ) {
				Notifications::cancel_scheduled( $sponsor, $cancel_at );
			}
		}

		return true;
	}

	/**
	 * `invoice.paid`: guarda a data do último pagamento e registra o valor pago.
	 *
	 * @param array<string, mixed> $invoice Fatura.
	 */
	private static function invoice_paid( array $invoice ): bool {
		$customer_id = self::id( $invoice['customer'] ?? '' );

		if ( '' === $customer_id ) {
			return true;
		}

		$paid_at = absint( $invoice['status_transitions']['paid_at'] ?? 0 );
		$fields  = array(
			'payment_failed' => 0,
			'last_paid_at'   => gmdate( 'Y-m-d H:i:s', $paid_at ? $paid_at : time() ),
		);

		if ( ! Sponsors::find_by_customer( $customer_id ) ) {
			// Na primeira cobrança esta fatura costuma chegar antes dos eventos da assinatura.
			// Só vira padrinho se a fatura for de um plano deste site; a situação vem depois.
			if ( ! self::invoice_has_plan( $invoice ) ) {
				return true;
			}

			$fields['subscription_id'] = self::id( $invoice['parent']['subscription_details']['subscription'] ?? ( $invoice['subscription'] ?? '' ) );
		}

		if ( ! Sponsors::upsert( $customer_id, $fields ) ) {
			return false;
		}

		$sponsor = Sponsors::find_by_customer( $customer_id );
		$amount  = absint( $invoice['amount_paid'] ?? 0 );

		if ( ! $sponsor || ! $amount ) {
			return true;
		}

		$payment = array(
			'sponsor_id' => (int) $sponsor['id'],
			'amount'     => $amount,
			'paid_at'    => $fields['last_paid_at'],
		);

		// Só as versões antigas da API trazem o pagamento na fatura; nas recentes ele chega em `invoice_payment.paid`.
		$payment_intent = self::id( $invoice['payment_intent'] ?? '' );
		if ( '' !== $payment_intent ) {
			$payment['payment_intent_id'] = $payment_intent;
		}

		return Payments::record( self::id( $invoice ), $payment );
	}

	/**
	 * `invoice_payment.paid`: liga a fatura ao pagamento, para achar a linha em um estorno ou contestação.
	 *
	 * Vale para qualquer fatura da conta: a linha só conta para um padrinho depois do
	 * `invoice.paid` da mesma fatura, que pode chegar antes ou depois deste evento.
	 *
	 * @param array<string, mixed> $invoice_payment Pagamento de fatura.
	 */
	private static function invoice_payment_paid( array $invoice_payment ): bool {
		$invoice_id     = self::id( $invoice_payment['invoice'] ?? '' );
		$payment_intent = self::id( $invoice_payment['payment']['payment_intent'] ?? '' );

		if ( '' === $invoice_id || '' === $payment_intent ) {
			return true;
		}

		return Payments::record( $invoice_id, array( 'payment_intent_id' => $payment_intent ) );
	}

	/**
	 * `charge.refunded`: a protetora devolveu o pagamento, inteiro ou em parte.
	 *
	 * @param array<string, mixed> $charge Cobrança.
	 */
	private static function charge_refunded( array $charge ): bool {
		// Só as versões antigas da API trazem a fatura na cobrança.
		$payment = Payments::find_by_intent( self::id( $charge['payment_intent'] ?? '' ) ) ?? Payments::find_by_invoice( self::id( $charge['invoice'] ?? '' ) );

		if ( ! $payment ) {
			return true;
		}

		return Payments::set_refunded( (int) $payment['id'], absint( $charge['amount_refunded'] ?? 0 ) );
	}

	/**
	 * `charge.dispute.*`: o padrinho contestou o pagamento no banco.
	 *
	 * Enquanto a contestação está em análise, e se for perdida, o valor fica com o
	 * banco e sai do total doado; se for ganha, volta a contar.
	 *
	 * @param array<string, mixed> $dispute Contestação.
	 * @param int                  $created Momento em que o evento foi gerado.
	 * @param string               $type    Tipo do evento.
	 */
	private static function dispute_changed( array $dispute, int $created, string $type ): bool {
		$payment = Payments::find_by_intent( self::id( $dispute['payment_intent'] ?? '' ) );
		$status  = (string) ( $dispute['status'] ?? '' );

		if ( ! $payment || ! (int) $payment['sponsor_id'] ) {
			return true;
		}

		$final = array(
			'won'  => Payments::DISPUTE_WON,
			'lost' => Payments::DISPUTE_LOST,
		);

		if ( isset( $final[ $status ] ) ) {
			$state = $final[ $status ];
		} elseif ( isset( $final[ $payment['dispute'] ] ) ) {
			// Evento antigo entregue com atraso: a contestação já terminou.
			return true;
		} else {
			// Pedido de informação do banco (`warning_*`): o valor ainda não foi retirado.
			$state = str_starts_with( $status, 'warning_' ) ? '' : Payments::DISPUTE_OPEN;
		}

		if ( ! Payments::set_dispute( (int) $payment['id'], $state ) ) {
			return false;
		}

		$sponsor = 'charge.dispute.created' === $type ? Sponsors::find( (int) $payment['sponsor_id'] ) : null;
		if ( $sponsor ) {
			Notifications::disputed( $sponsor, absint( $dispute['amount'] ?? 0 ) );
		}

		return true;
	}

	/**
	 * Informa se alguma linha da fatura é de um plano de apadrinhamento deste site.
	 *
	 * @param array<string, mixed> $invoice Fatura.
	 */
	private static function invoice_has_plan( array $invoice ): bool {
		foreach ( (array) ( $invoice['lines']['data'] ?? array() ) as $line ) {
			// Versões recentes da API trazem o preço em `pricing.price_details`; as antigas, em `price`.
			$price_id = self::id( $line['pricing']['price_details']['price'] ?? ( $line['price'] ?? '' ) );

			if ( Plans::find( $price_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * `invoice.payment_failed`: marca o aviso no painel e avisa a protetora. A Stripe tenta de novo e avisa o padrinho.
	 *
	 * @param array<string, mixed> $invoice Fatura.
	 */
	private static function invoice_payment_failed( array $invoice ): bool {
		$customer_id = self::id( $invoice['customer'] ?? '' );
		$sponsor     = Sponsors::find_by_customer( $customer_id );

		if ( ! $sponsor ) {
			return true;
		}

		if ( ! Sponsors::upsert( $customer_id, array( 'payment_failed' => 1 ) ) ) {
			return false;
		}

		// Um aviso só, mesmo que a Stripe tente a cobrança outras vezes.
		if ( ! (int) $sponsor['payment_failed'] ) {
			Notifications::payment_failed( $sponsor );
		}

		return true;
	}

	/**
	 * `customer.updated`: o padrinho mudou nome ou e-mail no portal.
	 *
	 * @param array<string, mixed> $customer Cliente.
	 */
	private static function customer_updated( array $customer ): bool {
		$customer_id = self::id( $customer );

		if ( ! Sponsors::find_by_customer( $customer_id ) ) {
			return true;
		}

		$fields = array_filter(
			array(
				'name'  => (string) ( $customer['name'] ?? '' ),
				'email' => (string) ( $customer['email'] ?? '' ),
			)
		);

		return $fields ? Sponsors::upsert( $customer_id, $fields ) : true;
	}
}
