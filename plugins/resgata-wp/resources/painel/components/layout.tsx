import type { ComponentType } from 'react';
import { NavLink, Outlet } from 'react-router';
import { __ } from '@wordpress/i18n';
import { ExternalLinkIcon, HomeIcon, PawPrintIcon, UserIcon } from 'lucide-react';
import { cn } from '@/lib/utils';
import { config } from '@/lib/config';

interface NavItem {
	to: string;
	label: string;
	icon: ComponentType< { className?: string } >;
	end?: boolean;
}

const items: NavItem[] = [
	{ to: '/', label: __( 'Início', 'resgata-wp' ), icon: HomeIcon, end: true },
	{ to: '/animais', label: __( 'Animais', 'resgata-wp' ), icon: PawPrintIcon },
	{ to: '/conta', label: __( 'Minha conta', 'resgata-wp' ), icon: UserIcon },
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

export function Layout() {
	return (
		<div className="min-h-dvh md:grid md:grid-cols-[16rem_1fr]">
			{ /* Computador: menu lateral. */ }
			<aside className="bg-card sticky top-0 hidden h-dvh flex-col gap-6 border-r p-4 md:flex">
				<Brand />
				<nav className="flex flex-1 flex-col gap-1" aria-label={ __( 'Menu do painel', 'resgata-wp' ) }>
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
						{ __( 'Ver o site', 'resgata-wp' ) }
					</a>
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
				className="bg-card fixed inset-x-0 bottom-0 z-30 grid grid-cols-3 border-t pb-[env(safe-area-inset-bottom)] md:hidden"
				aria-label={ __( 'Menu do painel', 'resgata-wp' ) }
			>
				{ items.map( ( item ) => (
					<NavLink
						key={ item.to }
						to={ item.to }
						end={ item.end }
						className={ ( { isActive } ) =>
							cn(
								'flex h-16 flex-col items-center justify-center gap-1 text-xs font-medium',
								isActive ? 'text-primary' : 'text-muted-foreground'
							)
						}
					>
						<item.icon className="size-6" aria-hidden="true" />
						{ item.label }
					</NavLink>
				) ) }
			</nav>
		</div>
	);
}
