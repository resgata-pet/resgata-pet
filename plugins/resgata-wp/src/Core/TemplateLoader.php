<?php
/**
 * Localização de templates sobrescrevíveis pelo tema.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Procura um template primeiro na pasta `resgata-wp/` do tema (filho e pai)
 * e, se não existir, usa o template neutro do plugin.
 */
final class TemplateLoader {

	public const THEME_DIR = 'resgata-wp';

	/**
	 * Caminho absoluto do template a usar.
	 *
	 * @param string $name Nome do arquivo, ex.: `animal-card.php`.
	 * @return string Caminho do arquivo ou string vazia se não existir.
	 */
	public static function locate( string $name ): string {
		$name = ltrim( $name, '/' );

		// Apenas nomes simples de arquivo: nada de subir diretórios.
		if ( ! preg_match( '/^[a-z0-9\-]+\.php$/', $name ) ) {
			return '';
		}

		$path = locate_template( self::THEME_DIR . '/' . $name );

		if ( ! $path ) {
			$path = RWP_PLUGIN_DIR . 'templates/' . $name;
		}

		/**
		 * Filtra o caminho do template do Resgata WP.
		 *
		 * @param string $path Caminho absoluto do template.
		 * @param string $name Nome do arquivo de template.
		 */
		$path = (string) apply_filters( 'rwp_locate_template', $path, $name );

		return is_readable( $path ) ? $path : '';
	}

	/**
	 * Renderiza um template e devolve o HTML.
	 *
	 * @param string               $name Nome do arquivo de template.
	 * @param array<string, mixed> $args Variáveis disponíveis no template em `$rwp_args`.
	 */
	public static function render( string $name, array $args = array() ): string {
		$path = self::locate( $name );
		if ( '' === $path ) {
			return '';
		}

		ob_start();
		self::include_template( $path, $args );

		return (string) ob_get_clean();
	}

	/**
	 * Inclui o template em um escopo isolado.
	 *
	 * @param string               $rwp_path Caminho do template.
	 * @param array<string, mixed> $rwp_args Variáveis do template.
	 */
	private static function include_template( string $rwp_path, array $rwp_args ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Usada pelo template incluído.
		include $rwp_path;
	}
}
