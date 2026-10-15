import { useLayoutEffect, useRef, type ComponentProps } from 'react';
import { Input } from '@/components/ui/input';
import type { Mask } from '@/lib/masks';

type MaskedInputProps = Omit< ComponentProps< typeof Input >, 'value' | 'onChange' | 'defaultValue' > & {
	/** Máscara de `lib/masks.ts` (`maskCnpj`, `maskPhone`...) ou criada com `createMask()`. */
	mask: Mask;
	value: string;
	/** Recebe o valor já formatado. */
	onValueChange: ( value: string ) => void;
};

const SIGNIFICANT = /[A-Za-z0-9]/;

/** Posição logo depois do enésimo caractere que não é pontuação. */
function caretAfter( text: string, count: number ): number {
	if ( count <= 0 ) {
		return 0;
	}

	let seen = 0;

	for ( let index = 0; index < text.length; index++ ) {
		if ( SIGNIFICANT.test( text[ index ] ) && ++seen === count ) {
			return index + 1;
		}
	}

	return text.length;
}

/** Campo de texto que formata o valor enquanto a pessoa digita, sem tirar o cursor do lugar. */
export function MaskedInput( { mask, value, onValueChange, ...props }: MaskedInputProps ) {
	const ref = useRef< HTMLInputElement >( null );
	const caret = useRef< number | null >( null );

	// Ao corrigir um caractere no meio do texto, o navegador jogaria o cursor para o fim.
	useLayoutEffect( () => {
		if ( caret.current !== null && ref.current && document.activeElement === ref.current ) {
			ref.current.setSelectionRange( caret.current, caret.current );
		}

		caret.current = null;
	} );

	return (
		<Input
			{ ...props }
			ref={ ref }
			value={ mask( value ) }
			onChange={ ( event ) => {
				const typed = event.target.value;
				const masked = mask( typed );
				const position = event.target.selectionStart ?? typed.length;
				const before = mask( typed.slice( 0, position ) ).replace( /[^A-Za-z0-9]/g, '' ).length;

				caret.current = caretAfter( masked, before );
				onValueChange( masked );
			} }
		/>
	);
}
