<?php
/**
 * Início da assinatura pelo site.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Sponsorship;

defined( 'ABSPATH' ) || exit;

/**
 * Recebe a escolha do plano e leva a pessoa para o Checkout da Stripe.
 *
 * Nada é gravado aqui: o padrinho só passa a existir no site quando a Stripe
 * avisa, pelo webhook, que a assinatura foi criada.
 */
final class Checkout {

	public const NONCE_ACTION = 'rwp_sponsorship_checkout';
	public const NONCE_NAME   = 'rwp_sponsorship_nonce';
	public const FIELD        = 'rwp_plan';
	public const RESULT_PARAM = 'rwp_apadrinhamento';

	/**
	 * Sessões de Checkout abertas por hora pelo mesmo endereço de rede.
	 */
	private const RATE_LIMIT = 10;

	/**
	 * Erro do último envio.
	 *
	 * @var string
	 */
	private static string $error = '';

	/**
	 * Registra os hooks.
	 */
	public function register(): void {
		add_action( 'template_redirect', array( $this, 'maybe_handle' ), 5 );
	}

	/**
	 * Erro do envio atual, para o bloco mostrar.
	 */
	public static function error(): string {
		return self::$error;
	}

	/**
	 * Informa se a pessoa acabou de voltar do Checkout.
	 */
	public static function is_thank_you(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Só escolhe a mensagem mostrada; nada é gravado.
		return isset( $_GET[ self::RESULT_PARAM ] ) && 'obrigado' === sanitize_key( wp_unslash( $_GET[ self::RESULT_PARAM ] ) );
	}

	/**
	 * Informa se o site já pode vender planos (chave definida e preços criados).
	 */
	public static function is_ready(): bool {
		return '' !== Keys::secret_key() && array() !== Plans::for_sale();
	}

	/**
	 * Processa a escolha do plano, se esta requisição for uma.
	 */
	public function maybe_handle(): void {
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] || ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			self::$error = __( 'A página ficou aberta por muito tempo. Escolha o valor de novo.', 'resgata-wp' );
			return;
		}

		// O valor enviado só serve para achar o plano: o preço cobrado é sempre o cadastrado no servidor.
		$plan = Plans::find_for_sale( isset( $_POST[ self::FIELD ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::FIELD ] ) ) : '' );

		if ( ! $plan || ! self::is_ready() ) {
			self::$error = __( 'Escolha um dos valores para continuar.', 'resgata-wp' );
			return;
		}

		if ( ! RateLimit::allow( 'checkout', self::RATE_LIMIT ) ) {
			self::$error = __( 'Recebemos muitas tentativas deste endereço em pouco tempo. Tente de novo mais tarde.', 'resgata-wp' );
			return;
		}

		$page = get_queried_object_id() ? get_permalink( get_queried_object_id() ) : home_url( '/' );
		$url  = Gateway::checkout_url( $plan['price_id'], (string) $page );

		if ( is_wp_error( $url ) ) {
			self::$error = $url->get_error_message();
			return;
		}

		nocache_headers();
		// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Endereço devolvido pela API da Stripe (página de pagamento hospedada por ela).
		wp_redirect( $url, 303 );
		exit;
	}
}
