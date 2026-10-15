<?php
/**
 * Contato resumido (para o rodapé): WhatsApp, telefone, e-mail, localização e redes sociais.
 *
 * Para personalizar, copie para `seu-tema/resgata-pet/contact-compact.php`.
 *
 * @package ResgataPet
 *
 * @var array<string, mixed> $resgata_args Os mesmos dados de `contact.php`.
 */

use ResgataPet\Core\Html;
use ResgataPet\Core\Icons;

defined( 'ABSPATH' ) || exit;

$resgata_data  = $resgata_args['data'];
$resgata_items = array();

// Cada item: HTML do ícone, texto e link (vazio = texto simples).
if ( '' !== $resgata_args['whatsapp_url'] ) {
	$resgata_items[] = array( Icons::whatsapp(), $resgata_args['whatsapp_label'], $resgata_args['whatsapp_url'], __( 'WhatsApp', 'resgata-pet' ) );
}
if ( '' !== $resgata_data['phone'] && $resgata_data['phone'] !== $resgata_data['whatsapp'] ) {
	$resgata_items[] = array( Icons::svg( 'phone' ), $resgata_args['phone_label'], 'tel:+55' . $resgata_data['phone'], __( 'Telefone', 'resgata-pet' ) );
}
if ( '' !== $resgata_data['email'] ) {
	$resgata_items[] = array( Icons::svg( 'mail' ), $resgata_data['email'], 'mailto:' . $resgata_data['email'], __( 'E-mail', 'resgata-pet' ) );
}
if ( '' !== $resgata_args['location'] ) {
	$resgata_items[] = array( Icons::svg( 'map-pin' ), $resgata_args['location'], '', __( 'Onde estamos', 'resgata-pet' ) );
}

if ( ! $resgata_items && ! $resgata_args['networks'] ) {
	return;
}

?>
<div <?php echo wp_kses_data( $resgata_args['wrapper_attributes'] ); ?>>
	<?php if ( $resgata_items ) : ?>
		<ul class="resgata-contact-compact__list">
			<?php foreach ( $resgata_items as $resgata_item ) : ?>
				<li>
					<?php echo wp_kses( $resgata_item[0], Html::svg() ); ?>
					<span class="screen-reader-text"><?php echo esc_html( $resgata_item[3] ); ?>:</span>
					<?php if ( '' !== $resgata_item[2] ) : ?>
						<a href="<?php echo esc_url( $resgata_item[2] ); ?>"<?php echo str_starts_with( $resgata_item[2], 'http' ) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $resgata_item[1] ); ?></a>
					<?php else : ?>
						<span><?php echo esc_html( $resgata_item[1] ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( $resgata_args['networks'] ) : ?>
		<ul class="resgata-contact-compact__networks" aria-label="<?php esc_attr_e( 'Redes sociais', 'resgata-pet' ); ?>">
			<?php foreach ( $resgata_args['networks'] as $resgata_network => $resgata_url ) : ?>
				<li>
					<a href="<?php echo esc_url( $resgata_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo wp_kses( Icons::brand( (string) ( $resgata_args['network_icons'][ $resgata_network ] ?? '' ) ), Html::svg() ); ?>
						<span class="screen-reader-text"><?php echo esc_html( $resgata_network ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
<?php
