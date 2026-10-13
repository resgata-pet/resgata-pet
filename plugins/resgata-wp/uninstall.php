<?php
/**
 * Desinstalação do Resgata WP.
 *
 * Por padrão os dados são mantidos. Eles só são apagados se a opção
 * "Manter os dados" for desmarcada em Resgata WP → Configurações.
 *
 * @package ResgataWP
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// As chaves da Stripe salvas no painel saem sempre, mesmo com "Manter os dados" marcado.
delete_option( 'resgata_stripe_keys' );

$resgata_settings = get_option( 'resgata_settings', array() );

if ( ! is_array( $resgata_settings ) || ! array_key_exists( 'keep_data', $resgata_settings ) || $resgata_settings['keep_data'] ) {
	return;
}

global $wpdb;

// Animais (inclui metadados e vínculos com termos). As fotos continuam na biblioteca de mídia.
$resgata_post_ids = get_posts(
	array(
		'post_type'   => 'resgata_animal',
		'post_status' => array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'trash' ),
		'numberposts' => -1,
		'fields'      => 'ids',
	)
);

foreach ( $resgata_post_ids as $resgata_post_id ) {
	wp_delete_post( (int) $resgata_post_id, true );
}

// Pedidos de adoção (dados pessoais).
$resgata_request_ids = get_posts(
	array(
		'post_type'   => 'resgata_adoption',
		'post_status' => array( 'publish', 'private', 'draft', 'trash' ),
		'numberposts' => -1,
		'fields'      => 'ids',
	)
);

foreach ( $resgata_request_ids as $resgata_request_id ) {
	wp_delete_post( (int) $resgata_request_id, true );
}

// Termos das taxonomias (elas não estão registradas durante a desinstalação).
foreach ( array( 'resgata_species', 'resgata_temperament' ) as $resgata_taxonomy ) {
	register_taxonomy( $resgata_taxonomy, 'resgata_animal' );

	$resgata_terms = get_terms(
		array(
			'taxonomy'   => $resgata_taxonomy,
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);

	if ( ! is_wp_error( $resgata_terms ) ) {
		foreach ( $resgata_terms as $resgata_term_id ) {
			wp_delete_term( (int) $resgata_term_id, $resgata_taxonomy );
		}
	}
}

// Capabilities.
$resgata_caps = array(
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
	'resgata_access_panel',
	'resgata_manage_settings',
	'resgata_manage_adoptions',
	'resgata_manage_sponsorship',
);

foreach ( wp_roles()->role_objects as $resgata_role ) {
	foreach ( $resgata_caps as $resgata_cap ) {
		$resgata_role->remove_cap( $resgata_cap );
	}
}

remove_role( 'resgata_protetor_admin' );

// Opções.
foreach ( array( 'resgata_settings', 'resgata_modules', 'resgata_db_version', 'resgata_flush_rewrite', 'resgata_organization', 'resgata_contact_page_id', 'resgata_help_page_id', 'resgata_banners', 'resgata_sponsorship', 'resgata_sponsorship_counter', 'resgata_payments_since' ) as $resgata_option ) {
	delete_option( $resgata_option );
}

// Tabelas próprias: padrinhos (dados pessoais), eventos da Stripe já processados e pagamentos.
foreach ( array( 'resgata_sponsors', 'resgata_stripe_events', 'resgata_payments' ) as $resgata_table ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.SchemaChange.SchemaChange -- Tabela do próprio plugin, na desinstalação.
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . $resgata_table ) );
}

wp_clear_scheduled_hook( 'resgata_stripe_events_cleanup' );
