<?php
/**
 * Contato da protetora: WhatsApp em destaque, telefone, e-mail, horário, localização, redes e Pix.
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
 *     @type array<string, string> $network_icons      Ícone de cada rede (nome => serviço, ex.: `instagram`).
 *     @type bool                  $show_pix           Se mostra os dados do Pix.
 *     @type string                $pix_type_label     Tipo da chave Pix.
 *     @type string                $help_url           Página "Como ajudar" (QR Code do Pix), ou vazio.
 * }
 */

use ResgataWP\Core\Icons;

defined( 'ABSPATH' ) || exit;

$rwp_data  = $rwp_args['data'];
$rwp_items = array();

// Cada item: ícone, rótulo, valor e link (vazio = texto simples).
if ( '' !== $rwp_data['phone'] ) {
	$rwp_items[] = array( 'phone', __( 'Telefone', 'resgata-wp' ), $rwp_args['phone_label'], 'tel:+55' . $rwp_data['phone'] );
}
if ( '' !== $rwp_data['email'] ) {
	$rwp_items[] = array( 'mail', __( 'E-mail', 'resgata-wp' ), $rwp_data['email'], 'mailto:' . $rwp_data['email'] );
}
if ( '' !== $rwp_data['hours'] ) {
	$rwp_items[] = array( 'clock', __( 'Horário de atendimento', 'resgata-wp' ), $rwp_data['hours'], '' );
}
if ( '' !== $rwp_args['location'] ) {
	$rwp_items[] = array( 'map-pin', __( 'Onde estamos', 'resgata-wp' ), $rwp_args['location'], '' );
}
if ( '' !== $rwp_data['cnpj'] ) {
	$rwp_items[] = array( 'file-text', __( 'CNPJ', 'resgata-wp' ), $rwp_data['cnpj'], '' );
}

$rwp_has_content = $rwp_items || $rwp_args['networks'] || '' !== $rwp_args['whatsapp_url'] || $rwp_args['show_pix'];

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Icons::svg() e Icons::brand() devolvem SVG do plugin ou do WordPress; atributos do wrapper escapados na origem.
?>
<div <?php echo $rwp_args['wrapper_attributes']; ?>>
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
		<div class="rwp-contact__whatsapp">
			<span class="rwp-contact__whatsapp-icon"><?php echo Icons::whatsapp(); ?></span>
			<div class="rwp-contact__whatsapp-text">
				<h2 class="rwp-contact__heading"><?php esc_html_e( 'Fale com a gente pelo WhatsApp', 'resgata-wp' ); ?></h2>
				<p>
					<?php
					/* translators: %s: número do WhatsApp. */
					echo esc_html( sprintf( __( 'É o jeito mais rápido de tirar dúvidas sobre adoção e como ajudar. Nosso número: %s', 'resgata-wp' ), $rwp_args['whatsapp_label'] ) );
					?>
				</p>
			</div>
			<a class="rwp-button rwp-button--primary wp-element-button" href="<?php echo esc_url( $rwp_args['whatsapp_url'] ); ?>" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Conversar no WhatsApp', 'resgata-wp' ); ?>
			</a>
		</div>
	<?php endif; ?>

	<?php if ( $rwp_items ) : ?>
		<dl class="rwp-contact__items">
			<?php foreach ( $rwp_items as $rwp_item ) : ?>
				<div class="rwp-contact__item">
					<dt>
						<span class="rwp-contact__item-icon"><?php echo Icons::svg( $rwp_item[0] ); ?></span>
						<?php echo esc_html( $rwp_item[1] ); ?>
					</dt>
					<dd>
						<?php if ( '' !== $rwp_item[3] ) : ?>
							<a href="<?php echo esc_url( $rwp_item[3] ); ?>"><?php echo esc_html( $rwp_item[2] ); ?></a>
						<?php else : ?>
							<?php echo esc_html( $rwp_item[2] ); ?>
						<?php endif; ?>
					</dd>
				</div>
			<?php endforeach; ?>
		</dl>
	<?php endif; ?>

	<?php if ( $rwp_args['networks'] ) : ?>
		<div class="rwp-contact__section">
			<h2 class="rwp-contact__heading"><?php esc_html_e( 'Acompanhe nas redes sociais', 'resgata-wp' ); ?></h2>
			<ul class="rwp-contact__networks">
				<?php foreach ( $rwp_args['networks'] as $rwp_network => $rwp_url ) : ?>
					<li>
						<a href="<?php echo esc_url( $rwp_url ); ?>" target="_blank" rel="noopener noreferrer">
							<?php echo Icons::brand( (string) ( $rwp_args['network_icons'][ $rwp_network ] ?? '' ) ); ?>
							<?php echo esc_html( $rwp_network ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php if ( $rwp_args['show_pix'] ) : ?>
		<div class="rwp-contact__section rwp-contact__pix">
			<h2 class="rwp-contact__heading">
				<?php echo Icons::svg( 'heart' ); ?>
				<?php esc_html_e( 'Ajude com um Pix', 'resgata-wp' ); ?>
			</h2>
			<dl class="rwp-contact__pix-data">
				<div>
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
					<div>
						<dt><?php esc_html_e( 'Titular', 'resgata-wp' ); ?></dt>
						<dd><?php echo esc_html( $rwp_data['pix_holder'] ); ?></dd>
					</div>
				<?php endif; ?>
			</dl>
			<?php if ( '' !== $rwp_args['help_url'] ) : ?>
				<p class="rwp-contact__pix-link">
					<a class="rwp-button rwp-button--outline" href="<?php echo esc_url( $rwp_args['help_url'] ); ?>">
						<?php echo Icons::svg( 'qr-code' ); ?>
						<?php esc_html_e( 'Doar com QR Code', 'resgata-wp' ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
<?php
// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
