<?php
/**
 * Envio do pedido de adoção pelo site.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Adoptions;

use ResgataWP\Modules\Animals\Animal;
use ResgataWP\Modules\Animals\PostType as AnimalPostType;

defined( 'ABSPATH' ) || exit;

/**
 * Recebe o formulário público, grava o pedido na fila interna e leva a pessoa
 * para a confirmação, de onde ela envia o pedido pelo WhatsApp da protetora.
 */
final class Submission {

	public const NONCE_ACTION = 'resgata_adoption_submit';
	public const NONCE_NAME   = 'resgata_adoption_nonce';
	public const FIELD        = 'resgata_adoption';
	public const HONEYPOT     = 'resgata_site';
	public const TOKEN_PARAM  = 'resgata_pedido';

	/**
	 * Pedidos aceitos por hora vindos do mesmo endereço de rede.
	 */
	private const RATE_LIMIT = 5;

	/**
	 * Erros do último envio, por campo (`_form` = erro geral).
	 *
	 * @var array<string, string>
	 */
	private static array $errors = array();

	/**
	 * Valores do último envio, para repreencher o formulário.
	 *
	 * @var array<string, mixed>
	 */
	private static array $old = array();

	/**
	 * Registra os hooks.
	 */
	public function register(): void {
		add_action( 'template_redirect', array( $this, 'maybe_handle' ), 5 );
	}

	/**
	 * Erros do envio atual.
	 *
	 * @return array<string, string>
	 */
	public static function errors(): array {
		return self::$errors;
	}

	/**
	 * Valores enviados (para repreencher o formulário depois de um erro).
	 *
	 * @return array<string, mixed>
	 */
	public static function old(): array {
		return self::$old;
	}

	/**
	 * Processa o envio do formulário, se esta requisição for um.
	 */
	public function maybe_handle(): void {
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] || ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cada resposta é validada em Questionnaire::validate().
		$input = isset( $_POST[ self::FIELD ] ) && is_array( $_POST[ self::FIELD ] ) ? wp_unslash( $_POST[ self::FIELD ] ) : array();

		self::$old = $input;

		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			self::$errors['_form'] = __( 'A página ficou aberta por muito tempo. Confira os dados e envie de novo.', 'resgata-wp' );
			return;
		}

		// Campo invisível: pessoas não preenchem, robôs costumam preencher. Finge sucesso e não grava nada.
		if ( ! empty( $_POST[ self::HONEYPOT ] ) ) {
			self::$old = array();
			return;
		}

		if ( self::rate_limit_count() >= self::RATE_LIMIT ) {
			self::$errors['_form'] = __( 'Recebemos muitos pedidos deste endereço em pouco tempo. Tente de novo mais tarde.', 'resgata-wp' );
			return;
		}

		$result = self::create( $input );

		if ( is_wp_error( $result ) ) {
			$data         = $result->get_error_data();
			self::$errors = is_array( $data ) && $data ? $data : array( '_form' => $result->get_error_message() );
			return;
		}

		self::$old = array();

		// Só pedidos aceitos contam para o limite: corrigir um campo e reenviar não bloqueia ninguém.
		set_transient( self::rate_limit_key(), self::rate_limit_count() + 1, HOUR_IN_SECONDS );

		$token = wp_generate_password( 24, false );
		set_transient( 'resgata_adoption_done_' . $token, $result, 30 * MINUTE_IN_SECONDS );

		$page = get_queried_object_id() ? get_permalink( get_queried_object_id() ) : home_url( '/' );

		// PRG: evita reenviar o formulário ao atualizar a página.
		wp_safe_redirect( add_query_arg( self::TOKEN_PARAM, $token, (string) $page ) . '#resgata-adocao' );
		exit;
	}

	/**
	 * Valida e grava um pedido.
	 *
	 * @param array<string, mixed> $input Respostas (sem barras de escape), mais `animal_id` e `consent`.
	 * @return int|\WP_Error ID do pedido, ou erro com os problemas por campo em `error_data`.
	 */
	public static function create( array $input ) {
		$validated = Questionnaire::validate( $input );
		$errors    = $validated['errors'];
		$answers   = $validated['answers'];
		$animal_id = absint( $input['animal_id'] ?? 0 );

		if ( ! self::is_adoptable( $animal_id ) ) {
			$errors['animal_id'] = __( 'Escolha um animal disponível para adoção.', 'resgata-wp' );
		}

		if ( empty( $input['consent'] ) ) {
			$errors['consent'] = __( 'Precisamos da sua autorização para receber o pedido.', 'resgata-wp' );
		}

		if ( $errors ) {
			return new \WP_Error( 'resgata_adoption_invalid', __( 'Confira os campos destacados.', 'resgata-wp' ), $errors );
		}

		$request_id = wp_insert_post(
			wp_slash(
				array(
					'post_type'   => PostType::SLUG,
					'post_status' => 'private',
					'post_title'  => $answers['name'],
				)
			),
			true
		);

		if ( is_wp_error( $request_id ) ) {
			return new \WP_Error( 'resgata_adoption_failed', __( 'Não foi possível registrar o pedido. Tente de novo.', 'resgata-wp' ) );
		}

		update_post_meta( $request_id, PostType::META_STATUS, PostType::STATUS_DEFAULT );
		update_post_meta( $request_id, PostType::META_ANIMAL, $animal_id );
		update_post_meta( $request_id, PostType::META_ANSWERS, wp_slash( $answers ) );
		update_post_meta(
			$request_id,
			PostType::META_CONSENT,
			wp_slash(
				array(
					'at'   => current_time( 'mysql' ),
					// Guarda o texto exato com que a pessoa concordou.
					'text' => Form::consent_text(),
				)
			)
		);

		Messages::notify( $request_id );

		/**
		 * Disparado quando um pedido de adoção é registrado.
		 *
		 * @param int $request_id ID do pedido.
		 * @param int $animal_id  ID do animal.
		 */
		do_action( 'resgata_adoption_created', $request_id, $animal_id );

		return $request_id;
	}

	/**
	 * Informa se o animal existe, está publicado e disponível para adoção.
	 *
	 * @param int $animal_id ID do animal.
	 */
	public static function is_adoptable( int $animal_id ): bool {
		$post = $animal_id ? get_post( $animal_id ) : null;

		return $post
			&& AnimalPostType::SLUG === $post->post_type
			&& 'publish' === $post->post_status
			&& 'available' === ( new Animal( $animal_id ) )->status();
	}

	/**
	 * Pedido que acabou de ser enviado nesta navegação (pelo código da URL), ou 0.
	 */
	public static function confirmed_request_id(): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Código aleatório de uso único, gerado pelo servidor após o envio.
		$token = isset( $_GET[ self::TOKEN_PARAM ] ) ? preg_replace( '/[^A-Za-z0-9]/', '', sanitize_text_field( wp_unslash( $_GET[ self::TOKEN_PARAM ] ) ) ) : '';

		if ( '' === $token ) {
			return 0;
		}

		$request_id = (int) get_transient( 'resgata_adoption_done_' . $token );

		return $request_id && PostType::SLUG === get_post_type( $request_id ) ? $request_id : 0;
	}

	/**
	 * Chave do contador de envios deste endereço de rede. O endereço não é gravado: só um resumo irreversível.
	 */
	private static function rate_limit_key(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		return 'resgata_adoption_rl_' . md5( wp_hash( $ip ) );
	}

	/**
	 * Pedidos aceitos deste endereço de rede na última hora.
	 */
	private static function rate_limit_count(): int {
		return (int) get_transient( self::rate_limit_key() );
	}
}
