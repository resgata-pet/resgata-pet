<?php
/**
 * Contato da protetora: WhatsApp, telefone, e-mail, horário, localização, redes e Pix.
 *
 * Para personalizar, copie para `seu-tema/resgata-wp/contact.php`.
 *
 * @package ResgataWP
 *
 * @var array<string, mixed> $rwp_args {
 *     @type string                $wrapper_attributes Atributos HTML do elemento externo (já escapados).
 *     @type array<string, string> $data               Dados públicos da protetora.
 *     @type string                $whatsapp_url       Link do WhatsApp (vazio se não houver número).
 *     @type string                $whatsapp_label     Número do WhatsApp formatado.
 *     @type string                $phone_label        Telefone formatado.
 *     @type string                $location           Bairro, cidade e estado.
 *     @type array<string, string> $networks           Redes sociais (nome => link).
 *     @type bool                  $show_pix           Se mostra os dados do Pix.
 *     @type string                $pix_type_label     Tipo da chave Pix.
 * }
 */

defined( 'ABSPATH' ) || exit;

$rwp_data  = $rwp_args['data'];
$rwp_items = array();

if ( '' !== $rwp_data['phone'] ) {
	$rwp_items[ __( 'Telefone', 'resgata-wp' ) ] = sprintf( '<a href="%s">%s</a>', esc_url( 'tel:+55' . $rwp_data['phone'] ), esc_html( $rwp_args['phone_label'] ) );
}
if ( '' !== $rwp_data['email'] ) {
	$rwp_items[ __( 'E-mail', 'resgata-wp' ) ] = sprintf( '<a href="%s">%s</a>', esc_url( 'mailto:' . $rwp_data['email'] ), esc_html( $rwp_data['email'] ) );
}
if ( '' !== $rwp_data['hours'] ) {
	$rwp_items[ __( 'Horário de atendimento', 'resgata-wp' ) ] = esc_html( $rwp_data['hours'] );
}
if ( '' !== $rwp_args['location'] ) {
	$rwp_items[ __( 'Onde estamos', 'resgata-wp' ) ] = esc_html( $rwp_args['location'] );
}
if ( '' !== $rwp_data['cnpj'] ) {
	$rwp_items[ __( 'CNPJ', 'resgata-wp' ) ] = esc_html( $rwp_data['cnpj'] );
}

$rwp_has_content = $rwp_items || $rwp_args['networks'] || '' !== $rwp_args['whatsapp_url'] || $rwp_args['show_pix'];
?>
<div <?php echo $rwp_args['wrapper_attributes']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Já escapado na origem. ?>>
	<?php if ( ! $rwp_has_content ) : ?>
		<p class="rwp-empty"><?php esc_html_e( 'Os dados de contato ainda não foram cadastrados.', 'resgata-wp' ); ?></p>
	<?php endif; ?>

	<?php if ( '' !== $rwp_data['name'] || '' !== $rwp_data['tagline'] ) : ?>
		<div class="rwp-contact__intro">
			<?php if ( '' !== $rwp_data['name'] ) : ?>
				<p class="rwp-contact__name"><?php echo esc_html( $rwp_data['name'] ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $rwp_data['tagline'] ) : ?>
				<p class="rwp-contact__tagline"><?php echo esc_html( $rwp_data['tagline'] ); ?></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( '' !== $rwp_args['whatsapp_url'] ) : ?>
		<p class="rwp-contact__whatsapp">
			<a class="rwp-button wp-element-button" href="<?php echo esc_url( $rwp_args['whatsapp_url'] ); ?>" target="_blank" rel="noopener noreferrer">
				<?php
				/* translators: %s: número do WhatsApp. */
				echo esc_html( sprintf( __( 'Conversar no WhatsApp: %s', 'resgata-wp' ), $rwp_args['whatsapp_label'] ) );
				?>
			</a>
		</p>
	<?php endif; ?>

	<?php if ( $rwp_items ) : ?>
		<dl class="rwp-facts rwp-contact__items">
			<?php foreach ( $rwp_items as $rwp_label => $rwp_value ) : ?>
				<div class="rwp-facts__item">
					<dt><?php echo esc_html( $rwp_label ); ?></dt>
					<dd><?php echo wp_kses( $rwp_value, array( 'a' => array( 'href' => true ) ) ); ?></dd>
				</div>
			<?php endforeach; ?>
		</dl>
	<?php endif; ?>

	<?php if ( $rwp_args['networks'] ) : ?>
		<div class="rwp-contact__section">
			<h2 class="rwp-contact__heading"><?php esc_html_e( 'Redes sociais', 'resgata-wp' ); ?></h2>
			<ul class="rwp-contact__networks">
				<?php foreach ( $rwp_args['networks'] as $rwp_network => $rwp_url ) : ?>
					<li><a href="<?php echo esc_url( $rwp_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $rwp_network ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php if ( $rwp_args['show_pix'] ) : ?>
		<div class="rwp-contact__section rwp-contact__pix">
			<h2 class="rwp-contact__heading"><?php esc_html_e( 'Ajude com um Pix', 'resgata-wp' ); ?></h2>
			<dl class="rwp-facts">
				<div class="rwp-facts__item">
					<dt>
						<?php
						echo esc_html(
							'' !== $rwp_args['pix_type_label']
								/* translators: %s: tipo da chave Pix (CPF, e-mail...). */
								? sprintf( __( 'Chave Pix (%s)', 'resgata-wp' ), $rwp_args['pix_type_label'] )
								: __( 'Chave Pix', 'resgata-wp' )
						);
						?>
					</dt>
					<dd class="rwp-contact__pix-key"><?php echo esc_html( $rwp_data['pix_key'] ); ?></dd>
				</div>
				<?php if ( '' !== $rwp_data['pix_holder'] ) : ?>
					<div class="rwp-facts__item">
						<dt><?php esc_html_e( 'Titular', 'resgata-wp' ); ?></dt>
						<dd><?php echo esc_html( $rwp_data['pix_holder'] ); ?></dd>
					</div>
				<?php endif; ?>
			</dl>
		</div>
	<?php endif; ?>
</div>
