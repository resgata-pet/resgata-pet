<?php
/**
 * Gravação dos dados de um animal.
 *
 * @package ResgataPet
 */

namespace ResgataPet\Modules\Animals;

defined( 'ABSPATH' ) || exit;

/**
 * Grava a ficha de um animal. É o único caminho de escrita dos metadados,
 * usado pela API do painel.
 *
 * Quem chama é responsável por conferir nonce/permissão antes. Os valores são
 * validados pelas regras de `Meta` (sanitize_callback de cada metadado).
 */
final class AnimalWriter {

	/**
	 * Campos simples: chave do array de dados => chave do metadado.
	 */
	private const FIELDS = array(
		'status'        => Meta::STATUS,
		'sex'           => Meta::SEX,
		'size'          => Meta::SIZE,
		'color'         => Meta::COLOR,
		'rescue_date'   => Meta::RESCUE_DATE,
		'dewormed_date' => Meta::DEWORMED_DATE,
		'neutered'      => Meta::NEUTERED,
		'fiv'           => Meta::FIV,
		'felv'          => Meta::FELV,
		'tagline'       => Meta::TAGLINE,
		'weight'        => Meta::WEIGHT,
		'vet_checked'   => Meta::VET_CHECKED,
		'vaccinated'    => Meta::VACCINATED,
	);

	/**
	 * Grava os dados informados. Chaves ausentes não são alteradas.
	 *
	 * @param int                  $post_id ID do animal.
	 * @param array<string, mixed> $data    Dados SEM barras de escape (já passados por wp_unslash, se vieram de $_POST).
	 */
	public static function save( int $post_id, array $data ): void {
		foreach ( self::FIELDS as $field => $meta_key ) {
			if ( array_key_exists( $field, $data ) ) {
				$value = is_scalar( $data[ $field ] ) ? sanitize_text_field( (string) $data[ $field ] ) : '';
				update_post_meta( $post_id, $meta_key, wp_slash( $value ) );
			}
		}

		if ( array_key_exists( 'intro', $data ) ) {
			// À parte dos campos simples porque o resumo pode ter quebras de linha.
			update_post_meta( $post_id, Meta::INTRO, wp_slash( Meta::sanitize_intro( $data['intro'] ) ) );
		}

		if ( array_key_exists( 'age_years', $data ) || array_key_exists( 'age_months', $data ) ) {
			self::save_age( $post_id, $data['age_years'] ?? '', $data['age_months'] ?? '' );
		}

		if ( array_key_exists( 'species', $data ) ) {
			$slug = is_scalar( $data['species'] ) ? sanitize_title( (string) $data['species'] ) : '';
			$term = '' !== $slug ? get_term_by( 'slug', $slug, Taxonomies::SPECIES ) : false;
			wp_set_object_terms( $post_id, $term ? array( (int) $term->term_id ) : array(), Taxonomies::SPECIES );
		}

		if ( array_key_exists( 'temperaments', $data ) ) {
			$names = array();
			foreach ( (array) $data['temperaments'] as $name ) {
				$name = is_scalar( $name ) ? sanitize_text_field( (string) $name ) : '';
				if ( '' !== $name ) {
					$names[] = $name;
				}
			}
			wp_set_object_terms( $post_id, array_values( array_unique( $names ) ), Taxonomies::TEMPERAMENT );
		}

		if ( array_key_exists( 'vaccines', $data ) ) {
			update_post_meta( $post_id, Meta::VACCINES, wp_slash( Meta::sanitize_vaccines( $data['vaccines'] ) ) );
		}

		if ( array_key_exists( 'gallery', $data ) ) {
			update_post_meta( $post_id, Meta::GALLERY, Meta::sanitize_gallery( $data['gallery'] ) );
		}

		if ( array_key_exists( 'featured_media', $data ) ) {
			$image_id = absint( $data['featured_media'] );
			if ( $image_id && wp_attachment_is_image( $image_id ) ) {
				set_post_thumbnail( $post_id, $image_id );
			} elseif ( ! $image_id ) {
				delete_post_thumbnail( $post_id );
			}
		}
	}

	/**
	 * Converte a idade informada em data de nascimento estimada.
	 *
	 * A data só muda quando a idade informada é diferente da calculada hoje,
	 * para não "rejuvenescer" o animal a cada vez que o cadastro é salvo.
	 *
	 * @param int   $post_id ID do animal.
	 * @param mixed $years   Anos informados (vazio ou null = não informado).
	 * @param mixed $months  Meses informados (vazio ou null = não informado).
	 */
	private static function save_age( int $post_id, $years, $months ): void {
		$years  = is_scalar( $years ) ? trim( (string) $years ) : '';
		$months = is_scalar( $months ) ? trim( (string) $months ) : '';

		if ( '' === $years && '' === $months ) {
			delete_post_meta( $post_id, Meta::BIRTH_DATE );
			return;
		}

		$years  = min( 40, absint( $years ) );
		$months = min( 11, absint( $months ) );

		$current = ( new Animal( $post_id ) )->age();
		if ( $current && $current['years'] === $years && $current['months'] === $months ) {
			return;
		}

		update_post_meta( $post_id, Meta::BIRTH_DATE, Animal::birth_date_from_age( $years, $months ) );
	}
}
