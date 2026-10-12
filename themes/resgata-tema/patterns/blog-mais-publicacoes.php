<?php
/**
 * Title: Blog: mais publicações
 * Slug: resgata-tema/blog-mais-publicacoes
 * Categories: resgata-tema, query
 * Keywords: blog, notícias, publicações, recentes
 * Block Types: core/query
 * Description: As três publicações mais recentes em cartões, com link para o blog. Para o fim de uma publicação ou de uma página.
 * Viewport Width: 1400
 *
 * @package ResgataTema
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide">
	<!-- wp:group {"style":{"spacing":{"blockGap":"0.35rem"}},"layout":{"type":"default"}} -->
	<div class="wp-block-group">
		<!-- wp:heading {"className":"is-style-text-subtitle"} -->
		<h2 class="wp-block-heading is-style-text-subtitle"><?php esc_html_e( 'Continue lendo', 'resgata-tema' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"textColor":"accent-4"} -->
		<p class="has-accent-4-color has-text-color"><?php esc_html_e( 'Outras histórias e novidades da protetora.', 'resgata-tema' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:query {"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"exclude","inherit":false,"taxQuery":null,"parents":[]},"layout":{"type":"default"}} -->
	<div class="wp-block-query">
		<!-- wp:post-template {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"grid","columnCount":3,"minimumColumnWidth":"17rem"}} -->
			<!-- wp:group {"className":"is-style-resgata-card","style":{"spacing":{"padding":{"top":"0","right":"0","bottom":"0","left":"0"},"blockGap":"0"},"dimensions":{"minHeight":"100%"}},"layout":{"type":"default"}} -->
			<div class="wp-block-group is-style-resgata-card" style="min-height:100%;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
				<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/10","style":{"border":{"radius":{"topLeft":"calc(var(--wp--preset--border-radius--medium) - 1px)","topRight":"calc(var(--wp--preset--border-radius--medium) - 1px)","bottomLeft":"0px","bottomRight":"0px"}}}} /-->

				<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","right":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30"},"blockGap":"0.5rem"}},"layout":{"type":"default"}} -->
				<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)">
					<!-- wp:post-terms {"term":"category"} /-->

					<!-- wp:post-title {"level":3,"isLink":true,"style":{"elements":{"link":{"color":{"text":"var:preset|color|contrast"},":hover":{"color":{"text":"var:preset|color|accent-3"}}}}},"fontSize":"large"} /-->

					<!-- wp:post-excerpt {"excerptLength":22,"textColor":"accent-4","fontSize":"small"} /-->

					<!-- wp:post-date {"textColor":"accent-4","fontSize":"small"} /-->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->
		<!-- /wp:post-template -->
	</div>
	<!-- /wp:query -->
</div>
<!-- /wp:group -->
