/**
 * Tela de cadastro do animal no wp-admin: vacinas e galeria de fotos.
 */

interface MediaAttachment {
	id: number;
	url: string;
	sizes?: { thumbnail?: { url: string } };
}

interface MediaFrame {
	on( event: 'select', callback: () => void ): void;
	open(): void;
	state(): { get( key: 'selection' ): { toJSON(): MediaAttachment[] } };
}

declare global {
	interface Window {
		rwpAdminAnimal?: { mediaTitle: string; mediaButton: string };
		wp: {
			media( options: {
				title: string;
				button: { text: string };
				library: { type: string };
				multiple: boolean;
			} ): MediaFrame;
		};
	}
}

/** Acrescenta um item a partir do <template>, trocando o marcador de índice. */
function addFromTemplate( wrapper: HTMLElement, templateSelector: string, targetSelector: string ): HTMLElement | null {
	const template = wrapper.querySelector< HTMLTemplateElement >( templateSelector );
	const target = wrapper.querySelector< HTMLElement >( targetSelector );

	if ( ! template || ! target ) {
		return null;
	}

	const index = parseInt( wrapper.dataset.nextIndex ?? '0', 10 ) || 0;
	const holder = document.createElement( 'div' );

	holder.innerHTML = template.innerHTML.replace( /__i__/g, String( index ) ).trim();
	const item = holder.firstElementChild as HTMLElement | null;

	if ( item ) {
		wrapper.dataset.nextIndex = String( index + 1 );
		target.append( item );
	}

	return item;
}

function init(): void {
	let frame: MediaFrame | undefined;

	document.addEventListener( 'click', ( event ) => {
		const target = event.target as HTMLElement;

		if ( target.closest( '.rwp-remove-row' ) ) {
			target.closest( '.rwp-vaccines__row, .rwp-gallery__item' )?.remove();
			return;
		}

		const addVaccine = target.closest< HTMLElement >( '.rwp-vaccines__add' );
		if ( addVaccine ) {
			const wrapper = addVaccine.closest< HTMLElement >( '.rwp-vaccines' );
			const row = wrapper && addFromTemplate( wrapper, '.rwp-vaccines__template', '.rwp-vaccines__rows' );
			row?.querySelector< HTMLInputElement >( 'input[type="text"]' )?.focus();
			return;
		}

		const addPhoto = target.closest< HTMLElement >( '.rwp-gallery__add' );
		const gallery = addPhoto?.closest< HTMLElement >( '.rwp-gallery' );
		if ( ! gallery || ! window.rwpAdminAnimal ) {
			return;
		}

		if ( ! frame ) {
			frame = window.wp.media( {
				title: window.rwpAdminAnimal.mediaTitle,
				button: { text: window.rwpAdminAnimal.mediaButton },
				library: { type: 'image' },
				multiple: true,
			} );

			const mediaFrame = frame;
			mediaFrame.on( 'select', () => {
				const existing = new Set(
					Array.from( gallery.querySelectorAll< HTMLInputElement >( '.rwp-gallery__id' ) ).map( ( input ) =>
						parseInt( input.value, 10 )
					)
				);

				for ( const attachment of mediaFrame.state().get( 'selection' ).toJSON() ) {
					if ( existing.has( attachment.id ) ) {
						continue;
					}

					const item = addFromTemplate( gallery, '.rwp-gallery__template', '.rwp-gallery__items' );
					const image = item?.querySelector< HTMLImageElement >( 'img' );
					const input = item?.querySelector< HTMLInputElement >( '.rwp-gallery__id' );

					if ( image && input ) {
						image.src = attachment.sizes?.thumbnail?.url ?? attachment.url;
						input.value = String( attachment.id );
					}
				}
			} );
		}

		frame.open();
	} );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}

export {};
