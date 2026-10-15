import { Link } from 'react-router';
import { __ } from '@wordpress/i18n';
import { buttonVariants } from '@/components/ui/button';
import { PageHeader } from '@/components/page-header';

export function NotFoundPage() {
	return (
		<>
			<PageHeader
				title={ __( 'Página não encontrada', 'resgata-pet' ) }
				description={ __( 'Esse endereço não existe no painel.', 'resgata-pet' ) }
			/>
			<Link to="/" className={ buttonVariants( { variant: 'outline' } ) }>
				{ __( 'Voltar ao início', 'resgata-pet' ) }
			</Link>
		</>
	);
}
