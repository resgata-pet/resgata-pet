<?php
/**
 * Comando WP-CLI para gerar animais de exemplo.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Animals\CLI;

use ResgataWP\Modules\Animals\Animal;
use ResgataWP\Modules\Animals\Meta;
use ResgataWP\Modules\Animals\PostType;
use ResgataWP\Modules\Animals\Taxonomies;

defined( 'ABSPATH' ) || exit;

/**
 * Gera (ou remove) animais de exemplo para desenvolvimento e demonstração.
 */
final class SeedCommand {

	/**
	 * Marca os posts e as imagens criados por este comando.
	 */
	private const FLAG = '_rwp_seed';

	/**
	 * Gera animais de exemplo.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<quantidade>]
	 * : Quantos animais criar.
	 * ---
	 * default: 12
	 * ---
	 *
	 * [--skip-images]
	 * : Não gera as imagens de exemplo.
	 *
	 * [--delete]
	 * : Remove os animais (e as imagens) criados por este comando, em vez de criar.
	 *
	 * ## EXAMPLES
	 *
	 *     wp resgata seed-animals --count=12
	 *     wp resgata seed-animals --delete
	 *
	 * @param string[]              $args       Argumentos posicionais.
	 * @param array<string, string> $assoc_args Argumentos nomeados.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'delete', false ) ) {
			$this->delete();
			return;
		}

		$count       = max( 1, min( 200, absint( $assoc_args['count'] ?? 12 ) ) );
		$with_images = ! \WP_CLI\Utils\get_flag_value( $assoc_args, 'skip-images', false ) && function_exists( 'imagecreatetruecolor' );

		Taxonomies::ensure_default_terms();

		$progress = \WP_CLI\Utils\make_progress_bar( 'Criando animais de exemplo', $count );

		for ( $i = 0; $i < $count; $i++ ) {
			$this->create_animal( $i, $with_images );
			$progress->tick();
		}

		$progress->finish();
		\WP_CLI::success( sprintf( '%d animais de exemplo criados.', $count ) );
	}

	/**
	 * Dados de exemplo.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function samples(): array {
		return array(
			array( 'Mingau', 'gato', 'male', 'small', 'Branco', array( 'Dócil', 'Carinhoso(a)' ) ),
			array( 'Pipoca', 'gato', 'female', 'small', 'Tricolor', array( 'Brincalhão(ona)', 'Curioso(a)' ) ),
			array( 'Thor', 'cao', 'male', 'large', 'Caramelo', array( 'Protetor(a)', 'Brincalhão(ona)' ) ),
			array( 'Luna', 'gato', 'female', 'small', 'Preta', array( 'Tímido(a)', 'Carinhoso(a)' ) ),
			array( 'Paçoca', 'cao', 'male', 'medium', 'Caramelo', array( 'Dócil', 'Sociável' ) ),
			array( 'Frida', 'gato', 'female', 'small', 'Tigrada', array( 'Independente' ) ),
			array( 'Bolinha', 'cao', 'female', 'small', 'Preto e branco', array( 'Brincalhão(ona)', 'Sociável' ) ),
			array( 'Simba', 'gato', 'male', 'medium', 'Laranja', array( 'Brincalhão(ona)', 'Curioso(a)' ) ),
			array( 'Mel', 'cao', 'female', 'medium', 'Dourada', array( 'Carinhoso(a)', 'Calmo(a)' ) ),
			array( 'Fumaça', 'gato', 'male', 'small', 'Cinza', array( 'Calmo(a)', 'Dócil' ) ),
			array( 'Amora', 'gato', 'female', 'small', 'Preta e branca', array( 'Curioso(a)', 'Sociável' ) ),
			array( 'Bento', 'cao', 'male', 'small', 'Branco', array( 'Tímido(a)', 'Carinhoso(a)' ) ),
		);
	}

	/**
	 * Cria um animal de exemplo.
	 *
	 * @param int  $index       Posição na sequência.
	 * @param bool $with_images Se gera imagens.
	 */
	private function create_animal( int $index, bool $with_images ): void {
		$samples  = $this->samples();
		$sample   = $samples[ $index % count( $samples ) ];
		$round    = intdiv( $index, count( $samples ) );
		$name     = $sample[0] . ( $round ? ' ' . ( $round + 1 ) : '' );
		$statuses = array( 'available', 'available', 'available', 'reserved', 'available', 'treatment', 'available', 'adopted' );
		$status   = $statuses[ $index % count( $statuses ) ];
		$is_cat   = 'gato' === $sample[1];

		$story = sprintf(
			"<p>%s foi encontrado(a) na rua, com fome e muito medo. Depois de semanas de cuidado, comida boa e carinho, hoje é outro bicho: confiante e cheio de energia.</p>\n\n<p>Este é um texto de exemplo. Conte aqui a história do resgate: onde o animal foi encontrado, como estava e como foi a recuperação.</p>",
			esc_html( $name )
		);

		$post_id = wp_insert_post(
			array(
				'post_type'    => PostType::SLUG,
				'post_status'  => 'publish',
				'post_title'   => $name,
				'post_content' => $story,
				'post_date'    => wp_date( 'Y-m-d H:i:s', time() - ( $index + 1 ) * HOUR_IN_SECONDS ),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			\WP_CLI::warning( $post_id->get_error_message() );
			return;
		}

		$months_old = 4 + ( $index * 7 ) % 90;

		update_post_meta( $post_id, self::FLAG, 1 );
		update_post_meta( $post_id, Meta::STATUS, $status );
		update_post_meta( $post_id, Meta::SEX, $sample[2] );
		update_post_meta( $post_id, Meta::SIZE, $sample[3] );
		update_post_meta( $post_id, Meta::COLOR, $sample[4] );
		update_post_meta( $post_id, Meta::BIRTH_DATE, Animal::birth_date_from_age( 0, $months_old ) );
		update_post_meta( $post_id, Meta::RESCUE_DATE, current_datetime()->modify( '-' . ( 20 + $index * 11 ) . ' days' )->format( 'Y-m-d' ) );
		update_post_meta( $post_id, Meta::NEUTERED, 0 === $index % 4 ? 'no' : 'yes' );
		update_post_meta( $post_id, Meta::DEWORMED_DATE, current_datetime()->modify( '-' . ( 5 + $index ) . ' days' )->format( 'Y-m-d' ) );
		update_post_meta(
			$post_id,
			Meta::VACCINES,
			array(
				array(
					'name' => $is_cat ? 'V4' : 'V10',
					'date' => current_datetime()->modify( '-' . ( 15 + $index ) . ' days' )->format( 'Y-m-d' ),
				),
				array(
					'name' => 'Antirrábica',
					'date' => current_datetime()->modify( '-' . ( 10 + $index ) . ' days' )->format( 'Y-m-d' ),
				),
			)
		);

		update_post_meta( $post_id, Meta::TAGLINE, 0 === $index % 2 ? 'Um companheiro cheio de alegria e carinho!' : 'Pronto para ganhar um lar e muito colo.' );
		update_post_meta( $post_id, Meta::INTRO, sprintf( '%s foi resgatado(a) das ruas e hoje está pronto(a) para encontrar uma família. É sociável, brincalhão(ona) e adora companhia.', $name ) );
		update_post_meta( $post_id, Meta::WEIGHT, (string) ( $is_cat ? 3.5 + ( $index % 3 ) : 8 + ( $index % 5 ) * 4 ) );
		update_post_meta( $post_id, Meta::VET_CHECKED, 'yes' );
		update_post_meta( $post_id, Meta::VACCINATED, 0 === $index % 5 ? 'no' : 'yes' );

		if ( $is_cat ) {
			update_post_meta( $post_id, Meta::FIV, 'negative' );
			update_post_meta( $post_id, Meta::FELV, 5 === $index % 6 ? 'positive' : 'negative' );
		}

		wp_set_object_terms( $post_id, $sample[1], Taxonomies::SPECIES );
		wp_set_object_terms( $post_id, $sample[5], Taxonomies::TEMPERAMENT );

		if ( ! $with_images ) {
			return;
		}

		$main = $this->create_image( $post_id, $name, $index, 0 );
		if ( $main ) {
			set_post_thumbnail( $post_id, $main );
		}

		$gallery = array();
		foreach ( array( 'before', 'after' ) as $offset => $type ) {
			$image = $this->create_image( $post_id, $name . ' – ' . ( 'before' === $type ? 'antes' : 'depois' ), $index, $offset + 1 );
			if ( $image ) {
				$gallery[] = array(
					'id'   => $image,
					'type' => $type,
				);
			}
		}
		update_post_meta( $post_id, Meta::GALLERY, $gallery );
	}

	/**
	 * Gera uma imagem simples (fundo colorido com uma patinha) e a anexa ao animal.
	 *
	 * @param int    $post_id ID do animal.
	 * @param string $title   Título da imagem.
	 * @param int    $index   Posição do animal (define a cor).
	 * @param int    $variant Variação de cor dentro do mesmo animal.
	 * @return int ID do anexo, ou 0 em caso de falha.
	 */
	private function create_image( int $post_id, string $title, int $index, int $variant ): int {
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$palette = array(
			array( 244, 202, 170 ),
			array( 250, 222, 160 ),
			array( 198, 222, 196 ),
			array( 190, 214, 232 ),
			array( 232, 196, 206 ),
			array( 214, 204, 232 ),
		);
		$color   = $palette[ ( $index + $variant * 2 ) % count( $palette ) ];

		$width  = 1200;
		$height = 900;
		$image  = imagecreatetruecolor( $width, $height );
		$bg     = imagecolorallocate( $image, $color[0], $color[1], $color[2] );
		$paw    = imagecolorallocate( $image, max( 0, $color[0] - 60 ), max( 0, $color[1] - 60 ), max( 0, $color[2] - 60 ) );

		imagefill( $image, 0, 0, $bg );

		// Patinha: uma almofada grande e quatro dedos.
		$cx = (int) ( $width / 2 );
		$cy = (int) ( $height / 2 ) + 60;
		imagefilledellipse( $image, $cx, $cy + 40, 300, 250, $paw );
		imagefilledellipse( $image, $cx - 190, $cy - 110, 110, 150, $paw );
		imagefilledellipse( $image, $cx - 70, $cy - 210, 115, 160, $paw );
		imagefilledellipse( $image, $cx + 70, $cy - 210, 115, 160, $paw );
		imagefilledellipse( $image, $cx + 190, $cy - 110, 110, 150, $paw );

		ob_start();
		imagejpeg( $image, null, 85 );
		$bits = (string) ob_get_clean();

		$upload = wp_upload_bits( sanitize_title( 'rwp-exemplo-' . $title ) . '.jpg', null, $bits );
		if ( ! empty( $upload['error'] ) ) {
			\WP_CLI::warning( $upload['error'] );
			return 0;
		}

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => 'image/jpeg',
				'post_title'     => $title,
				'post_status'    => 'inherit',
			),
			$upload['file'],
			$post_id,
			true
		);

		if ( is_wp_error( $attachment_id ) ) {
			return 0;
		}

		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
		update_post_meta( $attachment_id, self::FLAG, 1 );
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $title );

		return (int) $attachment_id;
	}

	/**
	 * Remove tudo o que foi criado por este comando.
	 */
	private function delete(): void {
		$ids = get_posts(
			array(
				'post_type'   => array( PostType::SLUG, 'attachment' ),
				'post_status' => 'any',
				'numberposts' => -1,
				'fields'      => 'ids',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Comando de desenvolvimento.
				'meta_key'    => self::FLAG,
			)
		);

		foreach ( $ids as $id ) {
			if ( 'attachment' === get_post_type( $id ) ) {
				wp_delete_attachment( $id, true );
			} else {
				wp_delete_post( $id, true );
			}
		}

		\WP_CLI::success( sprintf( '%d itens de exemplo removidos.', count( $ids ) ) );
	}
}
