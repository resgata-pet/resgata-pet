import { Link } from 'react-router';
import { __ } from '@wordpress/i18n';
import { buttonVariants } from '@/components/ui/button';
import { PageHeader } from '@/components/page-header';

export function NotFoundPage() {
	return (
		<>
			<PageHeader
				title={ __( 'Página não encontrada', 'resgata-wp' ) }
				description={ __( 'Esse endereço não existe no painel.', 'resgata-wp' ) }
			/>
			<Link to="/" className={ buttonVariants( { variant: 'outline' } ) }>
				{ __( 'Voltar ao início', 'resgata-wp' ) }
			</Link>
		</>
	);
}
