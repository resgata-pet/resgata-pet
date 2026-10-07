<?php
/**
 * Criptografia de dados guardados no banco.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Cifra e decifra textos antes de gravá-los no banco (ex.: chaves da Stripe).
 *
 * A chave de criptografia nunca fica no banco: vem das constantes
 * `RWP_ENCRYPTION_KEY` e `RWP_ENCRYPTION_SALT` do `wp-config.php` ou, sem elas,
 * de `LOGGED_IN_KEY` e `LOGGED_IN_SALT`. Quem copia só o banco (backup, falha
 * de outro plugin) não consegue ler o valor. Se essas constantes mudarem, o que
 * já foi gravado deixa de ser legível e precisa ser informado de novo.
 *
 * Sem a extensão OpenSSL ou sem as constantes, nada é gravado: não existe
 * gravação em texto puro.
 */
final class Encryption {

	private const METHOD = 'aes-256-ctr';

	/**
	 * Valor de exemplo do `wp-config-sample.php`, que não serve como chave.
	 */
	private const PLACEHOLDER = 'put your unique phrase here';

	/**
	 * Informa se o site consegue cifrar dados.
	 */
	public static function available(): bool {
		return extension_loaded( 'openssl' ) && '' !== self::secret( 'KEY' ) && '' !== self::secret( 'SALT' );
	}

	/**
	 * Cifra um texto.
	 *
	 * @param string $value Texto puro.
	 * @return string Texto cifrado (base64), ou string vazia se não foi possível cifrar.
	 */
	public static function encrypt( string $value ): string {
		if ( ! self::available() ) {
			return '';
		}

		$iv = random_bytes( (int) openssl_cipher_iv_length( self::METHOD ) );

		// O "sal" vai junto do texto e é conferido na leitura: é assim que se percebe a troca da chave.
		$encrypted = openssl_encrypt( $value . self::secret( 'SALT' ), self::METHOD, self::key(), OPENSSL_RAW_DATA, $iv );

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Formato de gravação do dado cifrado.
		return false === $encrypted ? '' : base64_encode( $iv . $encrypted );
	}

	/**
	 * Decifra um texto gravado por `encrypt()`.
	 *
	 * @param string $stored Texto cifrado (base64).
	 * @return string|null Texto puro, ou null se não deu para decifrar (dado inválido ou chave trocada).
	 */
	public static function decrypt( string $stored ): ?string {
		if ( '' === $stored || ! self::available() ) {
			return null;
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Formato de gravação do dado cifrado.
		$decoded   = base64_decode( $stored, true );
		$iv_length = (int) openssl_cipher_iv_length( self::METHOD );

		if ( false === $decoded || strlen( $decoded ) <= $iv_length ) {
			return null;
		}

		$value = openssl_decrypt( substr( $decoded, $iv_length ), self::METHOD, self::key(), OPENSSL_RAW_DATA, substr( $decoded, 0, $iv_length ) );
		$salt  = self::secret( 'SALT' );

		if ( false === $value || strlen( $value ) < strlen( $salt ) || ! hash_equals( $salt, substr( $value, -strlen( $salt ) ) ) ) {
			return null;
		}

		return substr( $value, 0, -strlen( $salt ) );
	}

	/**
	 * Chave de 32 bytes derivada da constante do `wp-config.php`.
	 */
	private static function key(): string {
		return hash( 'sha256', self::secret( 'KEY' ), true );
	}

	/**
	 * Lê `RWP_ENCRYPTION_<tipo>` ou, na falta dela, `LOGGED_IN_<tipo>`.
	 *
	 * @param string $type `KEY` ou `SALT`.
	 */
	private static function secret( string $type ): string {
		foreach ( array( 'RWP_ENCRYPTION_' . $type, 'LOGGED_IN_' . $type ) as $name ) {
			$value = defined( $name ) ? constant( $name ) : '';

			if ( is_string( $value ) && '' !== $value && self::PLACEHOLDER !== $value ) {
				return $value;
			}
		}

		return '';
	}
}
