<?php
/**
 * Confirmação do pedido de adoção, com o botão para enviar pelo WhatsApp.
 *
 * Para personalizar, copie para `seu-tema/resgata-wp/adoption-success.php`.
 *
 * @package ResgataWP
 *
 * @var array{wrapper_attributes: string, animal_name: string, whatsapp_url: string, request_id: int} $rwp_args
 */

defined( 'ABSPATH' ) || exit;
?>
<div <?php echo $rwp_args['wrapper_attributes']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Já escapado na origem. ?>>
	<div class="rwp-adoption__success" role="status">
		<h2 class="rwp-adoption__title"><?php esc_html_e( 'Pedido registrado!', 'resgata-wp' ); ?></h2>
		<?php if ( '' !== $rwp_args['whatsapp_url'] ) : ?>
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: nome do animal. */
						__( 'Falta só um passo: envie o seu pedido de adoção de %s pelo WhatsApp. A mensagem já está pronta, é só tocar em enviar.', 'resgata-wp' ),
						$rwp_args['animal_name']
					)
				);
				?>
			</p>
			<p>
				<a class="rwp-button wp-element-button" href="<?php echo esc_url( $rwp_args['whatsapp_url'] ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Enviar pedido pelo WhatsApp', 'resgata-wp' ); ?>
				</a>
			</p>
		<?php else : ?>
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: nome do animal. */
						__( 'Recebemos o seu pedido de adoção de %s. Entraremos em contato em breve.', 'resgata-wp' ),
						$rwp_args['animal_name']
					)
				);
				?>
			</p>
		<?php endif; ?>
	</div>
</div>
