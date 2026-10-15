import type { ComponentType } from 'react';
import { Link } from 'react-router';
import { useQuery } from '@tanstack/react-query';
import { __, _n, sprintf } from '@wordpress/i18n';
import {
	ChevronRightIcon,
	ExternalLinkIcon,
	FilePenLineIcon,
	HandCoinsIcon,
	HeartHandshakeIcon,
	HeartIcon,
	HomeIcon,
	ImagesIcon,
	PawPrintIcon,
	PlusIcon,
	SettingsIcon,
	StethoscopeIcon,
} from 'lucide-react';
import { api, errorMessage } from '@/lib/api';
import { cn } from '@/lib/utils';
import { config } from '@/lib/config';
import { buttonVariants } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { PageHeader } from '@/components/page-header';

type Icon = ComponentType< { className?: string } >;

/** Cores de apoio dos cartões (fundo claro + ícone), como no guia de design. */
const TONES = {
	green: { card: 'bg-[#f3f8f4]', icon: 'bg-[#dcefe3] text-[#2f7d55]' },
	blue: { card: 'bg-[#eef4fe]', icon: 'bg-[#dbe8fc] text-[#2f6fdb]' },
	orange: { card: 'bg-[#fef5e8]', icon: 'bg-[#fbe5c5] text-[#b3610a]' },
	purple: { card: 'bg-[#f5f0fe]', icon: 'bg-[#e6dbfb] text-[#6d3fd9]' },
	coral: { card: 'bg-[#fef0f0]', icon: 'bg-[#fbdada] text-[#d43d42]' },
} as const;

/** Cartão de número: leva à lista correspondente. */
function StatCard( { to, icon: IconComponent, tone, value, label }: { to: string; icon: Icon; tone: keyof typeof TONES; value: number; label: string } ) {
	return (
		<Link
			to={ to }
			className={ cn(
				'focus-visible:ring-ring/50 flex min-h-20 items-center gap-4 rounded-xl p-4 transition-shadow outline-none hover:shadow-md focus-visible:ring-3',
				TONES[ tone ].card
			) }
		>
			<span className={ cn( 'flex size-12 shrink-0 items-center justify-center rounded-full', TONES[ tone ].icon ) }>
				<IconComponent className="size-6" aria-hidden="true" />
			</span>
			<span className="min-w-0">
				<span className="block text-2xl leading-tight font-bold tracking-tight">{ value.toLocaleString( 'pt-BR' ) }</span>
				<span className="text-muted-foreground block text-sm">{ label }</span>
			</span>
		</Link>
	);
}

/** Linha de "precisa da sua atenção" ou de atalho: ícone, texto e seta. */
function RowLink( { to, href, icon: IconComponent, title, text }: { to?: string; href?: string; icon: Icon; title: string; text?: string } ) {
	const className = 'hover:bg-muted flex min-h-14 items-center gap-3 px-4 py-3 first:rounded-t-xl last:rounded-b-xl';
	const content = (
		<>
			<IconComponent className="text-brand size-6 shrink-0" aria-hidden="true" />
			<span className="min-w-0 flex-1">
				<span className="block font-medium">{ title }</span>
				{ text && <span className="text-muted-foreground block text-sm">{ text }</span> }
			</span>
			{ href ? (
				<ExternalLinkIcon className="text-muted-foreground size-4 shrink-0" aria-hidden="true" />
			) : (
				<ChevronRightIcon className="text-muted-foreground size-5 shrink-0" aria-hidden="true" />
			) }
		</>
	);

	return href ? (
		<a href={ href } target="_blank" rel="noreferrer" className={ className }>
			{ content }
		</a>
	) : (
		<Link to={ to ?? '/' } className={ className }>
			{ content }
		</Link>
	);
}

export function HomePage() {
	const firstName = config.user.name.split( ' ' )[ 0 ];
	const summary = useQuery( { queryKey: [ 'dashboard' ], queryFn: api.getDashboard, refetchOnWindowFocus: true, staleTime: 30_000 } );
	const data = summary.data;
	const animals = data?.animals ?? null;
	const newRequests = data?.adoptions?.new ?? 0;
	const drafts = animals?.drafts ?? 0;

	return (
		<>
			<PageHeader
				title={
					/* translators: %s: primeiro nome da pessoa. */
					sprintf( __( 'Olá, %s!', 'resgata-pet' ), firstName )
				}
				description={ __( 'Veja como estão os resgatados e o que precisa da sua atenção.', 'resgata-pet' ) }
				action={
					<Link to="/animais/novo" className={ buttonVariants( { className: 'w-full sm:w-auto' } ) }>
						<PlusIcon aria-hidden="true" />
						{ __( 'Cadastrar animal', 'resgata-pet' ) }
					</Link>
				}
			/>

			{ summary.isError && (
				<p className="text-destructive mb-6" role="alert">
					{ errorMessage( summary.error ) }
				</p>
			) }

			{ summary.isPending && (
				<div className="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5" aria-busy="true">
					{ [ 0, 1, 2, 3, 4 ].map( ( key ) => (
						<Skeleton key={ key } className="h-20 w-full rounded-xl" />
					) ) }
				</div>
			) }

			{ data && (
				<div className="flex flex-col gap-8">
					{ animals && (
						<section aria-labelledby="inicio-animais">
							<h2 id="inicio-animais" className="mb-3 text-lg font-semibold">
								{ __( 'Animais', 'resgata-pet' ) }
							</h2>
							<div className="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
								<StatCard to="/animais" icon={ PawPrintIcon } tone="green" value={ animals.total } label={ __( 'Total de animais', 'resgata-pet' ) } />
								<StatCard
									to="/animais?situacao=available"
									icon={ HeartIcon }
									tone="blue"
									value={ animals.by_status.available ?? 0 }
									label={ __( 'Para adoção', 'resgata-pet' ) }
								/>
								<StatCard
									to="/animais?situacao=treatment"
									icon={ StethoscopeIcon }
									tone="orange"
									value={ animals.by_status.treatment ?? 0 }
									label={ __( 'Em tratamento', 'resgata-pet' ) }
								/>
								<StatCard
									to="/animais?situacao=reserved"
									icon={ HeartHandshakeIcon }
									tone="coral"
									value={ animals.by_status.reserved ?? 0 }
									label={ __( 'Reservados', 'resgata-pet' ) }
								/>
								<StatCard
									to="/animais?situacao=adopted"
									icon={ HomeIcon }
									tone="purple"
									value={ animals.by_status.adopted ?? 0 }
									label={ __( 'Já adotados', 'resgata-pet' ) }
								/>
							</div>
						</section>
					) }

					{ ( data.adoptions || data.sponsorship ) && (
						<section aria-labelledby="inicio-pessoas">
							<h2 id="inicio-pessoas" className="mb-3 text-lg font-semibold">
								{ __( 'Adoções e padrinhos', 'resgata-pet' ) }
							</h2>
							<div className="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
								{ data.adoptions && (
									<StatCard
										to="/adocoes?situacao=new"
										icon={ HeartHandshakeIcon }
										tone="coral"
										value={ data.adoptions.new }
										label={ __( 'Pedidos de adoção novos', 'resgata-pet' ) }
									/>
								) }
								{ data.sponsorship && (
									<StatCard
										to="/apadrinhamento"
										icon={ HandCoinsIcon }
										tone="purple"
										value={ data.sponsorship.active }
										label={ __( 'Padrinhos ativos', 'resgata-pet' ) }
									/>
								) }
							</div>
						</section>
					) }

					{ ( newRequests > 0 || drafts > 0 ) && (
						<section aria-labelledby="inicio-atencao">
							<h2 id="inicio-atencao" className="mb-3 text-lg font-semibold">
								{ __( 'Precisa da sua atenção', 'resgata-pet' ) }
							</h2>
							<div className="bg-card divide-y rounded-xl border">
								{ newRequests > 0 && (
									<RowLink
										to="/adocoes?situacao=new"
										icon={ HeartHandshakeIcon }
										title={ sprintf(
											/* translators: %d: quantidade de pedidos de adoção novos. */
											_n( '%d pedido de adoção esperando resposta', '%d pedidos de adoção esperando resposta', newRequests, 'resgata-pet' ),
											newRequests
										) }
										text={ __( 'Quem pediu está aguardando o seu contato.', 'resgata-pet' ) }
									/>
								) }
								{ drafts > 0 && (
									<RowLink
										to="/animais?publicacao=draft"
										icon={ FilePenLineIcon }
										title={ sprintf(
											/* translators: %d: quantidade de animais em rascunho. */
											_n( '%d animal em rascunho', '%d animais em rascunho', drafts, 'resgata-pet' ),
											drafts
										) }
										text={ __( 'Rascunhos não aparecem no site. Termine o cadastro e publique.', 'resgata-pet' ) }
									/>
								) }
							</div>
						</section>
					) }

					<section aria-labelledby="inicio-atalhos">
						<h2 id="inicio-atalhos" className="mb-3 text-lg font-semibold">
							{ __( 'Atalhos', 'resgata-pet' ) }
						</h2>
						<div className="bg-card divide-y rounded-xl border">
							{ config.user.canManageSettings && (
								<RowLink
									to="/banners"
									icon={ ImagesIcon }
									title={ __( 'Banners da página inicial', 'resgata-pet' ) }
									text={ __( 'Troque as fotos e os textos de destaque do site.', 'resgata-pet' ) }
								/>
							) }
							{ config.user.canManageSettings && (
								<RowLink
									to="/configuracoes"
									icon={ SettingsIcon }
									title={ __( 'Dados da protetora', 'resgata-pet' ) }
									text={ __( 'WhatsApp, redes sociais, chave Pix e regras de adoção.', 'resgata-pet' ) }
								/>
							) }
							<RowLink href={ config.urls.site } icon={ ExternalLinkIcon } title={ __( 'Ver o site', 'resgata-pet' ) } />
						</div>
					</section>
				</div>
			) }
		</>
	);
}
