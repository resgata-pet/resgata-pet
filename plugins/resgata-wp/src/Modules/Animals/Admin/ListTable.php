<?php
/**
 * Lista de animais no painel.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Animals\Admin;

use ResgataWP\Modules\Animals\Animal;
use ResgataWP\Modules\Animals\Meta;
use ResgataWP\Modules\Animals\Options;
use ResgataWP\Modules\Animals\PostType;

defined( 'ABSPATH' ) || exit;

/**
 * Colunas e filtro por situação na lista "Animais".
 */
final class ListTable {

	private const FILTER = 'rwp_status';

	/**
	 * Registra os hooks.
	 */
	public function register(): void {
		add_filter( 'manage_' . PostType::SLUG . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . PostType::SLUG . '_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( $this, 'render_filter' ) );
		add_action( 'pre_get_posts', array( $this, 'apply_filter' ) );
		add_action( 'admin_head-edit.php', array( $this, 'styles' ) );
	}

	/**
	 * Define as colunas.
	 *
	 * @param array<string, string> $columns Colunas padrão.
	 * @return array<string, string>
	 */
	public function columns( array $columns ): array {
		$new = array();

		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$new['rwp_photo']  = __( 'Foto', 'resgata-wp' );
				$new['title']      = __( 'Nome', 'resgata-wp' );
				$new['rwp_status'] = __( 'Situação', 'resgata-wp' );
				continue;
			}
			if ( 'date' === $key ) {
				$new['rwp_profile'] = __( 'Sexo e idade', 'resgata-wp' );
			}
			$new[ $key ] = $label;
		}

		return $new;
	}

	/**
	 * Conteúdo das colunas próprias.
	 *
	 * @param string $column  Coluna.
	 * @param int    $post_id ID do animal.
	 */
	public function render_column( string $column, int $post_id ): void {
		$animal = new Animal( $post_id );

		switch ( $column ) {
			case 'rwp_photo':
				echo get_the_post_thumbnail( $post_id, array( 48, 48 ) ) ? get_the_post_thumbnail( $post_id, array( 48, 48 ) ) : '<span aria-hidden="true">—</span>';
				break;

			case 'rwp_status':
				printf(
					'<span class="rwp-status rwp-status--%s">%s</span>',
					esc_attr( $animal->status() ),
					esc_html( $animal->status_label() )
				);
				break;

			case 'rwp_profile':
				$parts = array_filter( array( $animal->sex_label(), $animal->age_label() ) );
				echo $parts ? esc_html( implode( ' · ', $parts ) ) : '<span aria-hidden="true">—</span>';
				break;
		}
	}

	/**
	 * Situação escolhida no filtro, já validada.
	 */
	private function current_filter(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Filtro de leitura da lista, sem alteração de dados.
		$value = isset( $_GET[ self::FILTER ] ) ? sanitize_key( wp_unslash( $_GET[ self::FILTER ] ) ) : '';

		return isset( Options::statuses()[ $value ] ) ? $value : '';
	}

	/**
	 * Mostra o filtro por situação.
	 *
	 * @param string $post_type Post type da lista.
	 */
	public function render_filter( string $post_type ): void {
		if ( PostType::SLUG !== $post_type ) {
			return;
		}

		$current = $this->current_filter();

		printf( '<label class="screen-reader-text" for="rwp-filter-status">%s</label>', esc_html__( 'Filtrar por situação', 'resgata-wp' ) );
		printf( '<select id="rwp-filter-status" name="%s">', esc_attr( self::FILTER ) );
		printf( '<option value="">%s</option>', esc_html__( 'Todas as situações', 'resgata-wp' ) );
		foreach ( Options::statuses() as $value => $label ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $value ), selected( $current, $value, false ), esc_html( $label ) );
		}
		echo '</select>';
	}

	/**
	 * Aplica o filtro por situação na consulta principal da lista.
	 *
	 * @param \WP_Query $query Consulta.
	 */
	public function apply_filter( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() || PostType::SLUG !== $query->get( 'post_type' ) ) {
			return;
		}

		$status = $this->current_filter();
		if ( '' === $status ) {
			return;
		}

		$meta_query   = (array) $query->get( 'meta_query' );
		$meta_query[] = array(
			'key'   => Meta::STATUS,
			'value' => $status,
		);
		$query->set( 'meta_query', $meta_query );
	}

	/**
	 * Estilos mínimos das colunas.
	 */
	public function styles(): void {
		$screen = get_current_screen();
		if ( ! $screen || PostType::SLUG !== $screen->post_type ) {
			return;
		}
		?>
		<style>
			.column-rwp_photo { width: 56px; }
			.column-rwp_photo img { border-radius: 4px; display: block; object-fit: cover; }
			.column-rwp_status { width: 14em; }
			.rwp-status { background: #f0f0f1; border-radius: 999px; display: inline-block; padding: 2px 10px; }
			.rwp-status--available { background: #d7f5dd; color: #0a4b1c; }
			.rwp-status--reserved { background: #fff1c2; color: #5c4400; }
			.rwp-status--adopted { background: #dbe9ff; color: #0b3a7e; }
			.rwp-status--treatment { background: #ffe2d1; color: #7a2e00; }
		</style>
		<?php
	}
}
