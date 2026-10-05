<?php
/**
 * Capabilities próprias do plugin.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Concede e remove as capabilities do plugin nos papéis do WordPress.
 */
final class Capabilities {

	/**
	 * Papéis que recebem acesso ao cadastro de animais.
	 */
	private const ROLES = array( 'administrator', 'editor' );

	/**
	 * Capabilities do módulo de animais.
	 *
	 * @return string[]
	 */
	public static function animals(): array {
		return array(
			'edit_rwp_animals',
			'edit_others_rwp_animals',
			'edit_private_rwp_animals',
			'edit_published_rwp_animals',
			'publish_rwp_animals',
			'read_private_rwp_animals',
			'delete_rwp_animals',
			'delete_others_rwp_animals',
			'delete_private_rwp_animals',
			'delete_published_rwp_animals',
			'manage_rwp_animal_terms',
		);
	}

	/**
	 * Concede as capabilities aos papéis padrão.
	 */
	public static function grant(): void {
		foreach ( self::ROLES as $role_name ) {
			$role = get_role( $role_name );
			if ( ! $role ) {
				continue;
			}
			foreach ( self::animals() as $cap ) {
				$role->add_cap( $cap );
			}
		}
	}

	/**
	 * Remove as capabilities de todos os papéis (desinstalação).
	 */
	public static function revoke(): void {
		foreach ( wp_roles()->role_objects as $role ) {
			foreach ( self::animals() as $cap ) {
				$role->remove_cap( $cap );
			}
		}
	}
}
