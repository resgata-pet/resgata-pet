<?php
/**
 * Arquivos gerados pelo build (pasta `build/`).
 *
 * @package ResgataWP
 */

namespace ResgataWP\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Localiza os scripts e estilos gerados por `npm run build`.
 */
final class Assets {

	/**
	 * Dados de um script do build: URL, dependências e versão.
	 *
	 * @param string $name Caminho dentro de `build/`, sem extensão. Ex.: `painel/index`.
	 * @return array{url: string, dependencies: string[], version: string}|null Null se o build não existir.
	 */
	public static function script( string $name ): ?array {
		$asset_file = RESGATA_PLUGIN_DIR . 'build/' . $name . '.asset.php';

		if ( ! is_readable( $asset_file ) ) {
			return null;
		}

		$asset = require $asset_file;

		return array(
			'url'          => RESGATA_PLUGIN_URL . 'build/' . $name . '.js',
			'dependencies' => (array) ( $asset['dependencies'] ?? array() ),
			'version'      => (string) ( $asset['version'] ?? RESGATA_VERSION ),
		);
	}

	/**
	 * URL de um arquivo CSS do build, ou string vazia se não existir.
	 *
	 * @param string $name Caminho dentro de `build/`, sem extensão.
	 */
	public static function style_url( string $name ): string {
		return is_readable( RESGATA_PLUGIN_DIR . 'build/' . $name . '.css' ) ? RESGATA_PLUGIN_URL . 'build/' . $name . '.css' : '';
	}

	/**
	 * Mensagem para quem instalou o plugin direto do repositório, sem gerar o build.
	 */
	public static function missing_build_message(): string {
		return __( 'Os arquivos do Resgata WP ainda não foram gerados. Instale o plugin pelo arquivo .zip ou rode "make build" na pasta do projeto.', 'resgata-wp' );
	}
}
