<?php
/**
 * Plugin Name: Resgata Pet – Mailpit (somente desenvolvimento)
 * Description: Envia todos os e-mails do WordPress para o Mailpit do ambiente Docker local.
 *
 * @package ResgataPet
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'phpmailer_init',
	static function ( $phpmailer ) {
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Propriedades do PHPMailer.
		$phpmailer->isSMTP();
		$phpmailer->Host     = 'mailpit';
		$phpmailer->Port     = 1025;
		$phpmailer->SMTPAuth = false;
		// phpcs:enable
	}
);

add_filter(
	'wp_mail_from',
	static function () {
		return 'wordpress@resgata.test';
	}
);
