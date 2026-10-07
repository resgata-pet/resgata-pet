<?php
/**
 * Área do padrinho: pedido do link de acesso e confirmação para abrir o portal de pagamento.
 *
 * Para personalizar, copie para `seu-tema/resgata-wp/sponsor-area.php`.
 *
 * @package ResgataWP
 *
 * @var array{wrapper_attributes: string, token: string, sent: bool, error: string} $rwp_args
 */

use ResgataWP\Modules\Sponsorship\MagicLink;

defined( 'ABSPATH' ) || exit;
?>
<div <?php echo $rwp_args['wrapper_attributes']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Já escapado na origem. ?>>
	<?php if ( '' !== $rwp_args['token'] ) : ?>
		<form class="rwp-sponsor-area__form" method="post" action="#rwp-padrinho">
			<?php wp_nonce_field( MagicLink::NONCE_ACTION, MagicLink::NONCE_NAME ); ?>
			<input type="hidden" name="<?php echo esc_attr( MagicLink::TOKEN_FIELD ); ?>" value="<?php echo esc_attr( $rwp_args['token'] ); ?>">
			<p><?php esc_html_e( 'Tudo certo! Toque no botão para trocar o cartão, mudar o valor ou cancelar o seu apadrinhamento.', 'resgata-wp' ); ?></p>
			<p class="rwp-sponsor-area__actions">
				<button type="submit" class="rwp-button wp-element-button"><?php esc_html_e( 'Abrir meu apadrinhamento', 'resgata-wp' ); ?></button>
			</p>
		</form>
	<?php elseif ( $rwp_args['sent'] && '' === $rwp_args['error'] ) : ?>
		<div class="rwp-sponsor-area__success" role="status">
			<p><?php esc_html_e( 'Se este e-mail for de um padrinho, o link de acesso chega em alguns minutos. Confira também a caixa de spam. O link vale por 30 minutos.', 'resgata-wp' ); ?></p>
		</div>
	<?php else : ?>
		<form class="rwp-sponsor-area__form" method="post" action="#rwp-padrinho">
			<?php wp_nonce_field( MagicLink::NONCE_ACTION, MagicLink::NONCE_NAME ); ?>

			<?php if ( '' !== $rwp_args['error'] ) : ?>
				<p class="rwp-sponsor-area__error" role="alert"><?php echo esc_html( $rwp_args['error'] ); ?></p>
			<?php endif; ?>

			<p class="rwp-sponsor-area__field">
				<label for="rwp-sponsor-email"><?php esc_html_e( 'E-mail usado no apadrinhamento', 'resgata-wp' ); ?></label>
				<input type="email" id="rwp-sponsor-email" name="<?php echo esc_attr( MagicLink::EMAIL_FIELD ); ?>" autocomplete="email" required>
			</p>
			<p class="rwp-sponsor-area__hp" aria-hidden="true">
				<label for="rwp-sponsor-site"><?php esc_html_e( 'Não preencha este campo', 'resgata-wp' ); ?></label>
				<input type="text" id="rwp-sponsor-site" name="<?php echo esc_attr( MagicLink::HONEYPOT ); ?>" tabindex="-1" autocomplete="off">
			</p>
			<p class="rwp-sponsor-area__actions">
				<button type="submit" class="rwp-button wp-element-button"><?php esc_html_e( 'Receber link de acesso', 'resgata-wp' ); ?></button>
			</p>
			<p class="rwp-sponsor-area__note">
				<?php esc_html_e( 'Enviamos um link para o seu e-mail. Não precisa de senha.', 'resgata-wp' ); ?>
			</p>
		</form>
	<?php endif; ?>
</div>
