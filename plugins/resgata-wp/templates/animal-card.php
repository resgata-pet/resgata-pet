<?php
/**
 * Cartão de um animal na listagem.
 *
 * Para personalizar, copie para `seu-tema/resgata-wp/animal-card.php`.
 *
 * @package ResgataWP
 *
 * @var array{animal: ResgataWP\Modules\Animals\Animal} $resgata_args
 */

use ResgataWP\Core\Icons;

defined( 'ABSPATH' ) || exit;

$resgata_animal    = $resgata_args['animal'];
$resgata_permalink = get_permalink( $resgata_animal->id() );
$resgata_name      = get_the_title( $resgata_animal->id() );
$resgata_summary   = $resgata_animal->card_summary();
// No cartão cabem até três temperamentos; os demais aparecem na página do animal.
$resgata_tags = array_slice( $resgata_animal->temperaments(), 0, 3 );

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Icons::svg() devolve SVG fixo do plugin, com a classe já escapada.
?>
<li class="resgata-card resgata-card--<?php echo esc_attr( $resgata_animal->status() ); ?>">
	<div class="resgata-card__media">
		<?php
		if ( has_post_thumbnail( $resgata_animal->id() ) ) {
			echo get_the_post_thumbnail( $resgata_animal->id(), 'medium_large', array( 'alt' => '' ) );
		} else {
			echo '<span class="resgata-card__placeholder">' . Icons::svg( 'paw-print' ) . '</span>';
		}
		?>
		<span class="resgata-badge resgata-badge--<?php echo esc_attr( $resgata_animal->status() ); ?>"><?php echo esc_html( $resgata_animal->status_label() ); ?></span>
	</div>
	<div class="resgata-card__body">
		<?php // O link do nome cobre o cartão inteiro (ver CSS); o "Ver perfil" é só visual. ?>
		<h3 class="resgata-card__name"><a href="<?php echo esc_url( $resgata_permalink ); ?>"><?php echo esc_html( $resgata_name ); ?></a></h3>
		<?php if ( $resgata_summary ) : ?>
			<p class="resgata-card__meta">
				<?php echo Icons::svg( 'paw-print' ); ?>
				<?php echo esc_html( implode( ' • ', $resgata_summary ) ); ?>
			</p>
		<?php endif; ?>
		<?php if ( $resgata_tags ) : ?>
			<ul class="resgata-tags" aria-label="<?php esc_attr_e( 'Temperamento', 'resgata-wp' ); ?>">
				<?php foreach ( $resgata_tags as $resgata_tag ) : ?>
					<li class="resgata-tag"><?php echo esc_html( $resgata_tag ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<span class="resgata-card__cta" aria-hidden="true"><?php esc_html_e( 'Ver perfil', 'resgata-wp' ); ?></span>
	</div>
</li>
<?php
// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
