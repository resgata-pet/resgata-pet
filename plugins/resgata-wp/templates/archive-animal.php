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
		<h1 class="rwp-page__title"><?php post_type_archive_title(); ?></h1>
	</header>
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML montado por template que escapa cada valor.
	echo ListRenderer::render();
	?>
</main>
<?php
get_footer();
