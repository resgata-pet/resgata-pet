<?php
/**
 * Limite de envios por endereço de rede.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Sponsorship;

defined( 'ABSPATH' ) || exit;

/**
 * Conta os envios de um formulário público por hora. O endereço de rede não é
 * gravado: só um resumo irreversível, como no formulário de adoção.
 */
final class RateLimit {

	/**
	 * Registra uma tentativa e informa se ela ainda está dentro do limite.
	 *
	 * @param string $action Nome curto do formulário.
	 * @param int    $limit  Tentativas aceitas por hora.
	 */
	public static function allow( string $action, int $limit ): bool {
		$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key   = 'resgata_rl_' . $action . '_' . md5( wp_hash( $ip ) );
		$count = (int) get_transient( $key );

		if ( $count >= $limit ) {
			return false;
		}

		set_transient( $key, $count + 1, HOUR_IN_SECONDS );

		return true;
	}
}
