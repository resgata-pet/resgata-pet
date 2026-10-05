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
 * Nenhum módulo implementado até agora precisa de tabela própria: as tabelas
 * financeiras entram com o módulo de prestação de contas. Cada mudança de
 * schema deve incrementar RWP_DB_VERSION e ser adicionada em `tables()`.
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
		return array();
	}

	/**
	 * Nomes (sem prefixo) das tabelas do plugin, usados na desinstalação.
	 *
	 * @return string[]
	 */
	public static function table_names(): array {
		return array();
	}
}
