import { useEffect, useId, useState, type ReactNode } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import { toast } from 'sonner';
import { ChevronRightIcon, CreditCardIcon, ExternalLinkIcon, HeartHandshakeIcon, ImagesIcon, Loader2Icon, PuzzleIcon } from 'lucide-react';
import { Link, useSearchParams } from 'react-router';
import { api, errorMessage, type OrganizationSettings, type SettingsResponse } from '@/lib/api';
import { config, hasModule } from '@/lib/config';
import { maskCnpj, maskPhone, type Mask } from '@/lib/masks';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { Field } from '@/components/field';
import { MaskedInput } from '@/components/masked-input';
import { OptionSelect } from '@/components/option-select';
import { PageHeader } from '@/components/page-header';
import { StripeCard } from '@/components/stripe-card';
import { ModulesCard } from '@/components/modules-card';

/** Mostra telefones como (11) 91234-5678 (o servidor guarda só os dígitos) e o CNPJ pontuado. */
function withMasks( settings: OrganizationSettings ): OrganizationSettings {
	return {
		...settings,
		cnpj: maskCnpj( settings.cnpj ),
		whatsapp: maskPhone( settings.whatsapp ),
		phone: maskPhone( settings.phone ),
	};
}

const TAB_GENERAL = 'protetora';
const TAB_PAYMENTS = 'pagamentos';
const TAB_MODULES = 'recursos';
const TABS = [ TAB_GENERAL, TAB_PAYMENTS, TAB_MODULES ];

function Section( { title, description, children }: { title: string; description?: string; children: ReactNode } ) {
	return (
		<Card>
			<CardHeader>
				<CardTitle>{ title }</CardTitle>
				{ description && <CardDescription>{ description }</CardDescription> }
			</CardHeader>
			<CardContent className="grid gap-5 md:grid-cols-2 md:gap-x-8">{ children }</CardContent>
		</Card>
	);
}

export function SettingsPage() {
	const uid = useId();
	const queryClient = useQueryClient();
	const [ form, setForm ] = useState< OrganizationSettings | null >( null );

	// A aba aberta fica na URL (`?aba=pagamentos`), para o link direto e o botão "voltar" funcionarem.
	const [ searchParams, setSearchParams ] = useSearchParams();
	const tab = TABS.find( ( item ) => item === searchParams.get( 'aba' ) ) ?? TAB_GENERAL;
	const hasSponsorship = hasModule( 'sponsorship' );

	const query = useQuery( { queryKey: [ 'settings' ], queryFn: api.getSettings, staleTime: Infinity } );

	// Chaves da Stripe: mesma consulta da tela de padrinhos, só quando a aba está aberta.
	const stripe = useQuery( {
		queryKey: [ 'sponsorship' ],
		queryFn: api.getSponsorship,
		enabled: hasSponsorship && config.user.canManageSponsorship && tab === TAB_PAYMENTS,
	} );

	useEffect( () => {
		if ( query.data && form === null ) {
			setForm( withMasks( query.data.settings ) );
		}
	}, [ query.data, form ] );

	const save = useMutation( {
		mutationFn: ( data: OrganizationSettings ) => api.saveSettings( data ),
		onSuccess: ( response: SettingsResponse ) => {
			const hadPage = Boolean( query.data?.contact_page );
			const hadHelpPage = Boolean( query.data?.help_page );

			queryClient.setQueryData( [ 'settings' ], response );
			// O servidor devolve os valores já ajustados (ex.: @usuario vira link).
			setForm( withMasks( response.settings ) );
			toast.success( __( 'Configurações salvas.', 'resgata-pet' ) );

			if ( ! hadPage && response.contact_page ) {
				toast.info( __( 'A página de contato do site foi criada.', 'resgata-pet' ) );
			}

			if ( ! hadHelpPage && response.help_page ) {
				toast.info( __( 'A página "Como ajudar" foi criada, com o QR Code do Pix.', 'resgata-pet' ) );
			}
		},
		onError: ( error ) => toast.error( errorMessage( error ) ),
	} );

	if ( ! config.user.canManageSettings ) {
		return (
			<PageHeader
				title={ __( 'Configurações', 'resgata-pet' ) }
				description={ __( 'Você não tem permissão para alterar as configurações.', 'resgata-pet' ) }
			/>
		);
	}

	if ( query.isError ) {
		return <PageHeader title={ __( 'Configurações', 'resgata-pet' ) } description={ errorMessage( query.error ) } />;
	}

	if ( ! form || ! query.data ) {
		return (
			<div className="flex flex-col gap-4" aria-busy="true">
				<Skeleton className="h-9 w-56" />
				<Skeleton className="h-56 w-full" />
				<Skeleton className="h-56 w-full" />
			</div>
		);
	}

	const { choices, contact_page: contactPage } = query.data;
	const id = ( name: string ) => `${ uid }-${ name }`;
	const formId = id( 'form' );
	const set = ( key: keyof OrganizationSettings, value: string ) => setForm( { ...form, [ key ]: value } );

	/** Campo de texto simples ligado a uma chave das configurações. */
	const text = (
		key: keyof OrganizationSettings,
		label: string,
		options: { hint?: string; placeholder?: string; type?: string; inputMode?: 'tel' | 'email' | 'url' | 'numeric'; wide?: boolean; maxLength?: number; mask?: Mask } = {}
	) => {
		const mask = options.mask;
		const props = {
			id: id( key ),
			type: options.type ?? 'text',
			inputMode: options.inputMode,
			placeholder: options.placeholder,
			maxLength: options.maxLength,
			autoComplete: 'off',
			value: form[ key ],
			// O <form> só envolve a barra de salvar: o campo se liga a ele por aqui (Enter salva).
			form: formId,
		};

		return (
			<Field label={ label } htmlFor={ id( key ) } hint={ options.hint } className={ options.wide ? 'md:col-span-2' : undefined }>
				{ mask ? (
					<MaskedInput { ...props } mask={ mask } onValueChange={ ( value ) => set( key, value ) } />
				) : (
					<Input { ...props } onChange={ ( event ) => set( key, event.target.value ) } />
				) }
			</Field>
		);
	};

	return (
		<>
			<PageHeader
				title={ __( 'Configurações', 'resgata-pet' ) }
				description={ __( 'Os dados da protetora aparecem na página de contato do site.', 'resgata-pet' ) }
				action={
					contactPage && (
						<a
							href={ contactPage.url }
							target="_blank"
							rel="noreferrer"
							className="text-primary inline-flex items-center gap-1 text-sm underline-offset-4 hover:underline"
						>
							{ __( 'Ver página de contato', 'resgata-pet' ) }
							<ExternalLinkIcon className="size-4" aria-hidden="true" />
						</a>
					)
				}
			/>

			<Tabs
				value={ tab }
				onValueChange={ ( value ) => setSearchParams( value === TAB_GENERAL ? {} : { aba: String( value ) }, { replace: true } ) }
			>
				<TabsList aria-label={ __( 'Seções das configurações', 'resgata-pet' ) }>
					<TabsTrigger value={ TAB_GENERAL }>
						<HeartHandshakeIcon aria-hidden="true" />
						{ __( 'Protetora e site', 'resgata-pet' ) }
					</TabsTrigger>
					<TabsTrigger value={ TAB_PAYMENTS }>
						<CreditCardIcon aria-hidden="true" />
						{ hasSponsorship ? __( 'Pagamentos e apadrinhamento', 'resgata-pet' ) : __( 'Pagamentos', 'resgata-pet' ) }
					</TabsTrigger>
					<TabsTrigger value={ TAB_MODULES }>
						<PuzzleIcon aria-hidden="true" />
						{ __( 'Recursos do site', 'resgata-pet' ) }
					</TabsTrigger>
				</TabsList>

				<TabsContent value={ TAB_GENERAL } className="flex flex-col gap-6">
					<Link
						to="/banners"
						className="bg-card hover:bg-muted flex min-h-14 items-center justify-between gap-3 rounded-xl border px-4 py-3"
					>
						<span className="flex items-center gap-3">
							<ImagesIcon className="text-brand size-6 shrink-0" aria-hidden="true" />
							<span>
								<span className="block font-medium">{ __( 'Banners da página inicial', 'resgata-pet' ) }</span>
								<span className="text-muted-foreground block text-sm">
									{ __( 'Escolha as fotos e os textos de destaque do site.', 'resgata-pet' ) }
								</span>
							</span>
						</span>
						<ChevronRightIcon className="text-muted-foreground size-5 shrink-0" aria-hidden="true" />
					</Link>

					<Section title={ __( 'Identidade', 'resgata-pet' ) }>
						{ text( 'name', __( 'Nome da protetora ou do projeto', 'resgata-pet' ), { maxLength: 120 } ) }
						{ text( 'cnpj', __( 'CNPJ (se houver)', 'resgata-pet' ), { mask: maskCnpj, placeholder: '12.345.678/0001-90' } ) }
						{ text( 'tagline', __( 'Frase de apresentação', 'resgata-pet' ), {
							wide: true,
							maxLength: 200,
							placeholder: __( 'Ex.: Resgatamos e cuidamos de gatos em situação de rua.', 'resgata-pet' ),
						} ) }
					</Section>

					<Section title={ __( 'Contato', 'resgata-pet' ) }>
						{ text( 'whatsapp', __( 'WhatsApp', 'resgata-pet' ), {
							type: 'tel',
							inputMode: 'tel',
							mask: maskPhone,
							placeholder: '(11) 91234-5678',
							hint: __( 'Com DDD. O link para a conversa é criado sozinho.', 'resgata-pet' ),
						} ) }
						{ text( 'phone', __( 'Telefone', 'resgata-pet' ), { type: 'tel', inputMode: 'tel', mask: maskPhone, placeholder: '(11) 1234-5678' } ) }
						{ text( 'whatsapp_message', __( 'Mensagem inicial do WhatsApp', 'resgata-pet' ), {
							wide: true,
							maxLength: 300,
							placeholder: __( 'Olá! Vi o site de vocês e gostaria de saber mais.', 'resgata-pet' ),
							hint: __( 'Texto que já vem escrito quando a pessoa abre a conversa.', 'resgata-pet' ),
						} ) }
						{ text( 'email', __( 'E-mail', 'resgata-pet' ), { type: 'email', inputMode: 'email' } ) }
						{ text( 'hours', __( 'Horário de atendimento', 'resgata-pet' ), {
							placeholder: __( 'Ex.: segunda a sexta, das 9h às 18h', 'resgata-pet' ),
						} ) }
					</Section>

					<Section
						title={ __( 'Redes sociais', 'resgata-pet' ) }
						description={ __( 'Pode colar o link do perfil ou escrever só o @usuario.', 'resgata-pet' ) }
					>
						{ text( 'instagram', 'Instagram', { placeholder: '@suaprotetora' } ) }
						{ text( 'facebook', 'Facebook', { placeholder: '@suaprotetora' } ) }
						{ text( 'tiktok', 'TikTok', { placeholder: '@suaprotetora' } ) }
						{ text( 'youtube', 'YouTube', { placeholder: '@suaprotetora' } ) }
					</Section>

					<Section
						title={ __( 'Localização', 'resgata-pet' ) }
						description={ __( 'Só cidade, estado e bairro aparecem no site. Não pedimos endereço completo.', 'resgata-pet' ) }
					>
						{ text( 'city', __( 'Cidade', 'resgata-pet' ) ) }
						<Field label={ __( 'Estado', 'resgata-pet' ) } htmlFor={ id( 'state' ) }>
							<OptionSelect
								id={ id( 'state' ) }
								value={ form.state }
								options={ choices.states }
								emptyLabel={ __( 'Não informado', 'resgata-pet' ) }
								onChange={ ( value ) => set( 'state', value ) }
							/>
						</Field>
						{ text( 'neighborhood', __( 'Bairro ou região (opcional)', 'resgata-pet' ) ) }
					</Section>

					{ hasModule( 'adoptions' ) && (
						<Section
							title={ __( 'Adoção', 'resgata-pet' ) }
							description={ __( 'Usado pelo formulário de pedidos de adoção.', 'resgata-pet' ) }
						>
							{ text( 'adoption_email', __( 'E-mail que recebe os pedidos de adoção', 'resgata-pet' ), {
								type: 'email',
								inputMode: 'email',
								hint: __( 'Não aparece no site.', 'resgata-pet' ),
							} ) }
							{ text( 'adoption_fee', __( 'Taxa de adoção', 'resgata-pet' ), {
								placeholder: __( 'Ex.: não cobramos taxa', 'resgata-pet' ),
							} ) }
							<Field
								className="md:col-span-2"
								label={ __( 'Regras e requisitos para adotar', 'resgata-pet' ) }
								htmlFor={ id( 'adoption_rules' ) }
							>
								<Textarea
									id={ id( 'adoption_rules' ) }
									rows={ 5 }
									className="bg-card text-base"
									value={ form.adoption_rules }
									placeholder={ __( 'Ex.: ser maior de 18 anos, telar janelas, aceitar visita antes da adoção…', 'resgata-pet' ) }
									onChange={ ( event ) => set( 'adoption_rules', event.target.value ) }
								/>
							</Field>
						</Section>
					) }

					<Section
						title={ __( 'Privacidade', 'resgata-pet' ) }
						description={ __( 'Explica a quem preenche um formulário como os dados dela são usados (LGPD).', 'resgata-pet' ) }
					>
						{ text( 'privacy_contact', __( 'Responsável pelos dados', 'resgata-pet' ), {
							hint: __( 'Nome de quem responde a pedidos sobre dados pessoais.', 'resgata-pet' ),
						} ) }
						<Field className="md:col-span-2" label={ __( 'Texto de privacidade', 'resgata-pet' ) } htmlFor={ id( 'privacy_text' ) }>
							<Textarea
								id={ id( 'privacy_text' ) }
								rows={ 6 }
								className="bg-card text-base"
								value={ form.privacy_text }
								onChange={ ( event ) => set( 'privacy_text', event.target.value ) }
							/>
						</Field>
					</Section>
				</TabsContent>

				<TabsContent value={ TAB_PAYMENTS } className="flex flex-col gap-6">
					<Section
						title={ __( 'Doações por Pix', 'resgata-pet' ) }
						description={ __(
							'Ao cadastrar a chave, o site ganha a página "Como ajudar", com o QR Code do Pix. A chave também aparece na página de contato.',
							'resgata-pet'
						) }
					>
						{ query.data.help_page && (
							<p className="md:col-span-2">
								<a
									href={ query.data.help_page.url }
									target="_blank"
									rel="noreferrer"
									className="text-primary inline-flex min-h-11 items-center gap-1 text-sm underline-offset-4 hover:underline"
								>
									{ __( 'Ver a página "Como ajudar"', 'resgata-pet' ) }
									<ExternalLinkIcon className="size-4" aria-hidden="true" />
								</a>
							</p>
						) }
						<Field label={ __( 'Tipo da chave', 'resgata-pet' ) } htmlFor={ id( 'pix_key_type' ) }>
							<OptionSelect
								id={ id( 'pix_key_type' ) }
								value={ form.pix_key_type }
								options={ choices.pixKeyTypes }
								emptyLabel={ __( 'Não informado', 'resgata-pet' ) }
								onChange={ ( value ) => set( 'pix_key_type', value ) }
							/>
						</Field>
						{ text( 'pix_key', __( 'Chave Pix', 'resgata-pet' ) ) }
						{ text( 'pix_holder', __( 'Nome do titular', 'resgata-pet' ), {
							hint: __( 'Como aparece no comprovante, para a pessoa conferir antes de pagar.', 'resgata-pet' ),
						} ) }
					</Section>

					{ hasSponsorship && config.user.canManageSponsorship && (
						<>
							{ stripe.isPending && <Skeleton className="h-56 w-full" aria-busy="true" /> }
							{ stripe.isError && (
								<p className="text-destructive" role="alert">
									{ errorMessage( stripe.error ) }
								</p>
							) }
							{ stripe.data && <StripeCard overview={ stripe.data } /> }
						</>
					) }

					{ hasSponsorship && (
						<Section
							title={ __( 'Apadrinhamento', 'resgata-pet' ) }
							description={ __(
								'Avisos de novo padrinho, pedido de cancelamento, pagamento recusado e pagamento contestado.',
								'resgata-pet'
							) }
						>
							{ text( 'sponsorship_email', __( 'E-mail que recebe os avisos de apadrinhamento', 'resgata-pet' ), {
								type: 'email',
								inputMode: 'email',
								hint: __( 'Não aparece no site. Sem ele, os avisos não são enviados.', 'resgata-pet' ),
							} ) }
							<Field
								label={ __( 'Página de agradecimento', 'resgata-pet' ) }
								htmlFor={ id( 'sponsorship_thanks_page' ) }
								hint={ __( 'Para onde a pessoa vai depois de pagar. O site cria a página "Obrigado" quando há um plano à venda.', 'resgata-pet' ) }
							>
								<OptionSelect
									id={ id( 'sponsorship_thanks_page' ) }
									value={ form.sponsorship_thanks_page }
									options={ choices.pages }
									emptyLabel={ __( 'Nenhuma (volta para a página dos planos)', 'resgata-pet' ) }
									onChange={ ( value ) => set( 'sponsorship_thanks_page', value ) }
								/>
								{ query.data.thanks_page && String( query.data.thanks_page.id ) === form.sponsorship_thanks_page && (
									<a
										href={ query.data.thanks_page.url }
										target="_blank"
										rel="noreferrer"
										className="text-primary mt-2 inline-flex items-center gap-1 text-sm underline-offset-4 hover:underline"
									>
										{ __( 'Ver página de agradecimento', 'resgata-pet' ) }
										<ExternalLinkIcon className="size-4" aria-hidden="true" />
									</a>
								) }
							</Field>
							{ text( 'sponsorship_thanks_title', __( 'Título do agradecimento', 'resgata-pet' ), {
								placeholder: __( 'Obrigado por apadrinhar!', 'resgata-pet' ),
								maxLength: 120,
								wide: true,
							} ) }
							<Field
								className="md:col-span-2"
								label={ __( 'Texto do agradecimento', 'resgata-pet' ) }
								htmlFor={ id( 'sponsorship_thanks_text' ) }
								hint={ __( 'Aparece na página de agradecimento. Em branco, o site usa um texto padrão.', 'resgata-pet' ) }
							>
								<Textarea
									id={ id( 'sponsorship_thanks_text' ) }
									rows={ 4 }
									maxLength={ 600 }
									className="bg-card text-base"
									value={ form.sponsorship_thanks_text }
									placeholder={ __( 'Você vai receber a confirmação do pagamento por e-mail. Sua ajuda chega todo mês para cuidar dos animais.', 'resgata-pet' ) }
									onChange={ ( event ) => set( 'sponsorship_thanks_text', event.target.value ) }
								/>
							</Field>
						</Section>
					) }
				</TabsContent>

				<TabsContent value={ TAB_MODULES } className="flex flex-col gap-6">
					<ModulesCard />
				</TabsContent>
			</Tabs>

			{ /* O formulário é só a barra de salvar, sempre à vista (acima da navegação inferior no celular):
			     os campos se ligam a ele pelo atributo `form`. Assim o cartão da Stripe, que tem o seu
			     próprio formulário, pode ficar no meio da aba. */ }
			<form
				id={ formId }
				hidden={ tab === TAB_MODULES }
				noValidate
				className="bg-background/95 sticky bottom-16 z-20 mt-6 flex justify-end border-t py-4 backdrop-blur md:bottom-0"
				onSubmit={ ( event ) => {
					event.preventDefault();
					save.mutate( form );
				} }
			>
				<Button type="submit" disabled={ save.isPending }>
					{ save.isPending && <Loader2Icon className="animate-spin" aria-hidden="true" /> }
					{ __( 'Salvar configurações', 'resgata-pet' ) }
				</Button>
			</form>
		</>
	);
}
