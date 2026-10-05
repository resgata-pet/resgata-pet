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

$rwp_settings = get_option( 'rwp_settings', array() );

if ( ! is_array( $rwp_settings ) || ! array_key_exists( 'keep_data', $rwp_settings ) || $rwp_settings['keep_data'] ) {
	return;
}

global $wpdb;

// Animais (inclui metadados e vínculos com termos). As fotos continuam na biblioteca de mídia.
$rwp_post_ids = get_posts(
	array(
		'post_type'        => 'rwp_animal',
		'post_status'      => array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'trash' ),
		'numberposts'      => -1,
		'fields'           => 'ids',
		'suppress_filters' => true,
	)
);

foreach ( $rwp_post_ids as $rwp_post_id ) {
	wp_delete_post( (int) $rwp_post_id, true );
}

// Termos das taxonomias (elas não estão registradas durante a desinstalação).
foreach ( array( 'rwp_species', 'rwp_temperament' ) as $rwp_taxonomy ) {
	register_taxonomy( $rwp_taxonomy, 'rwp_animal' );

	$rwp_terms = get_terms(
		array(
			'taxonomy'   => $rwp_taxonomy,
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);

	if ( ! is_wp_error( $rwp_terms ) ) {
		foreach ( $rwp_terms as $rwp_term_id ) {
			wp_delete_term( (int) $rwp_term_id, $rwp_taxonomy );
		}
	}
}

// Capabilities.
$rwp_caps = array(
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

foreach ( wp_roles()->role_objects as $rwp_role ) {
	foreach ( $rwp_caps as $rwp_cap ) {
		$rwp_role->remove_cap( $rwp_cap );
	}
}

// Opções.
foreach ( array( 'rwp_settings', 'rwp_modules', 'rwp_db_version', 'rwp_flush_rewrite' ) as $rwp_option ) {
	delete_option( $rwp_option );
}

/*
 * Tabelas próprias: nenhuma até a versão atual do schema. Quando o módulo de
 * prestação de contas criar tabelas, elas devem ser removidas aqui.
 */
