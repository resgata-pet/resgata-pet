<?php
/**
 * API do painel: resumo da tela inicial.
 *
 * @package ResgataPet
 */

namespace ResgataPet\Modules\Panel\Rest;

use ResgataPet\Core\Capabilities;
use ResgataPet\Core\Modules;
use ResgataPet\Modules\Adoptions\PostType as AdoptionPostType;
use ResgataPet\Modules\Animals\Meta;
use ResgataPet\Modules\Animals\Options;
use ResgataPet\Modules\Animals\PostType as AnimalPostType;
use ResgataPet\Modules\Sponsorship\Plans;

defined( 'ABSPATH' ) || exit;

/**
 * Rota `resgata-pet/v1/dashboard`: os números dos cartões da tela Início.
 *
 * Cada parte só vem para quem pode ver aquela área do painel (e com o módulo
 * ligado); o resto chega como `null`. São só contagens: nenhum dado pessoal.
 */
final class DashboardController extends BaseController {

	/**
	 * Registra a rota.
	 */
	public function register_routes(): void {
		$this->route( '/dashboard', \WP_REST_Server::READABLE, array( $this, 'get_summary' ), '__return_true' );
	}

	/**
	 * GET /dashboard.
	 */
	public function get_summary(): \WP_REST_Response {
		return new \WP_REST_Response(
			array(
				'animals'     => $this->animals(),
				'adoptions'   => $this->adoptions(),
				'sponsorship' => $this->sponsorship(),
			)
		);
	}

	/**
	 * Animais por situação (publicados e rascunhos) e quantos ainda são rascunho.
	 *
	 * Quem não pode editar animais de outras pessoas só conta os seus.
	 *
	 * @return array{total: int, drafts: int, by_status: array<string, int>}|null
	 */
	private function animals(): ?array {
		if ( ! Modules::is_enabled( 'animals' ) || ! current_user_can( 'edit_resgata_animals' ) ) {
			return null;
		}

		global $wpdb;

		// 0 = todos os autores; quem não pode editar animais de outras pessoas só conta os seus.
		$author = current_user_can( 'edit_others_resgata_animals' ) ? 0 : get_current_user_id();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Contagem agrupada, só para a tela Início do painel.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT pm.meta_value AS status, p.post_status, COUNT(*) AS total
				FROM %i p LEFT JOIN %i pm ON pm.post_id = p.ID AND pm.meta_key = %s
				WHERE p.post_type = %s AND p.post_status IN ( %s, %s ) AND ( %d = 0 OR p.post_author = %d )
				GROUP BY pm.meta_value, p.post_status',
				$wpdb->posts,
				$wpdb->postmeta,
				Meta::STATUS,
				AnimalPostType::SLUG,
				'publish',
				'draft',
				$author,
				$author
			),
			ARRAY_A
		);

		$by_status = array_fill_keys( array_keys( Options::statuses() ), 0 );
		$total     = 0;
		$drafts    = 0;

		foreach ( (array) $rows as $row ) {
			$count  = (int) $row['total'];
			$status = isset( $by_status[ (string) $row['status'] ] ) ? (string) $row['status'] : Options::STATUS_DEFAULT;

			$by_status[ $status ] += $count;
			$total                += $count;

			if ( 'draft' === $row['post_status'] ) {
				$drafts += $count;
			}
		}

		return array(
			'total'     => $total,
			'drafts'    => $drafts,
			'by_status' => $by_status,
		);
	}

	/**
	 * Pedidos de adoção ainda não vistos.
	 *
	 * @return array{new: int}|null
	 */
	private function adoptions(): ?array {
		if ( ! Modules::is_enabled( 'adoptions' ) || ! current_user_can( Capabilities::ADOPTIONS ) ) {
			return null;
		}

		$query = new \WP_Query(
			array(
				'post_type'              => AdoptionPostType::SLUG,
				'post_status'            => 'private',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Volume pequeno (pedidos de uma protetora).
				'meta_key'               => AdoptionPostType::META_STATUS,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_value'             => AdoptionPostType::STATUS_DEFAULT,
			)
		);

		return array( 'new' => (int) $query->found_posts );
	}

	/**
	 * Padrinhos: quantos pagam pela Stripe e o número mostrado no site.
	 *
	 * @return array{active: int, total: int}|null
	 */
	private function sponsorship(): ?array {
		if ( ! Modules::is_enabled( 'sponsorship' ) || ! current_user_can( Capabilities::SPONSORSHIP ) ) {
			return null;
		}

		$counter = Plans::counter();

		return array(
			'active' => $counter['active'],
			'total'  => $counter['total'],
		);
	}
}
