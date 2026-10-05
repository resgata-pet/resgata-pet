import { useEffect, useId, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { __, sprintf } from '@wordpress/i18n';
import { toast } from 'sonner';
import { ArrowLeftIcon, Loader2Icon, MailIcon, MessageCircleIcon, PawPrintIcon, Trash2Icon } from 'lucide-react';
import { api, errorMessage } from '@/lib/api';
import { config } from '@/lib/config';
import { formatDateTime } from '@/lib/dates';
import {
	AlertDialog,
	AlertDialogAction,
	AlertDialogCancel,
	AlertDialogContent,
	AlertDialogDescription,
	AlertDialogFooter,
	AlertDialogHeader,
	AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Textarea } from '@/components/ui/textarea';
import { OptionSelect } from '@/components/option-select';
import { PageHeader } from '@/components/page-header';

export function AdoptionDetailPage() {
	const params = useParams();
	const id = Number( params.id );
	const uid = useId();
	const navigate = useNavigate();
	const queryClient = useQueryClient();
	const [ notes, setNotes ] = useState( '' );
	const [ confirmDelete, setConfirmDelete ] = useState( false );

	const query = useQuery( {
		queryKey: [ 'adoption', id ],
		queryFn: () => api.getAdoption( id ),
		enabled: Number.isInteger( id ) && id > 0 && config.user.canManageAdoptions,
	} );

	const loadedId = query.data?.id;
	const loadedNotes = query.data?.notes ?? '';

	// Preenche as anotações quando o pedido carrega (ou muda).
	useEffect( () => setNotes( loadedNotes ), [ loadedId, loadedNotes ] );

	const update = useMutation( {
		mutationFn: ( changes: { status?: string; notes?: string } ) => api.updateAdoption( id, changes ),
		onSuccess: ( adoption, changes ) => {
			queryClient.setQueryData( [ 'adoption', id ], adoption );
			void queryClient.invalidateQueries( { queryKey: [ 'adoptions' ] } );
			toast.success(
				changes.status ? __( 'Situação do pedido atualizada.', 'resgata-wp' ) : __( 'Anotações salvas.', 'resgata-wp' )
			);
		},
		onError: ( error ) => toast.error( errorMessage( error ) ),
	} );

	const remove = useMutation( {
		mutationFn: () => api.deleteAdoption( id ),
		onSuccess: () => {
			queryClient.removeQueries( { queryKey: [ 'adoption', id ] } );
			void queryClient.invalidateQueries( { queryKey: [ 'adoptions' ] } );
			toast.success( __( 'Pedido excluído.', 'resgata-wp' ) );
			void navigate( '/adocoes' );
		},
		onError: ( error ) => toast.error( errorMessage( error ) ),
	} );

	if ( query.isError || ! config.user.canManageAdoptions ) {
		return (
			<>
				<PageHeader
					title={ __( 'Pedido não encontrado', 'resgata-wp' ) }
					description={ query.isError ? errorMessage( query.error ) : undefined }
				/>
				<Link to="/adocoes" className="text-primary underline underline-offset-4">
					{ __( 'Voltar para os pedidos', 'resgata-wp' ) }
				</Link>
			</>
		);
	}

	if ( ! query.data ) {
		return (
			<div className="flex flex-col gap-4" aria-busy="true">
				<Skeleton className="h-9 w-56" />
				<Skeleton className="h-40 w-full" />
				<Skeleton className="h-64 w-full" />
			</div>
		);
	}

	const adoption = query.data;
	const animal = adoption.animal;

	return (
		<>
			<Link to="/adocoes" className="text-muted-foreground hover:text-foreground mb-3 inline-flex items-center gap-1 text-sm">
				<ArrowLeftIcon className="size-4" aria-hidden="true" />
				{ __( 'Pedidos de adoção', 'resgata-wp' ) }
			</Link>

			<PageHeader
				title={ adoption.name }
				description={ sprintf(
					/* translators: 1: número do pedido, 2: data e hora. */
					__( 'Pedido nº %1$d · recebido em %2$s', 'resgata-wp' ),
					adoption.id,
					formatDateTime( adoption.date )
				) }
				action={
					<div className="flex items-center gap-3">
						<OptionSelect
							className="w-44"
							ariaLabel={ __( 'Situação do pedido', 'resgata-wp' ) }
							value={ adoption.status }
							options={ config.options.adoptionStatuses }
							disabled={ update.isPending }
							onChange={ ( status ) => {
								if ( status && status !== adoption.status ) {
									update.mutate( { status } );
								}
							} }
						/>
						<Button
							type="button"
							variant="ghost"
							size="icon"
							aria-label={ __( 'Excluir pedido', 'resgata-wp' ) }
							onClick={ () => setConfirmDelete( true ) }
						>
							<Trash2Icon className="text-destructive" aria-hidden="true" />
						</Button>
					</div>
				}
			/>

			<div className="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
				<div className="flex flex-col gap-6">
					<Card>
						<CardHeader>
							<CardTitle>{ __( 'Respostas', 'resgata-wp' ) }</CardTitle>
						</CardHeader>
						<CardContent>
							<dl className="grid gap-x-8 gap-y-4 sm:grid-cols-2">
								{ adoption.answers.map( ( answer ) => (
									<div key={ answer.key } className={ answer.value.length > 60 ? 'sm:col-span-2' : undefined }>
										<dt className="text-muted-foreground text-sm">{ answer.label }</dt>
										<dd className="font-medium break-words whitespace-pre-line">{ answer.value }</dd>
									</div>
								) ) }
							</dl>
						</CardContent>
					</Card>

					<Card>
						<CardHeader>
							<CardTitle id={ `${ uid }-notes` }>{ __( 'Anotações internas', 'resgata-wp' ) }</CardTitle>
							<CardDescription>{ __( 'Só a equipe vê. Use para registrar a conversa e a entrevista.', 'resgata-wp' ) }</CardDescription>
						</CardHeader>
						<CardContent className="flex flex-col items-start gap-3">
							<Textarea
								rows={ 5 }
								maxLength={ 5000 }
								className="bg-card text-base"
								aria-labelledby={ `${ uid }-notes` }
								value={ notes }
								onChange={ ( event ) => setNotes( event.target.value ) }
							/>
							<Button
								type="button"
								variant="outline"
								disabled={ update.isPending || notes === adoption.notes }
								onClick={ () => update.mutate( { notes } ) }
							>
								{ update.isPending && <Loader2Icon className="animate-spin" aria-hidden="true" /> }
								{ __( 'Salvar anotações', 'resgata-wp' ) }
							</Button>
						</CardContent>
					</Card>
				</div>

				<div className="flex flex-col gap-6">
					<Card>
						<CardHeader>
							<CardTitle>{ __( 'Contato', 'resgata-wp' ) }</CardTitle>
						</CardHeader>
						<CardContent className="flex flex-col items-start gap-3">
							{ adoption.whatsapp_url && (
								<a href={ adoption.whatsapp_url } target="_blank" rel="noreferrer" className={ buttonVariants( { className: 'w-full' } ) }>
									<MessageCircleIcon aria-hidden="true" />
									{ sprintf(
										/* translators: %s: número do WhatsApp. */
										__( 'WhatsApp %s', 'resgata-wp' ),
										adoption.whatsapp
									) }
								</a>
							) }
							{ adoption.email && (
								<a
									href={ `mailto:${ adoption.email }` }
									className={ buttonVariants( { variant: 'outline', className: 'w-full min-w-0' } ) }
								>
									<MailIcon aria-hidden="true" />
									<span className="truncate">{ adoption.email }</span>
								</a>
							) }
						</CardContent>
					</Card>

					<Card>
						<CardHeader>
							<CardTitle>{ __( 'Animal', 'resgata-wp' ) }</CardTitle>
						</CardHeader>
						<CardContent>
							{ animal ? (
								<div className="flex items-center gap-3">
									{ animal.thumbnail ? (
										<img src={ animal.thumbnail } alt="" className="size-14 rounded-xl object-cover" />
									) : (
										<span className="bg-muted text-muted-foreground flex size-14 items-center justify-center rounded-xl">
											<PawPrintIcon className="size-5" aria-hidden="true" />
										</span>
									) }
									<div className="min-w-0">
										{ animal.trashed ? (
											<p className="font-medium">{ animal.name }</p>
										) : (
											<Link to={ `/animais/${ animal.id }` } className="text-primary font-medium underline-offset-4 hover:underline">
												{ animal.name }
											</Link>
										) }
										<p className="mt-1">
											<Badge variant="secondary">{ animal.trashed ? __( 'Na lixeira', 'resgata-wp' ) : animal.status_label }</Badge>
										</p>
									</div>
								</div>
							) : (
								<p className="text-muted-foreground">{ __( 'Este animal foi removido.', 'resgata-wp' ) }</p>
							) }
						</CardContent>
					</Card>

					<Card>
						<CardHeader>
							<CardTitle>{ __( 'Consentimento', 'resgata-wp' ) }</CardTitle>
							<CardDescription>
								{ adoption.consent_at
									? sprintf(
											/* translators: %s: data e hora. */
											__( 'A pessoa concordou em %s com o texto abaixo.', 'resgata-wp' ),
											formatDateTime( adoption.consent_at )
									  )
									: __( 'Sem registro de consentimento.', 'resgata-wp' ) }
							</CardDescription>
						</CardHeader>
						{ adoption.consent_text && (
							<CardContent>
								<p className="text-muted-foreground text-sm whitespace-pre-line">{ adoption.consent_text }</p>
							</CardContent>
						) }
					</Card>
				</div>
			</div>

			<AlertDialog open={ confirmDelete } onOpenChange={ setConfirmDelete }>
				<AlertDialogContent>
					<AlertDialogHeader>
						<AlertDialogTitle>{ __( 'Excluir este pedido?', 'resgata-wp' ) }</AlertDialogTitle>
						<AlertDialogDescription>
							{ __(
								'As respostas e os dados de contato desta pessoa são apagados para sempre. Não dá para desfazer.',
								'resgata-wp'
							) }
						</AlertDialogDescription>
					</AlertDialogHeader>
					<AlertDialogFooter>
						<AlertDialogCancel>{ __( 'Cancelar', 'resgata-wp' ) }</AlertDialogCancel>
						<AlertDialogAction variant="destructive" disabled={ remove.isPending } onClick={ () => remove.mutate() }>
							{ __( 'Excluir', 'resgata-wp' ) }
						</AlertDialogAction>
					</AlertDialogFooter>
				</AlertDialogContent>
			</AlertDialog>
		</>
	);
}
