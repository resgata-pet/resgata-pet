import { useEffect, useId, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { __, sprintf } from '@wordpress/i18n';
import { toast } from 'sonner';
import { ArrowDownIcon, ArrowUpIcon, ExternalLinkIcon, Loader2Icon, PlusIcon, Trash2Icon, XIcon } from 'lucide-react';
import { api, errorMessage, type Banner } from '@/lib/api';
import { config } from '@/lib/config';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Field } from '@/components/field';
import { ImageDropzone } from '@/components/image-dropzone';
import { PageHeader } from '@/components/page-header';

/** Limites iguais aos da API (`Banners::TEXT_FIELDS` e `URL_MAX`). */
const MAX = { title: 80, highlight: 60, text: 240, label: 40, url: 300 };

/** Banner na tela: os campos da API mais uma chave estável para a lista. */
type BannerForm = Banner & { key: number };

let nextKey = 1;

const withKey = ( banner: Banner ): BannerForm => ( { ...banner, key: nextKey++ } );

const emptyBanner = (): BannerForm =>
	withKey( {
		image_id: 0,
		image: null,
		active: true,
		title: '',
		highlight: '',
		text: '',
		button_label: '',
		button_url: '',
		button2_label: '',
		button2_url: '',
	} );

export function BannersPage() {
	const uid = useId();
	const queryClient = useQueryClient();
	const [ banners, setBanners ] = useState< BannerForm[] | null >( null );

	const query = useQuery( { queryKey: [ 'banners' ], queryFn: api.getBanners, staleTime: Infinity } );

	useEffect( () => {
		if ( query.data && banners === null ) {
			setBanners( query.data.banners.map( withKey ) );
		}
	}, [ query.data, banners ] );

	const save = useMutation( {
		mutationFn: ( list: BannerForm[] ) =>
			api.saveBanners(
				list.map( ( banner ) => ( {
					image_id: banner.image?.id ?? 0,
					active: banner.active,
					title: banner.title.trim(),
					highlight: banner.highlight.trim(),
					text: banner.text.trim(),
					button_label: banner.button_label.trim(),
					button_url: banner.button_url.trim(),
					button2_label: banner.button2_label.trim(),
					button2_url: banner.button2_url.trim(),
				} ) )
			),
		onSuccess: ( response ) => {
			queryClient.setQueryData( [ 'banners' ], response );
			setBanners( response.banners.map( withKey ) );
			toast.success( __( 'Banners salvos. O site já mostra a nova versão.', 'resgata-wp' ) );
		},
		onError: ( error ) => toast.error( errorMessage( error ) ),
	} );

	if ( ! config.user.canManageSettings ) {
		return (
			<PageHeader
				title={ __( 'Banners', 'resgata-wp' ) }
				description={ __( 'Você não tem permissão para alterar os banners.', 'resgata-wp' ) }
			/>
		);
	}

	if ( query.isError ) {
		return <PageHeader title={ __( 'Banners', 'resgata-wp' ) } description={ errorMessage( query.error ) } />;
	}

	if ( ! banners || ! query.data ) {
		return (
			<div className="flex flex-col gap-4" aria-busy="true">
				<Skeleton className="h-9 w-56" />
				<Skeleton className="h-72 w-full" />
			</div>
		);
	}

	const max = query.data.max;
	const saving = save.isPending;

	const update = ( key: number, changes: Partial< BannerForm > ) =>
		setBanners( banners.map( ( banner ) => ( banner.key === key ? { ...banner, ...changes } : banner ) ) );

	const move = ( index: number, offset: number ) => {
		const target = index + offset;

		if ( target < 0 || target >= banners.length ) {
			return;
		}

		const list = [ ...banners ];
		[ list[ index ], list[ target ] ] = [ list[ target ], list[ index ] ];
		setBanners( list );
	};

	const submit = () => {
		const untitled = banners.findIndex( ( banner ) => banner.active && banner.title.trim() === '' );

		if ( untitled !== -1 ) {
			/* translators: %d: posição do banner na lista. */
			toast.error( sprintf( __( 'O banner %d está ligado, mas sem título. Escreva um título ou desligue o banner.', 'resgata-wp' ), untitled + 1 ) );
			return;
		}

		save.mutate( banners );
	};

	return (
		<form
			noValidate
			onSubmit={ ( event ) => {
				event.preventDefault();
				submit();
			} }
		>
			<PageHeader
				title={ __( 'Banners', 'resgata-wp' ) }
				description={ __( 'São os destaques do topo da página inicial do site. Com mais de um, o visitante passa de um para o outro.', 'resgata-wp' ) }
				action={
					<a
						href={ config.urls.site }
						target="_blank"
						rel="noreferrer"
						className="text-primary inline-flex min-h-11 items-center gap-1 text-sm underline-offset-4 hover:underline"
					>
						{ __( 'Ver o site', 'resgata-wp' ) }
						<ExternalLinkIcon className="size-4" aria-hidden="true" />
					</a>
				}
			/>

			<div className="flex flex-col gap-6">
				{ banners.length === 0 && (
					<Card>
						<CardContent className="text-muted-foreground py-8 text-center">
							{ __( 'Nenhum banner cadastrado. Enquanto isso, o site mostra um banner padrão com a frase da protetora.', 'resgata-wp' ) }
						</CardContent>
					</Card>
				) }

				{ banners.map( ( banner, index ) => {
					const id = ( name: string ) => `${ uid }-${ banner.key }-${ name }`;

					return (
						<Card key={ banner.key }>
							<CardHeader className="flex flex-row flex-wrap items-center justify-between gap-3">
								<CardTitle>
									{
										/* translators: %d: posição do banner na lista. */
										sprintf( __( 'Banner %d', 'resgata-wp' ), index + 1 )
									}
								</CardTitle>
								<div className="flex flex-wrap items-center gap-1">
									<label className="mr-2 flex min-h-11 cursor-pointer items-center gap-2 text-sm font-medium">
										<Switch checked={ banner.active } onCheckedChange={ ( checked ) => update( banner.key, { active: checked } ) } />
										{ banner.active ? __( 'Aparece no site', 'resgata-wp' ) : __( 'Escondido', 'resgata-wp' ) }
									</label>
									<Button
										type="button"
										variant="ghost"
										size="icon"
										disabled={ index === 0 }
										aria-label={ __( 'Mover para cima', 'resgata-wp' ) }
										onClick={ () => move( index, -1 ) }
									>
										<ArrowUpIcon aria-hidden="true" />
									</Button>
									<Button
										type="button"
										variant="ghost"
										size="icon"
										disabled={ index === banners.length - 1 }
										aria-label={ __( 'Mover para baixo', 'resgata-wp' ) }
										onClick={ () => move( index, 1 ) }
									>
										<ArrowDownIcon aria-hidden="true" />
									</Button>
									<Button
										type="button"
										variant="ghost"
										size="icon"
										aria-label={ __( 'Excluir banner', 'resgata-wp' ) }
										onClick={ () => setBanners( banners.filter( ( item ) => item.key !== banner.key ) ) }
									>
										<Trash2Icon className="text-destructive" aria-hidden="true" />
									</Button>
								</div>
							</CardHeader>
							<CardContent className="grid gap-5 md:grid-cols-2 md:gap-x-8">
								<Field
									className="md:col-span-2"
									label={ __( 'Foto', 'resgata-wp' ) }
									hint={ __( 'Use uma foto deitada (mais larga que alta). O texto fica à esquerda, então deixe o animal mais para a direita.', 'resgata-wp' ) }
								>
									{ banner.image && (
										<div className="relative w-full max-w-md">
											<img src={ banner.image.url } alt="" className="aspect-[16/7] w-full rounded-xl object-cover" />
											<Button
												type="button"
												variant="secondary"
												size="icon-sm"
												className="absolute top-2 right-2"
												aria-label={ __( 'Remover foto', 'resgata-wp' ) }
												onClick={ () => update( banner.key, { image: null } ) }
											>
												<XIcon aria-hidden="true" />
											</Button>
										</div>
									) }
									<ImageDropzone onUploaded={ ( image ) => update( banner.key, { image } ) } />
								</Field>
								<Field label={ __( 'Título', 'resgata-wp' ) } htmlFor={ id( 'title' ) } hint={ __( 'Ex.: Juntos por mais', 'resgata-wp' ) }>
									<Input
										id={ id( 'title' ) }
										value={ banner.title }
										maxLength={ MAX.title }
										onChange={ ( event ) => update( banner.key, { title: event.target.value } ) }
									/>
								</Field>
								<Field
									label={ __( 'Final do título, em verde', 'resgata-wp' ) }
									htmlFor={ id( 'highlight' ) }
									hint={ __( 'Opcional. Ex.: vidas felizes', 'resgata-wp' ) }
								>
									<Input
										id={ id( 'highlight' ) }
										value={ banner.highlight }
										maxLength={ MAX.highlight }
										onChange={ ( event ) => update( banner.key, { highlight: event.target.value } ) }
									/>
								</Field>
								<Field className="md:col-span-2" label={ __( 'Texto', 'resgata-wp' ) } htmlFor={ id( 'text' ) }>
									<Textarea
										id={ id( 'text' ) }
										rows={ 2 }
										value={ banner.text }
										maxLength={ MAX.text }
										onChange={ ( event ) => update( banner.key, { text: event.target.value } ) }
									/>
								</Field>
								<Field label={ __( 'Botão principal', 'resgata-wp' ) } htmlFor={ id( 'button' ) } hint={ __( 'Ex.: Quero adotar', 'resgata-wp' ) }>
									<Input
										id={ id( 'button' ) }
										value={ banner.button_label }
										maxLength={ MAX.label }
										onChange={ ( event ) => update( banner.key, { button_label: event.target.value } ) }
									/>
								</Field>
								<Field
									label={ __( 'Link do botão principal', 'resgata-wp' ) }
									htmlFor={ id( 'button-url' ) }
									hint={ __( 'Ex.: /animais/ ou um endereço completo (https://...)', 'resgata-wp' ) }
								>
									<Input
										id={ id( 'button-url' ) }
										inputMode="url"
										autoComplete="off"
										value={ banner.button_url }
										maxLength={ MAX.url }
										onChange={ ( event ) => update( banner.key, { button_url: event.target.value } ) }
									/>
								</Field>
								<Field label={ __( 'Segundo botão', 'resgata-wp' ) } htmlFor={ id( 'button2' ) } hint={ __( 'Opcional. Ex.: Quero ser padrinho', 'resgata-wp' ) }>
									<Input
										id={ id( 'button2' ) }
										value={ banner.button2_label }
										maxLength={ MAX.label }
										onChange={ ( event ) => update( banner.key, { button2_label: event.target.value } ) }
									/>
								</Field>
								<Field label={ __( 'Link do segundo botão', 'resgata-wp' ) } htmlFor={ id( 'button2-url' ) }>
									<Input
										id={ id( 'button2-url' ) }
										inputMode="url"
										autoComplete="off"
										value={ banner.button2_url }
										maxLength={ MAX.url }
										onChange={ ( event ) => update( banner.key, { button2_url: event.target.value } ) }
									/>
								</Field>
							</CardContent>
						</Card>
					);
				} ) }

				<div className="flex flex-wrap items-center justify-between gap-3">
					<Button
						type="button"
						variant="outline"
						disabled={ banners.length >= max }
						onClick={ () => setBanners( [ ...banners, emptyBanner() ] ) }
					>
						<PlusIcon aria-hidden="true" />
						{ __( 'Adicionar banner', 'resgata-wp' ) }
					</Button>
					<Button type="submit" disabled={ saving }>
						{ saving && <Loader2Icon className="animate-spin" aria-hidden="true" /> }
						{ __( 'Salvar banners', 'resgata-wp' ) }
					</Button>
				</div>
				{ banners.length >= max && (
					<p className="text-muted-foreground text-sm">
						{
							/* translators: %d: quantidade máxima de banners. */
							sprintf( __( 'O limite é de %d banners.', 'resgata-wp' ), max )
						}
					</p>
				) }
			</div>
		</form>
	);
}
