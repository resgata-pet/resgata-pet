import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import { Label } from '@/components/ui/label';

/** Rótulo + campo + texto de ajuda/erro, empilhados. */
export function Field( {
	label,
	htmlFor,
	hint,
	error,
	className,
	children,
}: {
	label: string;
	htmlFor?: string;
	hint?: string;
	error?: string;
	className?: string;
	children: ReactNode;
} ) {
	return (
		<div className={ cn( 'flex flex-col gap-2', className ) }>
			<Label htmlFor={ htmlFor } className="text-base">
				{ label }
			</Label>
			{ children }
			{ error ? (
				<p className="text-destructive text-sm" role="alert">
					{ error }
				</p>
			) : (
				hint && <p className="text-muted-foreground text-sm">{ hint }</p>
			) }
		</div>
	);
}
