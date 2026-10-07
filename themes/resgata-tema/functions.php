<?php
/**
 * Resgata Tema: somente aparência. Toda a funcionalidade fica no plugin Resgata WP.
 *
 * @package ResgataTema
 */

defined( 'ABSPATH' ) || exit;

/**
 * Avisa o plugin Resgata WP que este tema cuida do visual dos blocos dele.
 */
function resgata_tema_setup(): void {
	add_theme_support( 'resgata-wp' );
}
add_action( 'after_setup_theme', 'resgata_tema_setup' );

/**
 * Estilos dos blocos do Resgata WP (site e editor).
 */
function resgata_tema_block_assets(): void {
	wp_enqueue_style(
		'resgata-tema-resgata-wp',
		get_theme_file_uri( 'assets/css/resgata-wp.css' ),
		array(),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'enqueue_block_assets', 'resgata_tema_block_assets' );

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
add_filter( 'rwp_help_page_content', 'resgata_tema_help_page_content' );

/**
 * A página "Como ajudar" usa o modelo sem título: a chamada do topo já traz o título.
 */
function resgata_tema_help_page_template(): string {
	return 'page-no-title';
}
add_filter( 'rwp_help_page_template', 'resgata_tema_help_page_template' );

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
add_filter( 'rwp_contact_page_content', 'resgata_tema_contact_page_content' );
add_filter( 'rwp_contact_page_template', 'resgata_tema_help_page_template' );

/**
 * Estilos de bloco do tema que dependem de CSS (ícones, numeração).
 *
 * Aparecem no painel "Estilos" do bloco, no editor. Os demais estilos ("Cartão",
 * "Faixa suave", "Chamada do topo"...) são declarados em styles/blocks/*.json.
 */
function resgata_tema_block_styles(): void {
	$styles = array(
		'core/group'  => array(
			'resgata-icon-paw'        => __( 'Com ícone: pata', 'resgata-tema' ),
			'resgata-icon-heart'      => __( 'Com ícone: coração', 'resgata-tema' ),
			'resgata-icon-hand-heart' => __( 'Com ícone: mão e coração', 'resgata-tema' ),
			'resgata-icon-message'    => __( 'Com ícone: conversa', 'resgata-tema' ),
			'resgata-icon-shield'     => __( 'Com ícone: segurança', 'resgata-tema' ),
			'resgata-icon-lock'       => __( 'Com ícone: cadeado aberto', 'resgata-tema' ),
			'resgata-step'            => __( 'Passo numerado', 'resgata-tema' ),
			'resgata-footer-bottom'   => __( 'Linha de direitos', 'resgata-tema' ),
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
