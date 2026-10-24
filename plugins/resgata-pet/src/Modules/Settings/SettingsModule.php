<?php
/**
 * Módulo de configurações.
 *
 * @package ResgataPet
 */

namespace ResgataPet\Modules\Settings;

use ResgataPet\Core\BlockAssets;
use ResgataPet\Core\Bindings;
use ResgataPet\Core\Links;
use ResgataPet\Core\Modules;
use ResgataPet\Core\ModuleVisibility;
use ResgataPet\Core\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Tela "Resgata Pet → Configurações" (Settings API).
 */
final class SettingsModule {

	public const OPTION     = 'resgata_settings';
	public const GROUP      = 'resgata_settings_group';
	public const PAGE       = 'resgata-pet';
	public const CAPABILITY = 'manage_options';

	/**
	 * Registra os hooks do módulo.
	 */
	public function register(): void {
		( new ContactBlock() )->register();
		( new Banners() )->register();

		( new PixDonation() )->register();

		// Ligações de blocos comuns (botões, parágrafos) aos links e dados do plugin, usadas pelos modelos do tema.
		add_action( 'init', array( Bindings::class, 'register' ) );
		add_action( 'init', array( $this, 'register_blocks' ), 21 );

		// Arquivos comuns dos blocos e as seções do tema que dependem de um módulo.
		BlockAssets::register();
		ModuleVisibility::register();

		// As páginas de apadrinhamento e "Como ajudar" podem ter sido criadas, alteradas ou apagadas.
		add_action( 'save_post_page', array( Links::class, 'forget_pages' ) );
		add_action( 'deleted_post', array( Links::class, 'forget_pages' ) );

		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'update_option_' . Modules::OPTION, array( $this, 'schedule_rewrite_flush' ) );
		add_action( 'add_option_' . Modules::OPTION, array( $this, 'schedule_rewrite_flush' ) );
		add_action( 'init', array( $this, 'maybe_flush_rewrite' ), 99 );
		add_filter( 'plugin_action_links_' . plugin_basename( RESGATA_PLUGIN_FILE ), array( $this, 'action_links' ) );
	}

	/**
	 * Registra os blocos "Links do site" e "Números da protetora", que servem a qualquer combinação de módulos.
	 */
	public function register_blocks(): void {
		register_block_type( RESGATA_PLUGIN_DIR . 'blocks/links' );
		register_block_type( RESGATA_PLUGIN_DIR . 'blocks/impact' );
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
			__( 'Resgata Pet', 'resgata-pet' ),
			__( 'Resgata Pet', 'resgata-pet' ),
			self::CAPABILITY,
			self::PAGE,
			array( $this, 'render_page' ),
			'dashicons-heart',
			58
		);

		add_submenu_page(
			self::PAGE,
			__( 'Configurações do Resgata Pet', 'resgata-pet' ),
			__( 'Configurações', 'resgata-pet' ),
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
			'resgata_section_modules',
			__( 'Módulos ativos', 'resgata-pet' ),
			static function (): void {
				echo '<p>' . esc_html__( 'Ligue somente o que a sua protetora vai usar. Desligar um módulo não apaga os dados dele.', 'resgata-pet' ) . '</p>';
			},
			self::PAGE
		);

		add_settings_field(
			'resgata_modules',
			__( 'Módulos', 'resgata-pet' ),
			array( $this, 'render_modules_field' ),
			self::PAGE,
			'resgata_section_modules'
		);

		add_settings_section(
			'resgata_section_data',
			__( 'Dados', 'resgata-pet' ),
			'__return_false',
			self::PAGE
		);

		add_settings_field(
			'resgata_keep_data',
			__( 'Ao excluir o plugin', 'resgata-pet' ),
			array( $this, 'render_keep_data_field' ),
			self::PAGE,
			'resgata_section_data'
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
		$enabled = Modules::chosen();

		echo '<fieldset><legend class="screen-reader-text">' . esc_html__( 'Módulos', 'resgata-pet' ) . '</legend>';

		foreach ( Modules::all() as $slug => $module ) {
			printf(
				'<p><label><input type="checkbox" name="%1$s[]" value="%2$s" %3$s %4$s> <strong>%5$s</strong>%6$s</label><br><span class="description">%7$s</span></p>',
				esc_attr( Modules::OPTION ),
				esc_attr( $slug ),
				checked( in_array( $slug, $enabled, true ), true, false ),
				disabled( $module['available'], false, false ),
				esc_html( $module['label'] ),
				$module['available'] ? '' : ' <em>' . esc_html__( '(em breve)', 'resgata-pet' ) . '</em>',
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
			esc_html__( 'Manter os dados (animais, fotos e configurações)', 'resgata-pet' ),
			esc_html__( 'Recomendado. Se você desmarcar, todos os dados do Resgata Pet serão apagados para sempre quando o plugin for excluído.', 'resgata-pet' )
		);
	}

	/**
	 * Renderiza a tela de configurações.
	 */
	public function render_page(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Você não tem permissão para acessar esta página.', 'resgata-pet' ) );
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
				submit_button( __( 'Salvar configurações', 'resgata-pet' ) );
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
				esc_html__( 'Configurações', 'resgata-pet' )
			)
		);

		return $links;
	}

	/**
	 * Marca que as regras de URL precisam ser recriadas (módulo ligado/desligado).
	 */
	public function schedule_rewrite_flush(): void {
		update_option( Schema::FLUSH_FLAG, 1, false );
	}

	/**
	 * Recria as regras de URL depois que os tipos de conteúdo foram registrados.
	 */
	public function maybe_flush_rewrite(): void {
		if ( get_option( Schema::FLUSH_FLAG ) ) {
			delete_option( Schema::FLUSH_FLAG );
			flush_rewrite_rules();
		}
	}
}
