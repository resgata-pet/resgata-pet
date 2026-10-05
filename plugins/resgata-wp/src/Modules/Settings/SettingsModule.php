<?php
/**
 * Módulo de configurações.
 *
 * @package ResgataWP
 */

namespace ResgataWP\Modules\Settings;

use ResgataWP\Core\Modules;

defined( 'ABSPATH' ) || exit;

/**
 * Tela "Resgata WP → Configurações" (Settings API).
 */
final class SettingsModule {

	public const OPTION     = 'rwp_settings';
	public const GROUP      = 'rwp_settings_group';
	public const PAGE       = 'resgata-wp';
	public const CAPABILITY = 'manage_options';

	private const FLUSH_FLAG = 'rwp_flush_rewrite';

	/**
	 * Registra os hooks do módulo.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'update_option_' . Modules::OPTION, array( $this, 'schedule_rewrite_flush' ) );
		add_action( 'add_option_' . Modules::OPTION, array( $this, 'schedule_rewrite_flush' ) );
		add_action( 'init', array( $this, 'maybe_flush_rewrite' ), 99 );
		add_filter( 'plugin_action_links_' . plugin_basename( RWP_PLUGIN_FILE ), array( $this, 'action_links' ) );
	}

	/**
	 * Valores padrão das configurações gerais.
	 *
	 * @return array{keep_data: bool}
	 */
	public static function defaults(): array {
		return array(
			'keep_data' => true,
		);
	}

	/**
	 * Configurações gerais salvas, já com os padrões aplicados.
	 *
	 * @return array{keep_data: bool}
	 */
	public static function get(): array {
		$saved = get_option( self::OPTION, array() );

		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
	}

	/**
	 * Adiciona o menu do plugin no painel.
	 */
	public function add_menu(): void {
		add_menu_page(
			__( 'Resgata WP', 'resgata-wp' ),
			__( 'Resgata WP', 'resgata-wp' ),
			self::CAPABILITY,
			self::PAGE,
			array( $this, 'render_page' ),
			'dashicons-heart',
			58
		);

		add_submenu_page(
			self::PAGE,
			__( 'Configurações do Resgata WP', 'resgata-wp' ),
			__( 'Configurações', 'resgata-wp' ),
			self::CAPABILITY,
			self::PAGE,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Registra opções, seções e campos.
	 */
	public function register_settings(): void {
		register_setting(
			self::GROUP,
			Modules::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( Modules::class, 'sanitize' ),
				'default'           => Modules::defaults(),
				'show_in_rest'      => false,
			)
		);

		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::defaults(),
				'show_in_rest'      => false,
			)
		);

		add_settings_section(
			'rwp_section_modules',
			__( 'Módulos ativos', 'resgata-wp' ),
			static function (): void {
				echo '<p>' . esc_html__( 'Ligue somente o que a sua protetora vai usar. Desligar um módulo não apaga os dados dele.', 'resgata-wp' ) . '</p>';
			},
			self::PAGE
		);

		add_settings_field(
			'rwp_modules',
			__( 'Módulos', 'resgata-wp' ),
			array( $this, 'render_modules_field' ),
			self::PAGE,
			'rwp_section_modules'
		);

		add_settings_section(
			'rwp_section_data',
			__( 'Dados', 'resgata-wp' ),
			'__return_false',
			self::PAGE
		);

		add_settings_field(
			'rwp_keep_data',
			__( 'Ao excluir o plugin', 'resgata-wp' ),
			array( $this, 'render_keep_data_field' ),
			self::PAGE,
			'rwp_section_data'
		);
	}

	/**
	 * Sanitiza as configurações gerais.
	 *
	 * @param mixed $value Valor enviado pelo formulário.
	 * @return array{keep_data: bool}
	 */
	public function sanitize( $value ): array {
		$value = is_array( $value ) ? $value : array();

		return array(
			'keep_data' => ! empty( $value['keep_data'] ),
		);
	}

	/**
	 * Campo com a lista de módulos.
	 */
	public function render_modules_field(): void {
		$enabled = Modules::enabled();

		echo '<fieldset><legend class="screen-reader-text">' . esc_html__( 'Módulos', 'resgata-wp' ) . '</legend>';

		foreach ( Modules::all() as $slug => $module ) {
			printf(
				'<p><label><input type="checkbox" name="%1$s[]" value="%2$s" %3$s %4$s> <strong>%5$s</strong>%6$s</label><br><span class="description">%7$s</span></p>',
				esc_attr( Modules::OPTION ),
				esc_attr( $slug ),
				checked( in_array( $slug, $enabled, true ), true, false ),
				disabled( $module['available'], false, false ),
				esc_html( $module['label'] ),
				$module['available'] ? '' : ' <em>' . esc_html__( '(em breve)', 'resgata-wp' ) . '</em>',
				esc_html( $module['description'] )
			);
		}

		echo '</fieldset>';
	}

	/**
	 * Campo "manter os dados ao excluir o plugin".
	 */
	public function render_keep_data_field(): void {
		$settings = self::get();

		printf(
			'<label><input type="checkbox" name="%1$s[keep_data]" value="1" %2$s> %3$s</label><p class="description">%4$s</p>',
			esc_attr( self::OPTION ),
			checked( $settings['keep_data'], true, false ),
			esc_html__( 'Manter os dados (animais, fotos e configurações)', 'resgata-wp' ),
			esc_html__( 'Recomendado. Se você desmarcar, todos os dados do Resgata WP serão apagados para sempre quando o plugin for excluído.', 'resgata-wp' )
		);
	}

	/**
	 * Renderiza a tela de configurações.
	 */
	public function render_page(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Você não tem permissão para acessar esta página.', 'resgata-wp' ) );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php settings_errors(); ?>
			<form action="options.php" method="post">
				<?php
				// Gera o nonce; o `options.php` confere nonce e capability antes de salvar.
				settings_fields( self::GROUP );
				do_settings_sections( self::PAGE );
				submit_button( __( 'Salvar configurações', 'resgata-wp' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Link "Configurações" na lista de plugins.
	 *
	 * @param string[] $links Links existentes.
	 * @return string[]
	 */
	public function action_links( array $links ): array {
		array_unshift(
			$links,
			sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'admin.php?page=' . self::PAGE ) ),
				esc_html__( 'Configurações', 'resgata-wp' )
			)
		);

		return $links;
	}

	/**
	 * Marca que as regras de URL precisam ser recriadas (módulo ligado/desligado).
	 */
	public function schedule_rewrite_flush(): void {
		update_option( self::FLUSH_FLAG, 1, false );
	}

	/**
	 * Recria as regras de URL depois que os tipos de conteúdo foram registrados.
	 */
	public function maybe_flush_rewrite(): void {
		if ( get_option( self::FLUSH_FLAG ) ) {
			delete_option( self::FLUSH_FLAG );
			flush_rewrite_rules();
		}
	}
}
