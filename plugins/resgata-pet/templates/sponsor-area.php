<?php
/**
 * Área do padrinho: pedido do link de acesso, confirmação do link e resumo do apadrinhamento.
 *
 * Para personalizar, copie para `seu-tema/resgata-pet/sponsor-area.php`.
 *
 * @package ResgataPet
 *
 * @var array{wrapper_attributes: string, token: string, sent: bool, error: string, summary: array<string, mixed>|null} $resgata_args
 */

use ResgataPet\Modules\Sponsorship\MagicLink;

defined( 'ABSPATH' ) || exit;

$resgata_summary = $resgata_args['summary'] ?? null;
?>
<div <?php echo $resgata_args['wrapper_attributes']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Já escapado na origem. ?>>
	<?php if ( $resgata_summary ) : ?>
		<div class="resgata-sponsor-area__summary">
			<p class="resgata-sponsor-area__hello">
				<?php
				if ( '' !== $resgata_summary['first_name'] ) {
					/* translators: %s: primeiro nome do padrinho. */
					echo esc_html( sprintf( __( 'Olá, %s!', 'resgata-pet' ), $resgata_summary['first_name'] ) );
				} else {
					esc_html_e( 'Olá!', 'resgata-pet' );
				}
				?>
			</p>

			<?php if ( '' !== $resgata_summary['total'] ) : ?>
				<p class="resgata-sponsor-area__total">
					<span class="resgata-sponsor-area__total-label">
						<?php
						if ( '' !== $resgata_summary['total_since'] ) {
							/* translators: %s: data. */
							echo esc_html( sprintf( __( 'Você doou desde %s', 'resgata-pet' ), $resgata_summary['total_since'] ) );
						} else {
							esc_html_e( 'Você já doou', 'resgata-pet' );
						}
						?>
					</span>
					<strong class="resgata-sponsor-area__total-value"><?php echo esc_html( $resgata_summary['total'] ); ?></strong>
					<span class="resgata-sponsor-area__total-note">
						<?php
						/* translators: %s: número de pagamentos. */
						echo esc_html( sprintf( _n( 'em %s pagamento. Muito obrigado!', 'em %s pagamentos. Muito obrigado!', $resgata_summary['payments'], 'resgata-pet' ), number_format_i18n( $resgata_summary['payments'] ) ) );
						?>
					</span>
				</p>
			<?php endif; ?>

			<?php if ( $resgata_summary['payment_failed'] ) : ?>
				<p class="resgata-sponsor-area__error" role="alert"><?php esc_html_e( 'A última cobrança foi recusada pelo cartão. Toque em "Alterar ou cancelar" para trocar o cartão.', 'resgata-pet' ); ?></p>
			<?php endif; ?>

			<dl class="resgata-sponsor-area__details">
				<div>
					<dt><?php esc_html_e( 'Plano', 'resgata-pet' ); ?></dt>
					<dd><?php echo esc_html( $resgata_summary['plan'] ); ?></dd>
				</div>
				<?php if ( '' !== $resgata_summary['status'] ) : ?>
					<div>
						<dt><?php esc_html_e( 'Situação', 'resgata-pet' ); ?></dt>
						<dd><?php echo esc_html( $resgata_summary['status'] ); ?></dd>
					</div>
				<?php endif; ?>
				<div>
					<dt><?php esc_html_e( 'Padrinho desde', 'resgata-pet' ); ?></dt>
					<dd><?php echo esc_html( $resgata_summary['since'] ); ?></dd>
				</div>
				<?php if ( '' !== $resgata_summary['last_paid'] ) : ?>
					<div>
						<dt><?php esc_html_e( 'Último pagamento', 'resgata-pet' ); ?></dt>
						<dd><?php echo esc_html( $resgata_summary['last_paid'] ); ?></dd>
					</div>
				<?php endif; ?>
				<?php if ( '' !== $resgata_summary['ends_at'] ) : ?>
					<div>
						<dt><?php esc_html_e( 'Cancelamento pedido', 'resgata-pet' ); ?></dt>
						<dd>
							<?php
							/* translators: %s: data. */
							echo esc_html( sprintf( __( 'Ativo até %s, sem novas cobranças', 'resgata-pet' ), $resgata_summary['ends_at'] ) );
							?>
						</dd>
					</div>
				<?php elseif ( '' !== $resgata_summary['next_charge'] ) : ?>
					<div>
						<dt><?php esc_html_e( 'Próxima cobrança', 'resgata-pet' ); ?></dt>
						<dd><?php echo esc_html( $resgata_summary['next_charge'] ); ?></dd>
					</div>
				<?php endif; ?>
			</dl>

			<?php if ( '' !== $resgata_args['error'] ) : ?>
				<p class="resgata-sponsor-area__error" role="alert"><?php echo esc_html( $resgata_args['error'] ); ?></p>
			<?php endif; ?>

			<form class="resgata-sponsor-area__form" method="post" action="#resgata-padrinho">
				<?php wp_nonce_field( MagicLink::NONCE_ACTION, MagicLink::NONCE_NAME ); ?>
				<input type="hidden" name="<?php echo esc_attr( MagicLink::TOKEN_FIELD ); ?>" value="<?php echo esc_attr( $resgata_summary['token'] ); ?>">
				<input type="hidden" name="<?php echo esc_attr( MagicLink::PORTAL_FIELD ); ?>" value="1">
				<p class="resgata-sponsor-area__actions">
					<button type="submit" class="resgata-button wp-element-button">
						<?php
						if ( $resgata_summary['active'] ) {
							esc_html_e( 'Alterar ou cancelar', 'resgata-pet' );
						} else {
							esc_html_e( 'Ver meus pagamentos', 'resgata-pet' );
						}
						?>
					</button>
				</p>
				<p class="resgata-sponsor-area__note">
					<?php esc_html_e( 'Você vai para a página segura da Stripe. Lá dá para trocar o cartão, mudar o valor, ver os recibos e cancelar.', 'resgata-pet' ); ?>
				</p>
			</form>
		</div>
	<?php elseif ( '' !== $resgata_args['token'] ) : ?>
		<form class="resgata-sponsor-area__form" method="post" action="#resgata-padrinho">
			<?php wp_nonce_field( MagicLink::NONCE_ACTION, MagicLink::NONCE_NAME ); ?>
			<input type="hidden" name="<?php echo esc_attr( MagicLink::TOKEN_FIELD ); ?>" value="<?php echo esc_attr( $resgata_args['token'] ); ?>">
			<p><?php esc_html_e( 'Tudo certo! Toque no botão para ver quanto você já doou, trocar o cartão, mudar o valor ou cancelar.', 'resgata-pet' ); ?></p>
			<p class="resgata-sponsor-area__actions">
				<button type="submit" class="resgata-button wp-element-button"><?php esc_html_e( 'Abrir meu apadrinhamento', 'resgata-pet' ); ?></button>
			</p>
		</form>
	<?php elseif ( $resgata_args['sent'] && '' === $resgata_args['error'] ) : ?>
		<div class="resgata-sponsor-area__success" role="status">
			<p><?php esc_html_e( 'Se este e-mail for de um padrinho, o link de acesso chega em alguns minutos. Confira também a caixa de spam. O link vale por 30 minutos.', 'resgata-pet' ); ?></p>
		</div>
	<?php else : ?>
		<form class="resgata-sponsor-area__form" method="post" action="#resgata-padrinho">
			<?php wp_nonce_field( MagicLink::NONCE_ACTION, MagicLink::NONCE_NAME ); ?>

			<?php if ( '' !== $resgata_args['error'] ) : ?>
				<p class="resgata-sponsor-area__error" role="alert"><?php echo esc_html( $resgata_args['error'] ); ?></p>
			<?php endif; ?>

			<p class="resgata-sponsor-area__field">
				<label for="resgata-sponsor-email"><?php esc_html_e( 'E-mail usado no apadrinhamento', 'resgata-pet' ); ?></label>
				<input type="email" id="resgata-sponsor-email" name="<?php echo esc_attr( MagicLink::EMAIL_FIELD ); ?>" autocomplete="email" required>
			</p>
			<p class="resgata-sponsor-area__hp" aria-hidden="true">
				<label for="resgata-sponsor-site"><?php esc_html_e( 'Não preencha este campo', 'resgata-pet' ); ?></label>
				<input type="text" id="resgata-sponsor-site" name="<?php echo esc_attr( MagicLink::HONEYPOT ); ?>" tabindex="-1" autocomplete="off">
			</p>
			<p class="resgata-sponsor-area__actions">
				<button type="submit" class="resgata-button wp-element-button"><?php esc_html_e( 'Receber link de acesso', 'resgata-pet' ); ?></button>
			</p>
			<p class="resgata-sponsor-area__note">
				<?php esc_html_e( 'Enviamos um link para o seu e-mail. Não precisa de senha.', 'resgata-pet' ); ?>
			</p>
		</form>
	<?php endif; ?>
</div>
