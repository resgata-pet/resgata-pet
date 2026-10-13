<?php
/**
 * Banner "Doe por Pix": chamada com o QR Code, o botão de copiar o código e o
 * botão para a página "Como ajudar".
 *
 * Para personalizar, copie para `seu-tema/resgata-wp/donate-banner.php`.
 *
 * O QR Code e o botão de copiar dependem do script e nascem escondidos
 * (`hidden`); sem ele, fica o botão que leva à página "Como ajudar".
 *
 * @package ResgataWP
 *
 * @var array{
 *     wrapper_attributes: string,
 *     url: string,
 *     pix: array{key: string, type: string, type_label: string, holder: string, code_key: string, code_name: string, code_city: string}
 * } $resgata_args
 */

use ResgataWP\Core\Icons;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Icons::svg() devolve SVG fixo do plugin; atributos do wrapper escapados na origem.
?>
<aside
	<?php echo $resgata_args['wrapper_attributes']; ?>
	aria-labelledby="resgata-doe-titulo"
	data-resgata-pix
	data-key="<?php echo esc_attr( $resgata_args['pix']['code_key'] ); ?>"
	data-name="<?php echo esc_attr( $resgata_args['pix']['code_name'] ); ?>"
	data-city="<?php echo esc_attr( $resgata_args['pix']['code_city'] ); ?>"
>
	<span class="resgata-donate__icon"><?php echo Icons::svg( 'heart' ); ?></span>
	<div class="resgata-donate__text">
		<h2 class="resgata-donate__title" id="resgata-doe-titulo"><?php esc_html_e( 'Ajude com qualquer valor', 'resgata-wp' ); ?></h2>
		<p><?php esc_html_e( 'Doe por Pix em poucos segundos. Cada real vira ração, vacina e cuidado para quem foi resgatado.', 'resgata-wp' ); ?></p>
		<div class="resgata-donate__actions">
			<a class="resgata-button resgata-button--primary wp-element-button" href="<?php echo esc_url( $resgata_args['url'] ); ?>">
				<?php esc_html_e( 'Doar por Pix', 'resgata-wp' ); ?>
			</a>
			<button type="button" class="resgata-donate__copy" data-resgata-pix-copy-code hidden>
				<?php echo Icons::svg( 'copy' ); ?>
				<span data-resgata-pix-copy-label data-done="<?php esc_attr_e( 'Copiado!', 'resgata-wp' ); ?>"><?php esc_html_e( 'Copiar código Pix', 'resgata-wp' ); ?></span>
			</button>
		</div>
		<input type="hidden" data-resgata-pix-payload>
		<span class="resgata-donate__status" role="status" aria-live="polite" data-resgata-pix-status></span>
	</div>
	<figure class="resgata-donate__qr" data-resgata-pix-qr hidden>
		<img class="resgata-donate__image" data-resgata-pix-image alt="<?php esc_attr_e( 'QR Code para doar por Pix', 'resgata-wp' ); ?>" width="144" height="144">
		<figcaption><?php esc_html_e( 'Aponte a câmera do app do banco', 'resgata-wp' ); ?></figcaption>
	</figure>
</aside>
<?php
// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
