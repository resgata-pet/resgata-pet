<?php
/**
 * Mensagens dos pedidos de adoção.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Adoptions;

use ResgataWP\Modules\Panel\Router;
use ResgataWP\Modules\Settings\Organization;

defined( 'ABSPATH' ) || exit;

/**
 * Monta a mensagem do WhatsApp (enviada por quem quer adotar) e o e-mail de aviso à protetora.
 */
final class Messages {

	/**
	 * Link do WhatsApp da protetora já com o pedido escrito, para a pessoa só tocar em "enviar".
	 *
	 * @param int $request_id ID do pedido.
	 * @return string Link, ou string vazia se a protetora não cadastrou WhatsApp.
	 */
	public static function whatsapp_url( int $request_id ): string {
		$digits = Organization::get()['whatsapp'];

		if ( '' === $digits ) {
			return '';
		}

		if ( strlen( $digits ) <= 11 ) {
			$digits = '55' . $digits;
		}

		return 'https://wa.me/' . $digits . '?text=' . rawurlencode( self::whatsapp_text( $request_id ) );
	}

	/**
	 * Texto do pedido para o WhatsApp.
	 *
	 * @param int $request_id ID do pedido.
	 */
	public static function whatsapp_text( int $request_id ): string {
		$animal_id = (int) get_post_meta( $request_id, PostType::META_ANIMAL, true );
		$answers   = get_post_meta( $request_id, PostType::META_ANSWERS, true );
		$animal    = $animal_id ? get_the_title( $animal_id ) : '';

		$lines = array(
			/* translators: %s: nome do animal. */
			sprintf( __( 'Olá! Quero adotar: *%s*', 'resgata-wp' ), $animal ),
			/* translators: %d: número do pedido. */
			sprintf( __( 'Pedido nº %d (enviado pelo site)', 'resgata-wp' ), $request_id ),
			'',
		);

		foreach ( Questionnaire::readable( is_array( $answers ) ? $answers : array() ) as $row ) {
			// O número de quem envia o WhatsApp já aparece na conversa.
			if ( 'whatsapp' === $row['key'] ) {
				continue;
			}
			$lines[] = sprintf( '*%s:* %s', $row['label'], $row['value'] );
		}

		if ( $animal_id && 'publish' === get_post_status( $animal_id ) ) {
			$lines[] = '';
			$lines[] = (string) get_permalink( $animal_id );
		}

		return implode( "\n", $lines );
	}

	/**
	 * Avisa a protetora por e-mail, se houver um e-mail de adoção cadastrado.
	 *
	 * O e-mail leva só o nome e o animal: as respostas ficam no painel, atrás do login.
	 *
	 * @param int $request_id ID do pedido.
	 */
	public static function notify( int $request_id ): void {
		$to = Organization::get()['adoption_email'];

		if ( '' === $to || ! is_email( $to ) ) {
			return;
		}

		$animal_id = (int) get_post_meta( $request_id, PostType::META_ANIMAL, true );
		$animal    = $animal_id ? get_the_title( $animal_id ) : '';
		// Direto do post: get_the_title() acrescentaria "Privado:" ao nome.
		$name = (string) get_post_field( 'post_title', $request_id );

		$subject = sprintf(
			/* translators: 1: nome do animal, 2: nome de quem quer adotar. */
			__( 'Novo pedido de adoção: %1$s (%2$s)', 'resgata-wp' ),
			$animal,
			$name
		);

		$body = sprintf(
			/* translators: 1: nome de quem quer adotar, 2: nome do animal, 3: link do pedido no painel. */
			__( "%1\$s enviou um pedido de adoção para %2\$s.\n\nVeja as respostas e responda pelo painel:\n%3\$s", 'resgata-wp' ),
			$name,
			$animal,
			Router::url( 'adocoes/' . $request_id )
		);

		wp_mail( $to, wp_specialchars_decode( $subject, ENT_QUOTES ), wp_specialchars_decode( $body, ENT_QUOTES ) );
	}
}
