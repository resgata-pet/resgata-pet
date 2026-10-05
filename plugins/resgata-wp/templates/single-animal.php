<?php
/**
 * Página individual do animal (temas clássicos).
 *
 * Para personalizar, copie para `seu-tema/resgata-wp/single-animal.php`.
 *
 * @package ResgataWP
 */

use ResgataWP\Modules\Animals\Frontend\DetailsRenderer;

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="primary" class="site-main rwp-page">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'rwp-page__article' ); ?>>
			<header class="rwp-page__header">
				<?php the_title( '<h1 class="rwp-page__title">', '</h1>' ); ?>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<div class="rwp-page__photo"><?php the_post_thumbnail( 'large' ); ?></div>
			<?php endif; ?>

			<?php
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML montado por template que escapa cada valor.
			echo DetailsRenderer::render( get_the_ID(), 'facts' );
			?>

			<div class="rwp-page__story entry-content"><?php the_content(); ?></div>

			<?php
			echo DetailsRenderer::render( get_the_ID(), 'health' );
			echo DetailsRenderer::render( get_the_ID(), 'gallery' );
			// phpcs:enable
			?>
		</article>
		<?php
	endwhile;
	?>
</main>
<?php
get_footer();
