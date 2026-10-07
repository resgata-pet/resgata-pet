<?php
/**
 * Página individual do animal (temas clássicos).
 *
 * Para personalizar, copie para `seu-tema/resgata-wp/single-animal.php`.
 *
 * @package ResgataWP
 */

use ResgataWP\Core\Modules;
use ResgataWP\Modules\Adoptions\Form as AdoptionForm;
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
			<?php
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML montado por template que escapa cada valor.
			echo DetailsRenderer::render( get_the_ID() );

			if ( Modules::is_enabled( 'adoptions' ) ) {
				echo AdoptionForm::render( get_the_ID() );
			}
			// phpcs:enable
			?>
		</article>
		<?php
	endwhile;
	?>
</main>
<?php
get_footer();
