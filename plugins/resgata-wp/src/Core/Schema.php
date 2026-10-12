<?php
/**
 * Versionamento do schema do banco de dados.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Cria e atualiza as tabelas próprias do plugin com `dbDelta`.
 *
 * Tabelas atuais: padrinhos, eventos da Stripe já processados (módulo de
 * apadrinhamento, schema 5) e pagamentos dos padrinhos (schema 6). Cada
 * mudança de schema deve incrementar RWP_DB_VERSION e ser adicionada em
 * `tables()`.
 */
final class Schema {

	public const OPTION = 'rwp_db_version';

	/**
	 * Marca que as regras de URL precisam ser recriadas no próximo `init`.
	 */
	public const FLUSH_FLAG = 'rwp_flush_rewrite';

	/**
	 * Momento em que o site começou a registrar os pagamentos, quando já havia padrinhos antes disso.
	 */
	public const PAYMENTS_SINCE = 'rwp_payments_since';

	/**
	 * Roda as migrações pendentes, se houver.
	 */
	public static function maybe_upgrade(): void {
		if ( (int) get_option( self::OPTION, 0 ) >= RWP_DB_VERSION ) {
			return;
		}

		self::install();
	}

	/**
	 * Cria/atualiza as tabelas e grava a versão do schema.
	 */
	public static function install(): void {
		$tables = self::tables();

		if ( $tables ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $tables );
		}

		// Quem atualiza de uma versão anterior ao módulo de adoção (schema 4) o recebe ligado.
		$previous = (int) get_option( self::OPTION, 0 );
		$enabled  = get_option( Modules::OPTION );

		if ( $previous > 0 && $previous < 4 && is_array( $enabled ) && ! in_array( 'adoptions', $enabled, true ) ) {
			$enabled[] = 'adoptions';
			update_option( Modules::OPTION, $enabled );
		}

		// Quem já era padrinho antes do schema 6 não recebe o e-mail de boas-vindas, e os
		// pagamentos anteriores não foram registrados: o total mostrado conta a partir de agora.
		if ( $previous > 0 && $previous < 6 ) {
			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Tabela própria, na migração.
			$wpdb->query( $wpdb->prepare( 'UPDATE %i SET welcomed_at = created_at WHERE welcomed_at IS NULL', $wpdb->prefix . 'rwp_sponsors' ) );
			add_option( self::PAYMENTS_SINCE, time(), '', false );
		}

		// Perfis/permissões e regras de URL novas também acompanham a versão.
		Capabilities::grant();
		update_option( self::FLUSH_FLAG, 1, false );

		update_option( self::OPTION, RWP_DB_VERSION, false );
	}

	/**
	 * Instruções CREATE TABLE no formato esperado pelo `dbDelta`.
	 *
	 * @return string[]
	 */
	private static function tables(): array {
		global $wpdb;

		$charset = $wpdb->get_charset_collate();

		return array(
			"CREATE TABLE {$wpdb->prefix}rwp_sponsors (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				customer_id varchar(64) NOT NULL,
				subscription_id varchar(64) NOT NULL DEFAULT '',
				name varchar(190) NOT NULL DEFAULT '',
				email varchar(190) NOT NULL DEFAULT '',
				price_id varchar(64) NOT NULL DEFAULT '',
				status varchar(20) NOT NULL DEFAULT 'incomplete',
				payment_failed tinyint(1) NOT NULL DEFAULT 0,
				last_paid_at datetime DEFAULT NULL,
				last_event_at bigint(20) unsigned NOT NULL DEFAULT 0,
				period_end datetime DEFAULT NULL,
				cancel_at datetime DEFAULT NULL,
				welcomed_at datetime DEFAULT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY customer_id (customer_id),
				KEY email (email),
				KEY status (status)
			) $charset;",
			"CREATE TABLE {$wpdb->prefix}rwp_stripe_events (
				event_id varchar(64) NOT NULL,
				type varchar(64) NOT NULL DEFAULT '',
				processed_at datetime NOT NULL,
				PRIMARY KEY  (event_id),
				KEY processed_at (processed_at)
			) $charset;",
			"CREATE TABLE {$wpdb->prefix}rwp_payments (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				sponsor_id bigint(20) unsigned NOT NULL DEFAULT 0,
				invoice_id varchar(64) NOT NULL,
				payment_intent_id varchar(64) NOT NULL DEFAULT '',
				amount int(10) unsigned NOT NULL DEFAULT 0,
				refunded int(10) unsigned NOT NULL DEFAULT 0,
				dispute varchar(10) NOT NULL DEFAULT '',
				paid_at datetime DEFAULT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY invoice_id (invoice_id),
				KEY sponsor_id (sponsor_id),
				KEY payment_intent_id (payment_intent_id)
			) $charset;",
		);
	}

	/**
	 * Nomes (sem prefixo) das tabelas do plugin, usados na desinstalação.
	 *
	 * @return string[]
	 */
	public static function table_names(): array {
		return array( 'rwp_sponsors', 'rwp_stripe_events', 'rwp_payments' );
	}
}
