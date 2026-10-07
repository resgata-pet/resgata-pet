<?php
/**
 * Dados da protetora.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Dados da protetora cadastrados em /painel/configuracoes (option `rwp_organization`):
 * identidade, contato, redes sociais, localização, adoção, privacidade e Pix.
 *
 * Os valores devolvidos NÃO estão escapados: escape na saída.
 */
final class Organization {

	public const OPTION = 'rwp_organization';

	/**
	 * Campos que nunca aparecem no site (uso interno do plugin).
	 */
	public const PRIVATE_FIELDS = array( 'adoption_email' );

	/**
	 * Estados (UF => nome).
	 *
	 * @return array<string, string>
	 */
	public static function states(): array {
		return array(
			'AC' => 'Acre',
			'AL' => 'Alagoas',
			'AP' => 'Amapá',
			'AM' => 'Amazonas',
			'BA' => 'Bahia',
			'CE' => 'Ceará',
			'DF' => 'Distrito Federal',
			'ES' => 'Espírito Santo',
			'GO' => 'Goiás',
			'MA' => 'Maranhão',
			'MT' => 'Mato Grosso',
			'MS' => 'Mato Grosso do Sul',
			'MG' => 'Minas Gerais',
			'PA' => 'Pará',
			'PB' => 'Paraíba',
			'PR' => 'Paraná',
			'PE' => 'Pernambuco',
			'PI' => 'Piauí',
			'RJ' => 'Rio de Janeiro',
			'RN' => 'Rio Grande do Norte',
			'RS' => 'Rio Grande do Sul',
			'RO' => 'Rondônia',
			'RR' => 'Roraima',
			'SC' => 'Santa Catarina',
			'SP' => 'São Paulo',
			'SE' => 'Sergipe',
			'TO' => 'Tocantins',
		);
	}

	/**
	 * Tipos de chave Pix.
	 *
	 * @return array<string, string>
	 */
	public static function pix_key_types(): array {
		return array(
			'cpf'    => __( 'CPF', 'resgata-wp' ),
			'cnpj'   => __( 'CNPJ', 'resgata-wp' ),
			'email'  => __( 'E-mail', 'resgata-wp' ),
			'phone'  => __( 'Celular', 'resgata-wp' ),
			'random' => __( 'Chave aleatória', 'resgata-wp' ),
		);
	}

	/**
	 * Redes sociais aceitas: campo => [rótulo, endereço base para montar o link a partir do @usuario].
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function networks(): array {
		return array(
			'instagram' => array( 'Instagram', 'https://www.instagram.com/' ),
			'facebook'  => array( 'Facebook', 'https://www.facebook.com/' ),
			'tiktok'    => array( 'TikTok', 'https://www.tiktok.com/@' ),
			'youtube'   => array( 'YouTube', 'https://www.youtube.com/@' ),
		);
	}

	/**
	 * Campos e tamanho máximo de cada um. Todos são texto.
	 *
	 * @return array<string, int>
	 */
	public static function fields(): array {
		return array(
			'name'             => 120,
			'tagline'          => 200,
			'cnpj'             => 18,
			'whatsapp'         => 20,
			'whatsapp_message' => 300,
			'phone'            => 20,
			'email'            => 120,
			'hours'            => 200,
			'instagram'        => 200,
			'facebook'         => 200,
			'tiktok'           => 200,
			'youtube'          => 200,
			'city'             => 100,
			'state'            => 2,
			'neighborhood'     => 100,
			'adoption_email'   => 120,
			'adoption_fee'     => 200,
			'adoption_rules'   => 5000,
			'privacy_contact'  => 120,
			'privacy_text'     => 10000,
			'pix_key_type'     => 10,
			'pix_key'          => 120,
			'pix_holder'       => 120,
		);
	}

	/**
	 * Dados salvos, com todos os campos presentes.
	 *
	 * @return array<string, string>
	 */
	public static function get(): array {
		$saved = get_option( self::OPTION, array() );
		$saved = is_array( $saved ) ? $saved : array();
		$data  = array();

		foreach ( array_keys( self::fields() ) as $field ) {
			$data[ $field ] = isset( $saved[ $field ] ) && is_scalar( $saved[ $field ] ) ? (string) $saved[ $field ] : '';
		}

		return $data;
	}

	/**
	 * Grava os campos informados (os demais ficam como estão).
	 *
	 * @param array<string, mixed> $changes Campos a alterar, sem barras de escape.
	 * @return array<string, string> Dados completos depois de salvar.
	 */
	public static function update( array $changes ): array {
		$data = self::sanitize( array_merge( self::get(), array_intersect_key( $changes, self::fields() ) ) );

		update_option( self::OPTION, $data, false );

		return $data;
	}

	/**
	 * Valida todos os campos.
	 *
	 * @param array<string, mixed> $input Dados recebidos.
	 * @return array<string, string>
	 */
	public static function sanitize( array $input ): array {
		$clean = array();

		foreach ( self::fields() as $field => $max ) {
			$value = isset( $input[ $field ] ) && is_scalar( $input[ $field ] ) ? (string) $input[ $field ] : '';

			switch ( $field ) {
				case 'email':
				case 'adoption_email':
					$value = sanitize_email( $value );
					break;

				case 'whatsapp':
				case 'phone':
					$value = (string) preg_replace( '/\D+/', '', $value );
					break;

				case 'state':
					$value = strtoupper( sanitize_key( $value ) );
					$value = isset( self::states()[ $value ] ) ? $value : '';
					break;

				case 'pix_key_type':
					$value = sanitize_key( $value );
					$value = isset( self::pix_key_types()[ $value ] ) ? $value : '';
					break;

				case 'instagram':
				case 'facebook':
				case 'tiktok':
				case 'youtube':
					$value = self::sanitize_network( $field, $value );
					break;

				case 'adoption_rules':
				case 'privacy_text':
					$value = sanitize_textarea_field( $value );
					break;

				default:
					$value = sanitize_text_field( $value );
			}

			$clean[ $field ] = mb_substr( $value, 0, $max );
		}

		return $clean;
	}

	/**
	 * Aceita o endereço completo ou só o @usuario e devolve sempre um link http(s).
	 *
	 * @param string $network Rede (chave de `networks()`).
	 * @param string $value   Valor informado.
	 */
	private static function sanitize_network( string $network, string $value ): string {
		$value = trim( sanitize_text_field( $value ) );

		if ( '' === $value ) {
			return '';
		}

		if ( ! preg_match( '#^https?://#i', $value ) ) {
			// Outros protocolos (javascript:, data:...) não são aceitos.
			if ( str_contains( $value, ':' ) ) {
				return '';
			}

			if ( str_contains( $value, '.' ) && str_contains( $value, '/' ) ) {
				$value = 'https://' . $value;
			} else {
				$handle = (string) preg_replace( '/[^A-Za-z0-9._-]/', '', $value );
				$value  = '' === $handle ? '' : self::networks()[ $network ][1] . $handle;
			}
		}

		return esc_url_raw( $value, array( 'http', 'https' ) );
	}

	/**
	 * Telefone formatado para exibição: (11) 91234-5678.
	 *
	 * @param string $digits Somente dígitos.
	 */
	public static function format_phone( string $digits ): string {
		$digits = (string) preg_replace( '/\D+/', '', $digits );

		if ( 13 === strlen( $digits ) || 12 === strlen( $digits ) ) {
			$digits = substr( $digits, 2 ); // Tira o 55 do país.
		}

		if ( 11 === strlen( $digits ) ) {
			return sprintf( '(%s) %s-%s', substr( $digits, 0, 2 ), substr( $digits, 2, 5 ), substr( $digits, 7 ) );
		}

		if ( 10 === strlen( $digits ) ) {
			return sprintf( '(%s) %s-%s', substr( $digits, 0, 2 ), substr( $digits, 2, 4 ), substr( $digits, 6 ) );
		}

		return $digits;
	}

	/**
	 * Link para conversar no WhatsApp, já com a mensagem inicial.
	 *
	 * @param string $animal_name Nome do animal, para trocar `{animal}` na mensagem (opcional).
	 * @return string Link, ou string vazia se não houver número cadastrado.
	 */
	public static function whatsapp_url( string $animal_name = '' ): string {
		$data   = self::get();
		$digits = $data['whatsapp'];

		if ( '' === $digits ) {
			return '';
		}

		// Números sem código do país recebem o do Brasil.
		if ( strlen( $digits ) <= 11 ) {
			$digits = '55' . $digits;
		}

		$message = trim( str_replace( '{animal}', $animal_name, $data['whatsapp_message'] ) );
		$url     = 'https://wa.me/' . $digits;

		return '' === $message ? $url : $url . '?text=' . rawurlencode( $message );
	}

	/**
	 * Dados do Pix para doação, ou null se a chave não estiver cadastrada.
	 *
	 * `code_key`, `code_name` e `code_city` são os valores no formato exigido
	 * pelo QR Code do Pix (chave sem pontuação, celular com +55, nome e cidade
	 * sem acentos).
	 *
	 * @return array{key: string, type: string, type_label: string, holder: string, code_key: string, code_name: string, code_city: string}|null
	 */
	public static function pix(): ?array {
		$data = self::get();
		$key  = trim( $data['pix_key'] );
		$type = $data['pix_key_type'];

		if ( '' === $key ) {
			return null;
		}

		switch ( $type ) {
			case 'cpf':
				$code_key = (string) preg_replace( '/\D+/', '', $key );
				break;

			case 'cnpj':
				$code_key = strtoupper( (string) preg_replace( '/[^A-Za-z0-9]+/', '', $key ) );
				break;

			case 'phone':
				$digits   = (string) preg_replace( '/\D+/', '', $key );
				$code_key = '+' . ( strlen( $digits ) <= 11 ? '55' . $digits : $digits );
				break;

			case 'email':
			case 'random':
				$code_key = strtolower( $key );
				break;

			default:
				// Tipo não informado: usa a chave como foi digitada, sem espaços.
				$code_key = (string) preg_replace( '/\s+/', '', $key );
		}

		$holder = '' !== $data['pix_holder'] ? $data['pix_holder'] : $data['name'];
		$holder = '' !== $holder ? $holder : get_bloginfo( 'name' );

		return array(
			'key'        => $key,
			'type'       => $type,
			'type_label' => self::pix_key_types()[ $type ] ?? '',
			'holder'     => $holder,
			'code_key'   => $code_key,
			'code_name'  => self::pix_text( $holder, 25, 'DOACAO' ),
			'code_city'  => self::pix_text( $data['city'], 15, 'BRASIL' ),
		);
	}

	/**
	 * Texto no formato do QR Code do Pix: maiúsculas, sem acentos nem símbolos, com tamanho máximo.
	 *
	 * @param string $text     Texto original.
	 * @param int    $max      Tamanho máximo.
	 * @param string $fallback Valor usado se o texto ficar vazio.
	 */
	private static function pix_text( string $text, int $max, string $fallback ): string {
		$text = strtoupper( remove_accents( $text ) );
		$text = trim( (string) preg_replace( '/\s+/', ' ', (string) preg_replace( '/[^A-Z0-9 ]+/', ' ', $text ) ) );
		$text = trim( substr( $text, 0, $max ) );

		return '' !== $text ? $text : $fallback;
	}

	/**
	 * Cidade/UF (e bairro), ou string vazia.
	 */
	public static function location(): string {
		$data  = self::get();
		$place = implode( '/', array_filter( array( $data['city'], $data['state'] ) ) );

		return implode( ' – ', array_filter( array( $data['neighborhood'], $place ) ) );
	}
}
