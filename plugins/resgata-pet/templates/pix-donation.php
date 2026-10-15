<?php
/**
 * Doação por Pix: QR Code, valores sugeridos, chave e código "copia e cola".
 *
 * Para personalizar, copie para `seu-tema/resgata-pet/pix-donation.php`.
 *
 * O QR Code, os valores e os botões de copiar dependem do script e nascem
 * escondidos (`hidden`); sem ele, ficam a chave e o titular.
 *
 * @package ResgataPet
 *
 * @var array{
 *     wrapper_attributes: string,
 *     pix: array{key: string, type: string, type_label: string, holder: string, code_key: string, code_name: string, code_city: string},
 *     amounts: int[],
 *     title: string,
 *     text: string
 * } $resgata_args
 */

use ResgataPet\Core\Html;
use ResgataPet\Core\Icons;

defined( 'ABSPATH' ) || exit;

$resgata_pix = $resgata_args['pix'];

?>
<section
	<?php echo wp_kses_data( $resgata_args['wrapper_attributes'] ); ?>
	aria-labelledby="resgata-pix-titulo"
	data-resgata-pix
	data-key="<?php echo esc_attr( $resgata_pix['code_key'] ); ?>"
	data-name="<?php echo esc_attr( $resgata_pix['code_name'] ); ?>"
	data-city="<?php echo esc_attr( $resgata_pix['code_city'] ); ?>"
>
	<div class="resgata-pix__intro">
		<h2 class="resgata-pix__title" id="resgata-pix-titulo">
			<?php echo wp_kses( Icons::svg( 'heart' ), Html::svg() ); ?>
			<?php echo esc_html( $resgata_args['title'] ); ?>
		</h2>
		<p class="resgata-pix__text"><?php echo esc_html( $resgata_args['text'] ); ?></p>
	</div>

	<div class="resgata-pix__body">
		<div class="resgata-pix__qr" data-resgata-pix-qr hidden>
			<img class="resgata-pix__image" data-resgata-pix-image alt="<?php esc_attr_e( 'QR Code para doar por Pix', 'resgata-pet' ); ?>" width="220" height="220">
			<p class="resgata-pix__hint"><?php esc_html_e( 'Abra o app do seu banco, escolha Pix e aponte a câmera.', 'resgata-pet' ); ?></p>
		</div>

		<div class="resgata-pix__details">
			<?php if ( $resgata_args['amounts'] ) : ?>
				<fieldset class="resgata-pix__amounts" data-resgata-pix-amounts hidden>
					<legend class="resgata-pix__label"><?php esc_html_e( 'Quanto você quer doar?', 'resgata-pet' ); ?></legend>
					<div class="resgata-pix__options">
						<label class="resgata-pix__option">
							<input type="radio" name="resgata_pix_valor" value="" checked>
							<span><?php esc_html_e( 'Eu escolho no app', 'resgata-pet' ); ?></span>
						</label>
						<?php foreach ( $resgata_args['amounts'] as $resgata_amount ) : ?>
							<label class="resgata-pix__option">
								<input type="radio" name="resgata_pix_valor" value="<?php echo esc_attr( (string) $resgata_amount ); ?>">
								<span>
									<?php
									/* translators: %s: valor em reais, sem centavos. */
									echo esc_html( sprintf( __( 'R$ %s', 'resgata-pet' ), number_format_i18n( $resgata_amount ) ) );
									?>
								</span>
							</label>
						<?php endforeach; ?>
					</div>
				</fieldset>
			<?php endif; ?>

			<dl class="resgata-pix__data">
				<div class="resgata-pix__row">
					<dt class="resgata-pix__label">
						<?php
						echo esc_html(
							'' !== $resgata_pix['type_label']
								/* translators: %s: tipo da chave Pix (CPF, CNPJ, e-mail...). */
								? sprintf( __( 'Chave Pix (%s)', 'resgata-pet' ), $resgata_pix['type_label'] )
								: __( 'Chave Pix', 'resgata-pet' )
						);
						?>
					</dt>
					<dd class="resgata-pix__value">
						<span class="resgata-pix__key"><?php echo esc_html( $resgata_pix['key'] ); ?></span>
						<button type="button" class="resgata-pix__copy" data-resgata-pix-copy="<?php echo esc_attr( $resgata_pix['key'] ); ?>" hidden>
							<?php echo wp_kses( Icons::svg( 'copy' ), Html::svg() ); ?>
							<span data-resgata-pix-copy-label data-done="<?php esc_attr_e( 'Copiado!', 'resgata-pet' ); ?>"><?php esc_html_e( 'Copiar chave', 'resgata-pet' ); ?></span>
						</button>
					</dd>
				</div>
				<div class="resgata-pix__row">
					<dt class="resgata-pix__label"><?php esc_html_e( 'Titular', 'resgata-pet' ); ?></dt>
					<dd class="resgata-pix__value"><?php echo esc_html( $resgata_pix['holder'] ); ?></dd>
				</div>
			</dl>

			<div class="resgata-pix__code" data-resgata-pix-code hidden>
				<label class="resgata-pix__label" for="resgata-pix-codigo"><?php esc_html_e( 'Pix copia e cola', 'resgata-pet' ); ?></label>
				<div class="resgata-pix__code-row">
					<input type="text" id="resgata-pix-codigo" class="resgata-pix__payload" data-resgata-pix-payload readonly>
					<button type="button" class="resgata-button resgata-button--primary wp-element-button resgata-pix__copy-code" data-resgata-pix-copy-code>
						<?php echo wp_kses( Icons::svg( 'copy' ), Html::svg() ); ?>
						<span data-resgata-pix-copy-label data-done="<?php esc_attr_e( 'Copiado!', 'resgata-pet' ); ?>"><?php esc_html_e( 'Copiar código', 'resgata-pet' ); ?></span>
					</button>
				</div>
			</div>

			<p class="resgata-pix__note" role="status" aria-live="polite" data-resgata-pix-status></p>
			<p class="resgata-pix__note">
				<?php
				/* translators: %s: nome do titular da conta. */
				echo esc_html( sprintf( __( 'Antes de confirmar no app, confira se o nome que aparece é %s.', 'resgata-pet' ), $resgata_pix['holder'] ) );
				?>
			</p>
		</div>
	</div>
</section>
<?php
