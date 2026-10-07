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
 * Confere a assinatura de cada evento e atualiza os padrinhos.
 *
 * Tudo o que o site sabe sobre uma assinatura vem daqui: a página de retorno
 * do Checkout só agradece. Cada evento é processado uma única vez (`Events`) e
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

		return Sponsors::upsert( $customer_id, $fields );
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

		$fields = array(
			'subscription_id' => $subscription_id,
			'price_id'        => $price_id,
			'status'          => 'customer.subscription.deleted' === $type ? 'canceled' : $status,
			'last_event_at'   => $created,
		);

		if ( Sponsors::STATUS_ACTIVE === $fields['status'] ) {
			$fields['payment_failed'] = 0;
		}

		if ( ! Sponsors::upsert( $customer_id, $fields ) ) {
			return false;
		}

		Plans::refresh_counter();

		return true;
	}

	/**
	 * `invoice.paid`: guarda a data do último pagamento.
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

		return Sponsors::upsert( $customer_id, $fields );
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
	 * `invoice.payment_failed`: marca o aviso no painel. A Stripe tenta de novo e avisa o padrinho.
	 *
	 * @param array<string, mixed> $invoice Fatura.
	 */
	private static function invoice_payment_failed( array $invoice ): bool {
		$customer_id = self::id( $invoice['customer'] ?? '' );

		if ( ! Sponsors::find_by_customer( $customer_id ) ) {
			return true;
		}

		return Sponsors::upsert( $customer_id, array( 'payment_failed' => 1 ) );
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
