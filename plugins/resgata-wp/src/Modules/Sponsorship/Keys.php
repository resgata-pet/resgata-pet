<?php
/**
 * Chaves da Stripe.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Sponsorship;

use ResgataWP\Core\Encryption;

defined( 'ABSPATH' ) || exit;

/**
 * Lê e guarda as chaves da Stripe.
 *
 * Cada chave vem da constante do `wp-config.php` (`RESGATA_STRIPE_SECRET_KEY`,
 * `RESGATA_STRIPE_WEBHOOK_SECRET`) ou, sem ela, do que o administrador salvou no
 * painel. O que vem do painel é gravado cifrado (`Core\Encryption`), numa option
 * própria que não é carregada junto com as demais. As chaves nunca são
 * mostradas em tela: o painel só informa se cada uma está definida.
 */
final class Keys {

	public const OPTION = 'resgata_stripe_keys';

	public const SECRET_KEY     = 'secret_key';
	public const WEBHOOK_SECRET = 'webhook_secret';

	/**
	 * Constante do `wp-config.php` de cada chave.
	 */
	private const CONSTANTS = array(
		self::SECRET_KEY     => 'RESGATA_STRIPE_SECRET_KEY',
		self::WEBHOOK_SECRET => 'RESGATA_STRIPE_WEBHOOK_SECRET',
	);

	/**
	 * Chaves do painel já decifradas nesta requisição.
	 *
	 * @var array<string, string>|null
	 */
	private static ?array $stored = null;

	/**
	 * Chave da API (de preferência restrita, `rk_...`), ou string vazia.
	 */
	public static function secret_key(): string {
		return self::get( self::SECRET_KEY );
	}

	/**
	 * Segredo de assinatura do webhook (`whsec_...`), ou string vazia.
	 */
	public static function webhook_secret(): string {
		return self::get( self::WEBHOOK_SECRET );
	}

	/**
	 * Ambiente da chave da API: `test`, `live` ou string vazia se não der para saber.
	 */
	public static function mode(): string {
		if ( preg_match( '/^[rs]k_(test|live)_/', self::secret_key(), $matches ) ) {
			return $matches[1];
		}

		return '';
	}

	/**
	 * De onde vem a chave: `constant` (wp-config.php), `panel` (salva no painel) ou string vazia.
	 *
	 * @param string $field `secret_key` ou `webhook_secret`.
	 */
	public static function source( string $field ): string {
		if ( '' !== self::constant( self::CONSTANTS[ $field ] ?? '' ) ) {
			return 'constant';
		}

		return '' !== self::stored( $field ) ? 'panel' : '';
	}

	/**
	 * Começo e fim da chave da API, para a pessoa reconhecer qual está em uso (ex.: `rk_live_…Ab12`).
	 */
	public static function hint(): string {
		$key = self::secret_key();

		if ( ! preg_match( '/^[rs]k_(?:test|live)_/', $key, $matches ) || strlen( $key ) < 20 ) {
			return '';
		}

		return $matches[0] . '…' . substr( $key, -4 );
	}

	/**
	 * Chave salva no painel, decifrada (ignora o `wp-config.php`).
	 *
	 * @param string $field `secret_key` ou `webhook_secret`.
	 */
	public static function stored( string $field ): string {
		if ( null === self::$stored ) {
			self::$stored = array();

			foreach ( self::raw() as $name => $encrypted ) {
				self::$stored[ $name ] = (string) Encryption::decrypt( $encrypted );
			}
		}

		return self::$stored[ $field ] ?? '';
	}

	/**
	 * Chaves que estão gravadas mas não puderam ser lidas (as chaves de segurança do site mudaram).
	 *
	 * @return string[]
	 */
	public static function unreadable(): array {
		return array_values( array_filter( array_keys( self::raw() ), static fn( string $field ): bool => '' === self::stored( $field ) ) );
	}

	/**
	 * Grava uma chave cifrada; valor vazio apaga a que estava salva.
	 *
	 * Quem chama confere a permissão e o formato do valor.
	 *
	 * @param string $field `secret_key` ou `webhook_secret`.
	 * @param string $value Chave em texto puro.
	 */
	public static function store( string $field, string $value ): bool {
		if ( ! isset( self::CONSTANTS[ $field ] ) ) {
			return false;
		}

		$raw = self::raw();

		if ( '' === $value ) {
			unset( $raw[ $field ] );
		} else {
			$encrypted = Encryption::encrypt( $value );

			if ( '' === $encrypted ) {
				return false;
			}

			$raw[ $field ] = $encrypted;
		}

		self::$stored = null;

		if ( array() === $raw ) {
			delete_option( self::OPTION );
		} else {
			// Sem carregamento automático: a option só é lida quando o apadrinhamento precisa dela.
			update_option( self::OPTION, $raw, false );
		}

		return true;
	}

	/**
	 * Chave em uso: a do `wp-config.php` tem prioridade sobre a do painel.
	 *
	 * @param string $field `secret_key` ou `webhook_secret`.
	 */
	private static function get( string $field ): string {
		$constant = self::constant( self::CONSTANTS[ $field ] );

		return '' !== $constant ? $constant : self::stored( $field );
	}

	/**
	 * Conteúdo da option, ainda cifrado.
	 *
	 * @return array<string, string>
	 */
	private static function raw(): array {
		$raw = get_option( self::OPTION, array() );
		$raw = is_array( $raw ) ? array_intersect_key( $raw, self::CONSTANTS ) : array();

		return array_filter( $raw, static fn( $value ): bool => is_string( $value ) && '' !== $value );
	}

	/**
	 * Valor de uma constante de texto, ou string vazia.
	 *
	 * @param string $name Nome da constante.
	 */
	private static function constant( string $name ): string {
		$value = '' !== $name && defined( $name ) ? constant( $name ) : '';

		return is_string( $value ) ? trim( $value ) : '';
	}
}
