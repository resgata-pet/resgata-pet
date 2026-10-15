<?php
/**
 * Bloco "Contato da protetora".
 *
 * @package ResgataPet
 */

namespace ResgataPet\Modules\Settings;

use ResgataPet\Core\Links;
use ResgataPet\Core\TemplateLoader;

defined( 'ABSPATH' ) || exit;

/**
 * Registra e renderiza o bloco `resgata-pet/contact`, que mostra no site os
 * dados públicos cadastrados em /painel/configuracoes.
 */
final class ContactBlock {

	/**
	 * Registra os hooks.
	 */
	public function register(): void {
		// Depois de Animals\Frontend\Blocks (prioridade 20), que registra os handles de estilo e script.
		// Depois dos blocos de animais (20), que registram os handles de estilo e script, e antes de o
		// WordPress acrescentar os atributos de suporte (alinhamento, espaçamento), na prioridade 22.
		add_action( 'init', array( $this, 'register_block' ), 21 );
	}

	/**
	 * Registra o bloco.
	 */
	public function register_block(): void {
		if ( ! wp_style_is( 'resgata-frontend', 'registered' ) ) {
			$src = current_theme_supports( 'resgata-pet' ) ? false : RESGATA_PLUGIN_URL . 'assets/css/frontend.css';
			wp_register_style( 'resgata-frontend', $src, array(), RESGATA_VERSION );
		}

		register_block_type( RESGATA_PLUGIN_DIR . 'blocks/contact' );
	}

	/**
	 * HTML do bloco.
	 *
	 * @param bool   $show_pix           Se mostra os dados do Pix.
	 * @param string $wrapper_attributes Atributos HTML do elemento externo, já escapados.
	 * @param bool   $compact            Versão resumida (WhatsApp, telefone, e-mail, local e redes), para o rodapé.
	 */
	public static function render( bool $show_pix = true, string $wrapper_attributes = '', bool $compact = false ): string {
		$data = Organization::get();

		// Campos internos nunca chegam ao template.
		foreach ( Organization::PRIVATE_FIELDS as $field ) {
			unset( $data[ $field ] );
		}

		$networks = array();
		$icons    = array();
		foreach ( Organization::networks() as $key => $network ) {
			if ( '' !== $data[ $key ] ) {
				$networks[ $network[0] ] = $data[ $key ];
				$icons[ $network[0] ]    = $key;
			}
		}

		$help_url = Links::help();

		wp_enqueue_style( 'resgata-frontend' );

		if ( '' === $wrapper_attributes ) {
			$wrapper_attributes = $compact ? 'class="resgata-contact-compact"' : 'class="resgata-contact"';
		}

		return TemplateLoader::render(
			$compact ? 'contact-compact.php' : 'contact.php',
			array(
				'wrapper_attributes' => $wrapper_attributes,
				'data'               => $data,
				'whatsapp_url'       => Organization::whatsapp_url(),
				'whatsapp_label'     => Organization::format_phone( $data['whatsapp'] ),
				'phone_label'        => Organization::format_phone( $data['phone'] ),
				'location'           => Organization::location(),
				'networks'           => $networks,
				'network_icons'      => $icons,
				'show_pix'           => $show_pix && '' !== $data['pix_key'],
				'pix_type_label'     => Organization::pix_key_types()[ $data['pix_key_type'] ] ?? '',
				'help_url'           => '' !== $help_url ? $help_url . '#resgata-pix' : '',
			)
		);
	}
}
