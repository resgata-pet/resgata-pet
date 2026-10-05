/**
 * Reduz fotos no próprio aparelho antes do envio: fotos de celular têm de 5 a
 * 10 MB, o que deixa o envio lento em rede móvel.
 */
const MAX_SIDE = 2000;
const QUALITY = 0.85;
const SMALL_ENOUGH = 1.5 * 1024 * 1024;

export async function resizeImage( file: File ): Promise< File > {
	// GIFs (animação) e arquivos que não são imagem seguem como estão.
	if ( ! file.type.startsWith( 'image/' ) || file.type === 'image/gif' ) {
		return file;
	}

	try {
		const bitmap = await createImageBitmap( file, { imageOrientation: 'from-image' } );
		const scale = Math.min( 1, MAX_SIDE / Math.max( bitmap.width, bitmap.height ) );

		if ( scale === 1 && file.size <= SMALL_ENOUGH && file.type === 'image/jpeg' ) {
			bitmap.close();
			return file;
		}

		const canvas = document.createElement( 'canvas' );
		canvas.width = Math.round( bitmap.width * scale );
		canvas.height = Math.round( bitmap.height * scale );

		const context = canvas.getContext( '2d' );
		if ( ! context ) {
			return file;
		}

		// Fundo branco para imagens com transparência, já que a saída é JPEG.
		context.fillStyle = '#ffffff';
		context.fillRect( 0, 0, canvas.width, canvas.height );
		context.drawImage( bitmap, 0, 0, canvas.width, canvas.height );
		bitmap.close();

		const blob = await new Promise< Blob | null >( ( resolve ) => canvas.toBlob( resolve, 'image/jpeg', QUALITY ) );
		if ( ! blob ) {
			return file;
		}

		return new File( [ blob ], file.name.replace( /\.[^.]+$/, '' ) + '.jpg', { type: 'image/jpeg' } );
	} catch {
		// Formato que o navegador não consegue ler: envia o original.
		return file;
	}
}
