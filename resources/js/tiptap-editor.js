import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import TextAlign from '@tiptap/extension-text-align';
import Underline from '@tiptap/extension-underline';
import Youtube from '@tiptap/extension-youtube';

document.addEventListener('alpine:init', () => {
    Alpine.data('tiptapEditor', (initialContent = '', livewireModel = '') => ({
        editor: null,
        content: initialContent,

        init() {
            this.editor = new Editor({
                element: this.$refs.editor,
                extensions: [
                    StarterKit.configure({
                        heading: { levels: [2, 3, 4] },
                    }),
                    Image.configure({ inline: false, allowBase64: true }),
                    Link.configure({ openOnClick: false, HTMLAttributes: { rel: 'noopener noreferrer', target: '_blank' } }),
                    Placeholder.configure({ placeholder: 'Start writing your article...' }),
                    TextAlign.configure({ types: ['heading', 'paragraph'] }),
                    Underline,
                    Youtube.configure({ width: 640, height: 360 }),
                ],
                content: this.content,
                editorProps: {
                    attributes: {
                        class: 'prose prose-sm sm:prose-base dark:prose-invert max-w-none focus:outline-none min-h-[400px] px-4 py-3',
                    },
                },
                onUpdate: ({ editor }) => {
                    this.content = editor.getHTML();
                    if (livewireModel) {
                        this.$wire.set(livewireModel, this.content);
                    }
                },
            });

            // Listen for external content updates (e.g., AI-generated content)
            this.$watch('content', (value) => {
                if (this.editor && value !== this.editor.getHTML()) {
                    this.editor.commands.setContent(value, false);
                }
            });

            // Listen for image insertion from media picker (browser event from Livewire dispatch)
            window.addEventListener('insert-content-image', (e) => {
                const url = e.detail?.url;
                if (this.editor && url) {
                    this.editor.chain().focus().setImage({ src: url }).run();
                }
            });

        },

        destroy() {
            this.editor?.destroy();
        },

        // Toolbar actions
        toggleBold() { this.editor.chain().focus().toggleBold().run(); },
        toggleItalic() { this.editor.chain().focus().toggleItalic().run(); },
        toggleUnderline() { this.editor.chain().focus().toggleUnderline().run(); },
        toggleStrike() { this.editor.chain().focus().toggleStrike().run(); },
        toggleHeading(level) { this.editor.chain().focus().toggleHeading({ level }).run(); },
        toggleBulletList() { this.editor.chain().focus().toggleBulletList().run(); },
        toggleOrderedList() { this.editor.chain().focus().toggleOrderedList().run(); },
        toggleBlockquote() { this.editor.chain().focus().toggleBlockquote().run(); },
        toggleCodeBlock() { this.editor.chain().focus().toggleCodeBlock().run(); },
        setHorizontalRule() { this.editor.chain().focus().setHorizontalRule().run(); },
        undo() { this.editor.chain().focus().undo().run(); },
        redo() { this.editor.chain().focus().redo().run(); },

        setTextAlign(align) { this.editor.chain().focus().setTextAlign(align).run(); },

        addLink() {
            const url = prompt('Enter URL:');
            if (url) {
                this.editor.chain().focus().setLink({ href: url }).run();
            }
        },
        removeLink() { this.editor.chain().focus().unsetLink().run(); },

        addImage() {
            // Get keyword from the post editor if available
            const keyword = document.querySelector('[wire\\:model\\.live\\.debounce\\.500ms=focus_keyword]')?.value
                || document.querySelector('[wire\\:model=title]')?.value
                || '';
            Livewire.dispatch('open-media-picker', { context: 'content_image', keyword });
        },

        addYoutube() {
            const url = prompt('Enter YouTube URL:');
            if (url) {
                this.editor.chain().focus().setYoutubeVideo({ src: url }).run();
            }
        },

        // State checks for active button styling
        isActive(type, attrs = {}) {
            return this.editor?.isActive(type, attrs) ?? false;
        },
    }));
});
