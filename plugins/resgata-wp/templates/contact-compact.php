<?php
/**
 * Contato resumido (para o rodapé): WhatsApp, telefone, e-mail, localização e redes sociais.
 *
 * Para personalizar, copie para `seu-tema/resgata-wp/contact-compact.php`.
 *
 * @package ResgataWP
 *
 * @var array<string, mixed> $rwp_args Os mesmos dados de `contact.php`.
 */

use ResgataWP\Core\Icons;

defined( 'ABSPATH' ) || exit;

$rwp_data  = $rwp_args['data'];
$rwp_items = array();

// Cada item: HTML do ícone, texto e link (vazio = texto simples).
if ( '' !== $rwp_args['whatsapp_url'] ) {
	$rwp_items[] = array( Icons::whatsapp(), $rwp_args['whatsapp_label'], $rwp_args['whatsapp_url'], __( 'WhatsApp', 'resgata-wp' ) );
}
if ( '' !== $rwp_data['phone'] && $rwp_data['phone'] !== $rwp_data['whatsapp'] ) {
	$rwp_items[] = array( Icons::svg( 'phone' ), $rwp_args['phone_label'], 'tel:+55' . $rwp_data['phone'], __( 'Telefone', 'resgata-wp' ) );
}
if ( '' !== $rwp_data['email'] ) {
	$rwp_items[] = array( Icons::svg( 'mail' ), $rwp_data['email'], 'mailto:' . $rwp_data['email'], __( 'E-mail', 'resgata-wp' ) );
}
if ( '' !== $rwp_args['location'] ) {
	$rwp_items[] = array( Icons::svg( 'map-pin' ), $rwp_args['location'], '', __( 'Onde estamos', 'resgata-wp' ) );
}

if ( ! $rwp_items && ! $rwp_args['networks'] ) {
	return;
}

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Icons::svg() e Icons::brand() devolvem SVG do plugin ou do WordPress; atributos do wrapper escapados na origem.
?>
<div <?php echo $rwp_args['wrapper_attributes']; ?>>
	<?php if ( $rwp_items ) : ?>
		<ul class="rwp-contact-compact__list">
			<?php foreach ( $rwp_items as $rwp_item ) : ?>
				<li>
					<?php echo $rwp_item[0]; ?>
					<span class="screen-reader-text"><?php echo esc_html( $rwp_item[3] ); ?>:</span>
					<?php if ( '' !== $rwp_item[2] ) : ?>
						<a href="<?php echo esc_url( $rwp_item[2] ); ?>"<?php echo str_starts_with( $rwp_item[2], 'http' ) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $rwp_item[1] ); ?></a>
					<?php else : ?>
						<span><?php echo esc_html( $rwp_item[1] ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( $rwp_args['networks'] ) : ?>
		<ul class="rwp-contact-compact__networks" aria-label="<?php esc_attr_e( 'Redes sociais', 'resgata-wp' ); ?>">
			<?php foreach ( $rwp_args['networks'] as $rwp_network => $rwp_url ) : ?>
				<li>
					<a href="<?php echo esc_url( $rwp_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo Icons::brand( (string) ( $rwp_args['network_icons'][ $rwp_network ] ?? '' ) ); ?>
						<span class="screen-reader-text"><?php echo esc_html( $rwp_network ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
<?php
// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
