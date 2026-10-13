<?php
/**
 * Área do padrinho: acesso por link enviado ao e-mail.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Sponsorship;

use ResgataWP\Modules\Settings\Organization;

defined( 'ABSPATH' ) || exit;

/**
 * Mostra ao padrinho o resumo do apadrinhamento e o leva ao portal da Stripe,
 * onde ele troca o cartão, muda o valor ou cancela. Não há senha nem conta no
 * site: a pessoa informa o e-mail, recebe um link de uso único e confirma a
 * abertura.
 *
 * Passos: (1) pedir o link; (2) abrir o link do e-mail, que só mostra um
 * botão; (3) tocar no botão, que gasta o código e mostra o resumo; (4) tocar
 * em "Alterar ou cancelar", que redireciona para a Stripe. O passo 2 existe
 * porque programas de e-mail costumam abrir os links sozinhos para
 * verificá-los, o que gastaria o código antes da pessoa.
 *
 * Não há sessão nem cookie: o resumo só existe na resposta ao envio do botão
 * (que nenhum cache de página guarda) e leva um código novo, de uso único,
 * para o passo seguinte.
 */
final class MagicLink {

	public const NONCE_ACTION = 'resgata_sponsor_area';
	public const NONCE_NAME   = 'resgata_sponsor_nonce';
	public const EMAIL_FIELD  = 'resgata_sponsor_email';
	public const TOKEN_FIELD  = 'resgata_sponsor_token';
	public const PORTAL_FIELD = 'resgata_sponsor_portal';
	public const HONEYPOT     = 'resgata_site';
	public const TOKEN_PARAM  = 'resgata_padrinho';
	public const SENT_PARAM   = 'resgata_link';

	/**
	 * Validade do link, em segundos.
	 */
	private const TTL = 30 * MINUTE_IN_SECONDS;

	/**
	 * Pedidos de link por hora pelo mesmo endereço de rede.
	 */
	private const RATE_LIMIT = 5;

	/**
	 * Intervalo mínimo, em segundos, entre dois e-mails para o mesmo padrinho.
	 */
	private const EMAIL_INTERVAL = 2 * MINUTE_IN_SECONDS;

	/**
	 * Erro do último envio.
	 *
	 * @var string
	 */
	private static string $error = '';

	/**
	 * Padrinho identificado pelo código nesta requisição.
	 *
	 * @var array<string, mixed>|null
	 */
	private static ?array $sponsor = null;

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
	 * Padrinho que acabou de abrir o link, para o bloco mostrar o resumo dele, ou null.
	 *
	 * @return array<string, mixed>|null
	 */
	public static function sponsor(): ?array {
		return self::$sponsor;
	}

	/**
	 * Informa se o pedido de link acabou de ser enviado.
	 */
	public static function was_sent(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Só escolhe a mensagem mostrada.
		return isset( $_GET[ self::SENT_PARAM ] ) && 'enviado' === sanitize_key( wp_unslash( $_GET[ self::SENT_PARAM ] ) );
	}

	/**
	 * Código do link aberto nesta navegação (ainda não conferido), ou string vazia.
	 */
	public static function token_from_url(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Código aleatório de uso único; é conferido só no envio do botão.
		return isset( $_GET[ self::TOKEN_PARAM ] ) ? self::clean_token( sanitize_text_field( wp_unslash( $_GET[ self::TOKEN_PARAM ] ) ) ) : '';
	}

	/**
	 * Processa os formulários da área do padrinho.
	 */
	public function maybe_handle(): void {
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] || ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			self::$error = __( 'A página ficou aberta por muito tempo. Tente de novo.', 'resgata-wp' );
			return;
		}

		$page = get_queried_object_id() ? (string) get_permalink( get_queried_object_id() ) : home_url( '/' );

		if ( isset( $_POST[ self::TOKEN_FIELD ] ) ) {
			$sponsor = Sponsors::find( self::consume_token( self::clean_token( sanitize_text_field( wp_unslash( $_POST[ self::TOKEN_FIELD ] ) ) ) ) );

			if ( ! $sponsor ) {
				self::$error = __( 'Este link venceu ou já foi usado. Peça um novo abaixo.', 'resgata-wp' );
				return;
			}

			// A resposta tem dados do padrinho: não pode ficar guardada em cache.
			nocache_headers();

			if ( isset( $_POST[ self::PORTAL_FIELD ] ) ) {
				$this->open_portal( $sponsor, $page );
			}

			// Sem redirecionamento (ou se a Stripe não respondeu), a página mostra o resumo.
			self::$sponsor = $sponsor;
			return;
		}

		if ( ! isset( $_POST[ self::EMAIL_FIELD ] ) ) {
			return;
		}

		$email = sanitize_email( wp_unslash( $_POST[ self::EMAIL_FIELD ] ) );

		if ( ! is_email( $email ) ) {
			self::$error = __( 'Confira o e-mail informado.', 'resgata-wp' );
			return;
		}

		// Campo invisível preenchido (robô) ou limite estourado: mesma resposta de sempre, sem enviar nada.
		if ( empty( $_POST[ self::HONEYPOT ] ) && RateLimit::allow( 'sponsor_link', self::RATE_LIMIT ) ) {
			self::send( $email, $page );
		}

		// A resposta é igual exista ou não um padrinho com esse e-mail, para não revelar quem é padrinho.
		wp_safe_redirect( add_query_arg( self::SENT_PARAM, 'enviado', $page ) . '#resgata-padrinho' );
		exit;
	}

	/**
	 * Envia o link ao padrinho, se o e-mail for de um.
	 *
	 * @param string $email E-mail informado.
	 * @param string $page  Página da área do padrinho.
	 */
	public static function send( string $email, string $page ): void {
		$sponsor = Sponsors::find_by_email( $email );

		if ( ! $sponsor ) {
			return;
		}

		$throttle = 'resgata_sponsor_mail_' . (int) $sponsor['id'];
		if ( get_transient( $throttle ) ) {
			return;
		}
		set_transient( $throttle, 1, self::EMAIL_INTERVAL );

		$token = self::create_token( (int) $sponsor['id'] );
		$name  = Organization::get()['name'];
		$name  = '' !== $name ? $name : get_bloginfo( 'name' );

		$subject = sprintf(
			/* translators: %s: nome da protetora. */
			__( 'Seu acesso à área do padrinho – %s', 'resgata-wp' ),
			$name
		);

		$body = sprintf(
			/* translators: 1: link de acesso, 2: nome da protetora. */
			__( "Olá!\n\nPara ver quanto você já doou ou alterar o seu apadrinhamento, abra o link abaixo. Ele vale por 30 minutos e só pode ser usado uma vez.\n\n%1\$s\n\nSe você não pediu este acesso, é só ignorar este e-mail.\n\n%2\$s", 'resgata-wp' ),
			add_query_arg( self::TOKEN_PARAM, $token, $page ) . '#resgata-padrinho',
			$name
		);

		wp_mail( $sponsor['email'], wp_specialchars_decode( $subject, ENT_QUOTES ), wp_specialchars_decode( $body, ENT_QUOTES ) );
	}

	/**
	 * Gera um código de uso único para o padrinho. No banco fica só o resumo do código.
	 *
	 * @param int $sponsor_id ID do padrinho.
	 */
	public static function create_token( int $sponsor_id ): string {
		$token = wp_generate_password( 40, false );

		set_transient( self::token_key( $token ), $sponsor_id, self::TTL );

		return $token;
	}

	/**
	 * Confere e gasta um código.
	 *
	 * @param string $token Código recebido.
	 * @return int ID do padrinho, ou 0 se o código não existe, venceu ou já foi usado.
	 */
	public static function consume_token( string $token ): int {
		if ( '' === $token ) {
			return 0;
		}

		$key        = self::token_key( $token );
		$sponsor_id = (int) get_transient( $key );

		delete_transient( $key );

		return $sponsor_id;
	}

	/**
	 * Leva o padrinho ao portal da Stripe.
	 *
	 * @param array<string, mixed> $sponsor Padrinho já identificado pelo código.
	 * @param string               $page    Página da área do padrinho.
	 */
	private function open_portal( array $sponsor, string $page ): void {
		$url = Gateway::portal_url( (string) $sponsor['customer_id'], $page );

		if ( is_wp_error( $url ) ) {
			self::$error = $url->get_error_message();
			return;
		}

		// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Endereço devolvido pela API da Stripe (portal hospedado por ela).
		wp_redirect( $url, 303 );
		exit;
	}

	/**
	 * Nome do transient de um código.
	 *
	 * @param string $token Código.
	 */
	private static function token_key( string $token ): string {
		return 'resgata_sponsor_link_' . substr( hash( 'sha256', $token ), 0, 40 );
	}

	/**
	 * Mantém só letras e números do código.
	 *
	 * @param string $token Código recebido.
	 */
	private static function clean_token( string $token ): string {
		return substr( (string) preg_replace( '/[^A-Za-z0-9]/', '', $token ), 0, 64 );
	}
}
