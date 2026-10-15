/** Data no formato brasileiro: 05/10/2026. */
export function formatDate( iso: string ): string {
	const date = new Date( iso );

	if ( Number.isNaN( date.getTime() ) ) {
		return '';
	}

	return date.toLocaleDateString( 'pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' } );
}

/** Data e hora no formato brasileiro: 05/10/2026 14:30. */
export function formatDateTime( iso: string ): string {
	const date = new Date( iso );

	if ( Number.isNaN( date.getTime() ) ) {
		return '';
	}

	return date.toLocaleString( 'pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' } );
}
