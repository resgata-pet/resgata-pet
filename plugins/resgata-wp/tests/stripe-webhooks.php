<?php
/**
 * Teste do processamento dos webhooks da Stripe (módulo de apadrinhamento).
 *
 * Roda com `make test` (WP-CLI: `wp eval-file`), no site de desenvolvimento.
 * Não fala com a Stripe: os eventos são montados aqui e assinados com um
 * segredo de teste, do mesmo jeito que a Stripe assina.
 *
 * Confere: assinatura inválida, evento duplicado, cancelamento, evento fora
 * de ordem, assinatura de outro produto, o contador de padrinhos, o registro
 * dos pagamentos (com estorno e contestação) e os e-mails do módulo.
 *
 * Cria padrinhos temporários e apaga tudo no final.
 *
 * @package ResgataWP
 */

use ResgataWP\Core\Modules;
use ResgataWP\Modules\Panel\Rest\BaseController;
use ResgataWP\Modules\Sponsorship\Events;
use ResgataWP\Modules\Sponsorship\Gateway;
use ResgataWP\Modules\Sponsorship\MagicLink;
use ResgataWP\Modules\Sponsorship\Payments;
use ResgataWP\Modules\Sponsorship\Plans;
use ResgataWP\Modules\Sponsorship\Sponsors;
use ResgataWP\Modules\Sponsorship\WebhookController;
use ResgataWP\Modules\Sponsorship\WebhookHandler;
use ResgataWP\Modules\Settings\Organization;
use Stripe\WebhookSignature;

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

if ( ! Modules::is_enabled( 'sponsorship' ) ) {
	WP_CLI::warning( 'Módulo de apadrinhamento desligado (ou sem a biblioteca da Stripe): testes dos webhooks pulados.' );
	return;
}

global $wpdb;

// Nenhum e-mail sai durante o teste: eles ficam guardados aqui para conferência.
$rwp_mails = array();
add_filter(
	'pre_wp_mail',
	static function ( $sent, array $mail ) use ( &$rwp_mails ) {
		unset( $sent );
		$rwp_mails[] = $mail;

		return true;
	},
	10,
	2
);

// E-mails enviados para um endereço cujo assunto começa com o texto pedido.
$rwp_mails_to = static function ( string $to, string $subject ) use ( &$rwp_mails ): array {
	return array_values( array_filter( $rwp_mails, static fn( array $mail ): bool => $to === $mail['to'] && str_starts_with( (string) $mail['subject'], $subject ) ) );
};

$rwp_saved_options = array(
	Plans::OPTION         => get_option( Plans::OPTION, null ),
	Plans::COUNTER_OPTION => get_option( Plans::COUNTER_OPTION, null ),
	Organization::OPTION  => get_option( Organization::OPTION, null ),
);

$rwp_team = 'avisos.rwpteste@example.org';
Organization::update( array( 'sponsorship_email' => $rwp_team ) );

$rwp_failures = array();
$rwp_checks   = 0;
$rwp_run      = strtolower( wp_generate_password( 8, false ) );
$rwp_secret   = 'whsec_teste_' . wp_generate_password( 24, false );
$rwp_price    = 'price_rwpteste_' . $rwp_run;
$rwp_customer = 'cus_rwpteste_' . $rwp_run;
$rwp_sub      = 'sub_rwpteste_' . $rwp_run;
$rwp_now      = time();
$rwp_sequence = 0;

Plans::update(
	array(
		'plans'      => array(
			array(
				'amount'     => 3000,
				'price_id'   => $rwp_price,
				'product_id' => 'prod_rwpteste_' . $rwp_run,
			),
		),
		'adjustment' => 0,
	)
);
Plans::refresh_counter();
$rwp_base_count = Plans::counter()['active'];

// Assinaturas ativas criadas por este teste (o site pode ter padrinhos de verdade).
$rwp_new_active = static fn(): int => Plans::counter()['active'] - $rwp_base_count;

$rwp_assert = static function ( bool $ok, string $message ) use ( &$rwp_failures, &$rwp_checks ): void {
	++$rwp_checks;
	if ( ! $ok ) {
		$rwp_failures[] = $message;
	}
};

// Monta o corpo de um evento, como a Stripe envia.
$rwp_event = static function ( string $type, array $data, int $created ) use ( &$rwp_sequence, $rwp_run ): string {
	++$rwp_sequence;

	return (string) wp_json_encode(
		array(
			'id'      => "evt_rwpteste_{$rwp_run}_{$rwp_sequence}",
			'object'  => 'event',
			'type'    => $type,
			'created' => $created,
			'data'    => array( 'object' => $data ),
		)
	);
};

// Entrega um evento com assinatura válida.
$rwp_deliver = static function ( string $payload ) use ( $rwp_secret ): array {
	return WebhookHandler::handle( $payload, WebhookSignature::generateSignatureHeader( $payload, $rwp_secret ), $rwp_secret );
};

$rwp_subscription = static function ( string $status, string $customer, string $subscription, string $price ): array {
	return array(
		'id'       => $subscription,
		'object'   => 'subscription',
		'customer' => $customer,
		'status'   => $status,
		'items'    => array( 'data' => array( array( 'price' => array( 'id' => $price ) ) ) ),
	);
};

// 1. Assinatura inválida: nada é processado.
$rwp_payload = $rwp_event( 'customer.subscription.created', $rwp_subscription( 'active', $rwp_customer, $rwp_sub, $rwp_price ), $rwp_now );

$rwp_result = WebhookHandler::handle( $rwp_payload, '', $rwp_secret );
$rwp_assert( 400 === $rwp_result['status'], 'Evento sem assinatura deveria ser recusado com 400' );

$rwp_result = WebhookHandler::handle( $rwp_payload, WebhookSignature::generateSignatureHeader( $rwp_payload, 'whsec_outro_segredo' ), $rwp_secret );
$rwp_assert( 400 === $rwp_result['status'] && 'invalid_signature' === $rwp_result['result'], 'Evento assinado com outro segredo deveria ser recusado com 400' );

$rwp_result = WebhookHandler::handle( $rwp_payload . ' ', WebhookSignature::generateSignatureHeader( $rwp_payload, $rwp_secret ), $rwp_secret );
$rwp_assert( 400 === $rwp_result['status'], 'Corpo alterado depois de assinado deveria ser recusado com 400' );

$rwp_result = WebhookHandler::handle( $rwp_payload, WebhookSignature::generateSignatureHeader( $rwp_payload, $rwp_secret, $rwp_now - HOUR_IN_SECONDS ), $rwp_secret );
$rwp_assert( 400 === $rwp_result['status'], 'Assinatura antiga (repetição de uma entrega capturada) deveria ser recusada com 400' );

$rwp_result = WebhookHandler::handle( $rwp_payload, WebhookSignature::generateSignatureHeader( $rwp_payload, $rwp_secret ), '' );
$rwp_assert( 503 === $rwp_result['status'], 'Sem segredo configurado, o endpoint deveria responder 503' );

$rwp_assert( null === Sponsors::find_by_customer( $rwp_customer ), 'Evento recusado não pode criar padrinho' );

// A rota pública também recusa quem chega sem assinatura.
wp_set_current_user( 0 );
$rwp_request = new WP_REST_Request( 'POST', '/' . BaseController::REST_NAMESPACE . WebhookController::ROUTE );
$rwp_request->set_body( $rwp_payload );
$rwp_status = rest_get_server()->dispatch( $rwp_request )->get_status();
$rwp_assert( in_array( $rwp_status, array( 400, 503 ), true ), "A rota do webhook deveria recusar evento sem assinatura (HTTP $rwp_status)" );
$rwp_assert( null === Sponsors::find_by_customer( $rwp_customer ), 'A rota do webhook não pode criar padrinho sem assinatura válida' );

// 2. Fluxo normal: assinatura criada, Checkout concluído, fatura paga.
$rwp_result = $rwp_deliver( $rwp_payload );
$rwp_assert( 200 === $rwp_result['status'] && 'processed' === $rwp_result['result'], 'Evento válido deveria ser processado' );

$rwp_sponsor = Sponsors::find_by_customer( $rwp_customer );
$rwp_assert( $rwp_sponsor && 'active' === $rwp_sponsor['status'] && $rwp_price === $rwp_sponsor['price_id'] && $rwp_sub === $rwp_sponsor['subscription_id'], 'A assinatura criada deveria gravar situação, plano e ID' );
$rwp_assert( 1 === $rwp_new_active(), 'O contador deveria subir com a assinatura ativa' );
$rwp_assert( $rwp_now <= Plans::counter()['updated_at'], 'O contador deveria marcar a data da atualização' );

$rwp_result  = $rwp_deliver(
	$rwp_event(
		'checkout.session.completed',
		array(
			'id'               => 'cs_rwpteste_' . $rwp_run,
			'object'           => 'checkout.session',
			'mode'             => 'subscription',
			'customer'         => $rwp_customer,
			'subscription'     => $rwp_sub,
			'metadata'         => array( Gateway::ORIGIN_KEY => Gateway::ORIGIN_VALUE ),
			'customer_details' => array(
				'name'    => 'Madrinha <b>Teste</b>',
				'email'   => "madrinha.$rwp_run@example.org",
				'address' => array(
					'line1'       => 'Rua de Teste, 1',
					'postal_code' => '01000-000',
				),
				'tax_ids' => array( array( 'value' => '12345678909' ) ),
			),
		),
		$rwp_now + 1
	)
);
$rwp_sponsor = Sponsors::find_by_customer( $rwp_customer );
$rwp_assert( 200 === $rwp_result['status'] && $rwp_sponsor && 'Madrinha Teste' === $rwp_sponsor['name'] && "madrinha.$rwp_run@example.org" === $rwp_sponsor['email'], 'O Checkout concluído deveria gravar nome (sem HTML) e e-mail' );
$rwp_assert( $rwp_sponsor && 'active' === $rwp_sponsor['status'], 'O Checkout concluído não pode mudar a situação da assinatura' );
$rwp_assert( $rwp_sponsor && ! str_contains( (string) wp_json_encode( $rwp_sponsor ), '12345678909' ) && ! str_contains( (string) wp_json_encode( $rwp_sponsor ), 'Rua de Teste' ), 'CPF e endereço não podem ser gravados' );

// Boas-vindas: saem quando o padrinho está ativo e já tem e-mail, uma única vez.
$rwp_sponsor_mail = "madrinha.$rwp_run@example.org";
$rwp_welcome      = $rwp_mails_to( $rwp_sponsor_mail, 'Obrigado por apadrinhar' );
$rwp_assert( 1 === count( $rwp_welcome ) && str_contains( (string) $rwp_welcome[0]['message'], 'Olá, Madrinha!' ), 'O novo padrinho deveria receber um e-mail de agradecimento' );
$rwp_assert( $rwp_welcome && ! str_contains( (string) $rwp_welcome[0]['message'], '<' ) && ! str_contains( implode( ' ', (array) $rwp_welcome[0]['headers'] ), 'text/html' ), 'Sem imagem informada pelo site, o agradecimento vai em texto simples' );
$rwp_assert( 1 === count( $rwp_mails_to( $rwp_team, 'Novo padrinho' ) ), 'A protetora deveria ser avisada do novo padrinho' );
$rwp_assert( ! str_contains( (string) wp_json_encode( $rwp_mails_to( $rwp_team, 'Novo padrinho' ) ), $rwp_sponsor_mail ), 'O aviso à protetora não leva o e-mail do padrinho' );

$rwp_deliver( $rwp_event( 'invoice.payment_failed', array( 'id' => 'in_rwpteste_1', 'customer' => $rwp_customer ), $rwp_now + 2 ) ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
$rwp_assert( '1' === (string) Sponsors::find_by_customer( $rwp_customer )['payment_failed'], 'Fatura recusada deveria marcar o aviso de pagamento' );
$rwp_deliver( $rwp_event( 'invoice.payment_failed', array( 'id' => 'in_rwpteste_1', 'customer' => $rwp_customer ), $rwp_now + 2 ) ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
$rwp_assert( 1 === count( $rwp_mails_to( $rwp_team, 'Pagamento recusado' ) ), 'A protetora deveria receber um aviso só de pagamento recusado, mesmo com novas tentativas' );

$rwp_deliver(
	$rwp_event(
		'invoice.paid',
		array(
			'id'                 => 'in_rwpteste_2',
			'customer'           => $rwp_customer,
			'status_transitions' => array( 'paid_at' => $rwp_now ),
		),
		$rwp_now + 3
	)
);
$rwp_sponsor = Sponsors::find_by_customer( $rwp_customer );
$rwp_assert( '0' === (string) $rwp_sponsor['payment_failed'] && gmdate( 'Y-m-d H:i:s', $rwp_now ) === $rwp_sponsor['last_paid_at'], 'Fatura paga deveria limpar o aviso e gravar a data' );

// 3. Evento duplicado: a segunda entrega não tem efeito.
$rwp_payload = $rwp_event( 'customer.subscription.updated', $rwp_subscription( 'past_due', $rwp_customer, $rwp_sub, $rwp_price ), $rwp_now + 10 );
$rwp_result  = $rwp_deliver( $rwp_payload );
$rwp_assert( 'processed' === $rwp_result['result'] && 'past_due' === Sponsors::find_by_customer( $rwp_customer )['status'], 'A atualização deveria gravar a situação nova' );
$rwp_assert( 0 === $rwp_new_active(), 'Assinatura com pagamento atrasado não conta como ativa' );

$rwp_deliver( $rwp_event( 'customer.subscription.updated', $rwp_subscription( 'active', $rwp_customer, $rwp_sub, $rwp_price ), $rwp_now + 20 ) );
$rwp_assert( 'active' === Sponsors::find_by_customer( $rwp_customer )['status'], 'A assinatura deveria voltar a ficar ativa' );

$rwp_result = $rwp_deliver( $rwp_payload );
$rwp_assert( 200 === $rwp_result['status'] && 'duplicate' === $rwp_result['result'], 'Evento repetido deveria responder 200 sem reprocessar' );
$rwp_assert( 'active' === Sponsors::find_by_customer( $rwp_customer )['status'], 'Evento repetido não pode desfazer a situação atual' );

// 4. Evento fora de ordem: um evento antigo, entregue com atraso, é ignorado.
$rwp_result = $rwp_deliver( $rwp_event( 'customer.subscription.updated', $rwp_subscription( 'incomplete', $rwp_customer, $rwp_sub, $rwp_price ), $rwp_now + 5 ) );
$rwp_assert( 200 === $rwp_result['status'] && 'active' === Sponsors::find_by_customer( $rwp_customer )['status'], 'Evento antigo entregue com atraso não pode desfazer a situação atual' );

// 5. Cancelamento.
$rwp_result  = $rwp_deliver( $rwp_event( 'customer.subscription.deleted', $rwp_subscription( 'canceled', $rwp_customer, $rwp_sub, $rwp_price ), $rwp_now + 30 ) );
$rwp_sponsor = Sponsors::find_by_customer( $rwp_customer );
$rwp_assert( 'processed' === $rwp_result['result'] && 'canceled' === $rwp_sponsor['status'], 'O cancelamento deveria marcar o padrinho como cancelado' );
$rwp_assert( 0 === $rwp_new_active(), 'O contador deveria cair com o cancelamento' );
$rwp_assert( '' !== $rwp_sponsor['name'], 'O cancelamento não apaga o cadastro do padrinho' );

$rwp_deliver( $rwp_event( 'customer.subscription.updated', $rwp_subscription( 'active', $rwp_customer, $rwp_sub, $rwp_price ), $rwp_now + 25 ) );
$rwp_assert( 'canceled' === Sponsors::find_by_customer( $rwp_customer )['status'], 'Atualização anterior ao cancelamento não pode reativar o padrinho' );

$rwp_assert( 1 === count( $rwp_mails_to( $rwp_team, 'Apadrinhamento encerrado' ) ), 'A protetora deveria ser avisada do apadrinhamento encerrado' );
$rwp_assert( 1 === count( $rwp_mails_to( $rwp_sponsor_mail, 'Obrigado por apadrinhar' ) ), 'O agradecimento não pode ser enviado de novo' );

// 6. O que não é apadrinhamento fica de fora.
$rwp_other = 'cus_rwpteste_outro_' . $rwp_run;
$rwp_deliver( $rwp_event( 'customer.subscription.created', $rwp_subscription( 'active', $rwp_other, 'sub_rwpteste_outro_' . $rwp_run, 'price_de_outro_produto' ), $rwp_now ) );
$rwp_assert( null === Sponsors::find_by_customer( $rwp_other ), 'Assinatura de outro produto da conta não pode virar padrinho' );

$rwp_deliver(
	$rwp_event(
		'checkout.session.completed',
		array(
			'id'               => 'cs_rwpteste_outro_' . $rwp_run,
			'mode'             => 'subscription',
			'customer'         => $rwp_other,
			'customer_details' => array( 'email' => 'outro@example.org' ),
		),
		$rwp_now
	)
);
$rwp_assert( null === Sponsors::find_by_customer( $rwp_other ), 'Checkout que não nasceu neste módulo não pode virar padrinho' );

// A primeira fatura paga pode chegar antes dos eventos da assinatura (acontece na Stripe de verdade).
$rwp_early      = 'cus_rwpteste_cedo_' . $rwp_run;
$rwp_early_sub  = 'sub_rwpteste_cedo_' . $rwp_run;
$rwp_early_line = static fn( string $price ): array => array( 'data' => array( array( 'pricing' => array( 'price_details' => array( 'price' => $price ) ) ) ) );

$rwp_deliver(
	$rwp_event(
		'invoice.paid',
		array(
			'id'                 => 'in_rwpteste_outro',
			'customer'           => $rwp_other,
			'status_transitions' => array( 'paid_at' => $rwp_now ),
			'lines'              => $rwp_early_line( 'price_de_outro_produto' ),
		),
		$rwp_now
	)
);
$rwp_assert( null === Sponsors::find_by_customer( $rwp_other ), 'Fatura paga de outro produto da conta não pode virar padrinho' );

$rwp_deliver(
	$rwp_event(
		'invoice.paid',
		array(
			'id'                 => 'in_rwpteste_cedo',
			'customer'           => $rwp_early,
			'status_transitions' => array( 'paid_at' => $rwp_now ),
			'parent'             => array( 'subscription_details' => array( 'subscription' => $rwp_early_sub ) ),
			'lines'              => $rwp_early_line( $rwp_price ),
		),
		$rwp_now
	)
);
$rwp_sponsor_early = Sponsors::find_by_customer( $rwp_early );
$rwp_assert( $rwp_sponsor_early && gmdate( 'Y-m-d H:i:s', $rwp_now ) === $rwp_sponsor_early['last_paid_at'] && 'active' !== $rwp_sponsor_early['status'], 'Fatura paga que chega antes da assinatura deveria guardar a data do pagamento, sem contar como ativo ainda' );

$rwp_deliver( $rwp_event( 'customer.subscription.created', $rwp_subscription( 'active', $rwp_early, $rwp_early_sub, $rwp_price ), $rwp_now ) );
$rwp_sponsor_early = Sponsors::find_by_customer( $rwp_early );
$rwp_assert( $rwp_sponsor_early && 'active' === $rwp_sponsor_early['status'] && gmdate( 'Y-m-d H:i:s', $rwp_now ) === $rwp_sponsor_early['last_paid_at'], 'A assinatura que chega depois da fatura deveria ativar o padrinho e manter a data do pagamento' );

$rwp_result = $rwp_deliver( $rwp_event( 'payment_intent.succeeded', array( 'id' => 'pi_rwpteste' ), $rwp_now ) );
$rwp_assert( 200 === $rwp_result['status'] && 'ignored' === $rwp_result['result'], 'Evento de tipo não tratado deveria responder 200 e ser ignorado' );

$rwp_result = WebhookHandler::handle( '{"sem":"id"}', WebhookSignature::generateSignatureHeader( '{"sem":"id"}', $rwp_secret ), $rwp_secret );
$rwp_assert( 400 === $rwp_result['status'], 'Corpo assinado mas sem formato de evento deveria ser recusado com 400' );

// 7. Pagamentos: registro, estorno e contestação.
$rwp_payer     = 'cus_rwpteste_paga_' . $rwp_run;
$rwp_payer_sub = 'sub_rwpteste_paga_' . $rwp_run;
$rwp_period    = $rwp_now + 30 * DAY_IN_SECONDS;
$rwp_paying    = static function ( array $extra = array() ) use ( $rwp_subscription, $rwp_payer, $rwp_payer_sub, $rwp_price, $rwp_period ): array {
	$subscription = $rwp_subscription( 'active', $rwp_payer, $rwp_payer_sub, $rwp_price );

	$subscription['items']['data'][0]['current_period_end'] = $rwp_period;

	return array_merge( $subscription, $extra );
};
$rwp_invoice   = static function ( int $number, int $amount ) use ( $rwp_run, $rwp_payer, $rwp_now ): array {
	return array(
		'id'                 => "in_rwpteste_paga_{$rwp_run}_{$number}",
		'customer'           => $rwp_payer,
		'amount_paid'        => $amount,
		'status_transitions' => array( 'paid_at' => $rwp_now ),
	);
};
$rwp_link      = static function ( int $number ) use ( $rwp_run ): array {
	return array(
		'id'      => "inpay_rwpteste_{$rwp_run}_{$number}",
		'invoice' => "in_rwpteste_paga_{$rwp_run}_{$number}",
		'payment' => array(
			'type'           => 'payment_intent',
			'payment_intent' => "pi_rwpteste_{$rwp_run}_{$number}",
		),
	);
};

$rwp_thanks_image = static fn(): string => 'https://example.org/obrigado-teste.gif';
add_filter( 'rwp_thanks_image_url', $rwp_thanks_image );
$rwp_deliver( $rwp_event( 'customer.subscription.created', $rwp_paying(), $rwp_now ) );
$rwp_deliver(
	$rwp_event(
		'checkout.session.completed',
		array(
			'id'               => 'cs_rwpteste_paga_' . $rwp_run,
			'mode'             => 'subscription',
			'customer'         => $rwp_payer,
			'subscription'     => $rwp_payer_sub,
			'metadata'         => array( Gateway::ORIGIN_KEY => Gateway::ORIGIN_VALUE ),
			'customer_details' => array(
				'name'  => 'Padrinho <i>Pagador</i>',
				'email' => "pagador.$rwp_run@example.org",
			),
		),
		$rwp_now
	)
);
remove_filter( 'rwp_thanks_image_url', $rwp_thanks_image );
$rwp_welcome_html = $rwp_mails_to( "pagador.$rwp_run@example.org", 'Obrigado por apadrinhar' );
$rwp_assert( 1 === count( $rwp_welcome_html ) && str_contains( (string) $rwp_welcome_html[0]['message'], 'https://example.org/obrigado-teste.gif' ) && str_contains( implode( ' ', (array) $rwp_welcome_html[0]['headers'] ), 'text/html' ) && ! str_contains( (string) $rwp_welcome_html[0]['message'], '<i>' ), 'Com imagem informada pelo site, o agradecimento vai em HTML, sem HTML vindo do nome' );
$rwp_payer_id = (int) Sponsors::find_by_customer( $rwp_payer )['id'];
$rwp_assert( gmdate( 'Y-m-d H:i:s', $rwp_period ) === Sponsors::find_by_customer( $rwp_payer )['period_end'], 'A assinatura deveria gravar o fim do período pago' );

// A ligação com o pagamento pode chegar depois da fatura (1) ou antes dela (2).
$rwp_deliver( $rwp_event( 'invoice.paid', $rwp_invoice( 1, 3000 ), $rwp_now + 1 ) );
$rwp_deliver( $rwp_event( 'invoice_payment.paid', $rwp_link( 1 ), $rwp_now + 1 ) );
$rwp_deliver( $rwp_event( 'invoice_payment.paid', $rwp_link( 2 ), $rwp_now + 2 ) );
$rwp_assert( array( 'total' => 3000, 'count' => 1 ) === Payments::totals( $rwp_payer_id ), 'Pagamento sem fatura paga ainda não conta para o padrinho' ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
$rwp_deliver( $rwp_event( 'invoice.paid', $rwp_invoice( 2, 3000 ), $rwp_now + 2 ) );
$rwp_deliver( $rwp_event( 'invoice.paid', $rwp_invoice( 3, 3000 ), $rwp_now + 3 ) );
$rwp_deliver( $rwp_event( 'invoice_payment.paid', $rwp_link( 3 ), $rwp_now + 3 ) );
$rwp_assert( array( 'total' => 9000, 'count' => 3 ) === Payments::totals( $rwp_payer_id ), 'Cada fatura paga deveria somar ao total do padrinho' ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
$rwp_assert( "pi_rwpteste_{$rwp_run}_2" === ( Payments::find_by_invoice( "in_rwpteste_paga_{$rwp_run}_2" )['payment_intent_id'] ?? '' ), 'A fatura que chega depois da ligação deveria manter o ID do pagamento' );

$rwp_result = $rwp_deliver( $rwp_event( 'invoice.paid', $rwp_invoice( 1, 3000 ), $rwp_now + 4 ) );
$rwp_assert( 'processed' === $rwp_result['result'] && 9000 === Payments::totals( $rwp_payer_id )['total'], 'A mesma fatura em outro evento não pode ser somada duas vezes' );

// Estorno parcial e depois total; um evento antigo não diminui o que já foi devolvido.
$rwp_charge = static function ( int $number, int $refunded ) use ( $rwp_run, $rwp_payer ): array {
	return array(
		'id'              => "ch_rwpteste_{$rwp_run}_{$number}",
		'customer'        => $rwp_payer,
		'payment_intent'  => "pi_rwpteste_{$rwp_run}_{$number}",
		'amount'          => 3000,
		'amount_refunded' => $refunded,
	);
};
$rwp_deliver( $rwp_event( 'charge.refunded', $rwp_charge( 1, 1000 ), $rwp_now + 5 ) );
$rwp_assert( array( 'total' => 8000, 'count' => 3 ) === Payments::totals( $rwp_payer_id ), 'Estorno parcial deveria sair do total' ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
$rwp_deliver( $rwp_event( 'charge.refunded', $rwp_charge( 1, 3000 ), $rwp_now + 6 ) );
$rwp_deliver( $rwp_event( 'charge.refunded', $rwp_charge( 1, 1000 ), $rwp_now + 5 ) );
$rwp_assert( array( 'total' => 6000, 'count' => 2 ) === Payments::totals( $rwp_payer_id ), 'Pagamento devolvido por inteiro deixa de contar, e evento antigo não desfaz o estorno' ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound

$rwp_result = $rwp_deliver( $rwp_event( 'charge.refunded', array( 'id' => 'ch_rwpteste_outro', 'payment_intent' => 'pi_de_outro_produto', 'amount_refunded' => 500 ), $rwp_now ) ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
$rwp_assert( 'processed' === $rwp_result['result'] && 6000 === Payments::totals( $rwp_payer_id )['total'], 'Estorno de outro produto da conta não mexe nos pagamentos' );

// Contestação: o valor sai enquanto está em análise e volta se a protetora ganhar.
$rwp_dispute = static function ( int $number, string $status ) use ( $rwp_run ): array {
	return array(
		'id'             => "dp_rwpteste_{$rwp_run}_{$number}",
		'charge'         => "ch_rwpteste_{$rwp_run}_{$number}",
		'payment_intent' => "pi_rwpteste_{$rwp_run}_{$number}",
		'amount'         => 3000,
		'status'         => $status,
	);
};
$rwp_deliver( $rwp_event( 'charge.dispute.created', $rwp_dispute( 2, 'needs_response' ), $rwp_now + 7 ) );
$rwp_assert( 3000 === Payments::totals( $rwp_payer_id )['total'], 'Pagamento contestado deveria sair do total' );
$rwp_disputes = $rwp_mails_to( $rwp_team, 'Pagamento contestado' );
$rwp_assert( 1 === count( $rwp_disputes ) && str_contains( (string) $rwp_disputes[0]['message'], 'R$ 30' ), 'A protetora deveria ser avisada da contestação, com o valor' );

$rwp_deliver( $rwp_event( 'charge.dispute.closed', $rwp_dispute( 2, 'won' ), $rwp_now + 9 ) );
$rwp_assert( 6000 === Payments::totals( $rwp_payer_id )['total'], 'Contestação ganha deveria voltar a contar' );
$rwp_deliver( $rwp_event( 'charge.dispute.updated', $rwp_dispute( 2, 'under_review' ), $rwp_now + 8 ) );
$rwp_assert( 6000 === Payments::totals( $rwp_payer_id )['total'], 'Evento antigo não pode reabrir uma contestação encerrada' );

$rwp_deliver( $rwp_event( 'charge.dispute.created', $rwp_dispute( 3, 'warning_needs_response' ), $rwp_now + 10 ) );
$rwp_assert( 6000 === Payments::totals( $rwp_payer_id )['total'], 'Pedido de informação do banco ainda não tira o valor' );
$rwp_deliver( $rwp_event( 'charge.dispute.closed', $rwp_dispute( 3, 'lost' ), $rwp_now + 11 ) );
$rwp_assert( array( 'total' => 3000, 'count' => 1 ) === Payments::totals( $rwp_payer_id ), 'Contestação perdida deixa de contar' ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound

// O que o painel mostra: total por padrinho, situação de cada pagamento e arrecadação do mês.
$rwp_status_of = static fn( int $number ): string => Payments::status( (array) Payments::find_by_invoice( "in_rwpteste_paga_{$rwp_run}_{$number}" ) );
$rwp_assert( array( $rwp_payer_id => 3000 ) === Payments::totals_by_sponsor( array( $rwp_payer_id, 0 ) ), 'O total por padrinho do painel deveria bater com o total da área do padrinho' );
$rwp_assert( 'refunded' === $rwp_status_of( 1 ) && 'dispute_won' === $rwp_status_of( 2 ) && 'dispute_lost' === $rwp_status_of( 3 ), 'Cada pagamento deveria mostrar a sua situação no painel' );
$rwp_revenue = Payments::revenue();
$wpdb->query( $wpdb->prepare( 'UPDATE %i SET dispute = %s WHERE invoice_id = %s', Payments::table(), Payments::DISPUTE_OPEN, "in_rwpteste_paga_{$rwp_run}_2" ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Reabre a contestação criada por este teste.
$rwp_reopened = Payments::revenue();
$rwp_assert( 3000 === $rwp_revenue['month'] - $rwp_reopened['month'] && 3000 === $rwp_revenue['total'] - $rwp_reopened['total'] && 1 === $rwp_reopened['open_disputes'] - $rwp_revenue['open_disputes'], 'A arrecadação do mês deveria descontar o pagamento em contestação e contar a contestação aberta' );
Payments::set_dispute( (int) Payments::find_by_invoice( "in_rwpteste_paga_{$rwp_run}_2" )['id'], Payments::DISPUTE_WON );

// Pedido de cancelamento: a protetora é avisada no pedido, e não de novo no encerramento.
$rwp_deliver( $rwp_event( 'customer.subscription.updated', $rwp_paying( array( 'cancel_at_period_end' => true ) ), $rwp_now + 20 ) );
$rwp_payer_row = Sponsors::find_by_customer( $rwp_payer );
$rwp_assert( 'active' === $rwp_payer_row['status'] && gmdate( 'Y-m-d H:i:s', $rwp_period ) === $rwp_payer_row['cancel_at'], 'O pedido de cancelamento deveria gravar até quando o padrinho segue ativo' );
$rwp_deliver( $rwp_event( 'customer.subscription.deleted', $rwp_paying( array( 'status' => 'canceled', 'cancel_at_period_end' => true ) ), $rwp_now + 30 ) ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
$rwp_assert( 1 === count( $rwp_mails_to( $rwp_team, 'Pedido de cancelamento' ) ) && 1 === count( $rwp_mails_to( $rwp_team, 'Apadrinhamento encerrado' ) ), 'Cancelamento pedido gera um aviso só, no pedido' );

// Faturas de outros produtos ficam sem padrinho e são apagadas depois de 30 dias.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Envelhece só a linha criada por este teste.
$wpdb->query( $wpdb->prepare( 'UPDATE %i SET sponsor_id = 0, created_at = %s WHERE invoice_id = %s', Payments::table(), gmdate( 'Y-m-d H:i:s', $rwp_now - 40 * DAY_IN_SECONDS ), "in_rwpteste_paga_{$rwp_run}_3" ) );
Payments::cleanup();
$rwp_assert( null === Payments::find_by_invoice( "in_rwpteste_paga_{$rwp_run}_3" ) && null !== Payments::find_by_invoice( "in_rwpteste_paga_{$rwp_run}_2" ), 'A limpeza apaga só as linhas antigas sem padrinho' );

// 8. Ajuste manual do contador.
Plans::set_adjustment( 5 );
$rwp_assert( 5 === Plans::counter()['total'] - Plans::counter()['active'], 'O ajuste manual deveria somar ao contador' );
Plans::set_adjustment( -100000 );
$rwp_assert( 0 === Plans::counter()['total'], 'O contador do site nunca fica negativo' );

// 9. Link mágico: uso único, e nada é gerado para quem não é padrinho.
$rwp_token = MagicLink::create_token( (int) $rwp_sponsor['id'] );
$rwp_assert( MagicLink::consume_token( $rwp_token ) === (int) $rwp_sponsor['id'], 'O código do link deveria identificar o padrinho' );
$rwp_assert( 0 === MagicLink::consume_token( $rwp_token ), 'O código do link só pode ser usado uma vez' );
$rwp_assert( 0 === MagicLink::consume_token( 'codigoinventado' ), 'Código inventado não dá acesso' );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Confere que o código não fica gravado em texto puro.
$rwp_assert( '0' === (string) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s", '%' . $wpdb->esc_like( MagicLink::create_token( (int) $rwp_sponsor['id'] ) ) . '%' ) ), 'O código do link não pode ficar gravado em texto puro' );

// Limpeza.
// phpcs:disable WordPress.DB.DirectDatabaseQuery -- Remove só as linhas criadas por este teste.
$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE customer_id LIKE %s', Sponsors::table(), $wpdb->esc_like( 'cus_rwpteste_' ) . '%' ) );
$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE invoice_id LIKE %s', Payments::table(), $wpdb->esc_like( 'in_rwpteste_' ) . '%' ) );
$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE event_id LIKE %s', Events::table(), $wpdb->esc_like( 'evt_rwpteste_' ) . '%' ) );
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_rwp_sponsor_link_' ) . '%', $wpdb->esc_like( '_transient_timeout_rwp_sponsor_link_' ) . '%' ) );
// phpcs:enable

foreach ( $rwp_saved_options as $rwp_option => $rwp_value ) {
	if ( null === $rwp_value ) {
		delete_option( $rwp_option );
	} else {
		update_option( $rwp_option, $rwp_value, false );
	}
}

if ( $rwp_failures ) {
	foreach ( $rwp_failures as $rwp_failure ) {
		WP_CLI::warning( $rwp_failure );
	}
	WP_CLI::error( sprintf( 'Webhooks da Stripe: %d de %d verificações falharam.', count( $rwp_failures ), $rwp_checks ) );
}

WP_CLI::success( sprintf( 'Webhooks da Stripe: %d verificações, tudo certo.', $rwp_checks ) );
