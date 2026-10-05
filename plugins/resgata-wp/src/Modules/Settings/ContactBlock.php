<?php
/**
 * Bloco "Contato da protetora".
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Settings;

use ResgataWP\Core\TemplateLoader;

defined( 'ABSPATH' ) || exit;

/**
 * Registra e renderiza o bloco `resgata-wp/contact`, que mostra no site os
 * dados públicos cadastrados em /painel/configuracoes.
 */
final class ContactBlock {

	/**
	 * Registra os hooks.
	 */
	public function register(): void {
		// Depois de Animals\Frontend\Blocks (prioridade 20), que registra os handles de estilo e script.
		add_action( 'init', array( $this, 'register_block' ), 25 );
	}

	/**
	 * Registra o bloco.
	 */
	public function register_block(): void {
		if ( ! wp_style_is( 'rwp-frontend', 'registered' ) ) {
			$src = current_theme_supports( 'resgata-wp' ) ? false : RWP_PLUGIN_URL . 'assets/css/frontend.css';
			wp_register_style( 'rwp-frontend', $src, array(), RWP_VERSION );
		}

		register_block_type( RWP_PLUGIN_DIR . 'blocks/contact' );
	}

	/**
	 * HTML do bloco.
	 *
	 * @param bool   $show_pix           Se mostra os dados do Pix.
	 * @param string $wrapper_attributes Atributos HTML do elemento externo, já escapados.
	 */
	public static function render( bool $show_pix = true, string $wrapper_attributes = '' ): string {
		$data = Organization::get();

		// Campos internos nunca chegam ao template.
		foreach ( Organization::PRIVATE_FIELDS as $field ) {
			unset( $data[ $field ] );
		}

		$networks = array();
		foreach ( Organization::networks() as $key => $network ) {
			if ( '' !== $data[ $key ] ) {
				$networks[ $network[0] ] = $data[ $key ];
			}
		}

		wp_enqueue_style( 'rwp-frontend' );

		return TemplateLoader::render(
			'contact.php',
			array(
				'wrapper_attributes' => '' !== $wrapper_attributes ? $wrapper_attributes : 'class="rwp-contact"',
				'data'               => $data,
				'whatsapp_url'       => Organization::whatsapp_url(),
				'whatsapp_label'     => Organization::format_phone( $data['whatsapp'] ),
				'phone_label'        => Organization::format_phone( $data['phone'] ),
				'location'           => Organization::location(),
				'networks'           => $networks,
				'show_pix'           => $show_pix && '' !== $data['pix_key'],
				'pix_type_label'     => Organization::pix_key_types()[ $data['pix_key_type'] ] ?? '',
			)
		);
	}
}
