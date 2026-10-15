import { useState } from 'react';
import { EditorContent, useEditor, useEditorState } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import { __ } from '@wordpress/i18n';
import { BoldIcon, HeadingIcon, ItalicIcon, LinkIcon, ListIcon } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

/**
 * Editor de texto (Tiptap) só com o essencial: negrito, itálico, subtítulo,
 * lista e link. O servidor filtra o HTML recebido de qualquer forma.
 */
export function RichText( {
	value,
	onChange,
	labelledBy,
}: {
	value: string;
	onChange: ( html: string ) => void;
	labelledBy?: string;
} ) {
	const [ linkOpen, setLinkOpen ] = useState( false );
	const [ linkUrl, setLinkUrl ] = useState( '' );

	const editor = useEditor( {
		extensions: [
			StarterKit.configure( {
				heading: { levels: [ 3 ] },
				blockquote: false,
				code: false,
				codeBlock: false,
				horizontalRule: false,
				strike: false,
				underline: false,
				link: { openOnClick: false, autolink: true, defaultProtocol: 'https' },
			} ),
		],
		content: value,
		editorProps: {
			attributes: {
				class: 'resgata-prose',
				role: 'textbox',
				'aria-multiline': 'true',
				...( labelledBy ? { 'aria-labelledby': labelledBy } : {} ),
			},
		},
		onUpdate: ( { editor: current } ) => onChange( current.isEmpty ? '' : current.getHTML() ),
	} );

	const active = useEditorState( {
		editor,
		selector: ( { editor: current } ) => ( {
			bold: current?.isActive( 'bold' ) ?? false,
			italic: current?.isActive( 'italic' ) ?? false,
			heading: current?.isActive( 'heading' ) ?? false,
			list: current?.isActive( 'bulletList' ) ?? false,
			link: current?.isActive( 'link' ) ?? false,
		} ),
	} );

	if ( ! editor ) {
		return null;
	}

	const openLink = () => {
		setLinkUrl( ( editor.getAttributes( 'link' ).href as string | undefined ) ?? '' );
		setLinkOpen( true );
	};

	const applyLink = () => {
		const url = linkUrl.trim();
		const chain = editor.chain().focus().extendMarkRange( 'link' );

		if ( url ) {
			chain.setLink( { href: /^[a-z]+:|^\//i.test( url ) ? url : `https://${ url }` } ).run();
		} else {
			chain.unsetLink().run();
		}
		setLinkOpen( false );
	};

	const tools = [
		{ key: 'bold', label: __( 'Negrito', 'resgata-pet' ), icon: BoldIcon, run: () => editor.chain().focus().toggleBold().run() },
		{ key: 'italic', label: __( 'Itálico', 'resgata-pet' ), icon: ItalicIcon, run: () => editor.chain().focus().toggleItalic().run() },
		{ key: 'heading', label: __( 'Subtítulo', 'resgata-pet' ), icon: HeadingIcon, run: () => editor.chain().focus().toggleHeading( { level: 3 } ).run() },
		{ key: 'list', label: __( 'Lista', 'resgata-pet' ), icon: ListIcon, run: () => editor.chain().focus().toggleBulletList().run() },
		{ key: 'link', label: __( 'Link', 'resgata-pet' ), icon: LinkIcon, run: openLink },
	] as const;

	return (
		<div className="bg-card focus-within:border-ring focus-within:ring-ring/50 rounded-lg border border-input focus-within:ring-3">
			<div className="flex flex-wrap gap-1 border-b p-1" role="toolbar" aria-label={ __( 'Formatação', 'resgata-pet' ) }>
				{ tools.map( ( tool ) => (
					<Button
						key={ tool.key }
						type="button"
						variant="ghost"
						size="icon-sm"
						aria-label={ tool.label }
						title={ tool.label }
						aria-pressed={ active?.[ tool.key ] ?? false }
						className={ cn( active?.[ tool.key ] && 'bg-accent text-accent-foreground' ) }
						onClick={ tool.run }
					>
						<tool.icon aria-hidden="true" />
					</Button>
				) ) }
			</div>

			<EditorContent editor={ editor } />

			<Dialog open={ linkOpen } onOpenChange={ setLinkOpen }>
				<DialogContent>
					<DialogHeader>
						<DialogTitle>{ __( 'Endereço do link', 'resgata-pet' ) }</DialogTitle>
					</DialogHeader>
					<Input
						type="url"
						inputMode="url"
						placeholder="https://"
						value={ linkUrl }
						aria-label={ __( 'Endereço do link', 'resgata-pet' ) }
						onChange={ ( event ) => setLinkUrl( event.target.value ) }
						onKeyDown={ ( event ) => {
							if ( event.key === 'Enter' ) {
								event.preventDefault();
								applyLink();
							}
						} }
					/>
					<DialogFooter>
						<Button type="button" variant="outline" onClick={ () => setLinkOpen( false ) }>
							{ __( 'Cancelar', 'resgata-pet' ) }
						</Button>
						<Button type="button" onClick={ applyLink }>
							{ linkUrl.trim() ? __( 'Aplicar', 'resgata-pet' ) : __( 'Remover link', 'resgata-pet' ) }
						</Button>
					</DialogFooter>
				</DialogContent>
			</Dialog>
		</div>
	);
}
