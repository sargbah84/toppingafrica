import { Node, mergeAttributes } from '@tiptap/core';

/**
 * Detects the embed platform from a URL.
 * Returns { platform, embedUrl, id } or null.
 */
export function detectEmbed(url) {
    try {
        const u = new URL(url);
        const host = u.hostname.replace('www.', '');

        // YouTube
        if (host === 'youtube.com' || host === 'youtu.be' || host === 'm.youtube.com') {
            let id = null;
            if (host === 'youtu.be') {
                id = u.pathname.slice(1);
            } else if (u.pathname.startsWith('/shorts/')) {
                id = u.pathname.split('/shorts/')[1];
            } else {
                id = u.searchParams.get('v');
            }
            if (id) {
                return { platform: 'youtube', embedUrl: `https://www.youtube.com/embed/${id}`, id };
            }
        }

        // Twitter / X
        if (host === 'twitter.com' || host === 'x.com') {
            const match = u.pathname.match(/\/(\w+)\/status\/(\d+)/);
            if (match) {
                return { platform: 'twitter', embedUrl: url, id: match[2] };
            }
        }

        // Instagram
        if (host === 'instagram.com') {
            const match = u.pathname.match(/\/(p|reel|tv)\/([\w-]+)/);
            if (match) {
                return { platform: 'instagram', embedUrl: `https://www.instagram.com/${match[1]}/${match[2]}/embed`, id: match[2] };
            }
        }

        // TikTok
        if (host === 'tiktok.com' || host.endsWith('.tiktok.com')) {
            const match = u.pathname.match(/\/video\/(\d+)/) || u.pathname.match(/@[\w.]+\/video\/(\d+)/);
            if (match) {
                return { platform: 'tiktok', embedUrl: `https://www.tiktok.com/embed/v2/${match[1]}`, id: match[1] };
            }
        }

        // Facebook video/post
        if (host === 'facebook.com' || host === 'fb.watch') {
            return { platform: 'facebook', embedUrl: `https://www.facebook.com/plugins/post.php?href=${encodeURIComponent(url)}`, id: url };
        }

        return null;
    } catch {
        return null;
    }
}

const platformLabels = {
    youtube: 'YouTube Video',
    twitter: 'Tweet',
    instagram: 'Instagram Post',
    tiktok: 'TikTok Video',
    facebook: 'Facebook Post',
};

const platformIcons = {
    youtube: '\u25B6',
    twitter: '\uD835\uDD4F',
    instagram: '\uD83D\uDCF7',
    tiktok: '\u266A',
    facebook: 'f',
};

/**
 * Custom TipTap node for social/video embeds.
 * Stores a data-embed div that the frontend renders with platform scripts.
 */
export const EmbedNode = Node.create({
    name: 'socialEmbed',
    group: 'block',
    atom: true,
    draggable: true,

    addAttributes() {
        return {
            src: { default: null },
            platform: { default: null },
            embedUrl: { default: null },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'div[data-embed]',
                getAttrs: (el) => ({
                    src: el.getAttribute('data-embed-src'),
                    platform: el.getAttribute('data-embed-platform'),
                    embedUrl: el.getAttribute('data-embed-url'),
                }),
            },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        return ['div', mergeAttributes({
            'data-embed': '',
            'data-embed-src': HTMLAttributes.src,
            'data-embed-platform': HTMLAttributes.platform,
            'data-embed-url': HTMLAttributes.embedUrl,
            'class': 'embed-block',
        })];
    },

    addNodeView() {
        return ({ node }) => {
            const dom = document.createElement('div');
            dom.classList.add('embed-block');
            dom.setAttribute('data-embed', '');
            dom.setAttribute('data-embed-src', node.attrs.src || '');
            dom.setAttribute('data-embed-platform', node.attrs.platform || '');
            dom.setAttribute('data-embed-url', node.attrs.embedUrl || '');

            const inner = document.createElement('div');
            inner.classList.add('embed-block-inner');

            const icon = document.createElement('span');
            icon.classList.add('embed-block-icon');
            icon.textContent = platformIcons[node.attrs.platform] || '\uD83D\uDD17';

            const label = document.createElement('span');
            label.classList.add('embed-block-label');
            label.textContent = platformLabels[node.attrs.platform] || 'Embed';

            const link = document.createElement('a');
            link.classList.add('embed-block-url');
            link.href = node.attrs.src || '#';
            link.target = '_blank';
            link.rel = 'noopener';
            link.textContent = node.attrs.src || '';

            inner.appendChild(icon);
            inner.appendChild(label);
            inner.appendChild(link);
            dom.appendChild(inner);

            return { dom };
        };
    },

    addCommands() {
        return {
            setEmbed: (attrs) => ({ commands }) => {
                return commands.insertContent({
                    type: 'socialEmbed',
                    attrs,
                });
            },
        };
    },
});
