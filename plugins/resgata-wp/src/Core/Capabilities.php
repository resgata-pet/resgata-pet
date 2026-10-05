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
	 * Permissão para entrar no painel (/painel) e usar a API dele.
	 */
	public const PANEL = 'rwp_access_panel';

	/**
	 * Perfil da protetora: usa somente o painel, nunca o wp-admin.
	 */
	public const PANEL_ROLE = 'rwp_protetor_admin';

	/**
	 * Papéis que recebem acesso ao cadastro de animais e ao painel.
	 */
	private const ROLES = array( 'administrator', 'editor', self::PANEL_ROLE );

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
		self::ensure_panel_role();

		foreach ( self::ROLES as $role_name ) {
			$role = get_role( $role_name );
			if ( ! $role ) {
				continue;
			}
			foreach ( self::animals() as $cap ) {
				$role->add_cap( $cap );
			}
			$role->add_cap( self::PANEL );
		}
	}

	/**
	 * Cria o perfil "Protetor admin" como cópia do Editor, se ainda não existir.
	 *
	 * O nome não passa por função de tradução porque este método também roda
	 * antes do hook `init` (atualização de versão).
	 */
	private static function ensure_panel_role(): void {
		if ( get_role( self::PANEL_ROLE ) ) {
			return;
		}

		$editor = get_role( 'editor' );

		add_role( self::PANEL_ROLE, 'Protetor admin', $editor ? $editor->capabilities : array( 'read' => true ) );
	}

	/**
	 * Informa se o usuário é um "Protetor admin" (e não um administrador).
	 *
	 * @param \WP_User|null $user Usuário; por padrão, o usuário atual.
	 */
	public static function is_panel_only_user( ?\WP_User $user = null ): bool {
		$user = $user ?? wp_get_current_user();

		return $user->exists() && in_array( self::PANEL_ROLE, (array) $user->roles, true ) && ! user_can( $user, 'manage_options' );
	}

	/**
	 * Remove as capabilities de todos os papéis (desinstalação).
	 */
	public static function revoke(): void {
		foreach ( wp_roles()->role_objects as $role ) {
			foreach ( self::animals() as $cap ) {
				$role->remove_cap( $cap );
			}
			$role->remove_cap( self::PANEL );
		}

		remove_role( self::PANEL_ROLE );
	}
}
