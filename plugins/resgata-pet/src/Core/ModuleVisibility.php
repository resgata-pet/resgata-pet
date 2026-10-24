<?php
/**
 * Seções do site que dependem de um módulo.
 *
 * @package ResgataPet
 */

namespace ResgataPet\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Esconde, no site, os blocos marcados para um módulo que está desligado.
 *
 * Os modelos e patterns do tema são marcação de blocos pura, sem `if`. Para uma
 * seção sumir junto com o módulo, o bloco (em geral um Grupo) recebe uma destas
 * classes em "Avançado → Classes CSS adicionais":
 *
 * - `resgata-needs-<módulo>`: só aparece com o módulo ligado
 *   (ex.: `resgata-needs-animals` na seção "Animais para adoção");
 * - `resgata-without-<módulo>`: só aparece com o módulo desligado
 *   (ex.: um texto alternativo para quem não trabalha com adoção).
 *
 * Módulos: `animals`, `adoptions` e `sponsorship`. O editor continua mostrando tudo,
 * para a seção poder ser editada mesmo com o módulo desligado.
 *
 * Numa grade com número fixo de colunas, as colunas acompanham os itens que
 * sobraram: três atalhos ocupam a linha toda, sem um buraco no lugar do quarto.
 */
final class ModuleVisibility {

	/**
	 * Classes reconhecidas: regra (`needs` ou `without`) e slug do módulo.
	 */
	private const CLASS_PATTERN = '/(?:^|\s)resgata-(needs|without)-([a-z]+)(?=\s|$)/';

	/**
	 * Registra os filtros.
	 */
	public static function register(): void {
		add_filter( 'pre_render_block', array( self::class, 'skip_hidden' ), 10, 2 );
		add_filter( 'render_block_data', array( self::class, 'fit_grid' ) );
	}

	/**
	 * Informa se o bloco está marcado para um módulo que não vale agora.
	 *
	 * @param array<string, mixed> $block Bloco analisado (`parse_blocks()`).
	 */
	public static function is_hidden( array $block ): bool {
		$class = $block['attrs']['className'] ?? '';

		if ( ! is_string( $class ) || ! str_contains( $class, 'resgata-' ) || ! preg_match_all( self::CLASS_PATTERN, $class, $matches, PREG_SET_ORDER ) ) {
			return false;
		}

		foreach ( $matches as $match ) {
			// Slug desconhecido: a classe é ignorada, o bloco aparece.
			if ( ! in_array( $match[2], self::module_slugs(), true ) ) {
				continue;
			}

			$enabled = Modules::is_enabled( $match[2] );

			if ( ( 'needs' === $match[1] && ! $enabled ) || ( 'without' === $match[1] && $enabled ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Filtro `pre_render_block`: o bloco escondido não é renderizado (nem os blocos de dentro dele).
	 *
	 * @param string|null $pre_render   Conteúdo já definido por outro filtro, ou null.
	 * @param mixed       $parsed_block Bloco analisado.
	 * @return string|null
	 */
	public static function skip_hidden( $pre_render, $parsed_block ) {
		if ( null === $pre_render && is_array( $parsed_block ) && self::is_hidden( $parsed_block ) ) {
			return '';
		}

		return $pre_render;
	}

	/**
	 * Filtro `render_block_data`: grade com colunas fixas e itens escondidos fica com uma coluna por item visível.
	 *
	 * @param mixed $parsed_block Bloco analisado.
	 * @return mixed
	 */
	public static function fit_grid( $parsed_block ) {
		if ( ! is_array( $parsed_block ) || empty( $parsed_block['innerBlocks'] ) ) {
			return $parsed_block;
		}

		$layout  = $parsed_block['attrs']['layout'] ?? null;
		$columns = is_array( $layout ) && 'grid' === ( $layout['type'] ?? '' ) ? (int) ( $layout['columnCount'] ?? 0 ) : 0;

		if ( $columns < 2 ) {
			return $parsed_block;
		}

		$visible = count( array_filter( $parsed_block['innerBlocks'], static fn( $inner ): bool => is_array( $inner ) && ! self::is_hidden( $inner ) ) );

		if ( $visible > 0 && $visible < $columns && $visible < count( $parsed_block['innerBlocks'] ) ) {
			$parsed_block['attrs']['layout']['columnCount'] = $visible;
		}

		return $parsed_block;
	}

	/**
	 * Slugs de todos os módulos do kit (ligados ou não).
	 *
	 * @return string[]
	 */
	private static function module_slugs(): array {
		static $slugs = null;

		if ( null === $slugs ) {
			$slugs = array_keys( Modules::all() );
		}

		return $slugs;
	}
}
