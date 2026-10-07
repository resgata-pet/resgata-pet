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
 * Tabelas atuais: padrinhos e eventos da Stripe já processados (módulo de
 * apadrinhamento, schema 5). Cada mudança de schema deve incrementar
 * RWP_DB_VERSION e ser adicionada em `tables()`.
 */
final class Schema {

	public const OPTION = 'rwp_db_version';

	/**
	 * Marca que as regras de URL precisam ser recriadas no próximo `init`.
	 */
	public const FLUSH_FLAG = 'rwp_flush_rewrite';

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
		);
	}

	/**
	 * Nomes (sem prefixo) das tabelas do plugin, usados na desinstalação.
	 *
	 * @return string[]
	 */
	public static function table_names(): array {
		return array( 'rwp_sponsors', 'rwp_stripe_events' );
	}
}
