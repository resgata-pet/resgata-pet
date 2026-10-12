<?php
/**
 * Title: Blog: lista de publicações
 * Slug: resgata-tema/blog-lista
 * Categories: resgata-tema, query
 * Keywords: blog, notícias, publicações, lista
 * Block Types: core/query
 * Description: Publicações em cartões (foto, categoria, título, resumo e data), em até três colunas, com paginação. Mostra as publicações da página em que está (blog, categoria, etiqueta, autor ou data).
 * Viewport Width: 1400
 *
 * @package ResgataTema
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:query {"query":{"perPage":9,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":true,"taxQuery":null,"parents":[]},"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-query alignwide">
	<!-- wp:post-template {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"grid","columnCount":3,"minimumColumnWidth":"17rem"}} -->
		<!-- wp:group {"className":"is-style-resgata-card","style":{"spacing":{"padding":{"top":"0","right":"0","bottom":"0","left":"0"},"blockGap":"0"},"dimensions":{"minHeight":"100%"}},"layout":{"type":"default"}} -->
		<div class="wp-block-group is-style-resgata-card" style="min-height:100%;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
			<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/10","style":{"border":{"radius":{"topLeft":"calc(var(--wp--preset--border-radius--medium) - 1px)","topRight":"calc(var(--wp--preset--border-radius--medium) - 1px)","bottomLeft":"0px","bottomRight":"0px"}}}} /-->

			<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","right":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30"},"blockGap":"0.5rem"}},"layout":{"type":"default"}} -->
			<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)">
				<!-- wp:post-terms {"term":"category"} /-->

				<!-- wp:post-title {"level":2,"isLink":true,"style":{"elements":{"link":{"color":{"text":"var:preset|color|contrast"},":hover":{"color":{"text":"var:preset|color|accent-3"}}}}},"fontSize":"large"} /-->

				<!-- wp:post-excerpt {"excerptLength":22,"textColor":"accent-4","fontSize":"small"} /-->

				<!-- wp:post-date {"textColor":"accent-4","fontSize":"small"} /-->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->
	<!-- /wp:post-template -->

	<!-- wp:query-no-results -->
		<!-- wp:group {"className":"is-style-resgata-art-sleeping-cat","layout":{"type":"default"}} -->
		<div class="wp-block-group is-style-resgata-art-sleeping-cat">
			<!-- wp:paragraph -->
			<p><?php esc_html_e( 'Ainda não há publicações por aqui. Volte em breve!', 'resgata-tema' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	<!-- /wp:query-no-results -->

	<!-- wp:query-pagination {"paginationArrow":"arrow","style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}},"layout":{"type":"flex","justifyContent":"center"}} -->
		<!-- wp:query-pagination-previous /-->

		<!-- wp:query-pagination-numbers /-->

		<!-- wp:query-pagination-next /-->
	<!-- /wp:query-pagination -->
</div>
<!-- /wp:query -->
