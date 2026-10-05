<?php
/**
 * Controle de acesso do painel.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Panel;

use ResgataWP\Core\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Mantém o "Protetor admin" dentro do painel e dá atalhos ao administrador.
 */
final class Access {

	/**
	 * Registra os hooks.
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this, 'keep_out_of_wp_admin' ) );
		add_filter( 'login_redirect', array( $this, 'login_redirect' ), 10, 3 );
		add_filter( 'show_admin_bar', array( $this, 'hide_admin_bar' ) );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_link' ), 80 );
		add_action( 'admin_menu', array( $this, 'admin_menu_link' ), 20 );
	}

	/**
	 * O Protetor admin nunca usa o wp-admin: é levado para o painel.
	 *
	 * Chamadas AJAX continuam liberadas (o front-end do site depende de admin-ajax.php).
	 */
	public function keep_out_of_wp_admin(): void {
		if ( wp_doing_ajax() || ! Capabilities::is_panel_only_user() ) {
			return;
		}

		wp_safe_redirect( Router::url() );
		exit;
	}

	/**
	 * Depois do login, o Protetor admin cai direto no painel.
	 *
	 * @param string             $redirect_to Destino calculado pelo WordPress.
	 * @param string             $requested   Destino pedido na URL.
	 * @param \WP_User|\WP_Error $user        Usuário que acabou de entrar.
	 */
	public function login_redirect( $redirect_to, $requested, $user ) {
		if ( $user instanceof \WP_User && Capabilities::is_panel_only_user( $user ) ) {
			$panel = Router::url();

			// Mantém o destino se já for uma tela do painel (ex.: link direto para um animal).
			return is_string( $requested ) && str_starts_with( $requested, $panel ) ? $requested : $panel;
		}

		return $redirect_to;
	}

	/**
	 * Esconde a barra do WordPress para o Protetor admin ao navegar no site.
	 *
	 * @param bool $show Valor atual.
	 */
	public function hide_admin_bar( $show ): bool {
		return Capabilities::is_panel_only_user() ? false : (bool) $show;
	}

	/**
	 * Atalho "Painel Resgata" na barra do WordPress.
	 *
	 * @param \WP_Admin_Bar $admin_bar Barra de administração.
	 */
	public function admin_bar_link( \WP_Admin_Bar $admin_bar ): void {
		if ( ! current_user_can( Capabilities::PANEL ) ) {
			return;
		}

		$admin_bar->add_node(
			array(
				'id'    => 'rwp-panel',
				'title' => esc_html__( 'Painel Resgata', 'resgata-wp' ),
				'href'  => esc_url( Router::url() ),
			)
		);
	}

	/**
	 * Atalho para o painel no menu "Resgata WP" do wp-admin.
	 */
	public function admin_menu_link(): void {
		global $submenu;

		if ( isset( $submenu['resgata-wp'] ) && current_user_can( Capabilities::PANEL ) ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Forma suportada de adicionar um link externo ao submenu.
			$submenu['resgata-wp'][] = array( __( 'Abrir o painel', 'resgata-wp' ), Capabilities::PANEL, esc_url( Router::url() ) );
		}
	}
}
