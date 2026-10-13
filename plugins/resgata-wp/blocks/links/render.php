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

$resgata_groups = array(
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

$resgata_group = isset( $resgata_groups[ $attributes['group'] ?? '' ] ) ? (string) $attributes['group'] : 'site';
$resgata_links = array();

// Só entram as páginas que existem.
foreach ( $resgata_groups[ $resgata_group ] as $resgata_key => $resgata_label ) {
	$resgata_url = Bindings::link( $resgata_key );

	if ( '' !== $resgata_url ) {
		$resgata_links[ $resgata_label ] = $resgata_url;
	}
}

if ( 'site' === $resgata_group && '' !== (string) get_privacy_policy_url() ) {
	$resgata_links[ __( 'Política de privacidade', 'resgata-wp' ) ] = (string) get_privacy_policy_url();
}

if ( ! $resgata_links ) {
	return;
}
?>
<ul <?php echo get_block_wrapper_attributes( array( 'class' => 'resgata-links' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escapado pelo core. ?>>
	<?php foreach ( $resgata_links as $resgata_label => $resgata_url ) : ?>
		<li><a href="<?php echo esc_url( $resgata_url ); ?>"><?php echo esc_html( $resgata_label ); ?></a></li>
	<?php endforeach; ?>
</ul>
