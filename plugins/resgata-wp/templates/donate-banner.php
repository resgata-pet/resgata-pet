<?php
/**
 * Banner "Doe por Pix": chamada com botão para a página "Como ajudar".
 *
 * Para personalizar, copie para `seu-tema/resgata-wp/donate-banner.php`.
 *
 * @package ResgataWP
 *
 * @var array{wrapper_attributes: string, url: string} $rwp_args
 */

use ResgataWP\Core\Icons;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Icons::svg() devolve SVG fixo do plugin; atributos do wrapper escapados na origem.
?>
<aside <?php echo $rwp_args['wrapper_attributes']; ?> aria-labelledby="rwp-doe-titulo">
	<span class="rwp-donate__icon"><?php echo Icons::svg( 'heart' ); ?></span>
	<div class="rwp-donate__text">
		<h2 class="rwp-donate__title" id="rwp-doe-titulo"><?php esc_html_e( 'Ajude com qualquer valor', 'resgata-wp' ); ?></h2>
		<p><?php esc_html_e( 'Doe por Pix em poucos segundos. Cada real vira ração, vacina e cuidado para quem foi resgatado.', 'resgata-wp' ); ?></p>
	</div>
	<a class="rwp-button rwp-button--primary wp-element-button" href="<?php echo esc_url( $rwp_args['url'] ); ?>">
		<?php esc_html_e( 'Doar por Pix', 'resgata-wp' ); ?>
	</a>
</aside>
<?php
// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
