<?php
/**
 * Doação por Pix: QR Code, valores sugeridos, chave e código "copia e cola".
 *
 * Para personalizar, copie para `seu-tema/resgata-wp/pix-donation.php`.
 *
 * O QR Code, os valores e os botões de copiar dependem do script e nascem
 * escondidos (`hidden`); sem ele, ficam a chave e o titular.
 *
 * @package ResgataWP
 *
 * @var array{
 *     wrapper_attributes: string,
 *     pix: array{key: string, type: string, type_label: string, holder: string, code_key: string, code_name: string, code_city: string},
 *     amounts: int[],
 *     title: string,
 *     text: string
 * } $rwp_args
 */

use ResgataWP\Core\Icons;

defined( 'ABSPATH' ) || exit;

$rwp_pix = $rwp_args['pix'];

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Icons::svg() devolve SVG fixo do plugin; atributos do wrapper escapados na origem.
?>
<section
	<?php echo $rwp_args['wrapper_attributes']; ?>
	aria-labelledby="rwp-pix-titulo"
	data-rwp-pix
	data-key="<?php echo esc_attr( $rwp_pix['code_key'] ); ?>"
	data-name="<?php echo esc_attr( $rwp_pix['code_name'] ); ?>"
	data-city="<?php echo esc_attr( $rwp_pix['code_city'] ); ?>"
>
	<div class="rwp-pix__intro">
		<h2 class="rwp-pix__title" id="rwp-pix-titulo">
			<?php echo Icons::svg( 'heart' ); ?>
			<?php echo esc_html( $rwp_args['title'] ); ?>
		</h2>
		<p class="rwp-pix__text"><?php echo esc_html( $rwp_args['text'] ); ?></p>
	</div>

	<div class="rwp-pix__body">
		<div class="rwp-pix__qr" data-rwp-pix-qr hidden>
			<img class="rwp-pix__image" data-rwp-pix-image alt="<?php esc_attr_e( 'QR Code para doar por Pix', 'resgata-wp' ); ?>" width="220" height="220">
			<p class="rwp-pix__hint"><?php esc_html_e( 'Abra o app do seu banco, escolha Pix e aponte a câmera.', 'resgata-wp' ); ?></p>
		</div>

		<div class="rwp-pix__details">
			<?php if ( $rwp_args['amounts'] ) : ?>
				<fieldset class="rwp-pix__amounts" data-rwp-pix-amounts hidden>
					<legend class="rwp-pix__label"><?php esc_html_e( 'Quanto você quer doar?', 'resgata-wp' ); ?></legend>
					<div class="rwp-pix__options">
						<label class="rwp-pix__option">
							<input type="radio" name="rwp_pix_valor" value="" checked>
							<span><?php esc_html_e( 'Eu escolho no app', 'resgata-wp' ); ?></span>
						</label>
						<?php foreach ( $rwp_args['amounts'] as $rwp_amount ) : ?>
							<label class="rwp-pix__option">
								<input type="radio" name="rwp_pix_valor" value="<?php echo esc_attr( (string) $rwp_amount ); ?>">
								<span>
									<?php
									/* translators: %s: valor em reais, sem centavos. */
									echo esc_html( sprintf( __( 'R$ %s', 'resgata-wp' ), number_format_i18n( $rwp_amount ) ) );
									?>
								</span>
							</label>
						<?php endforeach; ?>
					</div>
				</fieldset>
			<?php endif; ?>

			<dl class="rwp-pix__data">
				<div class="rwp-pix__row">
					<dt class="rwp-pix__label">
						<?php
						echo esc_html(
							'' !== $rwp_pix['type_label']
								/* translators: %s: tipo da chave Pix (CPF, CNPJ, e-mail...). */
								? sprintf( __( 'Chave Pix (%s)', 'resgata-wp' ), $rwp_pix['type_label'] )
								: __( 'Chave Pix', 'resgata-wp' )
						);
						?>
					</dt>
					<dd class="rwp-pix__value">
						<span class="rwp-pix__key"><?php echo esc_html( $rwp_pix['key'] ); ?></span>
						<button type="button" class="rwp-pix__copy" data-rwp-pix-copy="<?php echo esc_attr( $rwp_pix['key'] ); ?>" hidden>
							<?php echo Icons::svg( 'copy' ); ?>
							<span data-rwp-pix-copy-label data-done="<?php esc_attr_e( 'Copiado!', 'resgata-wp' ); ?>"><?php esc_html_e( 'Copiar chave', 'resgata-wp' ); ?></span>
						</button>
					</dd>
				</div>
				<div class="rwp-pix__row">
					<dt class="rwp-pix__label"><?php esc_html_e( 'Titular', 'resgata-wp' ); ?></dt>
					<dd class="rwp-pix__value"><?php echo esc_html( $rwp_pix['holder'] ); ?></dd>
				</div>
			</dl>

			<div class="rwp-pix__code" data-rwp-pix-code hidden>
				<label class="rwp-pix__label" for="rwp-pix-codigo"><?php esc_html_e( 'Pix copia e cola', 'resgata-wp' ); ?></label>
				<div class="rwp-pix__code-row">
					<input type="text" id="rwp-pix-codigo" class="rwp-pix__payload" data-rwp-pix-payload readonly>
					<button type="button" class="rwp-button rwp-button--primary wp-element-button rwp-pix__copy-code" data-rwp-pix-copy-code>
						<?php echo Icons::svg( 'copy' ); ?>
						<span data-rwp-pix-copy-label data-done="<?php esc_attr_e( 'Copiado!', 'resgata-wp' ); ?>"><?php esc_html_e( 'Copiar código', 'resgata-wp' ); ?></span>
					</button>
				</div>
			</div>

			<p class="rwp-pix__note" role="status" aria-live="polite" data-rwp-pix-status></p>
			<p class="rwp-pix__note">
				<?php
				/* translators: %s: nome do titular da conta. */
				echo esc_html( sprintf( __( 'Antes de confirmar no app, confira se o nome que aparece é %s.', 'resgata-wp' ), $rwp_pix['holder'] ) );
				?>
			</p>
		</div>
	</div>
</section>
<?php
// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
