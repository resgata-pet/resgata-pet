/**
 * Máscaras de digitação. Uma máscara recebe o texto digitado (ou colado) e devolve o texto
 * formatado; use com o componente `MaskedInput`.
 *
 * Para criar outra: `export const maskCep = createMask( '#####-###' );`
 */
export type Mask = ( value: string ) => string;

/** Símbolos do padrão: `#` aceita um número; `A` aceita um número ou uma letra. */
const SLOTS: Record< string, RegExp > = {
	'#': /\d/,
	A: /[A-Z0-9]/,
};

/**
 * Cria uma máscara a partir de um padrão, como `(##) #####-####`.
 *
 * Tudo que não é `#` nem `A` é pontuação fixa, e só aparece quando existe um caractere depois
 * dela (assim dá para apagar normalmente). Letras viram maiúsculas.
 */
export function createMask( pattern: string ): Mask {
	return ( value ) => {
		const chars = value.toUpperCase().replace( /[^A-Z0-9]/g, '' );
		let result = '';
		let punctuation = '';
		let index = 0;

		for ( const symbol of pattern ) {
			const slot = SLOTS[ symbol ];

			if ( ! slot ) {
				punctuation += symbol;
				continue;
			}

			// Pula o que não cabe nesta posição (ex.: letra em campo de número).
			while ( index < chars.length && ! slot.test( chars[ index ] ) ) {
				index++;
			}

			if ( index >= chars.length ) {
				break;
			}

			result += punctuation + chars[ index++ ];
			punctuation = '';
		}

		return result;
	};
}

/** CNPJ: 12.345.678/0001-90. Aceita letras nas 12 primeiras posições (CNPJ alfanumérico). */
export const maskCnpj: Mask = createMask( 'AA.AAA.AAA/AAAA-##' );

const maskLandline = createMask( '(##) ####-####' );
const maskMobile = createMask( '(##) #####-####' );

/** Telefone com DDD: (11) 1234-5678 ou (11) 91234-5678. Um "+55" colado no início é descartado. */
export const maskPhone: Mask = ( value ) => {
	const digits = value.replace( /^\s*\+55/, '' ).replace( /\D/g, '' );

	return digits.length > 10 ? maskMobile( digits ) : maskLandline( digits );
};
