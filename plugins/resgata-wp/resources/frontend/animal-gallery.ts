/**
 * Galeria da página do animal: setas e miniaturas.
 *
 * Sem este script a galeria continua funcionando: as fotos rolam com o dedo e
 * as miniaturas são links para cada foto. Aqui só deixamos a troca mais suave
 * e marcamos a miniatura da foto que está à vista.
 */

function setup( viewer: HTMLElement ): void {
	const track = viewer.querySelector< HTMLElement >( '[data-rwp-viewer-track]' );
	const slides = Array.from( viewer.querySelectorAll< HTMLElement >( '[data-rwp-viewer-slide]' ) );
	const thumbs = Array.from( viewer.querySelectorAll< HTMLAnchorElement >( '[data-rwp-viewer-thumb]' ) );
	const prev = viewer.querySelector< HTMLButtonElement >( '[data-rwp-viewer-prev]' );
	const next = viewer.querySelector< HTMLButtonElement >( '[data-rwp-viewer-next]' );

	if ( ! track || slides.length < 2 ) {
		return;
	}

	const reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	let current = 0;

	const goTo = ( index: number ): void => {
		const target = Math.min( slides.length - 1, Math.max( 0, index ) );

		// Rola só a faixa de fotos, sem mexer na rolagem da página.
		track.scrollTo( {
			left: target * track.clientWidth,
			behavior: reducedMotion.matches ? 'auto' : 'smooth',
		} );
	};

	const update = (): void => {
		current = Math.min( slides.length - 1, Math.max( 0, Math.round( track.scrollLeft / track.clientWidth ) ) );

		thumbs.forEach( ( thumb, index ) => {
			if ( index === current ) {
				thumb.setAttribute( 'aria-current', 'true' );
			} else {
				thumb.removeAttribute( 'aria-current' );
			}
		} );

		if ( prev ) {
			prev.disabled = current === 0;
		}
		if ( next ) {
			next.disabled = current === slides.length - 1;
		}
	};

	let frame = 0;
	track.addEventListener(
		'scroll',
		() => {
			cancelAnimationFrame( frame );
			frame = requestAnimationFrame( update );
		},
		{ passive: true }
	);

	thumbs.forEach( ( thumb, index ) => {
		thumb.addEventListener( 'click', ( event ) => {
			event.preventDefault();
			goTo( index );
		} );
	} );

	prev?.addEventListener( 'click', () => goTo( current - 1 ) );
	next?.addEventListener( 'click', () => goTo( current + 1 ) );

	track.addEventListener( 'keydown', ( event ) => {
		if ( event.key === 'ArrowLeft' || event.key === 'ArrowRight' ) {
			event.preventDefault();
			goTo( current + ( event.key === 'ArrowRight' ? 1 : -1 ) );
		}
	} );

	if ( prev ) {
		prev.hidden = false;
	}
	if ( next ) {
		next.hidden = false;
	}

	update();
}

const start = (): void => document.querySelectorAll< HTMLElement >( '[data-rwp-viewer]' ).forEach( setup );

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', start );
} else {
	start();
}

export {};
