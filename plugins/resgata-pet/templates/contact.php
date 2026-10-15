<?php
/**
 * Contato da protetora: WhatsApp em destaque, telefone, e-mail, horário, localização, redes e Pix.
 *
 * Para personalizar, copie para `seu-tema/resgata-pet/contact.php`.
 *
 * @package ResgataPet
 *
 * @var array<string, mixed> $resgata_args {
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

use ResgataPet\Core\Html;
use ResgataPet\Core\Icons;

defined( 'ABSPATH' ) || exit;

$resgata_data  = $resgata_args['data'];
$resgata_items = array();

// Cada item: ícone, rótulo, valor e link (vazio = texto simples).
if ( '' !== $resgata_data['phone'] ) {
	$resgata_items[] = array( 'phone', __( 'Telefone', 'resgata-pet' ), $resgata_args['phone_label'], 'tel:+55' . $resgata_data['phone'] );
}
if ( '' !== $resgata_data['email'] ) {
	$resgata_items[] = array( 'mail', __( 'E-mail', 'resgata-pet' ), $resgata_data['email'], 'mailto:' . $resgata_data['email'] );
}
if ( '' !== $resgata_data['hours'] ) {
	$resgata_items[] = array( 'clock', __( 'Horário de atendimento', 'resgata-pet' ), $resgata_data['hours'], '' );
}
if ( '' !== $resgata_args['location'] ) {
	$resgata_items[] = array( 'map-pin', __( 'Onde estamos', 'resgata-pet' ), $resgata_args['location'], '' );
}
if ( '' !== $resgata_data['cnpj'] ) {
	$resgata_items[] = array( 'file-text', __( 'CNPJ', 'resgata-pet' ), $resgata_data['cnpj'], '' );
}

$resgata_has_content = $resgata_items || $resgata_args['networks'] || '' !== $resgata_args['whatsapp_url'] || $resgata_args['show_pix'];

?>
<div <?php echo wp_kses_data( $resgata_args['wrapper_attributes'] ); ?>>
	<?php if ( ! $resgata_has_content ) : ?>
		<p class="resgata-empty"><?php esc_html_e( 'Os dados de contato ainda não foram cadastrados.', 'resgata-pet' ); ?></p>
	<?php endif; ?>

	<?php if ( '' !== $resgata_data['name'] || '' !== $resgata_data['tagline'] ) : ?>
		<div class="resgata-contact__intro">
			<?php if ( '' !== $resgata_data['name'] ) : ?>
				<p class="resgata-contact__name"><?php echo esc_html( $resgata_data['name'] ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $resgata_data['tagline'] ) : ?>
				<p class="resgata-contact__tagline"><?php echo esc_html( $resgata_data['tagline'] ); ?></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( '' !== $resgata_args['whatsapp_url'] ) : ?>
		<div class="resgata-contact__whatsapp">
			<span class="resgata-contact__whatsapp-icon"><?php echo wp_kses( Icons::whatsapp(), Html::svg() ); ?></span>
			<div class="resgata-contact__whatsapp-text">
				<h2 class="resgata-contact__heading"><?php esc_html_e( 'Fale com a gente pelo WhatsApp', 'resgata-pet' ); ?></h2>
				<p>
					<?php
					/* translators: %s: número do WhatsApp. */
					echo esc_html( sprintf( __( 'É o jeito mais rápido de tirar dúvidas sobre adoção e como ajudar. Nosso número: %s', 'resgata-pet' ), $resgata_args['whatsapp_label'] ) );
					?>
				</p>
			</div>
			<a class="resgata-button resgata-button--primary wp-element-button" href="<?php echo esc_url( $resgata_args['whatsapp_url'] ); ?>" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Conversar no WhatsApp', 'resgata-pet' ); ?>
			</a>
		</div>
	<?php endif; ?>

	<?php if ( $resgata_items ) : ?>
		<dl class="resgata-contact__items">
			<?php foreach ( $resgata_items as $resgata_item ) : ?>
				<div class="resgata-contact__item">
					<dt>
						<span class="resgata-contact__item-icon"><?php echo wp_kses( Icons::svg( $resgata_item[0] ), Html::svg() ); ?></span>
						<?php echo esc_html( $resgata_item[1] ); ?>
					</dt>
					<dd>
						<?php if ( '' !== $resgata_item[3] ) : ?>
							<a href="<?php echo esc_url( $resgata_item[3] ); ?>"><?php echo esc_html( $resgata_item[2] ); ?></a>
						<?php else : ?>
							<?php echo esc_html( $resgata_item[2] ); ?>
						<?php endif; ?>
					</dd>
				</div>
			<?php endforeach; ?>
		</dl>
	<?php endif; ?>

	<?php if ( $resgata_args['networks'] ) : ?>
		<div class="resgata-contact__section">
			<h2 class="resgata-contact__heading"><?php esc_html_e( 'Acompanhe nas redes sociais', 'resgata-pet' ); ?></h2>
			<ul class="resgata-contact__networks">
				<?php foreach ( $resgata_args['networks'] as $resgata_network => $resgata_url ) : ?>
					<li>
						<a href="<?php echo esc_url( $resgata_url ); ?>" target="_blank" rel="noopener noreferrer">
							<?php echo wp_kses( Icons::brand( (string) ( $resgata_args['network_icons'][ $resgata_network ] ?? '' ) ), Html::svg() ); ?>
							<?php echo esc_html( $resgata_network ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php if ( $resgata_args['show_pix'] ) : ?>
		<div class="resgata-contact__section resgata-contact__pix">
			<h2 class="resgata-contact__heading">
				<?php echo wp_kses( Icons::svg( 'heart' ), Html::svg() ); ?>
				<?php esc_html_e( 'Ajude com um Pix', 'resgata-pet' ); ?>
			</h2>
			<dl class="resgata-contact__pix-data">
				<div>
					<dt>
						<?php
						echo esc_html(
							'' !== $resgata_args['pix_type_label']
								/* translators: %s: tipo da chave Pix (CPF, e-mail...). */
								? sprintf( __( 'Chave Pix (%s)', 'resgata-pet' ), $resgata_args['pix_type_label'] )
								: __( 'Chave Pix', 'resgata-pet' )
						);
						?>
					</dt>
					<dd class="resgata-contact__pix-key"><?php echo esc_html( $resgata_data['pix_key'] ); ?></dd>
				</div>
				<?php if ( '' !== $resgata_data['pix_holder'] ) : ?>
					<div>
						<dt><?php esc_html_e( 'Titular', 'resgata-pet' ); ?></dt>
						<dd><?php echo esc_html( $resgata_data['pix_holder'] ); ?></dd>
					</div>
				<?php endif; ?>
			</dl>
			<?php if ( '' !== $resgata_args['help_url'] ) : ?>
				<p class="resgata-contact__pix-link">
					<a class="resgata-button resgata-button--outline" href="<?php echo esc_url( $resgata_args['help_url'] ); ?>">
						<?php echo wp_kses( Icons::svg( 'qr-code' ), Html::svg() ); ?>
						<?php esc_html_e( 'Doar com QR Code', 'resgata-pet' ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
<?php
