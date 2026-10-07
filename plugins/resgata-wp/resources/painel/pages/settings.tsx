import { useEffect, useId, useState, type ReactNode } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import { toast } from 'sonner';
import { ChevronRightIcon, ExternalLinkIcon, ImagesIcon, Loader2Icon } from 'lucide-react';
import { Link } from 'react-router';
import { api, errorMessage, type OrganizationSettings, type SettingsResponse } from '@/lib/api';
import { config } from '@/lib/config';
import { maskCnpj, maskPhone, type Mask } from '@/lib/masks';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { Textarea } from '@/components/ui/textarea';
import { Field } from '@/components/field';
import { MaskedInput } from '@/components/masked-input';
import { OptionSelect } from '@/components/option-select';
import { PageHeader } from '@/components/page-header';

/** Mostra telefones como (11) 91234-5678 (o servidor guarda só os dígitos) e o CNPJ pontuado. */
function withMasks( settings: OrganizationSettings ): OrganizationSettings {
	return {
		...settings,
		cnpj: maskCnpj( settings.cnpj ),
		whatsapp: maskPhone( settings.whatsapp ),
		phone: maskPhone( settings.phone ),
	};
}

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

	const query = useQuery( { queryKey: [ 'settings' ], queryFn: api.getSettings, staleTime: Infinity } );

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
			toast.success( __( 'Configurações salvas.', 'resgata-wp' ) );

			if ( ! hadPage && response.contact_page ) {
				toast.info( __( 'A página de contato do site foi criada.', 'resgata-wp' ) );
			}

			if ( ! hadHelpPage && response.help_page ) {
				toast.info( __( 'A página "Como ajudar" foi criada, com o QR Code do Pix.', 'resgata-wp' ) );
			}
		},
		onError: ( error ) => toast.error( errorMessage( error ) ),
	} );

	if ( ! config.user.canManageSettings ) {
		return (
			<PageHeader
				title={ __( 'Configurações', 'resgata-wp' ) }
				description={ __( 'Você não tem permissão para alterar as configurações.', 'resgata-wp' ) }
			/>
		);
	}

	if ( query.isError ) {
		return <PageHeader title={ __( 'Configurações', 'resgata-wp' ) } description={ errorMessage( query.error ) } />;
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
		<form
			noValidate
			onSubmit={ ( event ) => {
				event.preventDefault();
				save.mutate( form );
			} }
		>
			<PageHeader
				title={ __( 'Configurações', 'resgata-wp' ) }
				description={ __( 'Os dados da protetora aparecem na página de contato do site.', 'resgata-wp' ) }
				action={
					contactPage && (
						<a
							href={ contactPage.url }
							target="_blank"
							rel="noreferrer"
							className="text-primary inline-flex items-center gap-1 text-sm underline-offset-4 hover:underline"
						>
							{ __( 'Ver página de contato', 'resgata-wp' ) }
							<ExternalLinkIcon className="size-4" aria-hidden="true" />
						</a>
					)
				}
			/>

			<Link
				to="/banners"
				className="bg-card hover:bg-muted mb-6 flex min-h-14 items-center justify-between gap-3 rounded-xl border px-4 py-3"
			>
				<span className="flex items-center gap-3">
					<ImagesIcon className="text-brand size-6 shrink-0" aria-hidden="true" />
					<span>
						<span className="block font-medium">{ __( 'Banners da página inicial', 'resgata-wp' ) }</span>
						<span className="text-muted-foreground block text-sm">
							{ __( 'Escolha as fotos e os textos de destaque do site.', 'resgata-wp' ) }
						</span>
					</span>
				</span>
				<ChevronRightIcon className="text-muted-foreground size-5 shrink-0" aria-hidden="true" />
			</Link>

			<div className="flex flex-col gap-6">
				<Section title={ __( 'Identidade', 'resgata-wp' ) }>
					{ text( 'name', __( 'Nome da protetora ou do projeto', 'resgata-wp' ), { maxLength: 120 } ) }
					{ text( 'cnpj', __( 'CNPJ (se houver)', 'resgata-wp' ), { mask: maskCnpj, placeholder: '12.345.678/0001-90' } ) }
					{ text( 'tagline', __( 'Frase de apresentação', 'resgata-wp' ), {
						wide: true,
						maxLength: 200,
						placeholder: __( 'Ex.: Resgatamos e cuidamos de gatos em situação de rua.', 'resgata-wp' ),
					} ) }
				</Section>

				<Section title={ __( 'Contato', 'resgata-wp' ) }>
					{ text( 'whatsapp', __( 'WhatsApp', 'resgata-wp' ), {
						type: 'tel',
						inputMode: 'tel',
						mask: maskPhone,
						placeholder: '(11) 91234-5678',
						hint: __( 'Com DDD. O link para a conversa é criado sozinho.', 'resgata-wp' ),
					} ) }
					{ text( 'phone', __( 'Telefone', 'resgata-wp' ), { type: 'tel', inputMode: 'tel', mask: maskPhone, placeholder: '(11) 1234-5678' } ) }
					{ text( 'whatsapp_message', __( 'Mensagem inicial do WhatsApp', 'resgata-wp' ), {
						wide: true,
						maxLength: 300,
						placeholder: __( 'Olá! Vi o site de vocês e gostaria de saber mais.', 'resgata-wp' ),
						hint: __( 'Texto que já vem escrito quando a pessoa abre a conversa.', 'resgata-wp' ),
					} ) }
					{ text( 'email', __( 'E-mail', 'resgata-wp' ), { type: 'email', inputMode: 'email' } ) }
					{ text( 'hours', __( 'Horário de atendimento', 'resgata-wp' ), {
						placeholder: __( 'Ex.: segunda a sexta, das 9h às 18h', 'resgata-wp' ),
					} ) }
				</Section>

				<Section
					title={ __( 'Redes sociais', 'resgata-wp' ) }
					description={ __( 'Pode colar o link do perfil ou escrever só o @usuario.', 'resgata-wp' ) }
				>
					{ text( 'instagram', 'Instagram', { placeholder: '@suaprotetora' } ) }
					{ text( 'facebook', 'Facebook', { placeholder: '@suaprotetora' } ) }
					{ text( 'tiktok', 'TikTok', { placeholder: '@suaprotetora' } ) }
					{ text( 'youtube', 'YouTube', { placeholder: '@suaprotetora' } ) }
				</Section>

				<Section
					title={ __( 'Localização', 'resgata-wp' ) }
					description={ __( 'Só cidade, estado e bairro aparecem no site. Não pedimos endereço completo.', 'resgata-wp' ) }
				>
					{ text( 'city', __( 'Cidade', 'resgata-wp' ) ) }
					<Field label={ __( 'Estado', 'resgata-wp' ) } htmlFor={ id( 'state' ) }>
						<OptionSelect
							id={ id( 'state' ) }
							value={ form.state }
							options={ choices.states }
							emptyLabel={ __( 'Não informado', 'resgata-wp' ) }
							onChange={ ( value ) => set( 'state', value ) }
						/>
					</Field>
					{ text( 'neighborhood', __( 'Bairro ou região (opcional)', 'resgata-wp' ) ) }
				</Section>

				<Section
					title={ __( 'Adoção', 'resgata-wp' ) }
					description={ __( 'Usado pelo formulário de pedidos de adoção.', 'resgata-wp' ) }
				>
					{ text( 'adoption_email', __( 'E-mail que recebe os pedidos de adoção', 'resgata-wp' ), {
						type: 'email',
						inputMode: 'email',
						hint: __( 'Não aparece no site.', 'resgata-wp' ),
					} ) }
					{ text( 'adoption_fee', __( 'Taxa de adoção', 'resgata-wp' ), {
						placeholder: __( 'Ex.: não cobramos taxa', 'resgata-wp' ),
					} ) }
					<Field
						className="md:col-span-2"
						label={ __( 'Regras e requisitos para adotar', 'resgata-wp' ) }
						htmlFor={ id( 'adoption_rules' ) }
					>
						<Textarea
							id={ id( 'adoption_rules' ) }
							rows={ 5 }
							className="bg-card text-base"
							value={ form.adoption_rules }
							placeholder={ __( 'Ex.: ser maior de 18 anos, telar janelas, aceitar visita antes da adoção…', 'resgata-wp' ) }
							onChange={ ( event ) => set( 'adoption_rules', event.target.value ) }
						/>
					</Field>
				</Section>

				<Section
					title={ __( 'Privacidade', 'resgata-wp' ) }
					description={ __( 'Explica a quem preenche um formulário como os dados dela são usados (LGPD).', 'resgata-wp' ) }
				>
					{ text( 'privacy_contact', __( 'Responsável pelos dados', 'resgata-wp' ), {
						hint: __( 'Nome de quem responde a pedidos sobre dados pessoais.', 'resgata-wp' ),
					} ) }
					<Field className="md:col-span-2" label={ __( 'Texto de privacidade', 'resgata-wp' ) } htmlFor={ id( 'privacy_text' ) }>
						<Textarea
							id={ id( 'privacy_text' ) }
							rows={ 6 }
							className="bg-card text-base"
							value={ form.privacy_text }
							onChange={ ( event ) => set( 'privacy_text', event.target.value ) }
						/>
					</Field>
				</Section>

				<Section
					title={ __( 'Doações por Pix', 'resgata-wp' ) }
					description={ __(
						'Ao cadastrar a chave, o site ganha a página "Como ajudar", com o QR Code do Pix. A chave também aparece na página de contato.',
						'resgata-wp'
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
								{ __( 'Ver a página "Como ajudar"', 'resgata-wp' ) }
								<ExternalLinkIcon className="size-4" aria-hidden="true" />
							</a>
						</p>
					) }
					<Field label={ __( 'Tipo da chave', 'resgata-wp' ) } htmlFor={ id( 'pix_key_type' ) }>
						<OptionSelect
							id={ id( 'pix_key_type' ) }
							value={ form.pix_key_type }
							options={ choices.pixKeyTypes }
							emptyLabel={ __( 'Não informado', 'resgata-wp' ) }
							onChange={ ( value ) => set( 'pix_key_type', value ) }
						/>
					</Field>
					{ text( 'pix_key', __( 'Chave Pix', 'resgata-wp' ) ) }
					{ text( 'pix_holder', __( 'Nome do titular', 'resgata-wp' ), {
						hint: __( 'Como aparece no comprovante, para a pessoa conferir antes de pagar.', 'resgata-wp' ),
					} ) }
				</Section>
			</div>

			{ /* Barra de salvar sempre à vista (acima da navegação inferior no celular). */ }
			<div className="bg-background/95 sticky bottom-16 z-20 mt-6 flex justify-end border-t py-4 backdrop-blur md:bottom-0">
				<Button type="submit" disabled={ save.isPending }>
					{ save.isPending && <Loader2Icon className="animate-spin" aria-hidden="true" /> }
					{ __( 'Salvar configurações', 'resgata-wp' ) }
				</Button>
			</div>
		</form>
	);
}
