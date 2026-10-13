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
	public const PANEL = 'resgata_access_panel';

	/**
	 * Permissão para editar os dados da protetora em /painel/configuracoes.
	 */
	public const SETTINGS = 'resgata_manage_settings';

	/**
	 * Permissão para ver e responder os pedidos de adoção (dados pessoais).
	 */
	public const ADOPTIONS = 'resgata_manage_adoptions';

	/**
	 * Permissão para ver os padrinhos (dados pessoais) e ajustar o contador.
	 */
	public const SPONSORSHIP = 'resgata_manage_sponsorship';

	/**
	 * Papéis com acesso às configurações, aos pedidos de adoção e aos padrinhos (o Editor comum não).
	 */
	private const SETTINGS_ROLES = array( 'administrator', self::PANEL_ROLE );

	/**
	 * Perfil da protetora: usa somente o painel, nunca o wp-admin.
	 */
	public const PANEL_ROLE = 'resgata_protetor_admin';

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
			'edit_resgata_animals',
			'edit_others_resgata_animals',
			'edit_private_resgata_animals',
			'edit_published_resgata_animals',
			'publish_resgata_animals',
			'read_private_resgata_animals',
			'delete_resgata_animals',
			'delete_others_resgata_animals',
			'delete_private_resgata_animals',
			'delete_published_resgata_animals',
			'manage_resgata_animal_terms',
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

			if ( in_array( $role_name, self::SETTINGS_ROLES, true ) ) {
				$role->add_cap( self::SETTINGS );
				$role->add_cap( self::ADOPTIONS );
				$role->add_cap( self::SPONSORSHIP );
			}
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
			$role->remove_cap( self::SETTINGS );
			$role->remove_cap( self::ADOPTIONS );
			$role->remove_cap( self::SPONSORSHIP );
		}

		remove_role( self::PANEL_ROLE );
	}
}
