/**
 * Campos de escolha do site público.
 *
 * Troca cada `<select data-rwp-select>` das páginas públicas pelo Select do
 * shadcn/ui (o mesmo componente do painel). O `<select>` original continua na
 * página, escondido, e é ele que o formulário envia; sem este script (ou sem o
 * build) o visitante usa o campo nativo normalmente.
 */
import { useState } from 'react';
import { createRoot } from 'react-dom/client';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import './selects.css';

interface Item {
	/** `null` é a opção vazia ("Todas", "Escolha"). */
	value: string | null;
	label: string;
	disabled: boolean;
}

interface FieldProps {
	id: string;
	items: Item[];
	placeholder: string;
	initial: string | null;
	invalid: boolean;
	describedBy: string | null;
	required: boolean;
	disabled: boolean;
	onChange: ( value: string ) => void;
}

function Field( { id, items, placeholder, initial, invalid, describedBy, required, disabled, onChange }: FieldProps ) {
	const [ value, setValue ] = useState< string | null >( initial );

	return (
		<Select
			items={ items }
			value={ value }
			disabled={ disabled }
			onValueChange={ ( next ) => {
				const chosen = ( next as string | null ) ?? null;

				setValue( chosen );
				onChange( chosen ?? '' );
			} }
		>
			<SelectTrigger
				id={ id || undefined }
				className="rwp-ui"
				aria-invalid={ invalid || undefined }
				aria-describedby={ describedBy ?? undefined }
				aria-required={ required || undefined }
			>
				<SelectValue placeholder={ placeholder } />
			</SelectTrigger>
			{ /* Posição fixa: a barra do WordPress (para quem está logado) desloca a página e erraria a conta. */ }
			<SelectContent className="rwp-ui" alignItemWithTrigger={ false } positionMethod="fixed">
				{ items.map( ( item ) => (
					<SelectItem key={ item.value ?? '' } value={ item.value } disabled={ item.disabled }>
						{ item.label }
					</SelectItem>
				) ) }
			</SelectContent>
		</Select>
	);
}

function enhance( select: HTMLSelectElement ): void {
	if ( select.multiple || select.dataset.rwpSelectReady ) {
		return;
	}

	const options = Array.from( select.options );
	const empty = options.find( ( option ) => option.value === '' );
	const items: Item[] = options
		// Num campo obrigatório, a opção vazia ("Escolha") é só o texto de espera, não uma escolha.
		.filter( ( option ) => option.value !== '' || ! select.required )
		.map( ( option ) => ( {
			value: option.value === '' ? null : option.value,
			label: option.text,
			disabled: option.disabled,
		} ) );

	const id = select.id;
	const mount = document.createElement( 'span' );
	mount.className = 'rwp-select';
	select.after( mount );

	// O rótulo (`<label for>`) passa a apontar para o botão novo.
	select.removeAttribute( 'id' );
	select.hidden = true;
	select.tabIndex = -1;
	select.setAttribute( 'aria-hidden', 'true' );
	select.dataset.rwpSelectReady = '1';

	createRoot( mount ).render(
		<Field
			id={ id }
			items={ items }
			placeholder={ empty?.text ?? '' }
			initial={ select.value === '' ? null : select.value }
			invalid={ select.getAttribute( 'aria-invalid' ) === 'true' }
			describedBy={ select.getAttribute( 'aria-describedby' ) }
			required={ select.required }
			disabled={ select.disabled }
			onChange={ ( value ) => {
				select.value = value;
				select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			} }
		/>
	);
}

const start = (): void => document.querySelectorAll< HTMLSelectElement >( 'select[data-rwp-select]' ).forEach( enhance );

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', start );
} else {
	start();
}
