import { Link } from 'react-router';
import { __, sprintf } from '@wordpress/i18n';
import { PlusIcon } from 'lucide-react';
import { buttonVariants } from '@/components/ui/button';
import { config } from '@/lib/config';

export function HomePage() {
	const firstName = config.user.name.split( ' ' )[ 0 ];

	return (
		<div className="bg-card flex flex-col items-start gap-6 rounded-2xl border p-6 md:p-10">
			<div>
				<h1 className="text-2xl font-semibold tracking-tight">
					{ /* translators: %s: primeiro nome da pessoa. */ }
					{ sprintf( __( 'Olá, %s!', 'resgata-wp' ), firstName ) }
				</h1>
				<p className="text-muted-foreground mt-1">{ __( 'O que você quer fazer agora?', 'resgata-wp' ) }</p>
			</div>

			<Link to="/animais/novo" className={ buttonVariants( { size: 'lg', className: 'w-full sm:w-auto' } ) }>
				<PlusIcon aria-hidden="true" />
				{ __( 'Cadastrar animal', 'resgata-wp' ) }
			</Link>
		</div>
	);
}
