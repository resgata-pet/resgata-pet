<?php
/**
 * Endpoint que recebe os webhooks da Stripe.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Sponsorship;

use ResgataWP\Modules\Panel\Rest\BaseController;

defined( 'ABSPATH' ) || exit;

/**
 * Rota `resgata-wp/v1/stripe/webhook`.
 *
 * É a única rota do namespace que não passa por `BaseController::route()`:
 * quem chama é a Stripe, sem login. A autenticação é a assinatura do evento,
 * conferida em `WebhookHandler::handle()` antes de qualquer processamento.
 */
final class WebhookController {

	public const ROUTE = '/stripe/webhook';

	/**
	 * Registra a rota.
	 */
	public function register_routes(): void {
		register_rest_route(
			BaseController::REST_NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'receive' ),
				// Pública de propósito: a assinatura da Stripe é conferida em receive().
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Endereço público do endpoint, para cadastrar na Stripe.
	 */
	public static function url(): string {
		return rest_url( BaseController::REST_NAMESPACE . self::ROUTE );
	}

	/**
	 * POST /stripe/webhook.
	 *
	 * @param \WP_REST_Request $request Requisição.
	 */
	public function receive( \WP_REST_Request $request ): \WP_REST_Response {
		$result = WebhookHandler::handle(
			(string) $request->get_body(),
			(string) $request->get_header( 'stripe_signature' ),
			Keys::webhook_secret()
		);

		return new \WP_REST_Response( array( 'result' => $result['result'] ), $result['status'] );
	}
}
