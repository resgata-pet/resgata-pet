<?php
/**
 * Resgata Tema: somente aparência. Toda a funcionalidade fica no plugin Resgata Pet.
 *
 * @package ResgataTema
 */

defined( 'ABSPATH' ) || exit;

/**
 * Avisa o plugin Resgata Pet que este tema cuida do visual dos blocos dele.
 */
function resgata_tema_setup(): void {
	add_theme_support( 'resgata-pet' );
}
add_action( 'after_setup_theme', 'resgata_tema_setup' );

/**
 * CSS dos blocos, um arquivo por bloco em assets/css/blocks/: bloco => arquivo.
 *
 * O bloco de contato tem duas formas; a resumida (rodapé) usa o arquivo `contact-compact`.
 *
 * @return array<string, string>
 */
function resgata_tema_block_css_files(): array {
	return array(
		'resgata-pet/animals-list'       => 'animals-list',
		'resgata-pet/animal-details'     => 'animal-details',
		'resgata-pet/banners'            => 'banners',
		'resgata-pet/impact'             => 'impact',
		'resgata-pet/pix-donation'       => 'pix',
		'resgata-pet/pix-once'           => 'pix',
		'resgata-pet/donate-banner'      => 'donate-banner',
		'resgata-pet/contact'            => 'contact',
		'resgata-pet/contact#compact'    => 'contact-compact',
		'resgata-pet/links'              => 'links',
		'resgata-pet/adoption-form'      => 'adoption-form',
		'resgata-pet/sponsorship-plans'  => 'sponsorship',
		'resgata-pet/sponsor-area'       => 'sponsorship',
		'resgata-pet/sponsorship-thanks' => 'sponsorship',
		'core/group'                     => 'core-group',
		'core/navigation'                => 'core-navigation',
		'core/button'                    => 'core-button',
		'core/query-pagination'          => 'core-query-pagination',
		'core/post-content'              => 'core-post-content',
	);
}

/**
 * Registra o CSS de cada bloco. Os dos blocos do Resgata Pet dependem de
 * assets/css/comum.css (variáveis `--resgata-*` e peças que mais de um bloco usa), que por sua
 * vez depende de assets/css/enfeites.css (formas dos enfeites de fundo).
 */
function resgata_tema_register_block_css(): void {
	$version = wp_get_theme()->get( 'Version' );

	wp_register_style( 'resgata-tema-enfeites', get_theme_file_uri( 'assets/css/enfeites.css' ), array(), $version );
	wp_style_add_data( 'resgata-tema-enfeites', 'path', get_theme_file_path( 'assets/css/enfeites.css' ) );

	wp_register_style( 'resgata-tema-comum', get_theme_file_uri( 'assets/css/comum.css' ), array( 'resgata-tema-enfeites' ), $version );
	wp_style_add_data( 'resgata-tema-comum', 'path', get_theme_file_path( 'assets/css/comum.css' ) );

	foreach ( array_unique( resgata_tema_block_css_files() ) as $file ) {
		$deps = str_starts_with( $file, 'core-' ) ? array() : array( 'resgata-tema-comum' );

		// As faixas do bloco Grupo ("Chamada do topo" e "Faixa suave") também têm enfeites.
		if ( 'core-group' === $file ) {
			$deps = array( 'resgata-tema-enfeites' );
		}

		wp_register_style( 'resgata-tema-' . $file, get_theme_file_uri( "assets/css/blocks/$file.css" ), $deps, $version );
		wp_style_add_data( 'resgata-tema-' . $file, 'path', get_theme_file_path( "assets/css/blocks/$file.css" ) );
	}
}
add_action( 'init', 'resgata_tema_register_block_css' );

/**
 * Anota um arquivo de CSS de que a página precisa e devolve os anotados até aqui.
 *
 * @param string $file Arquivo (sem extensão) a anotar; vazio só consulta.
 * @return string[]
 */
function resgata_tema_needed_block_css( string $file = '' ): array {
	static $needed = array();

	if ( '' !== $file && ! in_array( $file, $needed, true ) ) {
		$needed[] = $file;

		// Bloco exibido depois do cabeçalho da página (tema clássico): o CSS sai no rodapé.
		if ( did_action( 'enqueue_block_assets' ) ) {
			wp_enqueue_style( 'resgata-tema-' . $file );
		}
	}

	return $needed;
}

/**
 * A cada bloco exibido, anota o CSS dele.
 *
 * @param string               $content HTML do bloco.
 * @param array<string, mixed> $block   Bloco analisado.
 */
function resgata_tema_collect_block_css( $content, $block ) {
	$name = (string) ( $block['blockName'] ?? '' );

	if ( 'resgata-pet/contact' === $name && ! empty( $block['attrs']['compact'] ) ) {
		$name .= '#compact';
	}

	$files = resgata_tema_block_css_files();

	if ( isset( $files[ $name ] ) ) {
		resgata_tema_needed_block_css( $files[ $name ] );
	}

	return $content;
}
add_filter( 'render_block', 'resgata_tema_collect_block_css', 10, 2 );

/**
 * A lista de animais também existe como shortcode ([resgata_animais]), fora de um bloco.
 *
 * @param string $output HTML do shortcode.
 * @param string $tag    Nome do shortcode.
 */
function resgata_tema_shortcode_css( $output, $tag ) {
	if ( 'resgata_animais' === $tag ) {
		resgata_tema_needed_block_css( 'animals-list' );
	}

	return $output;
}
add_filter( 'do_shortcode_tag', 'resgata_tema_shortcode_css', 10, 2 );

/**
 * Carrega só o CSS dos blocos que a página tem; o editor carrega todos.
 *
 * Fica neste gancho, e não em `wp_enqueue_block_style()`, para sair depois dos estilos
 * globais: as regras `.resgata-*` contam com essa ordem para vencer os empates com eles.
 */
function resgata_tema_enqueue_block_css(): void {
	$files = array_unique( resgata_tema_block_css_files() );

	if ( ! is_admin() ) {
		$files = array_intersect( $files, resgata_tema_needed_block_css() );
	}

	foreach ( $files as $file ) {
		wp_enqueue_style( 'resgata-tema-' . $file );
	}
}
add_action( 'enqueue_block_assets', 'resgata_tema_enqueue_block_css' );

/**
 * Categoria dos padrões do tema no inseridor.
 */
function resgata_tema_pattern_categories(): void {
	register_block_pattern_category(
		'resgata-tema',
		array(
			'label'       => __( 'Resgata', 'resgata-tema' ),
			'description' => __( 'Seções prontas para o site da protetora.', 'resgata-tema' ),
		)
	);
}
add_action( 'init', 'resgata_tema_pattern_categories' );

/**
 * Layout da página "Como ajudar" que o plugin cria ao cadastrar a chave Pix:
 * usa o pattern do tema (chamada, atalhos, Pix, apadrinhamento, números e animais).
 *
 * @param string $content Conteúdo padrão do plugin (só o bloco de doação por Pix).
 */
function resgata_tema_help_page_content( $content ) {
	$pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( 'resgata-tema/pagina-como-ajudar' );

	return is_array( $pattern ) && ! empty( $pattern['content'] ) ? $pattern['content'] : $content;
}
add_filter( 'resgata_help_page_content', 'resgata_tema_help_page_content' );

/**
 * A página "Como ajudar" usa o modelo sem título: a chamada do topo já traz o título.
 */
function resgata_tema_help_page_template(): string {
	return 'page-no-title';
}
add_filter( 'resgata_help_page_template', 'resgata_tema_help_page_template' );

/**
 * A página de agradecimento do apadrinhamento usa o modelo sem título: o bloco já traz o título.
 */
function resgata_tema_thanks_page_template(): string {
	return 'page-no-title';
}
add_filter( 'resgata_thanks_page_template', 'resgata_tema_thanks_page_template' );

/**
 * Layout da página "Contato" que o plugin cria ao salvar as configurações:
 * usa o pattern do tema (chamada, dados de contato e convite para apadrinhar).
 *
 * @param string $content Conteúdo padrão do plugin (só o bloco de contato).
 */
function resgata_tema_contact_page_content( $content ) {
	$pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( 'resgata-tema/pagina-contato' );

	return is_array( $pattern ) && ! empty( $pattern['content'] ) ? $pattern['content'] : $content;
}
add_filter( 'resgata_contact_page_content', 'resgata_tema_contact_page_content' );
add_filter( 'resgata_contact_page_template', 'resgata_tema_help_page_template' );

/**
 * Estilos de bloco do tema que dependem de CSS (ícones, numeração, ilustrações).
 *
 * Aparecem no painel "Estilos" do bloco, no editor. Os demais estilos ("Cartão",
 * "Faixa suave", "Chamada do topo"...) são declarados em styles/blocks/*.json.
 */
function resgata_tema_block_styles(): void {
	$styles = array(
		'core/group'  => array(
			'resgata-icon-paw'         => __( 'Com ícone: pata', 'resgata-tema' ),
			'resgata-icon-heart'       => __( 'Com ícone: coração', 'resgata-tema' ),
			'resgata-icon-hand-heart'  => __( 'Com ícone: mão e coração', 'resgata-tema' ),
			'resgata-icon-message'     => __( 'Com ícone: conversa', 'resgata-tema' ),
			'resgata-icon-shield'      => __( 'Com ícone: segurança', 'resgata-tema' ),
			'resgata-icon-lock'        => __( 'Com ícone: cadeado aberto', 'resgata-tema' ),
			'resgata-step'             => __( 'Passo numerado', 'resgata-tema' ),
			'resgata-art-sleeping-cat' => __( 'Com ilustração: gato dormindo', 'resgata-tema' ),
			'resgata-art-lost-puppy'   => __( 'Com ilustração: cachorrinho perdido', 'resgata-tema' ),
			'resgata-footer-bottom'    => __( 'Linha de direitos', 'resgata-tema' ),
		),
		'core/button' => array(
			'resgata-heart'      => __( 'Com coração', 'resgata-tema' ),
			'resgata-paw'        => __( 'Com pata', 'resgata-tema' ),
			'resgata-arrow-link' => __( 'Link com seta', 'resgata-tema' ),
		),
	);

	foreach ( $styles as $block => $block_styles ) {
		foreach ( $block_styles as $name => $label ) {
			register_block_style(
				$block,
				array(
					'name'  => $name,
					'label' => $label,
				)
			);
		}
	}
}
add_action( 'init', 'resgata_tema_block_styles' );
