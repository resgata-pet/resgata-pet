/* Resgata WP – tela de cadastro do animal: vacinas e galeria de fotos. */
( function ( $ ) {
	'use strict';

	function addFromTemplate( $wrapper, templateSelector, targetSelector ) {
		var index = parseInt( $wrapper.attr( 'data-next-index' ), 10 ) || 0;
		var html = $wrapper.find( templateSelector ).html().replace( /__i__/g, String( index ) );
		var $item = $( html.trim() );

		$wrapper.attr( 'data-next-index', index + 1 );
		$wrapper.find( targetSelector ).append( $item );

		return $item;
	}

	$( function () {
		var frame;

		$( document ).on( 'click', '.rwp-remove-row', function () {
			$( this ).closest( '.rwp-vaccines__row, .rwp-gallery__item' ).remove();
		} );

		$( '.rwp-vaccines__add' ).on( 'click', function () {
			var $wrapper = $( this ).closest( '.rwp-vaccines' );
			addFromTemplate( $wrapper, '.rwp-vaccines__template', '.rwp-vaccines__rows' )
				.find( 'input[type="text"]' )
				.trigger( 'focus' );
		} );

		$( '.rwp-gallery__add' ).on( 'click', function () {
			var $wrapper = $( this ).closest( '.rwp-gallery' );

			if ( ! frame ) {
				frame = wp.media( {
					title: window.rwpAdminAnimal.mediaTitle,
					button: { text: window.rwpAdminAnimal.mediaButton },
					library: { type: 'image' },
					multiple: true,
				} );

				frame.on( 'select', function () {
					frame.state().get( 'selection' ).each( function ( attachment ) {
						var data = attachment.toJSON();
						var alreadyAdded = $wrapper.find( '.rwp-gallery__id' ).filter( function () {
							return parseInt( this.value, 10 ) === data.id;
						} ).length;
						var src, $item;

						if ( alreadyAdded ) {
							return;
						}

						src = data.sizes && data.sizes.thumbnail ? data.sizes.thumbnail.url : data.url;
						$item = addFromTemplate( $wrapper, '.rwp-gallery__template', '.rwp-gallery__items' );
						$item.find( 'img' ).attr( 'src', src );
						$item.find( '.rwp-gallery__id' ).val( data.id );
					} );
				} );
			}

			frame.open();
		} );
	} );
} )( jQuery );
