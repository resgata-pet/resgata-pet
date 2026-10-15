<?php
/**
 * Página individual do animal (temas clássicos).
 *
 * Para personalizar, copie para `seu-tema/resgata-pet/single-animal.php`.
 *
 * @package ResgataPet
 */

use ResgataPet\Core\Html;
use ResgataPet\Core\Modules;
use ResgataPet\Modules\Adoptions\Form as AdoptionForm;
use ResgataPet\Modules\Animals\Frontend\DetailsRenderer;

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="primary" class="site-main resgata-page">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'resgata-page__article' ); ?>>
			<?php
			echo wp_kses( DetailsRenderer::render( get_the_ID() ), Html::allowed() );

			if ( Modules::is_enabled( 'adoptions' ) ) {
				echo wp_kses( AdoptionForm::render( get_the_ID() ), Html::allowed() );
			}
			?>
		</article>
		<?php
	endwhile;
	?>
</main>
<?php
get_footer();
