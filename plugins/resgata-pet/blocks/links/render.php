<?php
/**
 * Renderização do bloco "Links do site".
 *
 * @package ResgataPet
 *
 * @var array<string, mixed> $attributes Atributos do bloco.
 */

use ResgataPet\Core\Bindings;

defined( 'ABSPATH' ) || exit;

$resgata_groups = array(
	'site' => array(
		'home'    => __( 'Início', 'resgata-pet' ),
		'animals' => __( 'Animais para adoção', 'resgata-pet' ),
		'contact' => __( 'Contato', 'resgata-pet' ),
	),
	'help' => array(
		'help'        => __( 'Como ajudar', 'resgata-pet' ),
		'sponsorship' => __( 'Seja um padrinho', 'resgata-pet' ),
		'donate'      => __( 'Doar por Pix', 'resgata-pet' ),
		'animals'     => __( 'Adotar um animal', 'resgata-pet' ),
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
	$resgata_links[ __( 'Política de privacidade', 'resgata-pet' ) ] = (string) get_privacy_policy_url();
}

if ( ! $resgata_links ) {
	return;
}
?>
<ul <?php echo wp_kses_data( get_block_wrapper_attributes( array( 'class' => 'resgata-links' ) ) ); ?>>
	<?php foreach ( $resgata_links as $resgata_label => $resgata_url ) : ?>
		<li><a href="<?php echo esc_url( $resgata_url ); ?>"><?php echo esc_html( $resgata_label ); ?></a></li>
	<?php endforeach; ?>
</ul>
