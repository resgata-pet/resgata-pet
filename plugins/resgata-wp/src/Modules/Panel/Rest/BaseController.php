<?php
/**
 * Base das rotas da API do painel.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Panel\Rest;

use ResgataWP\Core\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Toda rota do painel é registrada por `route()`, que nega acesso por padrão:
 * exige usuário logado, permissão de acesso ao painel e a verificação própria
 * da rota. Assim não existe rota em `resgata-wp/v1` sem `permission_callback`.
 */
abstract class BaseController {

	public const REST_NAMESPACE = 'resgata-wp/v1';

	/**
	 * Registra as rotas do controller.
	 */
	abstract public function register_routes(): void;

	/**
	 * Registra uma rota protegida.
	 *
	 * @param string                              $path       Caminho da rota.
	 * @param string                              $methods    Métodos HTTP (constantes de WP_REST_Server).
	 * @param callable                            $callback   Função que atende a requisição.
	 * @param callable(\WP_REST_Request): bool    $permission Verificação de permissão própria da rota.
	 * @param array<string, array<string, mixed>> $args       Parâmetros aceitos (schema).
	 */
	protected function route( string $path, string $methods, callable $callback, callable $permission, array $args = array() ): void {
		foreach ( $args as $name => $schema ) {
			// Valida e converte cada parâmetro de acordo com o schema declarado.
			$args[ $name ] += array(
				'validate_callback' => 'rest_validate_request_arg',
				'sanitize_callback' => 'rest_sanitize_request_arg',
			);
		}

		register_rest_route(
			self::REST_NAMESPACE,
			$path,
			array(
				'methods'             => $methods,
				'callback'            => $callback,
				'permission_callback' => fn( \WP_REST_Request $request ) => $this->guard( $request, $permission ),
				'args'                => $args,
			)
		);
	}

	/**
	 * Verificação comum a todas as rotas.
	 *
	 * @param \WP_REST_Request                 $request    Requisição.
	 * @param callable(\WP_REST_Request): bool $permission Verificação própria da rota.
	 * @return true|\WP_Error
	 */
	private function guard( \WP_REST_Request $request, callable $permission ) {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error( 'resgata_rest_not_logged_in', __( 'Faça login para continuar.', 'resgata-wp' ), array( 'status' => 401 ) );
		}

		if ( ! current_user_can( Capabilities::PANEL ) || true !== $permission( $request ) ) {
			return new \WP_Error( 'resgata_rest_forbidden', __( 'Você não tem permissão para fazer isso.', 'resgata-wp' ), array( 'status' => 403 ) );
		}

		return true;
	}
}
