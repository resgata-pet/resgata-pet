<?php
/**
 * E-mails do apadrinhamento.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Sponsorship;

use ResgataWP\Core\Links;
use ResgataWP\Modules\Panel\Router;
use ResgataWP\Modules\Settings\Organization;

defined( 'ABSPATH' ) || exit;

/**
 * Agradece ao novo padrinho e avisa a protetora do que muda nos apadrinhamentos.
 *
 * Os avisos à protetora vão para o e-mail cadastrado nas Configurações e levam
 * só o nome do padrinho e o plano: o resto fica no painel, atrás do login. Os
 * avisos de cobrança ao padrinho (recibo, cartão recusado) são da Stripe.
 *
 * Todos são e-mails de texto. O agradecimento só vai em HTML (com a versão em
 * texto junto) quando o site informa uma imagem pelo filtro `resgata_thanks_image_url`.
 */
final class Notifications {

	/**
	 * Endereço da Stripe onde a protetora responde a uma contestação.
	 */
	private const DISPUTES_URL = 'https://dashboard.stripe.com/disputes';

	/**
	 * Envia as boas-vindas na primeira vez em que o padrinho aparece ativo e com e-mail.
	 *
	 * Os eventos da Stripe chegam em qualquer ordem: este método é chamado depois de
	 * cada um deles e só envia quando os dados estão completos, uma única vez.
	 *
	 * @param string $customer_id ID do cliente na Stripe.
	 */
	public static function maybe_welcome( string $customer_id ): void {
		$sponsor = Sponsors::find_by_customer( $customer_id );

		if ( ! $sponsor || Sponsors::STATUS_ACTIVE !== $sponsor['status'] || '' === $sponsor['email'] || null !== $sponsor['welcomed_at'] ) {
			return;
		}

		if ( ! Sponsors::claim_welcome( (int) $sponsor['id'] ) ) {
			return;
		}

		self::welcome( $sponsor );

		self::to_team(
			/* translators: %s: nome do padrinho. */
			sprintf( __( 'Novo padrinho: %s', 'resgata-wp' ), self::sponsor_name( $sponsor ) ),
			sprintf(
				/* translators: 1: nome do padrinho, 2: plano. */
				__( '%1$s começou a apadrinhar no plano %2$s.', 'resgata-wp' ),
				self::sponsor_name( $sponsor ),
				self::plan( $sponsor )
			)
		);
	}

	/**
	 * Avisa a protetora de que o padrinho pediu o cancelamento e segue ativo até a data informada.
	 *
	 * @param array<string, mixed> $sponsor Padrinho.
	 * @param int                  $ends_at Momento em que o apadrinhamento termina.
	 */
	public static function cancel_scheduled( array $sponsor, int $ends_at ): void {
		self::to_team(
			/* translators: %s: nome do padrinho. */
			sprintf( __( 'Pedido de cancelamento: %s', 'resgata-wp' ), self::sponsor_name( $sponsor ) ),
			sprintf(
				/* translators: 1: nome do padrinho, 2: plano, 3: data. */
				__( '%1$s pediu para cancelar o apadrinhamento (plano %2$s). O apadrinhamento continua ativo até %3$s e não será mais cobrado depois disso.', 'resgata-wp' ),
				self::sponsor_name( $sponsor ),
				self::plan( $sponsor ),
				wp_date( (string) get_option( 'date_format' ), $ends_at )
			)
		);
	}

	/**
	 * Avisa a protetora de que um apadrinhamento terminou sem pedido anterior (cancelado na hora ou por falta de pagamento).
	 *
	 * @param array<string, mixed> $sponsor Padrinho.
	 */
	public static function canceled( array $sponsor ): void {
		self::to_team(
			/* translators: %s: nome do padrinho. */
			sprintf( __( 'Apadrinhamento encerrado: %s', 'resgata-wp' ), self::sponsor_name( $sponsor ) ),
			sprintf(
				/* translators: 1: nome do padrinho, 2: plano. */
				__( 'O apadrinhamento de %1$s (plano %2$s) foi encerrado e não será mais cobrado.', 'resgata-wp' ),
				self::sponsor_name( $sponsor ),
				self::plan( $sponsor )
			)
		);
	}

	/**
	 * Avisa a protetora de que a cobrança do mês foi recusada.
	 *
	 * @param array<string, mixed> $sponsor Padrinho.
	 */
	public static function payment_failed( array $sponsor ): void {
		self::to_team(
			/* translators: %s: nome do padrinho. */
			sprintf( __( 'Pagamento recusado: %s', 'resgata-wp' ), self::sponsor_name( $sponsor ) ),
			sprintf(
				/* translators: 1: nome do padrinho, 2: plano. */
				__( 'A cobrança do apadrinhamento de %1$s (plano %2$s) foi recusada pelo cartão. A Stripe tenta de novo nos próximos dias. Se quiser, avise o padrinho: ele troca o cartão pela área do padrinho, no site.', 'resgata-wp' ),
				self::sponsor_name( $sponsor ),
				self::plan( $sponsor )
			)
		);
	}

	/**
	 * Avisa a protetora de que um pagamento foi contestado no banco: a resposta tem prazo.
	 *
	 * @param array<string, mixed> $sponsor Padrinho.
	 * @param int                  $amount  Valor contestado, em centavos.
	 */
	public static function disputed( array $sponsor, int $amount ): void {
		self::to_team(
			/* translators: %s: nome do padrinho. */
			sprintf( __( 'Pagamento contestado: %s', 'resgata-wp' ), self::sponsor_name( $sponsor ) ),
			sprintf(
				/* translators: 1: nome do padrinho, 2: valor, 3: endereço das contestações na Stripe. */
				__( "%1\$s contestou no banco um pagamento de %2\$s do apadrinhamento.\n\nA resposta tem prazo. Entre na Stripe para ver o motivo e responder:\n%3\$s", 'resgata-wp' ),
				self::sponsor_name( $sponsor ),
				Plans::format_amount( $amount ),
				self::DISPUTES_URL
			)
		);
	}

	/**
	 * E-mail de agradecimento ao novo padrinho.
	 *
	 * @param array<string, mixed> $sponsor Padrinho.
	 */
	private static function welcome( array $sponsor ): void {
		$organization = self::organization_name();
		$area         = Links::sponsor_area();
		$name         = strtok( (string) $sponsor['name'], ' ' );

		$lines = array(
			/* translators: %s: primeiro nome do padrinho. */
			$name ? sprintf( __( 'Olá, %s!', 'resgata-wp' ), $name ) : __( 'Olá!', 'resgata-wp' ),
			'',
			/* translators: %s: plano. */
			sprintf( __( 'O seu apadrinhamento está ativo no plano %s. Muito obrigado por ajudar os nossos animais!', 'resgata-wp' ), self::plan( $sponsor ) ),
			'',
			__( 'A cobrança é feita todo mês no cartão que você informou.', 'resgata-wp' ),
		);

		if ( '' !== $area ) {
			$lines[] = '';
			$lines[] = __( 'Para ver quanto você já doou, trocar o cartão, mudar o valor ou cancelar, abra a área do padrinho e informe este e-mail:', 'resgata-wp' );
			$lines[] = $area;
		}

		$lines[] = '';
		$lines[] = $organization;

		$text = wp_specialchars_decode( implode( "\n", $lines ), ENT_QUOTES );
		/* translators: %s: nome da protetora. */
		$subject = wp_specialchars_decode( sprintf( __( 'Obrigado por apadrinhar – %s', 'resgata-wp' ), $organization ), ENT_QUOTES );
		$image   = ThanksPage::image_url();

		if ( '' === $image ) {
			wp_mail( (string) $sponsor['email'], $subject, $text );
			return;
		}

		$html = sprintf(
			'<div style="font-family:sans-serif;font-size:16px;line-height:1.5;color:#222;max-width:520px"><p><img src="%1$s" alt="" style="max-width:100%%;height:auto;border-radius:8px"></p><p>%2$s</p></div>',
			esc_url( $image ),
			nl2br( make_clickable( esc_html( $text ) ) )
		);

		// Versão em texto, para programas de e-mail que não mostram HTML.
		$alt_body = static function ( $mailer ) use ( $text ): void {
			$mailer->AltBody = $text; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Propriedade do PHPMailer.
		};

		add_action( 'phpmailer_init', $alt_body );
		wp_mail( (string) $sponsor['email'], $subject, $html, array( 'Content-Type: text/html; charset=UTF-8' ) );
		remove_action( 'phpmailer_init', $alt_body );
	}

	/**
	 * Envia um aviso à protetora, se houver um e-mail de apadrinhamento cadastrado.
	 *
	 * @param string $subject Assunto.
	 * @param string $body    Texto do aviso.
	 */
	private static function to_team( string $subject, string $body ): void {
		$to = Organization::get()['sponsorship_email'];

		if ( '' === $to || ! is_email( $to ) ) {
			return;
		}

		/* translators: %s: endereço da tela de padrinhos no painel. */
		$body .= "\n\n" . sprintf( __( "Veja os padrinhos no painel:\n%s", 'resgata-wp' ), Router::url( 'apadrinhamento' ) );

		self::send( $to, $subject, $body );
	}

	/**
	 * Envia um e-mail de texto.
	 *
	 * @param string $to      Destinatário.
	 * @param string $subject Assunto.
	 * @param string $body    Texto.
	 */
	private static function send( string $to, string $subject, string $body ): void {
		wp_mail( $to, wp_specialchars_decode( $subject, ENT_QUOTES ), wp_specialchars_decode( $body, ENT_QUOTES ) );
	}

	/**
	 * Nome do padrinho para os avisos.
	 *
	 * @param array<string, mixed> $sponsor Padrinho.
	 */
	private static function sponsor_name( array $sponsor ): string {
		return '' !== $sponsor['name'] ? (string) $sponsor['name'] : __( 'Um padrinho', 'resgata-wp' );
	}

	/**
	 * Plano do padrinho com o valor: "Amigo fiel (R$ 30 por mês)".
	 *
	 * @param array<string, mixed> $sponsor Padrinho.
	 */
	public static function plan( array $sponsor ): string {
		$plan = Plans::find( (string) $sponsor['price_id'] );

		if ( ! $plan ) {
			return __( 'mensal', 'resgata-wp' );
		}

		/* translators: %s: valor, ex.: R$ 15. */
		$amount = sprintf( __( '%s por mês', 'resgata-wp' ), Plans::format_amount( (int) $plan['amount'] ) );

		return '' !== $plan['name'] ? sprintf( '%s (%s)', $plan['name'], $amount ) : $amount;
	}

	/**
	 * Nome da protetora ou, sem ele, o nome do site.
	 */
	private static function organization_name(): string {
		$name = Organization::get()['name'];

		return '' !== $name ? $name : (string) get_bloginfo( 'name' );
	}
}
