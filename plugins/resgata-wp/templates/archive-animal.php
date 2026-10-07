<?php
/**
 * Listagem de animais (temas clássicos).
 *
 * Para personalizar, copie para `seu-tema/resgata-wp/archive-animal.php`.
 *
 * @package ResgataWP
 */

use ResgataWP\Modules\Animals\Frontend\ListRenderer;

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="primary" class="site-main rwp-page">
	<header class="rwp-page__header">
		<h1 class="rwp-page__title"><?php esc_html_e( 'Animais para adoção', 'resgata-wp' ); ?></h1>
		<p class="rwp-page__lead"><?php esc_html_e( 'Conheça quem está esperando por um lar.', 'resgata-wp' ); ?></p>
	</header>
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML montado por template que escapa cada valor.
	echo ListRenderer::render( array( 'columns' => 4 ) );
	?>
</main>
<?php
get_footer();
