import { useRef, useState, type DragEvent } from 'react';
import { __ } from '@wordpress/i18n';
import { toast } from 'sonner';
import { CameraIcon, ImageUpIcon, Loader2Icon } from 'lucide-react';
import { api, errorMessage, type AnimalImage } from '@/lib/api';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';

/** Aparelhos de toque (celular/tablet): mostram também o atalho da câmera. */
const isTouchDevice = typeof window !== 'undefined' && window.matchMedia( '(pointer: coarse)' ).matches;

/**
 * Área para enviar imagens: arrastar e soltar, clicar para escolher ou, no
 * celular, tirar a foto na hora. Reutilizável em qualquer tela do painel.
 *
 * Cada imagem é reduzida no aparelho e enviada para a biblioteca de mídia;
 * `onUploaded` é chamado uma vez por imagem enviada.
 */
export function ImageDropzone( {
	onUploaded,
	multiple = false,
	animalId,
	className,
}: {
	onUploaded: ( image: AnimalImage ) => void;
	multiple?: boolean;
	animalId?: number;
	className?: string;
} ) {
	const fileInput = useRef< HTMLInputElement >( null );
	const cameraInput = useRef< HTMLInputElement >( null );
	const [ dragging, setDragging ] = useState( false );
	const [ pending, setPending ] = useState( 0 );
	const busy = pending > 0;

	const upload = async ( files: File[] ) => {
		const images = files.filter( ( file ) => file.type.startsWith( 'image/' ) );

		if ( images.length < files.length ) {
			toast.error( __( 'Só é possível enviar imagens.', 'resgata-pet' ) );
		}

		const list = multiple ? images : images.slice( 0, 1 );
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

	const onDrop = ( event: DragEvent< HTMLDivElement > ) => {
		event.preventDefault();
		setDragging( false );
		if ( ! busy ) {
			void upload( Array.from( event.dataTransfer.files ) );
		}
	};

	const fromInput = ( input: HTMLInputElement ) => {
		void upload( Array.from( input.files ?? [] ) );
		input.value = '';
	};

	return (
		<div
			className={ cn(
				'bg-muted/40 flex flex-col items-center gap-3 rounded-xl border-2 border-dashed p-6 text-center transition-colors',
				dragging ? 'border-primary bg-accent' : 'border-input',
				className
			) }
			onDragOver={ ( event ) => {
				event.preventDefault();
				setDragging( true );
			} }
			onDragLeave={ () => setDragging( false ) }
			onDrop={ onDrop }
		>
			<input
				ref={ fileInput }
				type="file"
				accept="image/*"
				multiple={ multiple }
				className="sr-only"
				tabIndex={ -1 }
				aria-hidden="true"
				onChange={ ( event ) => fromInput( event.target ) }
			/>
			<input
				ref={ cameraInput }
				type="file"
				accept="image/*"
				capture="environment"
				className="sr-only"
				tabIndex={ -1 }
				aria-hidden="true"
				onChange={ ( event ) => fromInput( event.target ) }
			/>

			{ busy ? (
				<Loader2Icon className="text-primary size-8 animate-spin" aria-hidden="true" />
			) : (
				<ImageUpIcon className="text-brand size-8" aria-hidden="true" />
			) }

			<p className="text-muted-foreground text-sm" role="status">
				{ busy && __( 'Enviando…', 'resgata-pet' ) }
				{ ! busy && multiple && __( 'Arraste as fotos para cá ou escolha do seu aparelho.', 'resgata-pet' ) }
				{ ! busy && ! multiple && __( 'Arraste a foto para cá ou escolha do seu aparelho.', 'resgata-pet' ) }
			</p>

			<div className="flex flex-wrap justify-center gap-2">
				<Button type="button" variant="outline" disabled={ busy } onClick={ () => fileInput.current?.click() }>
					{ multiple ? __( 'Escolher fotos', 'resgata-pet' ) : __( 'Escolher foto', 'resgata-pet' ) }
				</Button>
				{ isTouchDevice && (
					<Button type="button" variant="outline" disabled={ busy } onClick={ () => cameraInput.current?.click() }>
						<CameraIcon aria-hidden="true" />
						{ __( 'Tirar foto', 'resgata-pet' ) }
					</Button>
				) }
			</div>
		</div>
	);
}
