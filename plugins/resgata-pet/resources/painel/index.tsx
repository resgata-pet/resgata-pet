import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { createBrowserRouter, RouterProvider } from 'react-router';
import { config, hasModule } from '@/lib/config';
import { Toaster } from '@/components/ui/sonner';
import { Layout } from '@/components/layout';
import { HomePage } from '@/pages/home';
import { AnimalsListPage } from '@/pages/animals-list';
import { AnimalFormPage } from '@/pages/animal-form';
import { AccountPage } from '@/pages/account';
import { AdoptionDetailPage } from '@/pages/adoption-detail';
import { AdoptionsListPage } from '@/pages/adoptions-list';
import { SettingsPage } from '@/pages/settings';
import { BannersPage } from '@/pages/banners';
import { SponsorshipPage } from '@/pages/sponsorship';
import { NotFoundPage } from '@/pages/not-found';
import './index.css';

const queryClient = new QueryClient( {
	defaultOptions: { queries: { retry: 1, refetchOnWindowFocus: false } },
} );

// Só as telas dos módulos ligados: as outras caem em "página não encontrada".
const moduleRoutes = [
	...( hasModule( 'animals' )
		? [
				{ path: 'animais', element: <AnimalsListPage /> },
				{ path: 'animais/novo', element: <AnimalFormPage /> },
				{ path: 'animais/:id', element: <AnimalFormPage /> },
			]
		: [] ),
	...( hasModule( 'adoptions' )
		? [
				{ path: 'adocoes', element: <AdoptionsListPage /> },
				{ path: 'adocoes/:id', element: <AdoptionDetailPage /> },
			]
		: [] ),
	...( hasModule( 'sponsorship' ) ? [ { path: 'apadrinhamento', element: <SponsorshipPage /> } ] : [] ),
];

const router = createBrowserRouter(
	[
		{
			element: <Layout />,
			children: [
				{ index: true, element: <HomePage /> },
				...moduleRoutes,
				{ path: 'configuracoes', element: <SettingsPage /> },
				{ path: 'banners', element: <BannersPage /> },
				{ path: 'conta', element: <AccountPage /> },
				{ path: '*', element: <NotFoundPage /> },
			],
		},
	],
	{ basename: config.basePath }
);

const container = document.getElementById( 'resgata-painel' );

if ( container ) {
	createRoot( container ).render(
		<StrictMode>
			<QueryClientProvider client={ queryClient }>
				<RouterProvider router={ router } />
				<Toaster />
			</QueryClientProvider>
		</StrictMode>
	);
}
