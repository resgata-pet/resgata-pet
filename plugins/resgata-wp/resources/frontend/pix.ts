/**
 * Doação por Pix: QR Code, "Pix copia e cola" e valores sugeridos.
 *
 * O servidor entrega a chave, o nome e a cidade já no formato do Pix; aqui a
 * biblioteca `qrcode-pix` monta o código e o desenho do QR. Sem este script a
 * página continua mostrando a chave e o titular.
 */
import { QrCodePix } from 'qrcode-pix';

/** Copia o texto e troca o rótulo do botão por "Copiado!" por alguns segundos. */
async function copy( text: string, button: HTMLElement, status: HTMLElement | null ): Promise< void > {
	try {
		await navigator.clipboard.writeText( text );
	} catch {
		// Navegador sem permissão para copiar: a pessoa ainda pode selecionar o texto na tela.
		return;
	}

	const label = button.querySelector< HTMLElement >( '[data-rwp-pix-copy-label]' );

	if ( ! label ) {
		return;
	}

	const original = label.dataset.original ?? label.textContent ?? '';
	label.dataset.original = original;
	label.textContent = label.dataset.done ?? original;

	if ( status ) {
		status.textContent = label.textContent;
	}

	window.setTimeout( () => {
		label.textContent = original;
		if ( status ) {
			status.textContent = '';
		}
	}, 2500 );
}

function setup( root: HTMLElement ): void {
	const { key, name, city } = root.dataset;
	const qr = root.querySelector< HTMLElement >( '[data-rwp-pix-qr]' );
	const image = root.querySelector< HTMLImageElement >( '[data-rwp-pix-image]' );
	const code = root.querySelector< HTMLElement >( '[data-rwp-pix-code]' );
	const payload = root.querySelector< HTMLInputElement >( '[data-rwp-pix-payload]' );
	const amounts = root.querySelector< HTMLElement >( '[data-rwp-pix-amounts]' );
	const status = root.querySelector< HTMLElement >( '[data-rwp-pix-status]' );

	if ( ! key || ! name || ! city ) {
		return;
	}

	const render = async ( value?: number ): Promise< void > => {
		let pix: ReturnType< typeof QrCodePix >;

		try {
			pix = QrCodePix( { version: '01', key, name, city, value } );
		} catch {
			// Dados fora do formato do Pix: fica só a chave, que já está na página.
			return;
		}

		if ( payload && code ) {
			payload.value = pix.payload();
			code.hidden = false;
		}

		if ( image && qr ) {
			image.src = await pix.base64( { width: 440, margin: 1 } );
			qr.hidden = false;
		}

		if ( amounts ) {
			amounts.hidden = false;
		}
	};

	amounts?.addEventListener( 'change', ( event ) => {
		const input = event.target as HTMLInputElement;

		void render( input.value === '' ? undefined : Number( input.value ) );
	} );

	root.querySelectorAll< HTMLButtonElement >( '[data-rwp-pix-copy]' ).forEach( ( button ) => {
		button.hidden = ! navigator.clipboard;
		button.addEventListener( 'click', () => void copy( button.dataset.rwpPixCopy ?? '', button, status ) );
	} );

	const copyCode = root.querySelector< HTMLButtonElement >( '[data-rwp-pix-copy-code]' );
	if ( copyCode ) {
		copyCode.hidden = ! navigator.clipboard;
		copyCode.addEventListener( 'click', () => void copy( payload?.value ?? '', copyCode, status ) );
	}

	payload?.addEventListener( 'focus', () => payload.select() );

	void render();
}

const start = (): void => document.querySelectorAll< HTMLElement >( '[data-rwp-pix]' ).forEach( setup );

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', start );
} else {
	start();
}
