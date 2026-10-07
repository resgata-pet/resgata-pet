<?php
/**
 * Templates públicos dos animais.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Animals\Frontend;

use ResgataWP\Core\TemplateLoader;
use ResgataWP\Modules\Animals\PostType;

defined( 'ABSPATH' ) || exit;

/**
 * Garante uma listagem e uma página individual funcionais em qualquer tema.
 *
 * - Temas de blocos: o plugin registra modelos (editáveis no Editor do Site)
 *   que o tema pode substituir com `templates/single-rwp_animal.html` e
 *   `templates/archive-rwp_animal.html`.
 * - Temas clássicos: o plugin usa seus templates PHP, que o tema pode
 *   substituir em `resgata-wp/single-animal.php` e `resgata-wp/archive-animal.php`.
 */
final class Templates {

	/**
	 * Registra os hooks.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_block_templates' ), 20 );
		add_filter( 'template_include', array( $this, 'template_include' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Em temas clássicos, carrega os estilos já no cabeçalho das páginas de animais.
	 */
	public function enqueue_styles(): void {
		if ( ! wp_is_block_theme() && ( is_singular( PostType::SLUG ) || is_post_type_archive( PostType::SLUG ) ) ) {
			wp_enqueue_style( Blocks::STYLE_HANDLE );
		}
	}

	/**
	 * Registra os modelos de bloco do plugin.
	 */
	public function register_block_templates(): void {
		if ( ! wp_is_block_theme() || ! function_exists( 'register_block_template' ) ) {
			return;
		}

		register_block_template(
			'resgata-wp//single-' . PostType::SLUG,
			array(
				'title'       => __( 'Página do animal', 'resgata-wp' ),
				'description' => __( 'Mostra a página individual de um animal: fotos, resumo, história, saúde e formulário de adoção.', 'resgata-wp' ),
				'post_types'  => array( PostType::SLUG ),
				'content'     => $this->single_content(),
			)
		);

		register_block_template(
			'resgata-wp//archive-' . PostType::SLUG,
			array(
				'title'       => __( 'Lista de animais', 'resgata-wp' ),
				'description' => __( 'Mostra a listagem de animais com filtros.', 'resgata-wp' ),
				'content'     => $this->archive_content(),
			)
		);
	}

	/**
	 * Abertura comum dos modelos: cabeçalho do tema + área principal.
	 */
	private function open(): string {
		return '<!-- wp:template-part {"slug":"header"} /-->' . "\n\n"
			. '<!-- wp:group {"tagName":"main","style":{"spacing":{"margin":{"top":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->' . "\n"
			. '<main class="wp-block-group" style="margin-top:var(--wp--preset--spacing--60)">' . "\n";
	}

	/**
	 * Fechamento comum dos modelos: fim da área principal + rodapé do tema.
	 */
	private function close(): string {
		return '</main>' . "\n" . '<!-- /wp:group -->' . "\n\n" . '<!-- wp:template-part {"slug":"footer"} /-->';
	}

	/**
	 * Conteúdo do modelo da página individual.
	 */
	private function single_content(): string {
		// O bloco da página do animal já traz o nome, as fotos e a história.
		return $this->open()
			. '<!-- wp:resgata-wp/animal-details {"align":"wide"} /-->' . "\n"
			. '<!-- wp:resgata-wp/adoption-form /-->' . "\n"
			. $this->close();
	}

	/**
	 * Conteúdo do modelo da listagem.
	 */
	private function archive_content(): string {
		return $this->open()
			. '<!-- wp:query-title {"type":"archive","showPrefix":false} /-->' . "\n"
			. '<!-- wp:resgata-wp/animals-list {"columns":4,"align":"wide"} /-->' . "\n"
			. $this->close();
	}

	/**
	 * Em temas clássicos, usa os templates PHP do plugin (ou do tema).
	 *
	 * @param string $template Template escolhido pelo WordPress.
	 */
	public function template_include( $template ) {
		if ( wp_is_block_theme() ) {
			return $template;
		}

		$name = '';
		if ( is_singular( PostType::SLUG ) ) {
			$name = 'single-animal.php';
		} elseif ( is_post_type_archive( PostType::SLUG ) ) {
			$name = 'archive-animal.php';
		}

		$path = '' !== $name ? TemplateLoader::locate( $name ) : '';

		return '' !== $path ? $path : $template;
	}
}
