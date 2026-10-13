<?php
/**
 * Página de agradecimento do apadrinhamento.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Sponsorship;

use ResgataWP\Modules\Settings\Organization;

defined( 'ABSPATH' ) || exit;

/**
 * Página para onde a pessoa volta depois de pagar na Stripe.
 *
 * O plugin cria a página "Obrigado" com o bloco `resgata-wp/sponsorship-thanks`.
 * Nas Configurações do painel a protetora troca o título e o texto do
 * agradecimento e, se quiser, escolhe outra página do site (campos
 * `sponsorship_thanks_*` de `Organization`). Sem página publicada, a pessoa
 * volta para a página dos planos, que mostra um agradecimento simples.
 */
final class ThanksPage {

	public const FIELD = 'sponsorship_thanks_page';

	private const BLOCK = '<!-- wp:resgata-wp/sponsorship-thanks /-->';

	/**
	 * Registra os hooks.
	 */
	public function register(): void {
		add_filter( 'wp_robots', array( $this, 'robots' ) );
	}

	/**
	 * Página escolhida, se existir e não estiver na lixeira.
	 */
	public static function get(): ?\WP_Post {
		$page = get_post( (int) Organization::get()[ self::FIELD ] );

		return $page instanceof \WP_Post && 'page' === $page->post_type && 'trash' !== $page->post_status ? $page : null;
	}

	/**
	 * Endereço da página, ou vazio se ela não existe ou não está publicada.
	 */
	public static function url(): string {
		$page = self::get();

		return $page && 'publish' === $page->post_status ? (string) get_permalink( $page ) : '';
	}

	/**
	 * Dados da página para o painel.
	 *
	 * @return array{id: int, url: string, published: bool}|null
	 */
	public static function summary(): ?array {
		$page = self::get();

		return $page ? array(
			'id'        => $page->ID,
			'url'       => (string) get_permalink( $page ),
			'published' => 'publish' === $page->post_status,
		) : null;
	}

	/**
	 * Título do agradecimento: o das Configurações ou o padrão.
	 */
	public static function title(): string {
		$title = Organization::get()['sponsorship_thanks_title'];

		return '' !== $title ? $title : __( 'Obrigado por apadrinhar!', 'resgata-wp' );
	}

	/**
	 * Texto do agradecimento: o das Configurações ou o padrão.
	 */
	public static function text(): string {
		$text = Organization::get()['sponsorship_thanks_text'];

		return '' !== $text ? $text : __( 'Você vai receber a confirmação do pagamento por e-mail. Sua ajuda chega todo mês para cuidar dos animais.', 'resgata-wp' );
	}

	/**
	 * Imagem do agradecimento, mostrada na página e no e-mail de boas-vindas.
	 *
	 * O plugin não traz imagem: o site informa a dele pelo filtro.
	 *
	 * @return string Endereço da imagem, ou vazio se o site não informou nenhuma.
	 */
	public static function image_url(): string {
		/**
		 * Filtra a imagem (pode ser animada) mostrada no agradecimento ao padrinho, na página e no e-mail.
		 *
		 * @param string $url Endereço da imagem; vazio (padrão) não mostra imagem.
		 */
		return esc_url_raw( (string) apply_filters( 'resgata_thanks_image_url', '' ) );
	}

	/**
	 * Garante a página de agradecimento quando o site já vende planos e o usuário pode publicar páginas.
	 *
	 * @return \WP_Post|null A página (nova ou existente), ou null se não há o que criar.
	 */
	public static function ensure(): ?\WP_Post {
		$page = self::get();

		if ( $page || ! Plans::for_sale() || ! current_user_can( 'publish_pages' ) ) {
			return $page;
		}

		/**
		 * Filtra o conteúdo (blocos) da página de agradecimento criada pelo plugin.
		 *
		 * O tema pode devolver um layout completo; o conteúdo precisa ter o bloco
		 * `resgata-wp/sponsorship-thanks`, senão vale o padrão do plugin.
		 *
		 * @param string $content Conteúdo em blocos.
		 */
		$content = (string) apply_filters( 'resgata_thanks_page_content', self::BLOCK );

		if ( ! str_contains( $content, '<!-- wp:resgata-wp/sponsorship-thanks ' ) ) {
			$content = self::BLOCK;
		}

		$page_id = wp_insert_post(
			wp_slash(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => __( 'Obrigado', 'resgata-wp' ),
					'post_name'    => 'obrigado',
					'post_content' => $content,
				)
			),
			true
		);

		if ( is_wp_error( $page_id ) ) {
			return null;
		}

		/**
		 * Filtra o modelo de página (template) usado pela página de agradecimento criada pelo plugin.
		 *
		 * @param string $template Slug do modelo; vazio usa o modelo padrão de páginas.
		 */
		$template = sanitize_key( (string) apply_filters( 'resgata_thanks_page_template', '' ) );

		if ( '' !== $template ) {
			update_post_meta( $page_id, '_wp_page_template', $template );
		}

		Organization::update( array( self::FIELD => (string) $page_id ) );

		return get_post( $page_id );
	}

	/**
	 * Páginas publicadas do site, para a protetora escolher nas Configurações.
	 *
	 * @return array<string, string> ID => título.
	 */
	public static function choices(): array {
		$choices = array();

		foreach ( get_pages( array( 'post_status' => 'publish' ) ) as $page ) {
			$choices[ (string) $page->ID ] = '' !== $page->post_title ? $page->post_title : '#' . $page->ID;
		}

		return $choices;
	}

	/**
	 * A página de agradecimento não entra nos buscadores: só faz sentido para quem acabou de pagar.
	 *
	 * @param array<string, mixed> $robots Diretivas da página atual.
	 * @return array<string, mixed>
	 */
	public function robots( $robots ): array {
		$robots = is_array( $robots ) ? $robots : array();
		$page   = self::get();

		return $page && is_page( $page->ID ) ? wp_robots_no_robots( $robots ) : $robots;
	}
}
