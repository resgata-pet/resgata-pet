<?php
/**
 * Renderização do bloco "Links do site".
 *
 * @package ResgataWP
 *
 * @var array<string, mixed> $attributes Atributos do bloco.
 */

use ResgataWP\Core\Bindings;

defined( 'ABSPATH' ) || exit;

$rwp_groups = array(
	'site' => array(
		'home'    => __( 'Início', 'resgata-wp' ),
		'animals' => __( 'Animais para adoção', 'resgata-wp' ),
		'contact' => __( 'Contato', 'resgata-wp' ),
	),
	'help' => array(
		'help'        => __( 'Como ajudar', 'resgata-wp' ),
		'sponsorship' => __( 'Seja um padrinho', 'resgata-wp' ),
		'donate'      => __( 'Doar por Pix', 'resgata-wp' ),
		'animals'     => __( 'Adotar um animal', 'resgata-wp' ),
	),
);

$rwp_group = isset( $rwp_groups[ $attributes['group'] ?? '' ] ) ? (string) $attributes['group'] : 'site';
$rwp_links = array();

// Só entram as páginas que existem.
foreach ( $rwp_groups[ $rwp_group ] as $rwp_key => $rwp_label ) {
	$rwp_url = Bindings::link( $rwp_key );

	if ( '' !== $rwp_url ) {
		$rwp_links[ $rwp_label ] = $rwp_url;
	}
}

if ( 'site' === $rwp_group && '' !== (string) get_privacy_policy_url() ) {
	$rwp_links[ __( 'Política de privacidade', 'resgata-wp' ) ] = (string) get_privacy_policy_url();
}

if ( ! $rwp_links ) {
	return;
}
?>
<ul <?php echo get_block_wrapper_attributes( array( 'class' => 'rwp-links' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escapado pelo core. ?>>
	<?php foreach ( $rwp_links as $rwp_label => $rwp_url ) : ?>
		<li><a href="<?php echo esc_url( $rwp_url ); ?>"><?php echo esc_html( $rwp_label ); ?></a></li>
	<?php endforeach; ?>
</ul>
