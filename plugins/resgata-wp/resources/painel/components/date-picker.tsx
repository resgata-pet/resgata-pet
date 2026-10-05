import { useState } from 'react';
import { ptBR } from 'react-day-picker/locale';
import { __ } from '@wordpress/i18n';
import { CalendarIcon } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';

/** "AAAA-MM-DD" -> Date no fuso do aparelho (sem o deslocamento de `new Date( string )`). */
function parse( value: string ): Date | undefined {
	const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec( value );

	return match ? new Date( Number( match[ 1 ] ), Number( match[ 2 ] ) - 1, Number( match[ 3 ] ) ) : undefined;
}

function toIso( date: Date ): string {
	const pad = ( number: number ) => String( number ).padStart( 2, '0' );

	return `${ date.getFullYear() }-${ pad( date.getMonth() + 1 ) }-${ pad( date.getDate() ) }`;
}

/**
 * Campo de data (shadcn/ui: Popover + Calendar). O valor é sempre "AAAA-MM-DD" ou vazio.
 */
export function DatePicker( {
	id,
	value,
	onChange,
	allowFuture = false,
	ariaLabel,
	className,
}: {
	id?: string;
	value: string;
	onChange: ( value: string ) => void;
	allowFuture?: boolean;
	ariaLabel?: string;
	className?: string;
} ) {
	const [ open, setOpen ] = useState( false );
	const selected = parse( value );
	const today = new Date();

	return (
		<Popover open={ open } onOpenChange={ setOpen }>
			<PopoverTrigger
				render={
					<Button
						id={ id }
						type="button"
						variant="outline"
						aria-label={ ariaLabel }
						className={ cn( 'border-input w-full justify-start font-normal', ! selected && 'text-muted-foreground', className ) }
					/>
				}
			>
				<CalendarIcon aria-hidden="true" />
				{ selected ? selected.toLocaleDateString( 'pt-BR' ) : __( 'Escolha a data', 'resgata-wp' ) }
			</PopoverTrigger>
			<PopoverContent align="start" className="w-auto p-2">
				<Calendar
					mode="single"
					locale={ ptBR }
					captionLayout="dropdown"
					selected={ selected }
					defaultMonth={ selected ?? today }
					startMonth={ new Date( today.getFullYear() - 30, 0 ) }
					endMonth={ allowFuture ? new Date( today.getFullYear() + 5, 11 ) : today }
					disabled={ allowFuture ? undefined : { after: today } }
					onSelect={ ( date ) => {
						onChange( date ? toIso( date ) : '' );
						setOpen( false );
					} }
				/>
				{ selected && (
					<Button
						type="button"
						variant="ghost"
						size="sm"
						className="self-start"
						onClick={ () => {
							onChange( '' );
							setOpen( false );
						} }
					>
						{ __( 'Limpar data', 'resgata-wp' ) }
					</Button>
				) }
			</PopoverContent>
		</Popover>
	);
}
