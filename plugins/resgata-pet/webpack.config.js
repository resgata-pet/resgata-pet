/**
 * Build do Resgata Pet (via @wordpress/scripts). Três configurações:
 *
 * - painel: aplicação React 19 de /painel. O React vai embutido no pacote e só
 *   os pacotes do WordPress que NÃO dependem de React ficam externos.
 * - site: campos de escolha das páginas públicas (Select do shadcn/ui), também
 *   com o React embutido; só é carregado nas páginas que têm um `<select>`.
 * - wp: scripts que rodam dentro do WordPress (editor de blocos e o
 *   site público), usando o React e os pacotes que o próprio WordPress fornece.
 */
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );

const PAINEL_EXTERNALS = {
	'@wordpress/api-fetch': { global: [ 'wp', 'apiFetch' ], handle: 'wp-api-fetch' },
	'@wordpress/i18n': { global: [ 'wp', 'i18n' ], handle: 'wp-i18n' },
};

const withoutPlugins = ( names ) =>
	defaultConfig.plugins.filter( ( plugin ) => ! names.includes( plugin.constructor.name ) );

const painel = {
	...defaultConfig,
	name: 'painel',
	entry: { index: path.resolve( __dirname, 'resources/painel/index.tsx' ) },
	output: { ...defaultConfig.output, path: path.resolve( __dirname, 'build/painel' ) },
	resolve: {
		...defaultConfig.resolve,
		alias: { ...defaultConfig.resolve.alias, '@': path.resolve( __dirname, 'resources/painel' ) },
	},
	plugins: [
		// Sem cópia de block.json nem CSS RTL (o RTL automático inverte utilitários do Tailwind).
		...withoutPlugins( [ 'DependencyExtractionWebpackPlugin', 'CopyPlugin', 'RtlCssPlugin' ] ),
		new DependencyExtractionWebpackPlugin( {
			useDefaults: false,
			requestToExternal: ( request ) => PAINEL_EXTERNALS[ request ]?.global,
			requestToHandle: ( request ) => PAINEL_EXTERNALS[ request ]?.handle,
		} ),
	],
};

// Site público: o Select do shadcn/ui (mesmo componente do painel), com o React embutido.
const site = {
	...painel,
	name: 'site',
	entry: { selects: path.resolve( __dirname, 'resources/site/selects.tsx' ) },
	output: { ...defaultConfig.output, path: path.resolve( __dirname, 'build/site' ) },
};

const wp = {
	...defaultConfig,
	name: 'wp',
	entry: {
		'blocks-editor': path.resolve( __dirname, 'resources/blocks/editor.tsx' ),
		'animal-gallery': path.resolve( __dirname, 'resources/frontend/animal-gallery.ts' ),
		pix: path.resolve( __dirname, 'resources/frontend/pix.ts' ),
	},
	output: { ...defaultConfig.output, path: path.resolve( __dirname, 'build/wp' ) },
	resolve: {
		...defaultConfig.resolve,
		// A biblioteca do QR Code do Pix (qrcode-pix → crc) espera o `Buffer` do Node.
		fallback: { ...defaultConfig.resolve.fallback, buffer: require.resolve( 'buffer/' ) },
	},
	plugins: withoutPlugins( [ 'CopyPlugin', 'RtlCssPlugin' ] ),
};

module.exports = [ painel, site, wp ];
