import { __, sprintf } from '@wordpress/i18n';
import { CheckIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

/**
 * Indicador de passos de um formulário em etapas: círculos numerados ligados
 * por uma linha de progresso. No celular os rótulos somem e o passo atual é
 * descrito em uma linha abaixo.
 */
export function Stepper( {
	steps,
	current,
	canNavigate,
	onSelect,
	label,
}: {
	steps: string[];
	/** Passo atual, começando em 1. */
	current: number;
	/** Se os outros passos podem ser abertos com um clique. */
	canNavigate: boolean;
	onSelect: ( step: number ) => void;
	label: string;
} ) {
	return (
		<nav aria-label={ label } className="bg-card mb-6 rounded-xl border p-4 sm:px-6">
			<ol className="flex items-center">
				{ steps.map( ( name, index ) => {
					const number = index + 1;
					const isCurrent = number === current;
					const isDone = number < current;
					const isLast = number === steps.length;

					return (
						<li key={ name } className={ cn( 'flex items-center', ! isLast && 'flex-1' ) }>
							<button
								type="button"
								disabled={ ! canNavigate || isCurrent }
								aria-current={ isCurrent ? 'step' : undefined }
								aria-label={ sprintf(
									/* translators: 1: número do passo, 2: nome do passo. */
									__( 'Passo %1$d: %2$s', 'resgata-wp' ),
									number,
									name
								) }
								onClick={ () => onSelect( number ) }
								className={ cn(
									'group focus-visible:ring-ring/50 flex items-center gap-3 rounded-full text-left outline-none focus-visible:ring-3 sm:rounded-lg sm:pr-2',
									canNavigate && ! isCurrent && 'cursor-pointer'
								) }
							>
								<span
									className={ cn(
										'flex size-9 shrink-0 items-center justify-center rounded-full border-2 text-sm font-semibold transition-colors',
										isDone && 'border-primary bg-primary text-primary-foreground',
										isCurrent && 'border-primary bg-card text-primary ring-accent ring-4',
										! isDone && ! isCurrent && 'border-border bg-card text-muted-foreground',
										canNavigate && ! isCurrent && ! isDone && 'group-hover:border-brand group-hover:text-foreground'
									) }
								>
									{ isDone ? <CheckIcon className="size-4" aria-hidden="true" /> : number }
								</span>
								<span className="hidden flex-col sm:flex">
									<span className="text-muted-foreground text-xs leading-tight">
										{ /* translators: %d: número do passo. */ }
										{ sprintf( __( 'Passo %d', 'resgata-wp' ), number ) }
									</span>
									<span
										className={ cn(
											'text-sm leading-tight font-medium whitespace-nowrap',
											isCurrent || isDone ? 'text-foreground' : 'text-muted-foreground',
											canNavigate && ! isCurrent && 'group-hover:text-foreground'
										) }
									>
										{ name }
									</span>
								</span>
							</button>

							{ ! isLast && (
								<span
									aria-hidden="true"
									className={ cn( 'mx-2 h-0.5 min-w-4 flex-1 rounded-full transition-colors sm:mx-4', isDone ? 'bg-primary' : 'bg-border' ) }
								/>
							) }
						</li>
					);
				} ) }
			</ol>

			{ /* Celular: os rótulos não cabem ao lado dos círculos. */ }
			<p className="mt-3 text-sm sm:hidden">
				<span className="text-muted-foreground">
					{ sprintf(
						/* translators: 1: passo atual, 2: total de passos. */
						__( 'Passo %1$d de %2$d', 'resgata-wp' ),
						current,
						steps.length
					) }
				</span>
				{ ' · ' }
				<span className="font-medium">{ steps[ current - 1 ] }</span>
			</p>
		</nav>
	);
}
