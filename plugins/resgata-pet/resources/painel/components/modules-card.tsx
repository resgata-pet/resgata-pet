import { useEffect, useState, type ComponentType } from 'react';
import { useMutation, useQuery } from '@tanstack/react-query';
import { __, sprintf } from '@wordpress/i18n';
import { toast } from 'sonner';
import { HandCoinsIcon, HeartHandshakeIcon, Loader2Icon, PawPrintIcon, PuzzleIcon, ReceiptTextIcon } from 'lucide-react';
import { api, errorMessage, type SiteModule } from '@/lib/api';
import { cn } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Switch } from '@/components/ui/switch';

const ICONS: Record< string, ComponentType< { className?: string } > > = {
	animals: PawPrintIcon,
	adoptions: HeartHandshakeIcon,
	sponsorship: HandCoinsIcon,
	accounting: ReceiptTextIcon,
};

/**
 * "Recursos do site": liga e desliga os módulos (animais, adoção, apadrinhamento).
 *
 * O menu, as telas e os dados do painel vêm do servidor ao abrir a página, então ela recarrega depois de salvar.
 */
export function ModulesCard() {
	const query = useQuery( { queryKey: [ 'modules' ], queryFn: api.getModules } );
	const [ chosen, setChosen ] = useState< string[] | null >( null );

	useEffect( () => {
		if ( query.data && chosen === null ) {
			setChosen( query.data.modules.filter( ( module ) => module.enabled ).map( ( module ) => module.slug ) );
		}
	}, [ query.data, chosen ] );

	const save = useMutation( {
		mutationFn: ( modules: string[] ) => api.saveModules( modules ),
		onSuccess: () => window.location.reload(),
		onError: ( error ) => toast.error( errorMessage( error ) ),
	} );

	if ( query.isError ) {
		return (
			<p className="text-destructive" role="alert">
				{ errorMessage( query.error ) }
			</p>
		);
	}

	if ( ! query.data || ! chosen ) {
		return <Skeleton className="h-72 w-full" aria-busy="true" />;
	}

	const modules = query.data.modules;
	const labels = Object.fromEntries( modules.map( ( module ) => [ module.slug, module.label ] ) );
	const isOn = ( slug: string ) => chosen.includes( slug );
	const toggle = ( slug: string, checked: boolean ) =>
		setChosen( checked ? [ ...chosen.filter( ( item ) => item !== slug ), slug ] : chosen.filter( ( item ) => item !== slug ) );
	const changed = modules.some( ( module ) => module.enabled !== isOn( module.slug ) );

	/** Por que o interruptor está travado, ou vazio se ele pode ser usado. */
	const lockReason = ( module: SiteModule ) => {
		if ( ! module.available ) {
			return __( 'Em breve', 'resgata-pet' );
		}

		if ( module.requires && ! isOn( module.requires ) ) {
			/* translators: %s: nome do recurso de que este depende (ex.: Animais). */
			return sprintf( __( 'Ligue "%s" para usar', 'resgata-pet' ), labels[ module.requires ] ?? module.requires );
		}

		return '';
	};

	return (
		<Card>
			<CardHeader>
				<CardTitle>{ __( 'Recursos do site', 'resgata-pet' ) }</CardTitle>
				<CardDescription>
					{ __(
						'Escolha o que a sua protetora usa. O site e o painel mostram só o que estiver ligado. Desligar não apaga nada: os cadastros voltam quando você ligar de novo.',
						'resgata-pet'
					) }
				</CardDescription>
			</CardHeader>
			<CardContent>
				<ul className="divide-y rounded-xl border">
					{ modules.map( ( module ) => {
						const Icon = ICONS[ module.slug ] ?? PuzzleIcon;
						const reason = lockReason( module );

						return (
							<li key={ module.slug }>
								<label className={ cn( 'flex items-start gap-3 p-4', reason === '' && 'cursor-pointer' ) }>
									<Icon className="text-brand mt-0.5 size-6 shrink-0" aria-hidden="true" />
									<span className="min-w-0 flex-1">
										<span className="flex flex-wrap items-center gap-2 font-medium">
											{ module.label }
											{ reason && <Badge variant="muted">{ reason }</Badge> }
										</span>
										<span className="text-muted-foreground block text-sm">{ module.description }</span>
									</span>
									<span className="flex min-h-11 items-center">
										<Switch
											checked={ reason === '' && isOn( module.slug ) }
											disabled={ reason !== '' || save.isPending }
											onCheckedChange={ ( checked ) => toggle( module.slug, checked ) }
										/>
									</span>
								</label>
							</li>
						);
					} ) }
				</ul>
			</CardContent>
			<CardFooter className="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center sm:justify-between">
				<p className="text-muted-foreground text-sm">{ __( 'Ao salvar, o painel recarrega com os menus novos.', 'resgata-pet' ) }</p>
				<Button type="button" disabled={ ! changed || save.isPending } onClick={ () => save.mutate( chosen ) }>
					{ save.isPending && <Loader2Icon className="animate-spin" aria-hidden="true" /> }
					{ __( 'Salvar recursos', 'resgata-pet' ) }
				</Button>
			</CardFooter>
		</Card>
	);
}
