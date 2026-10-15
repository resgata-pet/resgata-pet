<?php
/**
 * Confirmação do pedido de adoção, com o botão para enviar pelo WhatsApp.
 *
 * Para personalizar, copie para `seu-tema/resgata-pet/adoption-success.php`.
 *
 * @package ResgataPet
 *
 * @var array{wrapper_attributes: string, animal_name: string, whatsapp_url: string, request_id: int} $resgata_args
 */

defined( 'ABSPATH' ) || exit;
?>
<div <?php echo $resgata_args['wrapper_attributes']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Já escapado na origem. ?>>
	<div class="resgata-adoption__success" role="status">
		<h2 class="resgata-adoption__title"><?php esc_html_e( 'Pedido registrado!', 'resgata-pet' ); ?></h2>
		<?php if ( '' !== $resgata_args['whatsapp_url'] ) : ?>
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: nome do animal. */
						__( 'Falta só um passo: envie o seu pedido de adoção de %s pelo WhatsApp. A mensagem já está pronta, é só tocar em enviar.', 'resgata-pet' ),
						$resgata_args['animal_name']
					)
				);
				?>
			</p>
			<p>
				<a class="resgata-button wp-element-button" href="<?php echo esc_url( $resgata_args['whatsapp_url'] ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Enviar pedido pelo WhatsApp', 'resgata-pet' ); ?>
				</a>
			</p>
		<?php else : ?>
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: nome do animal. */
						__( 'Recebemos o seu pedido de adoção de %s. Entraremos em contato em breve.', 'resgata-pet' ),
						$resgata_args['animal_name']
					)
				);
				?>
			</p>
		<?php endif; ?>
	</div>
</div>
