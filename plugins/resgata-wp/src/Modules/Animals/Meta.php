<?php
/**
 * Metadados dos animais.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Animals;

defined( 'ABSPATH' ) || exit;

/**
 * Registra os metadados do animal e concentra as regras de sanitização.
 */
final class Meta {

	public const STATUS        = 'rwp_status';
	public const SEX           = 'rwp_sex';
	public const SIZE          = 'rwp_size';
	public const COLOR         = 'rwp_color';
	public const BIRTH_DATE    = 'rwp_birth_date';
	public const RESCUE_DATE   = 'rwp_rescue_date';
	public const GALLERY       = 'rwp_gallery';
	public const VACCINES      = 'rwp_vaccines';
	public const DEWORMED_DATE = 'rwp_dewormed_date';
	public const NEUTERED      = 'rwp_neutered';
	public const FIV           = 'rwp_fiv';
	public const FELV          = 'rwp_felv';
	public const TAGLINE       = 'rwp_tagline';
	public const INTRO         = 'rwp_intro';
	public const WEIGHT        = 'rwp_weight';
	public const VET_CHECKED   = 'rwp_vet_checked';
	public const VACCINATED    = 'rwp_vaccinated';

	/**
	 * Tamanho máximo (em caracteres) da frase de apresentação e do resumo.
	 */
	public const TAGLINE_MAX = 120;
	public const INTRO_MAX   = 500;

	/**
	 * Peso máximo aceito, em quilos.
	 */
	public const WEIGHT_MAX = 150;

	/**
	 * Registra todos os metadados com `register_post_meta`.
	 */
	public static function register_meta(): void {
		$strings = array(
			self::STATUS        => array( self::class, 'sanitize_status' ),
			self::SEX           => static fn( $value ) => self::sanitize_choice( $value, Options::sexes() ),
			self::SIZE          => static fn( $value ) => self::sanitize_choice( $value, Options::sizes() ),
			self::COLOR         => 'sanitize_text_field',
			self::BIRTH_DATE    => array( self::class, 'sanitize_date' ),
			self::RESCUE_DATE   => array( self::class, 'sanitize_date' ),
			self::DEWORMED_DATE => array( self::class, 'sanitize_date' ),
			self::NEUTERED      => static fn( $value ) => self::sanitize_choice( $value, Options::yes_no() ),
			self::FIV           => static fn( $value ) => self::sanitize_choice( $value, Options::test_results() ),
			self::FELV          => static fn( $value ) => self::sanitize_choice( $value, Options::test_results() ),
			self::TAGLINE       => array( self::class, 'sanitize_tagline' ),
			self::INTRO         => array( self::class, 'sanitize_intro' ),
			self::WEIGHT        => array( self::class, 'sanitize_weight' ),
			self::VET_CHECKED   => static fn( $value ) => self::sanitize_choice( $value, Options::yes_no() ),
			self::VACCINATED    => static fn( $value ) => self::sanitize_choice( $value, Options::yes_no() ),
		);

		foreach ( $strings as $key => $sanitize ) {
			register_post_meta(
				PostType::SLUG,
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'default'           => '',
					'sanitize_callback' => $sanitize,
					'auth_callback'     => array( self::class, 'can_edit' ),
					'show_in_rest'      => false,
				)
			);
		}

		$arrays = array(
			self::GALLERY  => array( self::class, 'sanitize_gallery' ),
			self::VACCINES => array( self::class, 'sanitize_vaccines' ),
		);

		foreach ( $arrays as $key => $sanitize ) {
			register_post_meta(
				PostType::SLUG,
				$key,
				array(
					'type'              => 'array',
					'single'            => true,
					'sanitize_callback' => $sanitize,
					'auth_callback'     => array( self::class, 'can_edit' ),
					'show_in_rest'      => false,
				)
			);
		}
	}

	/**
	 * Só quem pode editar o animal pode alterar seus metadados.
	 *
	 * @param bool   $allowed  Valor padrão.
	 * @param string $meta_key Chave do metadado.
	 * @param int    $post_id  ID do animal.
	 */
	public static function can_edit( $allowed, $meta_key, $post_id ): bool {
		return current_user_can( 'edit_post', (int) $post_id );
	}

	/**
	 * Aceita somente uma das chaves permitidas; qualquer outra coisa vira vazio.
	 *
	 * @param mixed                 $value   Valor recebido.
	 * @param array<string, string> $choices Opções permitidas (chave => rótulo).
	 */
	public static function sanitize_choice( $value, array $choices ): string {
		$value = is_scalar( $value ) ? sanitize_key( (string) $value ) : '';

		return isset( $choices[ $value ] ) ? $value : '';
	}

	/**
	 * Situação do animal.
	 *
	 * @param mixed $value Valor recebido.
	 */
	public static function sanitize_status( $value ): string {
		$value = self::sanitize_choice( $value, Options::statuses() );

		return '' === $value ? Options::STATUS_DEFAULT : $value;
	}

	/**
	 * Data no formato AAAA-MM-DD, ou vazio se inválida.
	 *
	 * @param mixed $value Valor recebido.
	 */
	public static function sanitize_date( $value ): string {
		$value = is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';

		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts ) ) {
			return '';
		}

		return checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] ) ? $value : '';
	}

	/**
	 * Frase de apresentação: uma linha, sem HTML.
	 *
	 * @param mixed $value Valor recebido.
	 */
	public static function sanitize_tagline( $value ): string {
		$value = is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';

		return mb_substr( $value, 0, self::TAGLINE_MAX );
	}

	/**
	 * Resumo: texto curto, sem HTML, que pode ter quebras de linha.
	 *
	 * @param mixed $value Valor recebido.
	 */
	public static function sanitize_intro( $value ): string {
		$value = is_scalar( $value ) ? sanitize_textarea_field( (string) $value ) : '';

		return mb_substr( $value, 0, self::INTRO_MAX );
	}

	/**
	 * Peso em quilos, com no máximo uma casa decimal (ex.: "18.5"), ou vazio.
	 *
	 * Aceita vírgula ou ponto como separador decimal.
	 *
	 * @param mixed $value Valor recebido.
	 */
	public static function sanitize_weight( $value ): string {
		$value = is_scalar( $value ) ? str_replace( ',', '.', trim( (string) $value ) ) : '';

		if ( ! is_numeric( $value ) ) {
			return '';
		}

		$weight = round( (float) $value, 1 );

		return $weight > 0 && $weight <= self::WEIGHT_MAX ? (string) $weight : '';
	}

	/**
	 * Galeria: lista de imagens da biblioteca de mídia, marcadas como antes ou depois.
	 *
	 * @param mixed $value Valor recebido.
	 * @return array<int, array{id: int, type: string}>
	 */
	public static function sanitize_gallery( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$clean = array();
		$seen  = array();

		foreach ( $value as $item ) {
			$id = is_array( $item ) ? absint( $item['id'] ?? 0 ) : 0;

			if ( ! $id || isset( $seen[ $id ] ) || ! wp_attachment_is_image( $id ) ) {
				continue;
			}

			$type        = self::sanitize_choice( $item['type'] ?? '', Options::photo_types() );
			$seen[ $id ] = true;
			$clean[]     = array(
				'id'   => $id,
				'type' => '' === $type ? 'after' : $type,
			);
		}

		return $clean;
	}

	/**
	 * Vacinas: lista de nome + data de aplicação.
	 *
	 * @param mixed $value Valor recebido.
	 * @return array<int, array{name: string, date: string}>
	 */
	public static function sanitize_vaccines( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$clean = array();

		foreach ( $value as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$name = is_scalar( $item['name'] ?? null ) ? sanitize_text_field( (string) $item['name'] ) : '';
			if ( '' === $name ) {
				continue;
			}

			$clean[] = array(
				'name' => $name,
				'date' => self::sanitize_date( $item['date'] ?? '' ),
			);
		}

		return $clean;
	}
}
