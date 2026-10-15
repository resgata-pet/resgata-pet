import type { ComponentType } from 'react';
import { NavLink, Outlet } from 'react-router';
import { __ } from '@wordpress/i18n';
import { useQuery } from '@tanstack/react-query';
import { ArrowLeftIcon, ExternalLinkIcon, HandCoinsIcon, HeartHandshakeIcon, HomeIcon, ImagesIcon, PawPrintIcon, SettingsIcon, UserIcon } from 'lucide-react';
import { api } from '@/lib/api';
import { cn } from '@/lib/utils';
import { config } from '@/lib/config';

interface NavItem {
	to: string;
	label: string;
	/** Rótulo curto para a navegação inferior do celular. */
	mobileLabel?: string;
	icon: ComponentType< { className?: string } >;
	end?: boolean;
	/** Fica só no menu lateral do computador; no celular, o acesso é por outra tela. */
	desktopOnly?: boolean;
	/** Mostra o contador de pedidos novos ao lado do item. */
	showNewAdoptions?: boolean;
}

const items: NavItem[] = [
	{ to: '/', label: __( 'Início', 'resgata-pet' ), icon: HomeIcon, end: true },
	{ to: '/animais', label: __( 'Animais', 'resgata-pet' ), icon: PawPrintIcon },
	...( config.user.canManageAdoptions
		? [ { to: '/adocoes', label: __( 'Adoções', 'resgata-pet' ), icon: HeartHandshakeIcon, showNewAdoptions: true } ]
		: [] ),
	...( config.user.canManageSponsorship ? [ { to: '/apadrinhamento', label: __( 'Padrinhos', 'resgata-pet' ), icon: HandCoinsIcon } ] : [] ),
	// No celular, os banners são abertos pela tela de Configurações (a barra inferior não comporta mais um item).
	...( config.user.canManageSettings ? [ { to: '/banners', label: __( 'Banners', 'resgata-pet' ), icon: ImagesIcon, desktopOnly: true } ] : [] ),
	...( config.user.canManageSettings
		? [ { to: '/configuracoes', label: __( 'Configurações', 'resgata-pet' ), mobileLabel: __( 'Ajustes', 'resgata-pet' ), icon: SettingsIcon } ]
		: [] ),
	{ to: '/conta', label: __( 'Minha conta', 'resgata-pet' ), mobileLabel: __( 'Conta', 'resgata-pet' ), icon: UserIcon },
];

function Brand() {
	return (
		<div className="flex min-w-0 items-center gap-3">
			<span className="bg-brand flex size-9 shrink-0 items-center justify-center rounded-xl text-white">
				<PawPrintIcon className="size-5" aria-hidden="true" />
			</span>
			<span className="truncate font-semibold">{ config.siteName }</span>
		</div>
	);
}

function CountBadge( { count, className }: { count: number; className?: string } ) {
	if ( count < 1 ) {
		return null;
	}

	return (
		<span
			className={ cn(
				'bg-primary text-primary-foreground flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-xs font-semibold',
				className
			) }
		>
			{ count > 99 ? '99+' : count }
			<span className="sr-only"> { __( 'pedidos novos', 'resgata-pet' ) }</span>
		</span>
	);
}

export function Layout() {
	// Pedidos de adoção ainda não vistos, para o aviso no menu.
	const newAdoptions = useQuery( {
		queryKey: [ 'adoptions', 'new-count' ],
		queryFn: () => api.listAdoptions( 1, 25, 'new' ),
		enabled: config.user.canManageAdoptions,
		refetchOnWindowFocus: true,
		staleTime: 60_000,
	} );
	const newCount = newAdoptions.data?.total ?? 0;

	return (
		<div className="min-h-dvh md:grid md:grid-cols-[16rem_1fr]">
			{ /* Computador: menu lateral. */ }
			<aside className="bg-card sticky top-0 hidden h-dvh flex-col gap-6 border-r p-4 md:flex">
				<Brand />
				<nav className="flex flex-1 flex-col gap-1" aria-label={ __( 'Menu do painel', 'resgata-pet' ) }>
					{ items.map( ( item ) => (
						<NavLink
							key={ item.to }
							to={ item.to }
							end={ item.end }
							className={ ( { isActive } ) =>
								cn(
									'flex h-11 items-center gap-3 rounded-lg px-3 font-medium transition-colors',
									isActive ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-muted'
								)
							}
						>
							<item.icon className="size-5" aria-hidden="true" />
							{ item.label }
							{ item.showNewAdoptions && <CountBadge count={ newCount } className="ml-auto" /> }
						</NavLink>
					) ) }
				</nav>
				<div className="flex flex-col gap-3 border-t pt-4">
					<a
						href={ config.urls.site }
						target="_blank"
						rel="noreferrer"
						className="text-muted-foreground hover:text-foreground flex items-center gap-2 px-3 text-sm"
					>
						<ExternalLinkIcon className="size-4" aria-hidden="true" />
						{ __( 'Ver o site', 'resgata-pet' ) }
					</a>
					{ config.user.isAdmin && (
						<a href={ config.urls.admin } className="text-muted-foreground hover:text-foreground flex items-center gap-2 px-3 text-sm">
							<ArrowLeftIcon className="size-4" aria-hidden="true" />
							{ __( 'Voltar ao WordPress', 'resgata-pet' ) }
						</a>
					) }
					<div className="min-w-0 px-3">
						<p className="truncate text-sm font-medium">{ config.user.name }</p>
						<p className="text-muted-foreground truncate text-xs">{ config.user.email }</p>
					</div>
				</div>
			</aside>

			<div className="flex min-w-0 flex-col">
				{ /* Celular: marca no topo. */ }
				<header className="bg-card sticky top-0 z-30 border-b px-4 py-3 md:hidden">
					<Brand />
				</header>

				<main className="mx-auto w-full max-w-[1400px] flex-1 px-4 pt-6 pb-28 md:px-10 md:pt-10 md:pb-12">
					<Outlet />
				</main>
			</div>

			{ /* Celular: navegação inferior, ao alcance do polegar. */ }
			<nav
				className="bg-card fixed inset-x-0 bottom-0 z-30 grid auto-cols-fr grid-flow-col border-t pb-[env(safe-area-inset-bottom)] md:hidden"
				aria-label={ __( 'Menu do painel', 'resgata-pet' ) }
			>
				{ items.filter( ( item ) => ! item.desktopOnly ).map( ( item ) => (
					<NavLink
						key={ item.to }
						to={ item.to }
						end={ item.end }
						className={ ( { isActive } ) =>
							cn(
								'relative flex h-16 flex-col items-center justify-center gap-1 text-xs font-medium',
								isActive ? 'text-primary' : 'text-muted-foreground'
							)
						}
					>
						<item.icon className="size-6" aria-hidden="true" />
						{ item.mobileLabel ?? item.label }
						{ item.showNewAdoptions && <CountBadge count={ newCount } className="absolute top-1.5 left-1/2 ml-2" /> }
					</NavLink>
				) ) }
			</nav>
		</div>
	);
}
