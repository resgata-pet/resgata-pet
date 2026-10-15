<?php
/**
 * HTML permitido na saída dos blocos e templates.
 *
 * @package ResgataPet
 */

namespace ResgataPet\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Lista de tags e atributos para `wp_kses()` na hora de imprimir o HTML montado pelos
 * templates: a do conteúdo de posts, mais formulários e os ícones em SVG.
 */
final class Html {

	/**
	 * Atributos aceitos em todas as tags acrescentadas aqui.
	 */
	private const GLOBAL_ATTRIBUTES = array(
		'aria-describedby' => true,
		'aria-hidden'      => true,
		'aria-invalid'     => true,
		'aria-label'       => true,
		'aria-labelledby'  => true,
		'aria-live'        => true,
		'class'            => true,
		'data-*'           => true,
		'hidden'           => true,
		'id'               => true,
		'role'             => true,
		'style'            => true,
		'tabindex'         => true,
		'title'            => true,
	);

	/**
	 * Cache da lista completa.
	 *
	 * @var array<string, array<string, bool>>|null
	 */
	private static ?array $allowed = null;

	/**
	 * Tags e atributos do HTML dos blocos e templates.
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function allowed(): array {
		if ( null !== self::$allowed ) {
			return self::$allowed;
		}

		$extra = array(
			'form'     => array(
				'action'     => true,
				'method'     => true,
				'novalidate' => true,
			),
			'fieldset' => array(),
			'legend'   => array(),
			'label'    => array( 'for' => true ),
			'input'    => array(
				'autocomplete' => true,
				'checked'      => true,
				'disabled'     => true,
				'inputmode'    => true,
				'max'          => true,
				'maxlength'    => true,
				'min'          => true,
				'name'         => true,
				'pattern'      => true,
				'placeholder'  => true,
				'readonly'     => true,
				'required'     => true,
				'step'         => true,
				'type'         => true,
				'value'        => true,
			),
			'select'   => array(
				'autocomplete' => true,
				'disabled'     => true,
				'multiple'     => true,
				'name'         => true,
				'required'     => true,
			),
			'option'   => array(
				'disabled' => true,
				'selected' => true,
				'value'    => true,
			),
			'textarea' => array(
				'autocomplete' => true,
				'disabled'     => true,
				'maxlength'    => true,
				'name'         => true,
				'placeholder'  => true,
				'readonly'     => true,
				'required'     => true,
				'rows'         => true,
			),
			'button'   => array(
				'disabled' => true,
				'name'     => true,
				'type'     => true,
				'value'    => true,
			),
			'img'      => array(
				'alt'           => true,
				'decoding'      => true,
				'fetchpriority' => true,
				'height'        => true,
				'loading'       => true,
				'sizes'         => true,
				'src'           => true,
				'srcset'        => true,
				'width'         => true,
			),
		);

		$allowed = wp_kses_allowed_html( 'post' );

		foreach ( $extra + self::svg() as $tag => $attributes ) {
			$allowed[ $tag ] = array_merge( $allowed[ $tag ] ?? array(), self::GLOBAL_ATTRIBUTES, $attributes );
		}

		// Tags do conteúdo de posts usadas pelos templates, com os mesmos atributos extras.
		foreach ( array( 'a', 'article', 'aside', 'dd', 'div', 'dl', 'dt', 'figure', 'h1', 'h2', 'h3', 'header', 'li', 'nav', 'ol', 'p', 'section', 'small', 'span', 'strong', 'ul' ) as $tag ) {
			$allowed[ $tag ] = array_merge( $allowed[ $tag ] ?? array(), self::GLOBAL_ATTRIBUTES );
		}

		$allowed['a']['aria-current']  = true;
		$allowed['li']['aria-current'] = true;

		self::$allowed = $allowed;

		return $allowed;
	}

	/**
	 * Tags e atributos dos ícones em SVG (`Icons`).
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function svg(): array {
		$shape = array(
			'clip-rule'       => true,
			'cx'              => true,
			'cy'              => true,
			'd'               => true,
			'fill'            => true,
			'fill-rule'       => true,
			'height'          => true,
			'points'          => true,
			'r'               => true,
			'rx'              => true,
			'ry'              => true,
			'stroke'          => true,
			'stroke-linecap'  => true,
			'stroke-linejoin' => true,
			'stroke-width'    => true,
			'transform'       => true,
			'width'           => true,
			'x'               => true,
			'x1'              => true,
			'x2'              => true,
			'y'               => true,
			'y1'              => true,
			'y2'              => true,
		);

		return array(
			'svg'      => array(
				'aria-hidden'     => true,
				'class'           => true,
				'fill'            => true,
				'focusable'       => true,
				'height'          => true,
				'role'            => true,
				'stroke'          => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'stroke-width'    => true,
				'version'         => true,
				'viewbox'         => true,
				'width'           => true,
				'xmlns'           => true,
			),
			'g'        => $shape,
			'path'     => $shape,
			'circle'   => $shape,
			'ellipse'  => $shape,
			'line'     => $shape,
			'polygon'  => $shape,
			'polyline' => $shape,
			'rect'     => $shape,
		);
	}
}
