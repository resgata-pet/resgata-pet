import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { createBrowserRouter, RouterProvider } from 'react-router';
import { config } from '@/lib/config';
import { Toaster } from '@/components/ui/sonner';
import { Layout } from '@/components/layout';
import { HomePage } from '@/pages/home';
import { AnimalsListPage } from '@/pages/animals-list';
import { AnimalFormPage } from '@/pages/animal-form';
import { AccountPage } from '@/pages/account';
import { AdoptionDetailPage } from '@/pages/adoption-detail';
import { AdoptionsListPage } from '@/pages/adoptions-list';
import { SettingsPage } from '@/pages/settings';
import { NotFoundPage } from '@/pages/not-found';
import './index.css';

const queryClient = new QueryClient( {
	defaultOptions: { queries: { retry: 1, refetchOnWindowFocus: false } },
} );

const router = createBrowserRouter(
	[
		{
			element: <Layout />,
			children: [
				{ index: true, element: <HomePage /> },
				{ path: 'animais', element: <AnimalsListPage /> },
				{ path: 'animais/novo', element: <AnimalFormPage /> },
				{ path: 'animais/:id', element: <AnimalFormPage /> },
				{ path: 'adocoes', element: <AdoptionsListPage /> },
				{ path: 'adocoes/:id', element: <AdoptionDetailPage /> },
				{ path: 'configuracoes', element: <SettingsPage /> },
				{ path: 'conta', element: <AccountPage /> },
				{ path: '*', element: <NotFoundPage /> },
			],
		},
	],
	{ basename: config.basePath }
);

const container = document.getElementById( 'rwp-painel' );

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
