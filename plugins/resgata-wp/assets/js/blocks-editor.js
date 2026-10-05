/* Resgata WP – registro dos blocos no editor (sem etapa de build). */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
	var registerBlockType = wp.blocks.registerBlockType;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var ServerSideRender = wp.serverSideRender;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var RangeControl = wp.components.RangeControl;
	var ToggleControl = wp.components.ToggleControl;
	var CheckboxControl = wp.components.CheckboxControl;
	var Disabled = wp.components.Disabled;
	var data = window.rwpBlocks || { species: {}, statuses: {} };

	function toOptions( map, emptyLabel ) {
		var options = emptyLabel ? [ { value: '', label: emptyLabel } ] : [];
		Object.keys( map ).forEach( function ( key ) {
			options.push( { value: key, label: map[ key ] } );
		} );
		return options;
	}

	registerBlockType( 'resgata-wp/animals-list', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;

			function toggleStatus( status, checked ) {
				var next = attributes.statuses.filter( function ( item ) {
					return item !== status;
				} );
				if ( checked ) {
					next.push( status );
				}
				setAttributes( { statuses: next } );
			}

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Quais animais mostrar', 'resgata-wp' ) },
						el( SelectControl, {
							label: __( 'Espécie', 'resgata-wp' ),
							value: attributes.species,
							options: toOptions( data.species, __( 'Todas', 'resgata-wp' ) ),
							onChange: function ( value ) {
								setAttributes( { species: value } );
							},
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
						} ),
						el( 'p', { style: { marginTop: '16px', marginBottom: '8px' } }, __( 'Situação', 'resgata-wp' ) ),
						Object.keys( data.statuses ).map( function ( status ) {
							return el( CheckboxControl, {
								key: status,
								label: data.statuses[ status ],
								checked: attributes.statuses.indexOf( status ) !== -1,
								onChange: function ( checked ) {
									toggleStatus( status, checked );
								},
								__nextHasNoMarginBottom: true,
							} );
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Aparência', 'resgata-wp' ) },
						el( RangeControl, {
							label: __( 'Animais por página', 'resgata-wp' ),
							value: attributes.perPage,
							min: 1,
							max: 48,
							onChange: function ( value ) {
								setAttributes( { perPage: value || 12 } );
							},
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
						} ),
						el( RangeControl, {
							label: __( 'Colunas', 'resgata-wp' ),
							value: attributes.columns,
							min: 1,
							max: 6,
							onChange: function ( value ) {
								setAttributes( { columns: value || 3 } );
							},
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
						} ),
						el( ToggleControl, {
							label: __( 'Mostrar filtros para o visitante', 'resgata-wp' ),
							checked: attributes.showFilters,
							onChange: function ( value ) {
								setAttributes( { showFilters: value } );
							},
							__nextHasNoMarginBottom: true,
						} ),
						el( ToggleControl, {
							label: __( 'Mostrar paginação', 'resgata-wp' ),
							checked: attributes.showPagination,
							onChange: function ( value ) {
								setAttributes( { showPagination: value } );
							},
							__nextHasNoMarginBottom: true,
						} )
					)
				),
				el(
					'div',
					useBlockProps(),
					el(
						Disabled,
						null,
						el( ServerSideRender, {
							block: 'resgata-wp/animals-list',
							attributes: attributes,
							skipBlockSupportAttributes: true,
						} )
					)
				)
			);
		},
		save: function () {
			return null;
		},
	} );

	registerBlockType( 'resgata-wp/animal-details', {
		edit: function ( props ) {
			var attributes = props.attributes;

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'O que mostrar', 'resgata-wp' ) },
						el( SelectControl, {
							label: __( 'Parte da ficha', 'resgata-wp' ),
							value: attributes.section,
							options: [
								{ value: 'all', label: __( 'Tudo (dados, saúde e fotos)', 'resgata-wp' ) },
								{ value: 'facts', label: __( 'Somente os dados', 'resgata-wp' ) },
								{ value: 'health', label: __( 'Somente a saúde', 'resgata-wp' ) },
								{ value: 'gallery', label: __( 'Somente as fotos (antes e depois)', 'resgata-wp' ) },
							],
							onChange: function ( value ) {
								props.setAttributes( { section: value } );
							},
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
						} )
					)
				),
				el(
					'div',
					useBlockProps(),
					el(
						Disabled,
						null,
						el( ServerSideRender, {
							block: 'resgata-wp/animal-details',
							attributes: attributes,
							skipBlockSupportAttributes: true,
							urlQueryArgs: props.context && props.context.postId ? { post_id: props.context.postId } : {},
						} )
					)
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
