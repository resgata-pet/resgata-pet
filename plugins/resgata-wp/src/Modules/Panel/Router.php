<?php
/**
 * Endereço próprio do painel.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Panel;

use ResgataWP\Core\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Faz `/painel` (e tudo abaixo dele) abrir a aplicação do painel.
 */
final class Router {

	public const SLUG      = 'painel';
	public const QUERY_VAR = 'rwp_panel';

	/**
	 * Registra os hooks.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		// Antes do redirecionamento canônico do WordPress (prioridade 10).
		add_action( 'template_redirect', array( $this, 'maybe_render' ), 0 );
	}

	/**
	 * Regra de URL do painel. A navegação interna (`/painel/animais/12`) é resolvida no navegador.
	 */
	public static function add_rewrite_rules(): void {
		add_rewrite_rule( '^' . self::SLUG . '(?:/.*)?$', 'index.php?' . self::QUERY_VAR . '=1', 'top' );
	}

	/**
	 * Registra a variável de consulta do painel.
	 *
	 * @param string[] $vars Variáveis existentes.
	 * @return string[]
	 */
	public function query_vars( array $vars ): array {
		$vars[] = self::QUERY_VAR;

		return $vars;
	}

	/**
	 * URL do painel.
	 *
	 * @param string $path Caminho interno, ex.: `animais`.
	 */
	public static function url( string $path = '' ): string {
		return home_url( '/' . self::SLUG . '/' . ltrim( $path, '/' ) );
	}

	/**
	 * Se a requisição é do painel, confere o acesso e mostra a aplicação.
	 */
	public function maybe_render(): void {
		if ( ! get_query_var( self::QUERY_VAR ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			// Volta para a mesma tela do painel depois do login.
			$request = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$target  = preg_match( '#/' . self::SLUG . '/([a-z0-9/_-]+)#i', $request, $matches ) ? self::url( $matches[1] ) : self::url();

			wp_safe_redirect( wp_login_url( $target ) );
			exit;
		}

		if ( ! current_user_can( Capabilities::PANEL ) ) {
			wp_die(
				esc_html__( 'Você não tem permissão para acessar o painel.', 'resgata-wp' ),
				esc_html__( 'Acesso negado', 'resgata-wp' ),
				array( 'response' => 403 )
			);
		}

		( new Page() )->render();
		exit;
	}
}
