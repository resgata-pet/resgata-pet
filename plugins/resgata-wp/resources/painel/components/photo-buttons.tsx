import { useRef, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { toast } from 'sonner';
import { CameraIcon, ImagesIcon, Loader2Icon } from 'lucide-react';
import { api, errorMessage, type AnimalImage } from '@/lib/api';
import { Button } from '@/components/ui/button';

/**
 * Botões "Tirar foto" e "Escolher fotos". Cada foto é reduzida no aparelho e
 * enviada para a biblioteca de mídia; `onUploaded` recebe uma por vez.
 */
export function PhotoButtons( {
	animalId,
	multiple = false,
	onUploaded,
}: {
	animalId?: number;
	multiple?: boolean;
	onUploaded: ( image: AnimalImage ) => void;
} ) {
	const cameraInput = useRef< HTMLInputElement >( null );
	const libraryInput = useRef< HTMLInputElement >( null );
	const [ pending, setPending ] = useState( 0 );

	const upload = async ( files: FileList | null ) => {
		const list = Array.from( files ?? [] );
		if ( ! list.length ) {
			return;
		}

		setPending( ( count ) => count + list.length );

		for ( const file of list ) {
			try {
				onUploaded( await api.uploadPhoto( file, animalId ) );
			} catch ( error ) {
				toast.error( errorMessage( error ) );
			} finally {
				setPending( ( count ) => count - 1 );
			}
		}
	};

	const busy = pending > 0;

	return (
		<div className="flex flex-wrap items-center gap-2">
			<input
				ref={ cameraInput }
				type="file"
				accept="image/*"
				capture="environment"
				className="sr-only"
				tabIndex={ -1 }
				aria-hidden="true"
				onChange={ ( event ) => {
					void upload( event.target.files );
					event.target.value = '';
				} }
			/>
			<input
				ref={ libraryInput }
				type="file"
				accept="image/*"
				multiple={ multiple }
				className="sr-only"
				tabIndex={ -1 }
				aria-hidden="true"
				onChange={ ( event ) => {
					void upload( event.target.files );
					event.target.value = '';
				} }
			/>

			<Button type="button" variant="outline" disabled={ busy } onClick={ () => cameraInput.current?.click() }>
				<CameraIcon aria-hidden="true" />
				{ __( 'Tirar foto', 'resgata-wp' ) }
			</Button>
			<Button type="button" variant="outline" disabled={ busy } onClick={ () => libraryInput.current?.click() }>
				<ImagesIcon aria-hidden="true" />
				{ multiple ? __( 'Escolher fotos', 'resgata-wp' ) : __( 'Escolher foto', 'resgata-wp' ) }
			</Button>

			{ busy && (
				<span className="text-muted-foreground flex items-center gap-2 text-sm" role="status">
					<Loader2Icon className="size-4 animate-spin" aria-hidden="true" />
					{ __( 'Enviando…', 'resgata-wp' ) }
				</span>
			) }
		</div>
	);
}
