<?php
/**
 * Faixa de números da protetora.
 *
 * Para personalizar, copie para `seu-tema/resgata-wp/impact.php`.
 *
 * @package ResgataWP
 *
 * @var array{
 *     wrapper_attributes: string,
 *     items: array<int, array{icon: string, value: int, label: string, tone: string}>
 * } $resgata_args
 */

use ResgataWP\Core\Icons;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Icons::svg() devolve SVG fixo do plugin; atributos do wrapper escapados na origem.
?>
<section <?php echo $resgata_args['wrapper_attributes']; ?> aria-label="<?php esc_attr_e( 'Nossos números', 'resgata-wp' ); ?>">
	<dl class="resgata-impact__list">
		<?php foreach ( $resgata_args['items'] as $resgata_item ) : ?>
			<div class="resgata-impact__item resgata-impact__item--<?php echo esc_attr( sanitize_html_class( (string) ( $resgata_item['tone'] ?? 'green' ) ) ); ?>">
				<dt class="resgata-impact__label"><?php echo esc_html( $resgata_item['label'] ); ?></dt>
				<dd class="resgata-impact__value">
					<span class="resgata-impact__icon"><?php echo Icons::svg( (string) ( $resgata_item['icon'] ?? 'paw-print' ) ); ?></span>
					<?php echo esc_html( number_format_i18n( (int) $resgata_item['value'] ) ); ?>
				</dd>
			</div>
		<?php endforeach; ?>
	</dl>
</section>
<?php
// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
