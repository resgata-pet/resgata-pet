/**
 * Galeria da página do animal: setas e miniaturas. Também as setas da fileira
 * "Outros animais para adoção".
 *
 * Sem este script a galeria continua funcionando: as fotos rolam com o dedo e
 * as miniaturas são links para cada foto. Aqui só deixamos a troca mais suave
 * e marcamos a miniatura da foto que está à vista. A fileira de outros animais
 * rola pela barra de rolagem; com o script, ganha setas e a barra some.
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

/** Fileira que rola para o lado: setas que avançam quase uma tela de cartões por vez. */
function setupRow( row: HTMLElement ): void {
	const track = row.querySelector< HTMLElement >( '[data-rwp-row-track]' );
	const prev = row.querySelector< HTMLButtonElement >( '[data-rwp-row-prev]' );
	const next = row.querySelector< HTMLButtonElement >( '[data-rwp-row-next]' );

	if ( ! track || ! prev || ! next ) {
		return;
	}

	const reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	const move = ( direction: number ): void => {
		track.scrollBy( {
			left: direction * track.clientWidth * 0.8,
			behavior: reducedMotion.matches ? 'auto' : 'smooth',
		} );
	};

	const update = (): void => {
		const max = track.scrollWidth - track.clientWidth;

		// Tudo cabe na tela: sem setas.
		prev.hidden = max <= 1;
		next.hidden = max <= 1;
		// Folga de alguns pixels: o encaixe da rolagem para um pouco antes das pontas.
		prev.disabled = track.scrollLeft <= 4;
		next.disabled = track.scrollLeft >= max - 4;
	};

	let frame = 0;
	const schedule = (): void => {
		cancelAnimationFrame( frame );
		frame = requestAnimationFrame( update );
	};

	track.addEventListener( 'scroll', schedule, { passive: true } );
	new ResizeObserver( schedule ).observe( track );

	prev.addEventListener( 'click', () => move( -1 ) );
	next.addEventListener( 'click', () => move( 1 ) );

	// Avisa o CSS de que as setas existem: ele esconde a barra de rolagem.
	row.dataset.rwpRow = 'ready';
	update();
}

const start = (): void => {
	document.querySelectorAll< HTMLElement >( '[data-rwp-viewer]' ).forEach( setup );
	document.querySelectorAll< HTMLElement >( '[data-rwp-row]' ).forEach( setupRow );
};

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', start );
} else {
	start();
}

export {};
