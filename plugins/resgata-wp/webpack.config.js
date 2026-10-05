/**
 * Build do Resgata WP (via @wordpress/scripts). Duas configurações:
 *
 * - painel: aplicação React 19 de /painel. O React vai embutido no pacote e só
 *   os pacotes do WordPress que NÃO dependem de React ficam externos.
 * - wp: scripts que rodam dentro do WordPress (editor de blocos e wp-admin),
 *   usando o React e os pacotes que o próprio WordPress fornece.
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

const wp = {
	...defaultConfig,
	name: 'wp',
	entry: {
		'blocks-editor': path.resolve( __dirname, 'resources/blocks/editor.tsx' ),
		'admin-animal': path.resolve( __dirname, 'resources/admin/animal.ts' ),
	},
	output: { ...defaultConfig.output, path: path.resolve( __dirname, 'build/wp' ) },
	plugins: withoutPlugins( [ 'CopyPlugin', 'RtlCssPlugin' ] ),
};

module.exports = [ painel, wp ];
