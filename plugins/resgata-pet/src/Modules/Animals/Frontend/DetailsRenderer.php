<?php
/**
 * Ficha pública do animal.
 *
 * @package ResgataPet
 */

namespace ResgataPet\Modules\Animals\Frontend;

use ResgataPet\Core\Links;
use ResgataPet\Core\TemplateLoader;
use ResgataPet\Modules\Animals\Animal;
use ResgataPet\Modules\Animals\Meta;
use ResgataPet\Modules\Animals\Options;
use ResgataPet\Modules\Animals\PostType;
use ResgataPet\Modules\Settings\Organization;

defined( 'ABSPATH' ) || exit;

/**
 * Monta os dados e o HTML da página de um animal: fotos, resumo, história,
 * saúde, outras formas de ajudar e outros animais.
 */
final class DetailsRenderer {

	/**
	 * Seções da página, na ordem em que aparecem.
	 */
	public const SECTIONS = array( 'breadcrumb', 'gallery', 'summary', 'story', 'health', 'help', 'related' );

	private const RELATED_LIMIT = 8;

	/**
	 * Animais cuja história está sendo montada agora (evita repetição infinita
	 * se o texto da história contiver a própria ficha).
	 *
	 * @var array<int, true>
	 */
	private static array $rendering_story = array();

	/**
	 * Informa se o visitante atual pode ver o animal.
	 *
	 * @param int $post_id ID do post.
	 */
	public static function can_view( int $post_id ): bool {
		$post = get_post( $post_id );

		if ( ! $post || PostType::SLUG !== $post->post_type || post_password_required( $post ) ) {
			return false;
		}

		return 'publish' === $post->post_status || current_user_can( 'read_post', $post_id );
	}

	/**
	 * Renderiza a página do animal, inteira ou só uma seção.
	 *
	 * @param int    $post_id            ID do animal.
	 * @param string $section            `all` (página inteira, com o layout) ou uma das seções em SECTIONS.
	 *                                   `facts` é aceito por compatibilidade: é o resumo sem o nome.
	 * @param string $wrapper_attributes Atributos HTML do elemento externo, já escapados.
	 */
	public static function render( int $post_id, string $section = 'all', string $wrapper_attributes = '' ): string {
		if ( ! self::can_view( $post_id ) ) {
			return '';
		}

		$is_page    = ! in_array( $section, self::SECTIONS, true ) && 'facts' !== $section;
		$show_title = 'facts' !== $section;
		$sections   = $is_page ? self::SECTIONS : array( 'facts' === $section ? 'summary' : $section );
		$animal     = new Animal( $post_id );
		$name       = get_the_title( $post_id );
		$has        = static fn( string $key ): bool => in_array( $key, $sections, true );
		$photos     = $has( 'gallery' ) ? self::photos( $animal ) : array();

		wp_enqueue_style( 'resgata-frontend' );

		// As setas (das fotos e da fileira de outros animais) e a troca de foto pelas miniaturas
		// dependem do script; sem ele, as duas rolam com o dedo ou pela barra de rolagem.
		if ( ( count( $photos ) > 1 || $has( 'related' ) ) && wp_script_is( Blocks::GALLERY_HANDLE, 'registered' ) ) {
			wp_enqueue_script( Blocks::GALLERY_HANDLE );
		}

		if ( '' === $wrapper_attributes ) {
			$wrapper_attributes = 'class="resgata-animal"';
		}

		if ( $is_page ) {
			// Os atributos já vêm escapados; só acrescenta a classe do layout de página inteira.
			$wrapper_attributes = (string) preg_replace( '/class="/', 'class="resgata-animal--page ', $wrapper_attributes, 1 );
		}

		return TemplateLoader::render(
			'animal-details.php',
			array(
				'wrapper_attributes' => $wrapper_attributes,
				'animal'             => $animal,
				'sections'           => $sections,
				'is_page'            => $is_page,
				'show_title'         => $show_title,
				'name'               => $name,
				'archive_url'        => (string) get_post_type_archive_link( PostType::SLUG ),
				'photos'             => $photos,
				'facts'              => $has( 'summary' ) ? self::facts( $animal ) : array(),
				'adopt_url'          => $has( 'summary' ) ? self::adopt_url( $post_id ) : '',
				'adopt_label'        => self::by_sex(
					$animal,
					/* translators: %s: nome do animal (macho). */
					__( 'Quero adotar o %s', 'resgata-pet' ),
					/* translators: %s: nome do animal (fêmea). */
					__( 'Quero adotar a %s', 'resgata-pet' ),
					/* translators: %s: nome do animal. */
					__( 'Quero adotar %s', 'resgata-pet' ),
					$name
				),
				'whatsapp_url'       => $has( 'summary' ) ? Organization::whatsapp_url( $name ) : '',
				'story'              => $has( 'story' ) ? self::story( $post_id ) : '',
				'story_title'        => self::by_sex(
					$animal,
					/* translators: %s: nome do animal (macho). */
					__( 'A história do %s', 'resgata-pet' ),
					/* translators: %s: nome do animal (fêmea). */
					__( 'A história da %s', 'resgata-pet' ),
					/* translators: %s: nome do animal. */
					__( 'A história de %s', 'resgata-pet' ),
					$name
				),
				'health'             => $has( 'health' ) ? self::health( $animal ) : array(),
				'tests'              => $has( 'health' ) ? self::tests( $animal ) : array(),
				'vaccines'           => $has( 'health' ) ? $animal->vaccines() : array(),
				'help_links'         => $has( 'help' ) ? self::help_links( $post_id ) : array(),
				'related'            => $has( 'related' ) ? self::related( $post_id ) : array(),
			)
		);
	}

	/**
	 * Escolhe a frase conforme o sexo do animal ("do Thor", "da Luna", "de Pipoca").
	 *
	 * @param Animal $animal  Animal.
	 * @param string $male    Frase para macho, com `%s` no lugar do nome.
	 * @param string $female  Frase para fêmea.
	 * @param string $neutral Frase para sexo não informado.
	 * @param string $name    Nome do animal.
	 */
	private static function by_sex( Animal $animal, string $male, string $female, string $neutral, string $name ): string {
		$phrases = array(
			'male'   => $male,
			'female' => $female,
		);

		return sprintf( $phrases[ $animal->sex() ] ?? $neutral, $name );
	}

	/**
	 * Fotos da galeria da página: a principal, depois as "depois" e por fim as "antes".
	 *
	 * @param Animal $animal Animal.
	 * @return array<int, array{id: int, type: string}>
	 */
	private static function photos( Animal $animal ): array {
		$photos   = array();
		$featured = (int) get_post_thumbnail_id( $animal->id() );

		if ( $featured && wp_attachment_is_image( $featured ) ) {
			$photos[ $featured ] = array(
				'id'   => $featured,
				'type' => 'after',
			);
		}

		foreach ( array( 'after', 'before' ) as $type ) {
			foreach ( $animal->gallery( $type ) as $photo ) {
				$photos += array( $photo['id'] => $photo );
			}
		}

		return array_values( $photos );
	}

	/**
	 * Dados em destaque no resumo (espécie, sexo, idade...). Só os preenchidos.
	 *
	 * @param Animal $animal Animal.
	 * @return array<int, array{icon: string, label: string, value: string}>
	 */
	private static function facts( Animal $animal ): array {
		$yes_no = Options::yes_no();
		$size   = $animal->size_label();
		$weight = $animal->weight_label();

		if ( '' !== $size && '' !== $weight ) {
			/* translators: 1: porte do animal, 2: peso. Ex.: "Médio (18 kg)". */
			$size = sprintf( __( '%1$s (%2$s)', 'resgata-pet' ), $size, $weight );
		} elseif ( '' === $size ) {
			$size = $weight;
		}

		$vaccines = '';
		if ( 'yes' === $animal->vaccinated() ) {
			$vaccines = __( 'Em dia', 'resgata-pet' );
		} elseif ( 'no' === $animal->vaccinated() ) {
			$vaccines = __( 'Ainda não estão em dia', 'resgata-pet' );
		} elseif ( $animal->vaccines() ) {
			$count = count( $animal->vaccines() );
			/* translators: %d: quantidade de vacinas aplicadas. */
			$vaccines = sprintf( _n( '%d aplicada', '%d aplicadas', $count, 'resgata-pet' ), $count );
		}

		$facts = array(
			array( 'paw-print', __( 'Espécie', 'resgata-pet' ), $animal->species_label() ),
			array( 'female' === $animal->sex() ? 'venus' : 'mars', __( 'Sexo', 'resgata-pet' ), $animal->sex_label() ),
			array( 'cake', __( 'Idade estimada', 'resgata-pet' ), $animal->age_label() ),
			array( 'heart', __( 'Castração', 'resgata-pet' ), $yes_no[ $animal->neutered() ] ?? '' ),
			array( 'weight', __( 'Porte', 'resgata-pet' ), $size ),
			array( 'syringe', __( 'Vacinas', 'resgata-pet' ), $vaccines ),
			array( 'palette', __( 'Cor / pelagem', 'resgata-pet' ), $animal->color() ),
			array( 'calendar-heart', __( 'Resgatado em', 'resgata-pet' ), Animal::format_date( $animal->rescue_date() ) ),
		);

		$list = array();
		foreach ( $facts as $fact ) {
			if ( '' !== $fact[2] ) {
				$list[] = array(
					'icon'  => $fact[0],
					'label' => $fact[1],
					'value' => $fact[2],
				);
			}
		}

		return $list;
	}

	/**
	 * Lista de cuidados de saúde: o que já foi feito e o que a protetora marcou como "ainda não".
	 *
	 * @param Animal $animal Animal.
	 * @return array<int, array{label: string, done: bool}>
	 */
	private static function health( Animal $animal ): array {
		$answers = array(
			array( $animal->vet_checked(), __( 'Consulta veterinária realizada', 'resgata-pet' ), __( 'Consulta veterinária ainda não realizada', 'resgata-pet' ) ),
			array( $animal->vaccinated(), __( 'Vacinas em dia', 'resgata-pet' ), __( 'Vacinas ainda não estão em dia', 'resgata-pet' ) ),
			array( $animal->neutered(), __( 'Castração realizada', 'resgata-pet' ), __( 'Castração ainda não realizada', 'resgata-pet' ) ),
		);

		$items = array();
		foreach ( $answers as $answer ) {
			if ( '' !== $answer[0] ) {
				$items[] = array(
					'label' => 'yes' === $answer[0] ? $answer[1] : $answer[2],
					'done'  => 'yes' === $answer[0],
				);
			}
		}

		$dewormed = Animal::format_date( $animal->dewormed_date() );
		if ( '' !== $dewormed ) {
			$items[] = array(
				/* translators: %s: data do último vermífugo. */
				'label' => sprintf( __( 'Vermífugo em %s', 'resgata-pet' ), $dewormed ),
				'done'  => true,
			);
		}

		// O que já foi feito aparece primeiro.
		usort( $items, static fn( array $a, array $b ): int => (int) $b['done'] <=> (int) $a['done'] );

		return $items;
	}

	/**
	 * Resultados dos testes de FIV e FeLV (rótulo => resultado). Só os informados.
	 *
	 * @param Animal $animal Animal.
	 * @return array<string, string>
	 */
	private static function tests( Animal $animal ): array {
		$results = Options::test_results();

		return array_filter(
			array(
				__( 'Teste de FIV', 'resgata-pet' )  => $results[ $animal->fiv() ] ?? '',
				__( 'Teste de FeLV', 'resgata-pet' ) => $results[ $animal->felv() ] ?? '',
			)
		);
	}

	/**
	 * História do resgate (o conteúdo do post), com os filtros de conteúdo do WordPress.
	 *
	 * @param int $post_id ID do animal.
	 */
	private static function story( int $post_id ): string {
		$content = (string) get_post_field( 'post_content', $post_id );

		if ( '' === trim( $content ) || isset( self::$rendering_story[ $post_id ] ) ) {
			return '';
		}

		self::$rendering_story[ $post_id ] = true;

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Filtro do próprio WordPress.
		$html = (string) apply_filters( 'the_content', $content );

		unset( self::$rendering_story[ $post_id ] );

		return trim( $html );
	}

	/**
	 * Endereço do formulário de adoção deste animal, ou vazio se não houver.
	 *
	 * @param int $post_id ID do animal.
	 */
	private static function adopt_url( int $post_id ): string {
		/**
		 * Filtra o destino do botão "Quero adotar" da página do animal.
		 *
		 * O módulo de pedidos de adoção devolve a âncora do formulário quando o
		 * animal está disponível. Vazio esconde o botão.
		 *
		 * @param string $url     Destino do botão.
		 * @param int    $post_id ID do animal.
		 */
		return (string) apply_filters( 'resgata_animal_adopt_url', '', $post_id );
	}

	/**
	 * Outras formas de ajudar: apadrinhar (se houver a página dos planos) e doar (página "Como ajudar"; sem ela, a de contato).
	 *
	 * @param int $post_id ID do animal.
	 * @return array<int, array{label: string, url: string, icon: string}>
	 */
	private static function help_links( int $post_id ): array {
		$links   = array();
		$sponsor = Links::sponsorship();

		if ( '' !== $sponsor ) {
			$links[] = array(
				'label' => __( 'Quero ser padrinho', 'resgata-pet' ),
				'url'   => $sponsor,
				'icon'  => 'hand-heart',
			);
		}

		$help    = Links::help();
		$contact = Links::contact();

		if ( '' !== $help ) {
			$links[] = array(
				'label' => __( 'Fazer uma doação', 'resgata-pet' ),
				'url'   => $help,
				'icon'  => 'heart',
			);
		} elseif ( '' !== $contact ) {
			$links[] = array(
				'label' => __( 'Outras formas de ajudar', 'resgata-pet' ),
				'url'   => $contact,
				'icon'  => 'heart',
			);
		}

		/**
		 * Filtra os links da caixa "Não pode adotar?" da página do animal.
		 *
		 * @param array<int, array{label: string, url: string, icon: string}> $links   Links (o primeiro é o principal).
		 * @param int                                                         $post_id ID do animal.
		 */
		$links = apply_filters( 'resgata_animal_help_links', $links, $post_id );

		return is_array( $links ) ? array_values( array_filter( $links, static fn( $link ): bool => is_array( $link ) && ! empty( $link['label'] ) && ! empty( $link['url'] ) ) ) : array();
	}

	/**
	 * Outros animais disponíveis para adoção, os mais recentes primeiro.
	 *
	 * @param int $post_id ID do animal exibido (fica de fora).
	 * @return int[]
	 */
	private static function related( int $post_id ): array {
		$ids = get_posts(
			array(
				'post_type'      => PostType::SLUG,
				'post_status'    => 'publish',
				'posts_per_page' => self::RELATED_LIMIT,
				'post__not_in'   => array( $post_id ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Um ID só; volume pequeno.
				'fields'         => 'ids',
				'no_found_rows'  => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Volume pequeno (animais de uma protetora).
				'meta_key'       => Meta::STATUS,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_value'     => 'available',
			)
		);

		return array_map( 'intval', $ids );
	}
}
