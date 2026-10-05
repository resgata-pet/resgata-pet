import { useMemo } from 'react';
import type { OptionMap } from '@/lib/config';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

/**
 * Campo de escolha (shadcn/ui Select) a partir de um mapa valor => rótulo.
 * Com `emptyLabel`, a lista ganha uma opção vazia (ex.: "Não informado").
 */
export function OptionSelect( {
	id,
	value,
	options,
	emptyLabel,
	placeholder,
	invalid,
	className,
	ariaLabel,
	onChange,
}: {
	id?: string;
	value: string;
	options: OptionMap;
	emptyLabel?: string;
	placeholder?: string;
	invalid?: boolean;
	className?: string;
	ariaLabel?: string;
	onChange: ( value: string ) => void;
} ) {
	const items = useMemo( () => {
		const list: { value: string | null; label: string }[] = Object.entries( options ).map( ( [ key, label ] ) => ( {
			value: key,
			label,
		} ) );

		return emptyLabel === undefined ? list : [ { value: null, label: emptyLabel }, ...list ];
	}, [ options, emptyLabel ] );

	return (
		<Select items={ items } value={ value === '' ? null : value } onValueChange={ ( next ) => onChange( ( next as string | null ) ?? '' ) }>
			<SelectTrigger id={ id } className={ className } aria-invalid={ invalid || undefined } aria-label={ ariaLabel }>
				<SelectValue placeholder={ placeholder ?? emptyLabel } />
			</SelectTrigger>
			<SelectContent alignItemWithTrigger={ false }>
				{ items.map( ( item ) => (
					<SelectItem key={ item.value ?? '' } value={ item.value }>
						{ item.label }
					</SelectItem>
				) ) }
			</SelectContent>
		</Select>
	);
}
