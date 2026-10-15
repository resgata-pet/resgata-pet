import { useEffect, useId, useState } from 'react';
import { Link, useNavigate, useParams, useSearchParams } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import { toast } from 'sonner';
import { ArrowLeftIcon, CheckIcon, ExternalLinkIcon, Loader2Icon, PlusIcon, Trash2Icon, XIcon } from 'lucide-react';
import { api, errorMessage, type Animal, type PostStatus, type AnimalImage, type AnimalInput, type GalleryPhoto, type PhotoType, type Vaccine } from '@/lib/api';
import { config } from '@/lib/config';
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
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Skeleton } from '@/components/ui/skeleton';
import { DatePicker } from '@/components/date-picker';
import { Field } from '@/components/field';
import { ImageDropzone } from '@/components/image-dropzone';
import { OptionSelect } from '@/components/option-select';
import { PageHeader } from '@/components/page-header';
import { RichText } from '@/components/rich-text';
import { Stepper } from '@/components/stepper';

/** Status de publicação: "Rascunho" não aparece no site. */
const POST_STATUSES: Record< PostStatus, string > = {
	publish: __( 'Publicado', 'resgata-pet' ),
	draft: __( 'Rascunho', 'resgata-pet' ),
};

/** Limites iguais aos da API (`Meta::TAGLINE_MAX`, `INTRO_MAX` e `WEIGHT_MAX`). */
const MAX_TAGLINE = 120;
const MAX_INTRO = 500;
const MAX_WEIGHT = 150;

const STEPS = [
	__( 'Básico', 'resgata-pet' ),
	__( 'Ficha', 'resgata-pet' ),
	__( 'Saúde', 'resgata-pet' ),
	__( 'História e fotos', 'resgata-pet' ),
];

/** Estado do formulário: tudo em texto, como os campos da tela. */
interface FormState {
	name: string;
	species: string;
	sex: string;
	featured: AnimalImage | null;
	status: string;
	ageYears: string;
	ageMonths: string;
	size: string;
	color: string;
	weight: string;
	temperaments: string[];
	rescueDate: string;
	neutered: string;
	dewormedDate: string;
	fiv: string;
	felv: string;
	vetChecked: string;
	vaccinated: string;
	vaccines: Vaccine[];
	tagline: string;
	intro: string;
	story: string;
	gallery: GalleryPhoto[];
}

const emptyForm: FormState = {
	name: '',
	species: '',
	sex: '',
	featured: null,
	status: 'available',
	ageYears: '',
	ageMonths: '',
	size: '',
	color: '',
	weight: '',
	temperaments: [],
	rescueDate: '',
	neutered: '',
	dewormedDate: '',
	fiv: '',
	felv: '',
	vetChecked: '',
	vaccinated: '',
	vaccines: [],
	tagline: '',
	intro: '',
	story: '',
	gallery: [],
};

function toForm( animal: Animal ): FormState {
	return {
		name: animal.name,
		species: animal.species,
		sex: animal.sex,
		featured: animal.featured_image,
		status: animal.status,
		ageYears: animal.age_years === null ? '' : String( animal.age_years ),
		ageMonths: animal.age_months === null ? '' : String( animal.age_months ),
		size: animal.size,
		color: animal.color,
		weight: animal.weight === null ? '' : String( animal.weight ).replace( '.', ',' ),
		temperaments: animal.temperaments,
		rescueDate: animal.rescue_date,
		neutered: animal.neutered,
		dewormedDate: animal.dewormed_date,
		fiv: animal.fiv,
		felv: animal.felv,
		vetChecked: animal.vet_checked,
		vaccinated: animal.vaccinated,
		vaccines: animal.vaccines,
		tagline: animal.tagline,
		intro: animal.intro,
		story: animal.story,
		gallery: animal.gallery,
	};
}

function toNumber( value: string, max: number ): number | null {
	if ( value.trim() === '' ) {
		return null;
	}

	const number = Math.trunc( Number( value ) );

	return Number.isFinite( number ) ? Math.min( max, Math.max( 0, number ) ) : null;
}

/** Peso digitado ("18,5") em quilos, com uma casa decimal; vazio ou inválido vira null. */
function toWeight( value: string ): number | null {
	const number = Number( value.trim().replace( ',', '.' ) );

	if ( value.trim() === '' || ! Number.isFinite( number ) || number <= 0 ) {
		return null;
	}

	return Math.min( MAX_WEIGHT, Math.round( number * 10 ) / 10 );
}

/** Só os campos do passo informado, no formato que a API espera. */
function stepPayload( step: number, form: FormState ): AnimalInput {
	switch ( step ) {
		case 1:
			return {
				name: form.name.trim(),
				species: form.species,
				sex: form.sex,
				featured_media: form.featured?.id ?? 0,
			};
		case 2: {
			const years = toNumber( form.ageYears, 40 );
			const months = toNumber( form.ageMonths, 11 );
			const hasAge = years !== null || months !== null;

			return {
				status: form.status,
				age_years: hasAge ? years ?? 0 : null,
				age_months: hasAge ? months ?? 0 : null,
				size: form.size,
				weight: toWeight( form.weight ),
				color: form.color.trim(),
				temperaments: form.temperaments,
				rescue_date: form.rescueDate,
			};
		}
		case 3:
			return {
				neutered: form.neutered,
				dewormed_date: form.dewormedDate,
				fiv: form.fiv,
				felv: form.felv,
				vet_checked: form.vetChecked,
				vaccinated: form.vaccinated,
				vaccines: form.vaccines.filter( ( vaccine ) => vaccine.name.trim() !== '' ),
			};
		default:
			return {
				tagline: form.tagline.trim(),
				intro: form.intro.trim(),
				story: form.story,
				gallery: form.gallery.map( ( photo ) => ( { id: photo.id, type: photo.type } ) ),
			};
	}
}

export function AnimalFormPage() {
	const params = useParams();
	const animalId = params.id ? Number( params.id ) : undefined;
	const navigate = useNavigate();
	const queryClient = useQueryClient();
	const [ searchParams, setSearchParams ] = useSearchParams();
	const uid = useId();

	const step = Math.min( STEPS.length, Math.max( 1, Number( searchParams.get( 'passo' ) ) || 1 ) );
	const [ form, setForm ] = useState< FormState >( emptyForm );
	const [ loadedId, setLoadedId ] = useState< number | undefined >();
	const [ errors, setErrors ] = useState< { name?: string; species?: string } >( {} );
	const [ confirmDelete, setConfirmDelete ] = useState( false );

	const query = useQuery( {
		queryKey: [ 'animal', animalId ],
		queryFn: () => api.getAnimal( animalId as number ),
		enabled: animalId !== undefined && Number.isInteger( animalId ),
		staleTime: Infinity,
	} );

	// Preenche o formulário uma vez por animal carregado (e limpa ao abrir "novo").
	useEffect( () => {
		if ( animalId === undefined ) {
			setForm( emptyForm );
			setLoadedId( undefined );
		} else if ( query.data && query.data.id !== loadedId ) {
			setForm( toForm( query.data ) );
			setLoadedId( query.data.id );
		}
	}, [ animalId, query.data, loadedId ] );

	const set = < K extends keyof FormState >( key: K, value: FormState[ K ] ) =>
		setForm( ( current ) => ( { ...current, [ key ]: value } ) );

	const goToStep = ( next: number ) => setSearchParams( { passo: String( next ) }, { replace: true } );

	const save = useMutation( {
		mutationFn: ( input: AnimalInput ) =>
			animalId === undefined ? api.createAnimal( input ) : api.updateAnimal( animalId, input ),
		onSuccess: ( animal ) => {
			queryClient.setQueryData( [ 'animal', animal.id ], animal );
			void queryClient.invalidateQueries( { queryKey: [ 'animals' ] } );
		},
		onError: ( error ) => toast.error( errorMessage( error ) ),
	} );

	// Publicar ou voltar para rascunho, direto do cabeçalho (não mexe nos campos do formulário).
	const changeStatus = useMutation( {
		mutationFn: ( postStatus: PostStatus ) => api.updateAnimal( animalId as number, { post_status: postStatus } ),
		onSuccess: ( animal ) => {
			queryClient.setQueryData( [ 'animal', animal.id ], animal );
			void queryClient.invalidateQueries( { queryKey: [ 'animals' ] } );
			toast.success(
				animal.post_status === 'publish'
					? __( 'Animal publicado no site.', 'resgata-pet' )
					: __( 'Animal voltou para rascunho e saiu do site.', 'resgata-pet' )
			);
		},
		onError: ( error ) => toast.error( errorMessage( error ) ),
	} );

	const trash = useMutation( {
		mutationFn: () => api.trashAnimal( animalId as number ),
		onSuccess: () => {
			void queryClient.invalidateQueries( { queryKey: [ 'animals' ] } );
			queryClient.removeQueries( { queryKey: [ 'animal', animalId ] } );
			toast.success( __( 'Animal movido para a lixeira.', 'resgata-pet' ) );
			void navigate( '/animais' );
		},
		onError: ( error ) => toast.error( errorMessage( error ) ),
	} );

	const validate = (): boolean => {
		const next: typeof errors = {};

		if ( form.name.trim() === '' ) {
			next.name = __( 'Informe o nome do animal.', 'resgata-pet' );
		}
		if ( form.species === '' ) {
			next.species = __( 'Escolha a espécie.', 'resgata-pet' );
		}
		setErrors( next );

		return Object.keys( next ).length === 0;
	};

	/** Salva o passo atual e segue para `nextStep` (ou conclui, com `extra`). */
	const saveStep = async ( nextStep: number | null, extra: AnimalInput = {}, successMessage?: string ) => {
		if ( step === 1 && ! validate() ) {
			return;
		}

		try {
			const animal = await save.mutateAsync( { ...stepPayload( step, form ), ...extra } );

			if ( successMessage ) {
				toast.success( successMessage );
			}

			if ( nextStep === null ) {
				void navigate( '/animais' );
			} else if ( animalId === undefined ) {
				// Acabou de ser criado como rascunho: a URL passa a ser a do animal.
				setLoadedId( animal.id );
				void navigate( `/animais/${ animal.id }?passo=${ nextStep }`, { replace: true } );
			} else {
				goToStep( nextStep );
			}
		} catch {
			// O erro já foi mostrado pelo onError da mutação.
		}
	};

	if ( animalId !== undefined && query.isError ) {
		return (
			<>
				<PageHeader title={ __( 'Animal não encontrado', 'resgata-pet' ) } description={ errorMessage( query.error ) } />
				<Link to="/animais" className="text-primary underline underline-offset-4">
					{ __( 'Voltar para a lista', 'resgata-pet' ) }
				</Link>
			</>
		);
	}

	if ( animalId !== undefined && loadedId !== animalId ) {
		return (
			<div className="flex flex-col gap-4" aria-busy="true">
				<Skeleton className="h-9 w-48" />
				<Skeleton className="h-12 w-full" />
				<Skeleton className="h-64 w-full" />
			</div>
		);
	}

	const animal = query.data;
	const isDraft = ! animal || animal.post_status === 'draft';
	const saving = save.isPending;
	const isLast = step === STEPS.length;
	const id = ( name: string ) => `${ uid }-${ name }`;

	// A lista padrão, mais o que este animal já tiver e não estiver nela (lista mudada depois do cadastro).
	const temperamentOptions = [
		...config.options.temperaments,
		...( animal?.temperaments ?? [] ).filter( ( name ) => ! config.options.temperaments.includes( name ) ),
	];

	return (
		<>
			<Link to="/animais" className="text-muted-foreground hover:text-foreground mb-3 inline-flex items-center gap-1 text-sm">
				<ArrowLeftIcon className="size-4" aria-hidden="true" />
				{ __( 'Animais', 'resgata-pet' ) }
			</Link>

			<PageHeader
				title={ animal ? animal.name : __( 'Cadastrar animal', 'resgata-pet' ) }
				action={
					animal && (
						<div className="flex flex-wrap items-center gap-3">
							{ ! isDraft && (
								<a
									href={ animal.permalink }
									target="_blank"
									rel="noreferrer"
									className="text-primary inline-flex items-center gap-1 text-sm underline-offset-4 hover:underline"
								>
									{ __( 'Ver no site', 'resgata-pet' ) }
									<ExternalLinkIcon className="size-4" aria-hidden="true" />
								</a>
							) }
							<OptionSelect
								className="w-40"
								ariaLabel={ __( 'Publicação', 'resgata-pet' ) }
								value={ animal.post_status }
								options={ POST_STATUSES }
								disabled={ changeStatus.isPending || saving }
								onChange={ ( value ) => {
									if ( value !== animal.post_status ) {
										changeStatus.mutate( value as PostStatus );
									}
								} }
							/>
							<Button
								type="button"
								variant="ghost"
								size="icon"
								aria-label={ __( 'Excluir animal', 'resgata-pet' ) }
								onClick={ () => setConfirmDelete( true ) }
							>
								<Trash2Icon className="text-destructive" aria-hidden="true" />
							</Button>
						</div>
					)
				}
			/>

			{ /* Depois que o animal existe, dá para ir direto a qualquer passo (o atual é salvo antes). */ }
			<Stepper
				label={ __( 'Passos do cadastro', 'resgata-pet' ) }
				steps={ STEPS }
				current={ step }
				canNavigate={ animalId !== undefined && ! saving }
				onSelect={ ( number ) => void saveStep( number ) }
			/>

			<form
				className="bg-card grid gap-5 rounded-xl border p-4 sm:p-6 md:grid-cols-2 md:gap-x-8 md:p-8"
				noValidate
				onSubmit={ ( event ) => {
					event.preventDefault();
					void saveStep( isLast ? null : step + 1, {}, isLast ? __( 'Cadastro salvo.', 'resgata-pet' ) : undefined );
				} }
			>
				{ step === 1 && (
					<>
						<Field label={ __( 'Nome', 'resgata-pet' ) } htmlFor={ id( 'name' ) } error={ errors.name }>
							<Input
								id={ id( 'name' ) }
								value={ form.name }
								maxLength={ 200 }
								autoComplete="off"
								aria-invalid={ errors.name ? true : undefined }
								onChange={ ( event ) => set( 'name', event.target.value ) }
							/>
						</Field>
						<Field label={ __( 'Espécie', 'resgata-pet' ) } htmlFor={ id( 'species' ) } error={ errors.species }>
							<OptionSelect
								id={ id( 'species' ) }
								value={ form.species }
								options={ config.options.species }
								placeholder={ __( 'Escolha', 'resgata-pet' ) }
								invalid={ Boolean( errors.species ) }
								onChange={ ( value ) => set( 'species', value ) }
							/>
						</Field>
						<Field label={ __( 'Sexo', 'resgata-pet' ) } htmlFor={ id( 'sex' ) }>
							<OptionSelect
								id={ id( 'sex' ) }
								value={ form.sex }
								options={ config.options.sexes }
								emptyLabel={ __( 'Não informado', 'resgata-pet' ) }
								onChange={ ( value ) => set( 'sex', value ) }
							/>
						</Field>
						<Field
							className="md:col-span-2"
							label={ __( 'Foto principal', 'resgata-pet' ) }
							hint={ __( 'É a foto que aparece na lista de animais do site.', 'resgata-pet' ) }
						>
							{ form.featured && (
								<div className="relative w-40">
									<img src={ form.featured.url } alt="" className="aspect-square w-40 rounded-xl object-cover" />
									<Button
										type="button"
										variant="secondary"
										size="icon-sm"
										className="absolute top-2 right-2"
										aria-label={ __( 'Remover foto principal', 'resgata-pet' ) }
										onClick={ () => set( 'featured', null ) }
									>
										<XIcon aria-hidden="true" />
									</Button>
								</div>
							) }
							<ImageDropzone animalId={ animalId } onUploaded={ ( image ) => set( 'featured', image ) } />
						</Field>
					</>
				) }

				{ step === 2 && (
					<>
						<Field
							className="md:col-span-2"
							label={ __( 'Situação', 'resgata-pet' ) }
							htmlFor={ id( 'status' ) }
							hint={ __( 'Só "Disponível para adoção" e "Reservado" aparecem na lista pública.', 'resgata-pet' ) }
						>
							<OptionSelect id={ id( 'status' ) } value={ form.status } options={ config.options.statuses } onChange={ ( value ) => set( 'status', value ) } />
						</Field>
						<Field
							label={ __( 'Idade estimada', 'resgata-pet' ) }
							htmlFor={ id( 'age-years' ) }
						>
							<div className="flex items-center gap-2">
								<Input
									id={ id( 'age-years' ) }
									type="number"
									inputMode="numeric"
									min={ 0 }
									max={ 40 }
									className="w-20"
									value={ form.ageYears }
									onChange={ ( event ) => set( 'ageYears', event.target.value ) }
								/>
								<span>{ __( 'anos e', 'resgata-pet' ) }</span>
								<Input
									type="number"
									inputMode="numeric"
									min={ 0 }
									max={ 11 }
									className="w-20"
									aria-label={ __( 'Meses', 'resgata-pet' ) }
									value={ form.ageMonths }
									onChange={ ( event ) => set( 'ageMonths', event.target.value ) }
								/>
								<span>{ __( 'meses', 'resgata-pet' ) }</span>
							</div>
						</Field>
						<Field label={ __( 'Porte', 'resgata-pet' ) } htmlFor={ id( 'size' ) }>
							<OptionSelect
								id={ id( 'size' ) }
								value={ form.size }
								options={ config.options.sizes }
								emptyLabel={ __( 'Não informado', 'resgata-pet' ) }
								onChange={ ( value ) => set( 'size', value ) }
							/>
						</Field>
						<Field
							label={ __( 'Peso (kg)', 'resgata-pet' ) }
							htmlFor={ id( 'weight' ) }
							hint={ __( 'Opcional. Aparece ao lado do porte. Ex.: 18,5', 'resgata-pet' ) }
						>
							<Input
								id={ id( 'weight' ) }
								inputMode="decimal"
								className="w-28"
								maxLength={ 5 }
								value={ form.weight }
								onChange={ ( event ) => set( 'weight', event.target.value.replace( /[^0-9.,]/g, '' ) ) }
							/>
						</Field>
						<Field label={ __( 'Cor / pelagem', 'resgata-pet' ) } htmlFor={ id( 'color' ) }>
							<Input
								id={ id( 'color' ) }
								value={ form.color }
								maxLength={ 100 }
								placeholder={ __( 'Ex.: tigrado, preto e branco', 'resgata-pet' ) }
								onChange={ ( event ) => set( 'color', event.target.value ) }
							/>
						</Field>
						<Field label={ __( 'Data do resgate', 'resgata-pet' ) } htmlFor={ id( 'rescue' ) }>
							<DatePicker id={ id( 'rescue' ) } value={ form.rescueDate } onChange={ ( value ) => set( 'rescueDate', value ) } />
						</Field>
						<fieldset className="md:col-span-2">
							<legend className="mb-3 text-base font-medium">{ __( 'Temperamento', 'resgata-pet' ) }</legend>
							<div className="grid grid-cols-1 gap-x-6 gap-y-1 sm:grid-cols-2 lg:grid-cols-3">
								{ temperamentOptions.map( ( name ) => (
									<label key={ name } className="flex min-h-11 cursor-pointer items-center gap-3">
										<Checkbox
											checked={ form.temperaments.includes( name ) }
											onCheckedChange={ ( checked ) =>
												set(
													'temperaments',
													checked ? [ ...form.temperaments, name ] : form.temperaments.filter( ( item ) => item !== name )
												)
											}
										/>
										{ name }
									</label>
								) ) }
							</div>
						</fieldset>
					</>
				) }

				{ step === 3 && (
					<>
						<Field label={ __( 'Castrado(a)', 'resgata-pet' ) } htmlFor={ id( 'neutered' ) }>
							<OptionSelect
								id={ id( 'neutered' ) }
								value={ form.neutered }
								options={ config.options.yesNo }
								emptyLabel={ __( 'Não informado', 'resgata-pet' ) }
								onChange={ ( value ) => set( 'neutered', value ) }
							/>
						</Field>
						<Field label={ __( 'Último vermífugo', 'resgata-pet' ) } htmlFor={ id( 'dewormed' ) }>
							<DatePicker id={ id( 'dewormed' ) } value={ form.dewormedDate } onChange={ ( value ) => set( 'dewormedDate', value ) } />
						</Field>
						<Field label={ __( 'Passou por consulta veterinária', 'resgata-pet' ) } htmlFor={ id( 'vet' ) }>
							<OptionSelect
								id={ id( 'vet' ) }
								value={ form.vetChecked }
								options={ config.options.yesNo }
								emptyLabel={ __( 'Não informado', 'resgata-pet' ) }
								onChange={ ( value ) => set( 'vetChecked', value ) }
							/>
						</Field>
						<Field
							label={ __( 'Vacinas em dia', 'resgata-pet' ) }
							htmlFor={ id( 'vaccinated' ) }
							hint={ __( 'Aparece em destaque na página do animal.', 'resgata-pet' ) }
						>
							<OptionSelect
								id={ id( 'vaccinated' ) }
								value={ form.vaccinated }
								options={ config.options.yesNo }
								emptyLabel={ __( 'Não informado', 'resgata-pet' ) }
								onChange={ ( value ) => set( 'vaccinated', value ) }
							/>
						</Field>
						<Field label={ __( 'Teste de FIV (gatos)', 'resgata-pet' ) } htmlFor={ id( 'fiv' ) }>
							<OptionSelect
								id={ id( 'fiv' ) }
								value={ form.fiv }
								options={ config.options.tests }
								emptyLabel={ __( 'Não testado', 'resgata-pet' ) }
								onChange={ ( value ) => set( 'fiv', value ) }
							/>
						</Field>
						<Field label={ __( 'Teste de FeLV (gatos)', 'resgata-pet' ) } htmlFor={ id( 'felv' ) }>
							<OptionSelect
								id={ id( 'felv' ) }
								value={ form.felv }
								options={ config.options.tests }
								emptyLabel={ __( 'Não testado', 'resgata-pet' ) }
								onChange={ ( value ) => set( 'felv', value ) }
							/>
						</Field>

						<fieldset className="flex flex-col gap-3 md:col-span-2">
							<legend className="mb-2 text-base font-medium">{ __( 'Vacinas', 'resgata-pet' ) }</legend>
							{ form.vaccines.map( ( vaccine, index ) => (
								<div key={ index } className="flex flex-wrap items-center gap-2">
									<Input
										className="min-w-40 flex-1"
										value={ vaccine.name }
										maxLength={ 100 }
										placeholder={ __( 'Ex.: V4, antirrábica', 'resgata-pet' ) }
										aria-label={ __( 'Nome da vacina', 'resgata-pet' ) }
										onChange={ ( event ) =>
											set(
												'vaccines',
												form.vaccines.map( ( item, position ) =>
													position === index ? { ...item, name: event.target.value } : item
												)
											)
										}
									/>
									<DatePicker
										className="w-44"
										value={ vaccine.date }
										ariaLabel={ __( 'Data da aplicação', 'resgata-pet' ) }
										onChange={ ( value ) =>
											set(
												'vaccines',
												form.vaccines.map( ( item, position ) => ( position === index ? { ...item, date: value } : item ) )
											)
										}
									/>
									<Button
										type="button"
										variant="ghost"
										size="icon"
										aria-label={ __( 'Remover vacina', 'resgata-pet' ) }
										onClick={ () =>
											set(
												'vaccines',
												form.vaccines.filter( ( _, position ) => position !== index )
											)
										}
									>
										<XIcon aria-hidden="true" />
									</Button>
								</div>
							) ) }
							<Button
								type="button"
								variant="outline"
								className="self-start"
								onClick={ () => set( 'vaccines', [ ...form.vaccines, { name: '', date: '' } ] ) }
							>
								<PlusIcon aria-hidden="true" />
								{ __( 'Adicionar vacina', 'resgata-pet' ) }
							</Button>
						</fieldset>
					</>
				) }

				{ step === 4 && (
					<>
						<Field
							className="md:col-span-2"
							label={ __( 'Frase de apresentação', 'resgata-pet' ) }
							htmlFor={ id( 'tagline' ) }
							hint={ __( 'Uma linha que aparece logo abaixo do nome. Ex.: Um companheiro cheio de alegria e carinho!', 'resgata-pet' ) }
						>
							<Input
								id={ id( 'tagline' ) }
								value={ form.tagline }
								maxLength={ MAX_TAGLINE }
								onChange={ ( event ) => set( 'tagline', event.target.value ) }
							/>
						</Field>
						<Field
							className="md:col-span-2"
							label={ __( 'Resumo', 'resgata-pet' ) }
							htmlFor={ id( 'intro' ) }
							hint={ __( 'Duas ou três frases sobre como o animal é hoje. Aparece no topo da página, ao lado da foto.', 'resgata-pet' ) }
						>
							<Textarea
								id={ id( 'intro' ) }
								rows={ 3 }
								value={ form.intro }
								maxLength={ MAX_INTRO }
								onChange={ ( event ) => set( 'intro', event.target.value ) }
							/>
						</Field>
						<div className="flex flex-col gap-2 md:col-span-2">
							<span id={ id( 'story' ) } className="text-base font-medium">
								{ __( 'História do resgate', 'resgata-pet' ) }
							</span>
							<RichText key={ loadedId } value={ form.story } labelledBy={ id( 'story' ) } onChange={ ( html ) => set( 'story', html ) } />
							<p className="text-muted-foreground text-sm">
								{ __( 'Conte onde o animal foi encontrado, como estava e como foi a recuperação.', 'resgata-pet' ) }
							</p>
						</div>

						<fieldset className="flex flex-col gap-3 md:col-span-2">
							<legend className="mb-1 text-base font-medium">{ __( 'Galeria de fotos (antes e depois)', 'resgata-pet' ) }</legend>
							<p className="text-muted-foreground text-sm">
								{ __( 'Marque cada foto como "Antes" (quando foi resgatado) ou "Depois" (recuperado).', 'resgata-pet' ) }
							</p>
							{ form.gallery.length > 0 && (
								<ul className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
									{ form.gallery.map( ( photo ) => (
										<li key={ photo.id } className="bg-muted flex flex-col gap-2 rounded-xl p-2">
											<div className="relative">
												<img src={ photo.url } alt="" className="aspect-square w-full rounded-lg object-cover" />
												<Button
													type="button"
													variant="secondary"
													size="icon-sm"
													className="absolute top-1 right-1"
													aria-label={ __( 'Remover foto', 'resgata-pet' ) }
													onClick={ () =>
														set(
															'gallery',
															form.gallery.filter( ( item ) => item.id !== photo.id )
														)
													}
												>
													<XIcon aria-hidden="true" />
												</Button>
											</div>
											<OptionSelect
												value={ photo.type }
												options={ config.options.photoTypes }
												ariaLabel={ __( 'Tipo da foto', 'resgata-pet' ) }
												onChange={ ( value ) =>
													set(
														'gallery',
														form.gallery.map( ( item ) => ( item.id === photo.id ? { ...item, type: value as PhotoType } : item ) )
													)
												}
											/>
										</li>
									) ) }
								</ul>
							) }
							<ImageDropzone
								animalId={ animalId }
								multiple
								onUploaded={ ( image ) =>
									setForm( ( current ) =>
										current.gallery.some( ( item ) => item.id === image.id )
											? current
											: { ...current, gallery: [ ...current.gallery, { ...image, type: 'after' } ] }
									)
								}
							/>
						</fieldset>
					</>
				) }

				<div className="flex flex-wrap items-center justify-between gap-3 border-t pt-5 md:col-span-2">
					{ step > 1 ? (
						<Button type="button" variant="ghost" disabled={ saving } onClick={ () => goToStep( step - 1 ) }>
							{ __( 'Voltar', 'resgata-pet' ) }
						</Button>
					) : (
						<span />
					) }

					<div className="flex flex-wrap items-center gap-2">
						{ isLast && isDraft && (
							<Button type="submit" variant="outline" disabled={ saving }>
								{ __( 'Salvar rascunho', 'resgata-pet' ) }
							</Button>
						) }
						{ isLast && isDraft ? (
							<Button
								type="button"
								disabled={ saving }
								onClick={ () => void saveStep( null, { post_status: 'publish' }, __( 'Animal publicado no site.', 'resgata-pet' ) ) }
							>
								{ saving ? <Loader2Icon className="animate-spin" aria-hidden="true" /> : <CheckIcon aria-hidden="true" /> }
								{ __( 'Publicar', 'resgata-pet' ) }
							</Button>
						) : (
							<Button type="submit" disabled={ saving }>
								{ saving && <Loader2Icon className="animate-spin" aria-hidden="true" /> }
								{ isLast ? __( 'Salvar', 'resgata-pet' ) : __( 'Salvar e continuar', 'resgata-pet' ) }
							</Button>
						) }
					</div>
				</div>
			</form>

			<AlertDialog open={ confirmDelete } onOpenChange={ setConfirmDelete }>
				<AlertDialogContent>
					<AlertDialogHeader>
						<AlertDialogTitle>{ __( 'Excluir este animal?', 'resgata-pet' ) }</AlertDialogTitle>
						<AlertDialogDescription>
							{ __(
								'O cadastro vai para a lixeira e sai do site. O administrador consegue restaurar em até 30 dias.',
								'resgata-pet'
							) }
						</AlertDialogDescription>
					</AlertDialogHeader>
					<AlertDialogFooter>
						<AlertDialogCancel>{ __( 'Cancelar', 'resgata-pet' ) }</AlertDialogCancel>
						<AlertDialogAction variant="destructive" disabled={ trash.isPending } onClick={ () => trash.mutate() }>
							{ __( 'Excluir', 'resgata-pet' ) }
						</AlertDialogAction>
					</AlertDialogFooter>
				</AlertDialogContent>
			</AlertDialog>
		</>
	);
}
