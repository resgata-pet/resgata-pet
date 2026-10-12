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
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_link' ), 35 );
		add_action( 'admin_menu', array( $this, 'admin_menu_link' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'shortcut_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'shortcut_styles' ) );
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
	 * Botão "Painel Resgata" na barra do WordPress (no wp-admin e no site).
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
				'title' => '<span class="ab-icon dashicons dashicons-pets" aria-hidden="true"></span><span class="ab-label">' . esc_html__( 'Painel Resgata', 'resgata-wp' ) . '</span>',
				'href'  => esc_url( Router::url() ),
				'meta'  => array( 'title' => __( 'Abrir o painel da protetora', 'resgata-wp' ) ),
			)
		);
	}

	/**
	 * Botão "Painel Resgata" no topo do menu lateral do wp-admin.
	 */
	public function admin_menu_link(): void {
		global $menu;

		if ( ! current_user_can( Capabilities::PANEL ) ) {
			return;
		}

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Forma suportada de adicionar um link externo ao menu, no topo.
		$menu['1.5'] = array(
			__( 'Painel Resgata', 'resgata-wp' ),
			Capabilities::PANEL,
			esc_url( Router::url() ),
			'',
			'menu-top',
			'menu-rwp-panel',
			'dashicons-pets',
		);
	}

	/**
	 * Destaca o botão da barra do topo com o verde da identidade (o do menu
	 * lateral segue o padrão do WordPress).
	 *
	 * Vai junto do CSS da barra do WordPress, que existe no wp-admin e no site.
	 */
	public function shortcut_styles(): void {
		if ( ! is_admin_bar_showing() || ! current_user_can( Capabilities::PANEL ) ) {
			return;
		}

		wp_add_inline_style(
			'admin-bar',
			'#wpadminbar #wp-admin-bar-rwp-panel > .ab-item{background:#2f7d55;color:#fff}'
			. '#wpadminbar #wp-admin-bar-rwp-panel > .ab-item:hover,#wpadminbar #wp-admin-bar-rwp-panel > .ab-item:focus{background:#256645;color:#fff}'
			. '#wpadminbar #wp-admin-bar-rwp-panel .ab-icon:before{color:#fff}'
			. '#wpadminbar #wp-admin-bar-rwp-panel .ab-icon{margin-right:6px}'
			. '@media screen and (max-width:782px){#wpadminbar li#wp-admin-bar-rwp-panel{display:block}#wpadminbar #wp-admin-bar-rwp-panel .ab-label{display:none}#wpadminbar #wp-admin-bar-rwp-panel .ab-icon{margin-right:0}}'
		);
	}
}
