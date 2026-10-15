<?php
/**
 * Eventos da Stripe já processados (tabela `resgata_stripe_events`).
 *
 * @package ResgataPet
 */

namespace ResgataPet\Modules\Sponsorship;

defined( 'ABSPATH' ) || exit;

/**
 * Garante que cada evento da Stripe seja processado uma única vez.
 *
 * A Stripe pode entregar o mesmo evento mais de uma vez. O ID do evento é a
 * chave primária da tabela: só a primeira entrega consegue inserir a linha.
 */
final class Events {

	public const CLEANUP_HOOK = 'resgata_stripe_events_cleanup';

	/**
	 * Por quantos dias um ID de evento fica guardado (a Stripe reenvia por até 3 dias).
	 */
	private const KEEP_DAYS = 30;

	/**
	 * Nome da tabela, com o prefixo do site.
	 */
	public static function table(): string {
		global $wpdb;

		return $wpdb->prefix . 'resgata_stripe_events';
	}

	/**
	 * Reserva um evento para processamento.
	 *
	 * @param string $event_id ID do evento (`evt_...`).
	 * @param string $type     Tipo do evento.
	 * @return bool True se é a primeira vez que o evento chega; false se já foi processado.
	 */
	public static function claim( string $event_id, string $type ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Tabela própria; INSERT IGNORE torna a reserva atômica.
		$inserted = $wpdb->query(
			$wpdb->prepare(
				'INSERT IGNORE INTO %i ( event_id, type, processed_at ) VALUES ( %s, %s, %s )',
				self::table(),
				$event_id,
				substr( $type, 0, 64 ),
				current_time( 'mysql', true )
			)
		);

		return 1 === $inserted;
	}

	/**
	 * Desfaz a reserva de um evento que não pôde ser processado, para a Stripe tentar de novo.
	 *
	 * @param string $event_id ID do evento.
	 */
	public static function release( string $event_id ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Tabela própria.
		$wpdb->delete( self::table(), array( 'event_id' => $event_id ), array( '%s' ) );
	}

	/**
	 * Apaga os IDs de eventos antigos (tarefa diária).
	 */
	public static function cleanup(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Tabela própria.
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i WHERE processed_at < %s',
				self::table(),
				gmdate( 'Y-m-d H:i:s', time() - self::KEEP_DAYS * DAY_IN_SECONDS )
			)
		);
	}
}
