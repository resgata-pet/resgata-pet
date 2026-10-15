<?php
/**
 * Listagem de animais (temas clássicos).
 *
 * Para personalizar, copie para `seu-tema/resgata-pet/archive-animal.php`.
 *
 * @package ResgataPet
 */

use ResgataPet\Core\Html;
use ResgataPet\Modules\Animals\Frontend\ListRenderer;

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="primary" class="site-main resgata-page">
	<header class="resgata-page__header">
		<h1 class="resgata-page__title"><?php esc_html_e( 'Animais para adoção', 'resgata-pet' ); ?></h1>
		<p class="resgata-page__lead"><?php esc_html_e( 'Conheça quem está esperando por um lar.', 'resgata-pet' ); ?></p>
	</header>
	<?php
	echo wp_kses( ListRenderer::render( array( 'columns' => 4 ) ), Html::allowed() );
	?>
</main>
<?php
get_footer();
