<?php
/**
 * Página do painel.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Panel;

use ResgataWP\Core\Assets;
use ResgataWP\Core\Capabilities;
use ResgataWP\Core\Modules;
use ResgataWP\Modules\Animals\Options;
use ResgataWP\Modules\Animals\Taxonomies;
use ResgataWP\Modules\Panel\Rest\BaseController;

defined( 'ABSPATH' ) || exit;

/**
 * Imprime a página em branco onde a aplicação React do painel é montada.
 *
 * Não usa o tema nem `wp_head()`/`wp_footer()`: só os arquivos do painel entram.
 */
final class Page {

	private const HANDLE = 'resgata-painel';

	/**
	 * Imprime a página.
	 */
	public function render(): void {
		$script = Assets::script( 'painel/index' );

		nocache_headers();
		send_frame_options_header();
		header( 'X-Robots-Tag: noindex, nofollow' );
		header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );

		if ( $script ) {
			wp_enqueue_script( self::HANDLE, $script['url'], $script['dependencies'], $script['version'], true );
			wp_add_inline_script( self::HANDLE, 'window.resgataPainel = ' . wp_json_encode( $this->config() ) . ';', 'before' );
			wp_enqueue_style( self::HANDLE, Assets::style_url( 'painel/index' ), array(), $script['version'] );
		}
		?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<meta name="robots" content="noindex, nofollow">
	<meta name="theme-color" content="#2f7d55">
	<title><?php echo esc_html( sprintf( /* translators: %s: nome do site. */ __( 'Painel – %s', 'resgata-wp' ), get_bloginfo( 'name' ) ) ); ?></title>
		<?php
		wp_site_icon();
		wp_print_styles( array( self::HANDLE ) );
		?>
</head>
<body>
	<div id="resgata-painel">
		<?php if ( ! $script ) : ?>
			<p style="font-family: sans-serif; margin: 2rem;"><?php echo esc_html( Assets::missing_build_message() ); ?></p>
		<?php endif; ?>
	</div>
	<noscript><?php esc_html_e( 'O painel precisa de JavaScript ativado no navegador.', 'resgata-wp' ); ?></noscript>
		<?php
		// Imprime o script do painel e as dependências (inclui o nonce da API no wp-api-fetch).
		wp_print_scripts( array( self::HANDLE ) );
		?>
</body>
</html>
		<?php
	}

	/**
	 * Dados entregues à aplicação. Nada aqui é segredo: tudo é do próprio usuário logado.
	 *
	 * @return array<string, mixed>
	 */
	private function config(): array {
		$user = wp_get_current_user();

		return array(
			'basePath'      => untrailingslashit( (string) wp_parse_url( Router::url(), PHP_URL_PATH ) ),
			'restNamespace' => BaseController::REST_NAMESPACE,
			'siteName'      => get_bloginfo( 'name' ),
			'user'          => array(
				'name'                 => $user->display_name,
				'email'                => $user->user_email,
				'isAdmin'              => current_user_can( 'manage_options' ),
				'canManageSettings'    => current_user_can( Capabilities::SETTINGS ),
				'canManageAdoptions'   => Modules::is_enabled( 'animals' ) && Modules::is_enabled( 'adoptions' ) && current_user_can( Capabilities::ADOPTIONS ),
				'canManageSponsorship' => Modules::is_enabled( 'sponsorship' ) && current_user_can( Capabilities::SPONSORSHIP ),
			),
			'urls'          => array(
				'site'         => home_url( '/' ),
				'admin'        => admin_url(),
				// wp_logout_url() devolve a URL com &amp;; aqui ela vai para JSON, não para HTML.
				'logout'       => html_entity_decode( wp_logout_url( Router::url() ), ENT_QUOTES ),
				'lostPassword' => wp_lostpassword_url(),
			),
			'modules'       => Modules::enabled(),
			'options'       => array(
				'statuses'         => Options::statuses(),
				'sexes'            => Options::sexes(),
				'sizes'            => Options::sizes(),
				'yesNo'            => Options::yes_no(),
				'tests'            => Options::test_results(),
				'photoTypes'       => Options::photo_types(),
				'species'          => Modules::is_enabled( 'animals' ) ? Taxonomies::species_options() : array(),
				'temperaments'     => Options::temperaments(),
				'adoptionStatuses' => \ResgataWP\Modules\Adoptions\PostType::statuses(),
				'sponsorStatuses'  => \ResgataWP\Modules\Sponsorship\Sponsors::statuses(),
			),
		);
	}
}
