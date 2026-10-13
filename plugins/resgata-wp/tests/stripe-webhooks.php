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
$resgata_mails = array();
add_filter(
	'pre_wp_mail',
	static function ( $sent, array $mail ) use ( &$resgata_mails ) {
		unset( $sent );
		$resgata_mails[] = $mail;

		return true;
	},
	10,
	2
);

// E-mails enviados para um endereço cujo assunto começa com o texto pedido.
$resgata_mails_to = static function ( string $to, string $subject ) use ( &$resgata_mails ): array {
	return array_values( array_filter( $resgata_mails, static fn( array $mail ): bool => $to === $mail['to'] && str_starts_with( (string) $mail['subject'], $subject ) ) );
};

$resgata_saved_options = array(
	Plans::OPTION         => get_option( Plans::OPTION, null ),
	Plans::COUNTER_OPTION => get_option( Plans::COUNTER_OPTION, null ),
	Organization::OPTION  => get_option( Organization::OPTION, null ),
);

$resgata_team = 'avisos.resgatateste@example.org';
Organization::update( array( 'sponsorship_email' => $resgata_team ) );

$resgata_failures = array();
$resgata_checks   = 0;
$resgata_run      = strtolower( wp_generate_password( 8, false ) );
$resgata_secret   = 'whsec_teste_' . wp_generate_password( 24, false );
$resgata_price    = 'price_resgatateste_' . $resgata_run;
$resgata_customer = 'cus_resgatateste_' . $resgata_run;
$resgata_sub      = 'sub_resgatateste_' . $resgata_run;
$resgata_now      = time();
$resgata_sequence = 0;

Plans::update(
	array(
		'plans'      => array(
			array(
				'amount'     => 3000,
				'price_id'   => $resgata_price,
				'product_id' => 'prod_resgatateste_' . $resgata_run,
			),
		),
		'adjustment' => 0,
	)
);
Plans::refresh_counter();
$resgata_base_count = Plans::counter()['active'];

// Assinaturas ativas criadas por este teste (o site pode ter padrinhos de verdade).
$resgata_new_active = static fn(): int => Plans::counter()['active'] - $resgata_base_count;

$resgata_assert = static function ( bool $ok, string $message ) use ( &$resgata_failures, &$resgata_checks ): void {
	++$resgata_checks;
	if ( ! $ok ) {
		$resgata_failures[] = $message;
	}
};

// Monta o corpo de um evento, como a Stripe envia.
$resgata_event = static function ( string $type, array $data, int $created ) use ( &$resgata_sequence, $resgata_run ): string {
	++$resgata_sequence;

	return (string) wp_json_encode(
		array(
			'id'      => "evt_resgatateste_{$resgata_run}_{$resgata_sequence}",
			'object'  => 'event',
			'type'    => $type,
			'created' => $created,
			'data'    => array( 'object' => $data ),
		)
	);
};

// Entrega um evento com assinatura válida.
$resgata_deliver = static function ( string $payload ) use ( $resgata_secret ): array {
	return WebhookHandler::handle( $payload, WebhookSignature::generateSignatureHeader( $payload, $resgata_secret ), $resgata_secret );
};

$resgata_subscription = static function ( string $status, string $customer, string $subscription, string $price ): array {
	return array(
		'id'       => $subscription,
		'object'   => 'subscription',
		'customer' => $customer,
		'status'   => $status,
		'items'    => array( 'data' => array( array( 'price' => array( 'id' => $price ) ) ) ),
	);
};

// 1. Assinatura inválida: nada é processado.
$resgata_payload = $resgata_event( 'customer.subscription.created', $resgata_subscription( 'active', $resgata_customer, $resgata_sub, $resgata_price ), $resgata_now );

$resgata_result = WebhookHandler::handle( $resgata_payload, '', $resgata_secret );
$resgata_assert( 400 === $resgata_result['status'], 'Evento sem assinatura deveria ser recusado com 400' );

$resgata_result = WebhookHandler::handle( $resgata_payload, WebhookSignature::generateSignatureHeader( $resgata_payload, 'whsec_outro_segredo' ), $resgata_secret );
$resgata_assert( 400 === $resgata_result['status'] && 'invalid_signature' === $resgata_result['result'], 'Evento assinado com outro segredo deveria ser recusado com 400' );

$resgata_result = WebhookHandler::handle( $resgata_payload . ' ', WebhookSignature::generateSignatureHeader( $resgata_payload, $resgata_secret ), $resgata_secret );
$resgata_assert( 400 === $resgata_result['status'], 'Corpo alterado depois de assinado deveria ser recusado com 400' );

$resgata_result = WebhookHandler::handle( $resgata_payload, WebhookSignature::generateSignatureHeader( $resgata_payload, $resgata_secret, $resgata_now - HOUR_IN_SECONDS ), $resgata_secret );
$resgata_assert( 400 === $resgata_result['status'], 'Assinatura antiga (repetição de uma entrega capturada) deveria ser recusada com 400' );

$resgata_result = WebhookHandler::handle( $resgata_payload, WebhookSignature::generateSignatureHeader( $resgata_payload, $resgata_secret ), '' );
$resgata_assert( 503 === $resgata_result['status'], 'Sem segredo configurado, o endpoint deveria responder 503' );

$resgata_assert( null === Sponsors::find_by_customer( $resgata_customer ), 'Evento recusado não pode criar padrinho' );

// A rota pública também recusa quem chega sem assinatura.
wp_set_current_user( 0 );
$resgata_request = new WP_REST_Request( 'POST', '/' . BaseController::REST_NAMESPACE . WebhookController::ROUTE );
$resgata_request->set_body( $resgata_payload );
$resgata_status = rest_get_server()->dispatch( $resgata_request )->get_status();
$resgata_assert( in_array( $resgata_status, array( 400, 503 ), true ), "A rota do webhook deveria recusar evento sem assinatura (HTTP $resgata_status)" );
$resgata_assert( null === Sponsors::find_by_customer( $resgata_customer ), 'A rota do webhook não pode criar padrinho sem assinatura válida' );

// 2. Fluxo normal: assinatura criada, Checkout concluído, fatura paga.
$resgata_result = $resgata_deliver( $resgata_payload );
$resgata_assert( 200 === $resgata_result['status'] && 'processed' === $resgata_result['result'], 'Evento válido deveria ser processado' );

$resgata_sponsor = Sponsors::find_by_customer( $resgata_customer );
$resgata_assert( $resgata_sponsor && 'active' === $resgata_sponsor['status'] && $resgata_price === $resgata_sponsor['price_id'] && $resgata_sub === $resgata_sponsor['subscription_id'], 'A assinatura criada deveria gravar situação, plano e ID' );
$resgata_assert( 1 === $resgata_new_active(), 'O contador deveria subir com a assinatura ativa' );
$resgata_assert( $resgata_now <= Plans::counter()['updated_at'], 'O contador deveria marcar a data da atualização' );

$resgata_result  = $resgata_deliver(
	$resgata_event(
		'checkout.session.completed',
		array(
			'id'               => 'cs_resgatateste_' . $resgata_run,
			'object'           => 'checkout.session',
			'mode'             => 'subscription',
			'customer'         => $resgata_customer,
			'subscription'     => $resgata_sub,
			'metadata'         => array( Gateway::ORIGIN_KEY => Gateway::ORIGIN_VALUE ),
			'customer_details' => array(
				'name'    => 'Madrinha <b>Teste</b>',
				'email'   => "madrinha.$resgata_run@example.org",
				'address' => array(
					'line1'       => 'Rua de Teste, 1',
					'postal_code' => '01000-000',
				),
				'tax_ids' => array( array( 'value' => '12345678909' ) ),
			),
		),
		$resgata_now + 1
	)
);
$resgata_sponsor = Sponsors::find_by_customer( $resgata_customer );
$resgata_assert( 200 === $resgata_result['status'] && $resgata_sponsor && 'Madrinha Teste' === $resgata_sponsor['name'] && "madrinha.$resgata_run@example.org" === $resgata_sponsor['email'], 'O Checkout concluído deveria gravar nome (sem HTML) e e-mail' );
$resgata_assert( $resgata_sponsor && 'active' === $resgata_sponsor['status'], 'O Checkout concluído não pode mudar a situação da assinatura' );
$resgata_assert( $resgata_sponsor && ! str_contains( (string) wp_json_encode( $resgata_sponsor ), '12345678909' ) && ! str_contains( (string) wp_json_encode( $resgata_sponsor ), 'Rua de Teste' ), 'CPF e endereço não podem ser gravados' );

// Boas-vindas: saem quando o padrinho está ativo e já tem e-mail, uma única vez.
$resgata_sponsor_mail = "madrinha.$resgata_run@example.org";
$resgata_welcome      = $resgata_mails_to( $resgata_sponsor_mail, 'Obrigado por apadrinhar' );
$resgata_assert( 1 === count( $resgata_welcome ) && str_contains( (string) $resgata_welcome[0]['message'], 'Olá, Madrinha!' ), 'O novo padrinho deveria receber um e-mail de agradecimento' );
$resgata_assert( $resgata_welcome && ! str_contains( (string) $resgata_welcome[0]['message'], '<' ) && ! str_contains( implode( ' ', (array) $resgata_welcome[0]['headers'] ), 'text/html' ), 'Sem imagem informada pelo site, o agradecimento vai em texto simples' );
$resgata_assert( 1 === count( $resgata_mails_to( $resgata_team, 'Novo padrinho' ) ), 'A protetora deveria ser avisada do novo padrinho' );
$resgata_assert( ! str_contains( (string) wp_json_encode( $resgata_mails_to( $resgata_team, 'Novo padrinho' ) ), $resgata_sponsor_mail ), 'O aviso à protetora não leva o e-mail do padrinho' );

$resgata_deliver( $resgata_event( 'invoice.payment_failed', array( 'id' => 'in_resgatateste_1', 'customer' => $resgata_customer ), $resgata_now + 2 ) ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
$resgata_assert( '1' === (string) Sponsors::find_by_customer( $resgata_customer )['payment_failed'], 'Fatura recusada deveria marcar o aviso de pagamento' );
$resgata_deliver( $resgata_event( 'invoice.payment_failed', array( 'id' => 'in_resgatateste_1', 'customer' => $resgata_customer ), $resgata_now + 2 ) ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
$resgata_assert( 1 === count( $resgata_mails_to( $resgata_team, 'Pagamento recusado' ) ), 'A protetora deveria receber um aviso só de pagamento recusado, mesmo com novas tentativas' );

$resgata_deliver(
	$resgata_event(
		'invoice.paid',
		array(
			'id'                 => 'in_resgatateste_2',
			'customer'           => $resgata_customer,
			'status_transitions' => array( 'paid_at' => $resgata_now ),
		),
		$resgata_now + 3
	)
);
$resgata_sponsor = Sponsors::find_by_customer( $resgata_customer );
$resgata_assert( '0' === (string) $resgata_sponsor['payment_failed'] && gmdate( 'Y-m-d H:i:s', $resgata_now ) === $resgata_sponsor['last_paid_at'], 'Fatura paga deveria limpar o aviso e gravar a data' );

// 3. Evento duplicado: a segunda entrega não tem efeito.
$resgata_payload = $resgata_event( 'customer.subscription.updated', $resgata_subscription( 'past_due', $resgata_customer, $resgata_sub, $resgata_price ), $resgata_now + 10 );
$resgata_result  = $resgata_deliver( $resgata_payload );
$resgata_assert( 'processed' === $resgata_result['result'] && 'past_due' === Sponsors::find_by_customer( $resgata_customer )['status'], 'A atualização deveria gravar a situação nova' );
$resgata_assert( 0 === $resgata_new_active(), 'Assinatura com pagamento atrasado não conta como ativa' );

$resgata_deliver( $resgata_event( 'customer.subscription.updated', $resgata_subscription( 'active', $resgata_customer, $resgata_sub, $resgata_price ), $resgata_now + 20 ) );
$resgata_assert( 'active' === Sponsors::find_by_customer( $resgata_customer )['status'], 'A assinatura deveria voltar a ficar ativa' );

$resgata_result = $resgata_deliver( $resgata_payload );
$resgata_assert( 200 === $resgata_result['status'] && 'duplicate' === $resgata_result['result'], 'Evento repetido deveria responder 200 sem reprocessar' );
$resgata_assert( 'active' === Sponsors::find_by_customer( $resgata_customer )['status'], 'Evento repetido não pode desfazer a situação atual' );

// 4. Evento fora de ordem: um evento antigo, entregue com atraso, é ignorado.
$resgata_result = $resgata_deliver( $resgata_event( 'customer.subscription.updated', $resgata_subscription( 'incomplete', $resgata_customer, $resgata_sub, $resgata_price ), $resgata_now + 5 ) );
$resgata_assert( 200 === $resgata_result['status'] && 'active' === Sponsors::find_by_customer( $resgata_customer )['status'], 'Evento antigo entregue com atraso não pode desfazer a situação atual' );

// 5. Cancelamento.
$resgata_result  = $resgata_deliver( $resgata_event( 'customer.subscription.deleted', $resgata_subscription( 'canceled', $resgata_customer, $resgata_sub, $resgata_price ), $resgata_now + 30 ) );
$resgata_sponsor = Sponsors::find_by_customer( $resgata_customer );
$resgata_assert( 'processed' === $resgata_result['result'] && 'canceled' === $resgata_sponsor['status'], 'O cancelamento deveria marcar o padrinho como cancelado' );
$resgata_assert( 0 === $resgata_new_active(), 'O contador deveria cair com o cancelamento' );
$resgata_assert( '' !== $resgata_sponsor['name'], 'O cancelamento não apaga o cadastro do padrinho' );

$resgata_deliver( $resgata_event( 'customer.subscription.updated', $resgata_subscription( 'active', $resgata_customer, $resgata_sub, $resgata_price ), $resgata_now + 25 ) );
$resgata_assert( 'canceled' === Sponsors::find_by_customer( $resgata_customer )['status'], 'Atualização anterior ao cancelamento não pode reativar o padrinho' );

$resgata_assert( 1 === count( $resgata_mails_to( $resgata_team, 'Apadrinhamento encerrado' ) ), 'A protetora deveria ser avisada do apadrinhamento encerrado' );
$resgata_assert( 1 === count( $resgata_mails_to( $resgata_sponsor_mail, 'Obrigado por apadrinhar' ) ), 'O agradecimento não pode ser enviado de novo' );

// 6. O que não é apadrinhamento fica de fora.
$resgata_other = 'cus_resgatateste_outro_' . $resgata_run;
$resgata_deliver( $resgata_event( 'customer.subscription.created', $resgata_subscription( 'active', $resgata_other, 'sub_resgatateste_outro_' . $resgata_run, 'price_de_outro_produto' ), $resgata_now ) );
$resgata_assert( null === Sponsors::find_by_customer( $resgata_other ), 'Assinatura de outro produto da conta não pode virar padrinho' );

$resgata_deliver(
	$resgata_event(
		'checkout.session.completed',
		array(
			'id'               => 'cs_resgatateste_outro_' . $resgata_run,
			'mode'             => 'subscription',
			'customer'         => $resgata_other,
			'customer_details' => array( 'email' => 'outro@example.org' ),
		),
		$resgata_now
	)
);
$resgata_assert( null === Sponsors::find_by_customer( $resgata_other ), 'Checkout que não nasceu neste módulo não pode virar padrinho' );

// A primeira fatura paga pode chegar antes dos eventos da assinatura (acontece na Stripe de verdade).
$resgata_early      = 'cus_resgatateste_cedo_' . $resgata_run;
$resgata_early_sub  = 'sub_resgatateste_cedo_' . $resgata_run;
$resgata_early_line = static fn( string $price ): array => array( 'data' => array( array( 'pricing' => array( 'price_details' => array( 'price' => $price ) ) ) ) );

$resgata_deliver(
	$resgata_event(
		'invoice.paid',
		array(
			'id'                 => 'in_resgatateste_outro',
			'customer'           => $resgata_other,
			'status_transitions' => array( 'paid_at' => $resgata_now ),
			'lines'              => $resgata_early_line( 'price_de_outro_produto' ),
		),
		$resgata_now
	)
);
$resgata_assert( null === Sponsors::find_by_customer( $resgata_other ), 'Fatura paga de outro produto da conta não pode virar padrinho' );

$resgata_deliver(
	$resgata_event(
		'invoice.paid',
		array(
			'id'                 => 'in_resgatateste_cedo',
			'customer'           => $resgata_early,
			'status_transitions' => array( 'paid_at' => $resgata_now ),
			'parent'             => array( 'subscription_details' => array( 'subscription' => $resgata_early_sub ) ),
			'lines'              => $resgata_early_line( $resgata_price ),
		),
		$resgata_now
	)
);
$resgata_sponsor_early = Sponsors::find_by_customer( $resgata_early );
$resgata_assert( $resgata_sponsor_early && gmdate( 'Y-m-d H:i:s', $resgata_now ) === $resgata_sponsor_early['last_paid_at'] && 'active' !== $resgata_sponsor_early['status'], 'Fatura paga que chega antes da assinatura deveria guardar a data do pagamento, sem contar como ativo ainda' );

$resgata_deliver( $resgata_event( 'customer.subscription.created', $resgata_subscription( 'active', $resgata_early, $resgata_early_sub, $resgata_price ), $resgata_now ) );
$resgata_sponsor_early = Sponsors::find_by_customer( $resgata_early );
$resgata_assert( $resgata_sponsor_early && 'active' === $resgata_sponsor_early['status'] && gmdate( 'Y-m-d H:i:s', $resgata_now ) === $resgata_sponsor_early['last_paid_at'], 'A assinatura que chega depois da fatura deveria ativar o padrinho e manter a data do pagamento' );

$resgata_result = $resgata_deliver( $resgata_event( 'payment_intent.succeeded', array( 'id' => 'pi_resgatateste' ), $resgata_now ) );
$resgata_assert( 200 === $resgata_result['status'] && 'ignored' === $resgata_result['result'], 'Evento de tipo não tratado deveria responder 200 e ser ignorado' );

$resgata_result = WebhookHandler::handle( '{"sem":"id"}', WebhookSignature::generateSignatureHeader( '{"sem":"id"}', $resgata_secret ), $resgata_secret );
$resgata_assert( 400 === $resgata_result['status'], 'Corpo assinado mas sem formato de evento deveria ser recusado com 400' );

// 7. Pagamentos: registro, estorno e contestação.
$resgata_payer     = 'cus_resgatateste_paga_' . $resgata_run;
$resgata_payer_sub = 'sub_resgatateste_paga_' . $resgata_run;
$resgata_period    = $resgata_now + 30 * DAY_IN_SECONDS;
$resgata_paying    = static function ( array $extra = array() ) use ( $resgata_subscription, $resgata_payer, $resgata_payer_sub, $resgata_price, $resgata_period ): array {
	$subscription = $resgata_subscription( 'active', $resgata_payer, $resgata_payer_sub, $resgata_price );

	$subscription['items']['data'][0]['current_period_end'] = $resgata_period;

	return array_merge( $subscription, $extra );
};
$resgata_invoice   = static function ( int $number, int $amount ) use ( $resgata_run, $resgata_payer, $resgata_now ): array {
	return array(
		'id'                 => "in_resgatateste_paga_{$resgata_run}_{$number}",
		'customer'           => $resgata_payer,
		'amount_paid'        => $amount,
		'status_transitions' => array( 'paid_at' => $resgata_now ),
	);
};
$resgata_link      = static function ( int $number ) use ( $resgata_run ): array {
	return array(
		'id'      => "inpay_resgatateste_{$resgata_run}_{$number}",
		'invoice' => "in_resgatateste_paga_{$resgata_run}_{$number}",
		'payment' => array(
			'type'           => 'payment_intent',
			'payment_intent' => "pi_resgatateste_{$resgata_run}_{$number}",
		),
	);
};

$resgata_thanks_image = static fn(): string => 'https://example.org/obrigado-teste.gif';
add_filter( 'resgata_thanks_image_url', $resgata_thanks_image );
$resgata_deliver( $resgata_event( 'customer.subscription.created', $resgata_paying(), $resgata_now ) );
$resgata_deliver(
	$resgata_event(
		'checkout.session.completed',
		array(
			'id'               => 'cs_resgatateste_paga_' . $resgata_run,
			'mode'             => 'subscription',
			'customer'         => $resgata_payer,
			'subscription'     => $resgata_payer_sub,
			'metadata'         => array( Gateway::ORIGIN_KEY => Gateway::ORIGIN_VALUE ),
			'customer_details' => array(
				'name'  => 'Padrinho <i>Pagador</i>',
				'email' => "pagador.$resgata_run@example.org",
			),
		),
		$resgata_now
	)
);
remove_filter( 'resgata_thanks_image_url', $resgata_thanks_image );
$resgata_welcome_html = $resgata_mails_to( "pagador.$resgata_run@example.org", 'Obrigado por apadrinhar' );
$resgata_assert( 1 === count( $resgata_welcome_html ) && str_contains( (string) $resgata_welcome_html[0]['message'], 'https://example.org/obrigado-teste.gif' ) && str_contains( implode( ' ', (array) $resgata_welcome_html[0]['headers'] ), 'text/html' ) && ! str_contains( (string) $resgata_welcome_html[0]['message'], '<i>' ), 'Com imagem informada pelo site, o agradecimento vai em HTML, sem HTML vindo do nome' );
$resgata_payer_id = (int) Sponsors::find_by_customer( $resgata_payer )['id'];
$resgata_assert( gmdate( 'Y-m-d H:i:s', $resgata_period ) === Sponsors::find_by_customer( $resgata_payer )['period_end'], 'A assinatura deveria gravar o fim do período pago' );

// A ligação com o pagamento pode chegar depois da fatura (1) ou antes dela (2).
$resgata_deliver( $resgata_event( 'invoice.paid', $resgata_invoice( 1, 3000 ), $resgata_now + 1 ) );
$resgata_deliver( $resgata_event( 'invoice_payment.paid', $resgata_link( 1 ), $resgata_now + 1 ) );
$resgata_deliver( $resgata_event( 'invoice_payment.paid', $resgata_link( 2 ), $resgata_now + 2 ) );
$resgata_assert( array( 'total' => 3000, 'count' => 1 ) === Payments::totals( $resgata_payer_id ), 'Pagamento sem fatura paga ainda não conta para o padrinho' ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
$resgata_deliver( $resgata_event( 'invoice.paid', $resgata_invoice( 2, 3000 ), $resgata_now + 2 ) );
$resgata_deliver( $resgata_event( 'invoice.paid', $resgata_invoice( 3, 3000 ), $resgata_now + 3 ) );
$resgata_deliver( $resgata_event( 'invoice_payment.paid', $resgata_link( 3 ), $resgata_now + 3 ) );
$resgata_assert( array( 'total' => 9000, 'count' => 3 ) === Payments::totals( $resgata_payer_id ), 'Cada fatura paga deveria somar ao total do padrinho' ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
$resgata_assert( "pi_resgatateste_{$resgata_run}_2" === ( Payments::find_by_invoice( "in_resgatateste_paga_{$resgata_run}_2" )['payment_intent_id'] ?? '' ), 'A fatura que chega depois da ligação deveria manter o ID do pagamento' );

$resgata_result = $resgata_deliver( $resgata_event( 'invoice.paid', $resgata_invoice( 1, 3000 ), $resgata_now + 4 ) );
$resgata_assert( 'processed' === $resgata_result['result'] && 9000 === Payments::totals( $resgata_payer_id )['total'], 'A mesma fatura em outro evento não pode ser somada duas vezes' );

// Estorno parcial e depois total; um evento antigo não diminui o que já foi devolvido.
$resgata_charge = static function ( int $number, int $refunded ) use ( $resgata_run, $resgata_payer ): array {
	return array(
		'id'              => "ch_resgatateste_{$resgata_run}_{$number}",
		'customer'        => $resgata_payer,
		'payment_intent'  => "pi_resgatateste_{$resgata_run}_{$number}",
		'amount'          => 3000,
		'amount_refunded' => $refunded,
	);
};
$resgata_deliver( $resgata_event( 'charge.refunded', $resgata_charge( 1, 1000 ), $resgata_now + 5 ) );
$resgata_assert( array( 'total' => 8000, 'count' => 3 ) === Payments::totals( $resgata_payer_id ), 'Estorno parcial deveria sair do total' ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
$resgata_deliver( $resgata_event( 'charge.refunded', $resgata_charge( 1, 3000 ), $resgata_now + 6 ) );
$resgata_deliver( $resgata_event( 'charge.refunded', $resgata_charge( 1, 1000 ), $resgata_now + 5 ) );
$resgata_assert( array( 'total' => 6000, 'count' => 2 ) === Payments::totals( $resgata_payer_id ), 'Pagamento devolvido por inteiro deixa de contar, e evento antigo não desfaz o estorno' ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound

$resgata_result = $resgata_deliver( $resgata_event( 'charge.refunded', array( 'id' => 'ch_resgatateste_outro', 'payment_intent' => 'pi_de_outro_produto', 'amount_refunded' => 500 ), $resgata_now ) ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
$resgata_assert( 'processed' === $resgata_result['result'] && 6000 === Payments::totals( $resgata_payer_id )['total'], 'Estorno de outro produto da conta não mexe nos pagamentos' );

// Contestação: o valor sai enquanto está em análise e volta se a protetora ganhar.
$resgata_dispute = static function ( int $number, string $status ) use ( $resgata_run ): array {
	return array(
		'id'             => "dp_resgatateste_{$resgata_run}_{$number}",
		'charge'         => "ch_resgatateste_{$resgata_run}_{$number}",
		'payment_intent' => "pi_resgatateste_{$resgata_run}_{$number}",
		'amount'         => 3000,
		'status'         => $status,
	);
};
$resgata_deliver( $resgata_event( 'charge.dispute.created', $resgata_dispute( 2, 'needs_response' ), $resgata_now + 7 ) );
$resgata_assert( 3000 === Payments::totals( $resgata_payer_id )['total'], 'Pagamento contestado deveria sair do total' );
$resgata_disputes = $resgata_mails_to( $resgata_team, 'Pagamento contestado' );
$resgata_assert( 1 === count( $resgata_disputes ) && str_contains( (string) $resgata_disputes[0]['message'], 'R$ 30' ), 'A protetora deveria ser avisada da contestação, com o valor' );

$resgata_deliver( $resgata_event( 'charge.dispute.closed', $resgata_dispute( 2, 'won' ), $resgata_now + 9 ) );
$resgata_assert( 6000 === Payments::totals( $resgata_payer_id )['total'], 'Contestação ganha deveria voltar a contar' );
$resgata_deliver( $resgata_event( 'charge.dispute.updated', $resgata_dispute( 2, 'under_review' ), $resgata_now + 8 ) );
$resgata_assert( 6000 === Payments::totals( $resgata_payer_id )['total'], 'Evento antigo não pode reabrir uma contestação encerrada' );

$resgata_deliver( $resgata_event( 'charge.dispute.created', $resgata_dispute( 3, 'warning_needs_response' ), $resgata_now + 10 ) );
$resgata_assert( 6000 === Payments::totals( $resgata_payer_id )['total'], 'Pedido de informação do banco ainda não tira o valor' );
$resgata_deliver( $resgata_event( 'charge.dispute.closed', $resgata_dispute( 3, 'lost' ), $resgata_now + 11 ) );
$resgata_assert( array( 'total' => 3000, 'count' => 1 ) === Payments::totals( $resgata_payer_id ), 'Contestação perdida deixa de contar' ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound

// O que o painel mostra: total por padrinho, situação de cada pagamento e arrecadação do mês.
$resgata_status_of = static fn( int $number ): string => Payments::status( (array) Payments::find_by_invoice( "in_resgatateste_paga_{$resgata_run}_{$number}" ) );
$resgata_assert( array( $resgata_payer_id => 3000 ) === Payments::totals_by_sponsor( array( $resgata_payer_id, 0 ) ), 'O total por padrinho do painel deveria bater com o total da área do padrinho' );
$resgata_assert( 'refunded' === $resgata_status_of( 1 ) && 'dispute_won' === $resgata_status_of( 2 ) && 'dispute_lost' === $resgata_status_of( 3 ), 'Cada pagamento deveria mostrar a sua situação no painel' );
$resgata_revenue = Payments::revenue();
$wpdb->query( $wpdb->prepare( 'UPDATE %i SET dispute = %s WHERE invoice_id = %s', Payments::table(), Payments::DISPUTE_OPEN, "in_resgatateste_paga_{$resgata_run}_2" ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Reabre a contestação criada por este teste.
$resgata_reopened = Payments::revenue();
$resgata_assert( 3000 === $resgata_revenue['month'] - $resgata_reopened['month'] && 3000 === $resgata_revenue['total'] - $resgata_reopened['total'] && 1 === $resgata_reopened['open_disputes'] - $resgata_revenue['open_disputes'], 'A arrecadação do mês deveria descontar o pagamento em contestação e contar a contestação aberta' );
Payments::set_dispute( (int) Payments::find_by_invoice( "in_resgatateste_paga_{$resgata_run}_2" )['id'], Payments::DISPUTE_WON );

// Pedido de cancelamento: a protetora é avisada no pedido, e não de novo no encerramento.
$resgata_deliver( $resgata_event( 'customer.subscription.updated', $resgata_paying( array( 'cancel_at_period_end' => true ) ), $resgata_now + 20 ) );
$resgata_payer_row = Sponsors::find_by_customer( $resgata_payer );
$resgata_assert( 'active' === $resgata_payer_row['status'] && gmdate( 'Y-m-d H:i:s', $resgata_period ) === $resgata_payer_row['cancel_at'], 'O pedido de cancelamento deveria gravar até quando o padrinho segue ativo' );
$resgata_deliver( $resgata_event( 'customer.subscription.deleted', $resgata_paying( array( 'status' => 'canceled', 'cancel_at_period_end' => true ) ), $resgata_now + 30 ) ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
$resgata_assert( 1 === count( $resgata_mails_to( $resgata_team, 'Pedido de cancelamento' ) ) && 1 === count( $resgata_mails_to( $resgata_team, 'Apadrinhamento encerrado' ) ), 'Cancelamento pedido gera um aviso só, no pedido' );

// Faturas de outros produtos ficam sem padrinho e são apagadas depois de 30 dias.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Envelhece só a linha criada por este teste.
$wpdb->query( $wpdb->prepare( 'UPDATE %i SET sponsor_id = 0, created_at = %s WHERE invoice_id = %s', Payments::table(), gmdate( 'Y-m-d H:i:s', $resgata_now - 40 * DAY_IN_SECONDS ), "in_resgatateste_paga_{$resgata_run}_3" ) );
Payments::cleanup();
$resgata_assert( null === Payments::find_by_invoice( "in_resgatateste_paga_{$resgata_run}_3" ) && null !== Payments::find_by_invoice( "in_resgatateste_paga_{$resgata_run}_2" ), 'A limpeza apaga só as linhas antigas sem padrinho' );

// 8. Ajuste manual do contador.
Plans::set_adjustment( 5 );
$resgata_assert( 5 === Plans::counter()['total'] - Plans::counter()['active'], 'O ajuste manual deveria somar ao contador' );
Plans::set_adjustment( -100000 );
$resgata_assert( 0 === Plans::counter()['total'], 'O contador do site nunca fica negativo' );

// 9. Link mágico: uso único, e nada é gerado para quem não é padrinho.
$resgata_token = MagicLink::create_token( (int) $resgata_sponsor['id'] );
$resgata_assert( MagicLink::consume_token( $resgata_token ) === (int) $resgata_sponsor['id'], 'O código do link deveria identificar o padrinho' );
$resgata_assert( 0 === MagicLink::consume_token( $resgata_token ), 'O código do link só pode ser usado uma vez' );
$resgata_assert( 0 === MagicLink::consume_token( 'codigoinventado' ), 'Código inventado não dá acesso' );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Confere que o código não fica gravado em texto puro.
$resgata_assert( '0' === (string) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s", '%' . $wpdb->esc_like( MagicLink::create_token( (int) $resgata_sponsor['id'] ) ) . '%' ) ), 'O código do link não pode ficar gravado em texto puro' );

// Limpeza.
// phpcs:disable WordPress.DB.DirectDatabaseQuery -- Remove só as linhas criadas por este teste.
$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE customer_id LIKE %s', Sponsors::table(), $wpdb->esc_like( 'cus_resgatateste_' ) . '%' ) );
$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE invoice_id LIKE %s', Payments::table(), $wpdb->esc_like( 'in_resgatateste_' ) . '%' ) );
$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE event_id LIKE %s', Events::table(), $wpdb->esc_like( 'evt_resgatateste_' ) . '%' ) );
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_resgata_sponsor_link_' ) . '%', $wpdb->esc_like( '_transient_timeout_resgata_sponsor_link_' ) . '%' ) );
// phpcs:enable

foreach ( $resgata_saved_options as $resgata_option => $resgata_value ) {
	if ( null === $resgata_value ) {
		delete_option( $resgata_option );
	} else {
		update_option( $resgata_option, $resgata_value, false );
	}
}

if ( $resgata_failures ) {
	foreach ( $resgata_failures as $resgata_failure ) {
		WP_CLI::warning( $resgata_failure );
	}
	WP_CLI::error( sprintf( 'Webhooks da Stripe: %d de %d verificações falharam.', count( $resgata_failures ), $resgata_checks ) );
}

WP_CLI::success( sprintf( 'Webhooks da Stripe: %d verificações, tudo certo.', $resgata_checks ) );
