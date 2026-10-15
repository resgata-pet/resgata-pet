<?php
/**
 * Leitura dos dados de um animal.
 *
 * @package ResgataPet
 */

namespace ResgataPet\Modules\Animals;

defined( 'ABSPATH' ) || exit;

/**
 * Dá acesso aos dados de um animal, já validados e prontos para exibir.
 *
 * Os valores devolvidos NÃO estão escapados: escape na saída.
 */
final class Animal {

	/**
	 * Construtor.
	 *
	 * @param int $id ID do post do animal.
	 */
	public function __construct( private int $id ) {}

	/**
	 * ID do animal.
	 */
	public function id(): int {
		return $this->id;
	}

	/**
	 * Lê um metadado de texto.
	 *
	 * @param string $key Chave do metadado.
	 */
	private function meta( string $key ): string {
		$value = get_post_meta( $this->id, $key, true );

		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * Rótulo de uma opção, ou vazio.
	 *
	 * @param string                $key     Chave do metadado.
	 * @param array<string, string> $choices Opções (chave => rótulo).
	 */
	private function label( string $key, array $choices ): string {
		return $choices[ $this->meta( $key ) ] ?? '';
	}

	/**
	 * Situação (chave).
	 */
	public function status(): string {
		$status = $this->meta( Meta::STATUS );

		return isset( Options::statuses()[ $status ] ) ? $status : Options::STATUS_DEFAULT;
	}

	/**
	 * Situação (rótulo).
	 */
	public function status_label(): string {
		return Options::statuses()[ $this->status() ];
	}

	/**
	 * Sexo (chave).
	 */
	public function sex(): string {
		return $this->meta( Meta::SEX );
	}

	/**
	 * Sexo (rótulo).
	 */
	public function sex_label(): string {
		return $this->label( Meta::SEX, Options::sexes() );
	}

	/**
	 * Porte (chave).
	 */
	public function size(): string {
		return $this->meta( Meta::SIZE );
	}

	/**
	 * Porte (rótulo).
	 */
	public function size_label(): string {
		return $this->label( Meta::SIZE, Options::sizes() );
	}

	/**
	 * Cor / pelagem.
	 */
	public function color(): string {
		return $this->meta( Meta::COLOR );
	}

	/**
	 * Frase de apresentação (uma linha).
	 */
	public function tagline(): string {
		return $this->meta( Meta::TAGLINE );
	}

	/**
	 * Resumo curto, mostrado no topo da página do animal.
	 */
	public function intro(): string {
		return $this->meta( Meta::INTRO );
	}

	/**
	 * Peso em quilos (ex.: "18.5") ou vazio.
	 */
	public function weight(): string {
		return Meta::sanitize_weight( $this->meta( Meta::WEIGHT ) );
	}

	/**
	 * Peso em texto. Ex.: "18,5 kg".
	 */
	public function weight_label(): string {
		$weight = $this->weight();
		if ( '' === $weight ) {
			return '';
		}

		$decimals = floor( (float) $weight ) === (float) $weight ? 0 : 1;

		/* translators: %s: peso do animal em quilos. */
		return sprintf( __( '%s kg', 'resgata-pet' ), number_format_i18n( (float) $weight, $decimals ) );
	}

	/**
	 * Primeira espécie do animal.
	 */
	public function species(): ?\WP_Term {
		$terms = get_the_terms( $this->id, Taxonomies::SPECIES );

		return is_array( $terms ) && $terms ? reset( $terms ) : null;
	}

	/**
	 * Nome da espécie.
	 */
	public function species_label(): string {
		$term = $this->species();

		return $term ? $term->name : '';
	}

	/**
	 * Nomes dos temperamentos.
	 *
	 * @return string[]
	 */
	public function temperaments(): array {
		$terms = get_the_terms( $this->id, Taxonomies::TEMPERAMENT );

		return is_array( $terms ) ? wp_list_pluck( $terms, 'name' ) : array();
	}

	/**
	 * Data de nascimento estimada (AAAA-MM-DD) ou vazio.
	 */
	public function birth_date(): string {
		return Meta::sanitize_date( $this->meta( Meta::BIRTH_DATE ) );
	}

	/**
	 * Idade em anos e meses completos.
	 *
	 * @return array{years: int, months: int}|null
	 */
	public function age(): ?array {
		return self::age_from_birth_date( $this->birth_date() );
	}

	/**
	 * Calcula a idade a partir da data de nascimento estimada.
	 *
	 * @param string $birth_date Data no formato AAAA-MM-DD.
	 * @return array{years: int, months: int}|null
	 */
	public static function age_from_birth_date( string $birth_date ): ?array {
		if ( '' === $birth_date ) {
			return null;
		}

		$birth = date_create_immutable( $birth_date, wp_timezone() );
		$today = current_datetime()->setTime( 0, 0 );

		if ( ! $birth || $birth > $today ) {
			return null;
		}

		$diff = $birth->diff( $today );

		return array(
			'years'  => (int) $diff->y,
			'months' => (int) $diff->m,
		);
	}

	/**
	 * Data de nascimento estimada para uma idade em anos e meses.
	 *
	 * @param int $years  Anos.
	 * @param int $months Meses.
	 */
	public static function birth_date_from_age( int $years, int $months ): string {
		$total = max( 0, $years ) * 12 + max( 0, $months );

		return current_datetime()->setTime( 0, 0 )->modify( "-{$total} months" )->format( 'Y-m-d' );
	}

	/**
	 * Idade estimada em texto. Ex.: "2 anos", "5 meses".
	 */
	public function age_label(): string {
		$age = $this->age();
		if ( null === $age ) {
			return '';
		}

		if ( $age['years'] >= 1 ) {
			/* translators: %d: idade em anos. */
			return sprintf( _n( '%d ano', '%d anos', $age['years'], 'resgata-pet' ), $age['years'] );
		}

		if ( $age['months'] >= 1 ) {
			/* translators: %d: idade em meses. */
			return sprintf( _n( '%d mês', '%d meses', $age['months'], 'resgata-pet' ), $age['months'] );
		}

		return __( 'Menos de 1 mês', 'resgata-pet' );
	}

	/**
	 * Formata uma data AAAA-MM-DD no formato de data do site.
	 *
	 * @param string $date Data no formato AAAA-MM-DD.
	 */
	public static function format_date( string $date ): string {
		$date = Meta::sanitize_date( $date );
		if ( '' === $date ) {
			return '';
		}

		$object = date_create_immutable( $date, wp_timezone() );

		return $object ? wp_date( get_option( 'date_format' ), $object->getTimestamp() ) : '';
	}

	/**
	 * Data do resgate (AAAA-MM-DD) ou vazio.
	 */
	public function rescue_date(): string {
		return Meta::sanitize_date( $this->meta( Meta::RESCUE_DATE ) );
	}

	/**
	 * Data do último vermífugo (AAAA-MM-DD) ou vazio.
	 */
	public function dewormed_date(): string {
		return Meta::sanitize_date( $this->meta( Meta::DEWORMED_DATE ) );
	}

	/**
	 * Castração: `yes`, `no` ou vazio (não informado).
	 */
	public function neutered(): string {
		return Meta::sanitize_choice( $this->meta( Meta::NEUTERED ), Options::yes_no() );
	}

	/**
	 * Resultado do teste de FIV: `negative`, `positive` ou vazio (não testado).
	 */
	public function fiv(): string {
		return Meta::sanitize_choice( $this->meta( Meta::FIV ), Options::test_results() );
	}

	/**
	 * Resultado do teste de FeLV: `negative`, `positive` ou vazio (não testado).
	 */
	public function felv(): string {
		return Meta::sanitize_choice( $this->meta( Meta::FELV ), Options::test_results() );
	}

	/**
	 * Passou por consulta veterinária: `yes`, `no` ou vazio (não informado).
	 */
	public function vet_checked(): string {
		return Meta::sanitize_choice( $this->meta( Meta::VET_CHECKED ), Options::yes_no() );
	}

	/**
	 * Vacinas em dia: `yes`, `no` ou vazio (não informado).
	 */
	public function vaccinated(): string {
		return Meta::sanitize_choice( $this->meta( Meta::VACCINATED ), Options::yes_no() );
	}

	/**
	 * Vacinas aplicadas.
	 *
	 * @return array<int, array{name: string, date: string}>
	 */
	public function vaccines(): array {
		return Meta::sanitize_vaccines( get_post_meta( $this->id, Meta::VACCINES, true ) );
	}

	/**
	 * Fotos da galeria.
	 *
	 * @param string $type Filtra por tipo (`before` ou `after`). Vazio devolve todas.
	 * @return array<int, array{id: int, type: string}>
	 */
	public function gallery( string $type = '' ): array {
		$gallery = Meta::sanitize_gallery( get_post_meta( $this->id, Meta::GALLERY, true ) );

		if ( '' === $type ) {
			return $gallery;
		}

		return array_values( array_filter( $gallery, static fn( $photo ) => $photo['type'] === $type ) );
	}

	/**
	 * Resumo curto para os cartões. Ex.: "Gato", "2 anos", "Porte médio".
	 *
	 * @return string[]
	 */
	public function card_summary(): array {
		$size = $this->size_label();

		if ( '' !== $size ) {
			/* translators: %s: porte do animal em minúsculas. Ex.: "Porte médio". */
			$size = sprintf( __( 'Porte %s', 'resgata-pet' ), mb_strtolower( $size ) );
		}

		return array_values( array_filter( array( $this->species_label(), $this->age_label(), $size ) ) );
	}
}
