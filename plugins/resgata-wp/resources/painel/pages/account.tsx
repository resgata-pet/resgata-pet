import { __ } from '@wordpress/i18n';
import { KeyRoundIcon, LogOutIcon, SettingsIcon } from 'lucide-react';
import { buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { PageHeader } from '@/components/page-header';
import { config } from '@/lib/config';

export function AccountPage() {
	return (
		<>
			<PageHeader title={ __( 'Minha conta', 'resgata-wp' ) } />

			<div className="grid gap-4 md:grid-cols-2">
				<Card>
					<CardHeader>
						<CardTitle>{ config.user.name }</CardTitle>
						<CardDescription>{ config.user.email }</CardDescription>
					</CardHeader>
				</Card>

				<Card>
					<CardHeader>
						<CardTitle>{ __( 'Senha', 'resgata-wp' ) }</CardTitle>
						<CardDescription>
							{ __(
								'Para trocar a senha, enviamos um link para o seu e-mail. Abra o link e escolha a senha nova.',
								'resgata-wp'
							) }
						</CardDescription>
					</CardHeader>
					<CardContent>
						<a href={ config.urls.lostPassword } className={ buttonVariants( { variant: 'outline' } ) }>
							<KeyRoundIcon aria-hidden="true" />
							{ __( 'Trocar minha senha', 'resgata-wp' ) }
						</a>
					</CardContent>
				</Card>

				<div className="flex flex-wrap gap-3 md:col-span-2">
					{ config.user.isAdmin && (
						<a href={ config.urls.admin } className={ buttonVariants( { variant: 'outline' } ) }>
							<SettingsIcon aria-hidden="true" />
							{ __( 'Ir para o WordPress', 'resgata-wp' ) }
						</a>
					) }
					<a href={ config.urls.logout } className={ buttonVariants( { variant: 'ghost' } ) }>
						<LogOutIcon aria-hidden="true" />
						{ __( 'Sair', 'resgata-wp' ) }
					</a>
				</div>
			</div>
		</>
	);
}
