/**
 * Alpine component powering the creator QR card modal.
 * Fetches creator data, renders a styled QR via qr-code-styling, and composes
 * a 1080x1350 branded share card on a canvas for PNG/JPEG download.
 */

import QRCodeStyling from 'qr-code-styling';

const CARD_W = 1080;
const CARD_H = 1350;
const LOGO_URL = '/img/topping-africa-logomark.svg';
const QR_RENDER_SIZE = 680;

const loadImage = (src) => new Promise((resolve, reject) => {
    if (!src) return resolve(null);
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = () => resolve(img);
    img.onerror = () => resolve(null);
    img.src = src;
});

async function renderStyledQr(data) {
    const qr = new QRCodeStyling({
        width: QR_RENDER_SIZE,
        height: QR_RENDER_SIZE,
        type: 'canvas',
        data,
        margin: 0,
        qrOptions: { errorCorrectionLevel: 'M' },
        dotsOptions: {
            type: 'square',
            gradient: {
                type: 'linear',
                rotation: Math.PI / 4,
                colorStops: [
                    { offset: 0, color: '#d60842' },
                    { offset: 1, color: '#2d3d8b' },
                ],
            },
        },
        backgroundOptions: { color: '#ffffff' },
        cornersSquareOptions: { type: 'square', color: '#d60842' },
        cornersDotOptions: { type: 'square', color: '#2d3d8b' },
    });

    const blob = await qr.getRawData('png');
    const url = URL.createObjectURL(blob);
    try {
        return await loadImage(url);
    } finally {
        // Revoke after the image is decoded into the canvas
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    }
}

function drawRoundedRect(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
}

function wrapText(ctx, text, maxWidth) {
    const words = text.split(/\s+/);
    const lines = [];
    let current = '';
    for (const word of words) {
        const candidate = current ? `${current} ${word}` : word;
        if (ctx.measureText(candidate).width > maxWidth && current) {
            lines.push(current);
            current = word;
        } else {
            current = candidate;
        }
    }
    if (current) lines.push(current);
    return lines;
}

export function creatorQrCard({ slug, endpoint }) {
    return {
        loading: false,
        error: null,
        data: null,
        rendered: false,

        async load() {
            if (this.rendered && !this.error) return;
            this.loading = true;
            this.error = null;

            try {
                const res = await fetch(endpoint, { headers: { Accept: 'application/json' } });
                if (!res.ok) throw new Error('Unable to generate card');
                this.data = await res.json();
                await this.$nextTick();
                await this.render();
                this.rendered = true;
            } catch (e) {
                this.error = e.message || 'Something went wrong.';
            } finally {
                this.loading = false;
            }
        },

        async render() {
            const canvas = this.$refs.cardCanvas;
            if (!canvas || !this.data) return;

            canvas.width = CARD_W;
            canvas.height = CARD_H;
            const ctx = canvas.getContext('2d');

            // Background gradient (brand)
            const bg = ctx.createLinearGradient(0, 0, CARD_W, CARD_H);
            bg.addColorStop(0, '#d60842');
            bg.addColorStop(1, '#2d3d8b');
            ctx.fillStyle = bg;
            ctx.fillRect(0, 0, CARD_W, CARD_H);

            // Soft white inner card
            const pad = 50;
            drawRoundedRect(ctx, pad, pad, CARD_W - pad * 2, CARD_H - pad * 2, 40);
            ctx.fillStyle = '#ffffff';
            ctx.fill();

            // Header strip
            const headerH = 110;
            drawRoundedRect(ctx, pad, pad, CARD_W - pad * 2, headerH, 40);
            const headerGrad = ctx.createLinearGradient(pad, pad, CARD_W - pad, pad);
            headerGrad.addColorStop(0, '#d60842');
            headerGrad.addColorStop(1, '#2d3d8b');
            ctx.fillStyle = headerGrad;
            ctx.fill();
            // Square off the bottom of the header
            ctx.fillRect(pad, pad + headerH - 40, CARD_W - pad * 2, 40);

            // Logo + wordmark
            const [logo, avatar] = await Promise.all([
                loadImage(LOGO_URL),
                loadImage(this.data.avatar_url),
            ]);

            if (logo) {
                const logoSize = 60;
                ctx.drawImage(logo, pad + 40, pad + (headerH - logoSize) / 2, logoSize, logoSize);
            }
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 36px Figtree, system-ui, sans-serif';
            ctx.textBaseline = 'middle';
            ctx.fillText('Topping Africa', pad + 40 + (logo ? 80 : 0), pad + headerH / 2);

            ctx.font = '500 20px Figtree, system-ui, sans-serif';
            ctx.textAlign = 'right';
            ctx.fillText('Discover African creators', CARD_W - pad - 40, pad + headerH / 2);
            ctx.textAlign = 'left';

            // Avatar (circular)
            const avatarSize = 300;
            const avatarX = CARD_W / 2 - avatarSize / 2;
            const avatarY = pad + headerH + 50;
            ctx.save();
            ctx.beginPath();
            ctx.arc(CARD_W / 2, avatarY + avatarSize / 2, avatarSize / 2, 0, Math.PI * 2);
            ctx.closePath();
            ctx.clip();
            if (avatar) {
                // object-fit: cover — crop the source to a square from its center
                const srcSize = Math.min(avatar.width, avatar.height);
                const srcX = (avatar.width - srcSize) / 2;
                const srcY = (avatar.height - srcSize) / 2;
                ctx.drawImage(avatar, srcX, srcY, srcSize, srcSize, avatarX, avatarY, avatarSize, avatarSize);
            } else {
                ctx.fillStyle = this.data.avatar_color || '#d60842';
                ctx.fillRect(avatarX, avatarY, avatarSize, avatarSize);
                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 90px Figtree, system-ui, sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(this.data.initials || '?', CARD_W / 2, avatarY + avatarSize / 2);
            }
            ctx.restore();

            // Ring around avatar (with soft brand glow)
            ctx.save();
            ctx.shadowColor = 'rgba(214, 8, 66, 0.35)';
            ctx.shadowBlur = 30;
            ctx.beginPath();
            ctx.arc(CARD_W / 2, avatarY + avatarSize / 2, avatarSize / 2 + 8, 0, Math.PI * 2);
            ctx.strokeStyle = '#d60842';
            ctx.lineWidth = 8;
            ctx.stroke();
            ctx.restore();

            // Name
            ctx.fillStyle = '#1f1f1f';
            ctx.font = 'bold 58px Figtree, system-ui, sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'alphabetic';
            const nameY = avatarY + avatarSize + 70;
            const nameLines = wrapText(ctx, this.data.name, CARD_W - pad * 4);
            nameLines.slice(0, 2).forEach((line, i) => {
                ctx.fillText(line, CARD_W / 2, nameY + i * 66);
            });

            // Category + country chip
            const chipY = nameY + nameLines.length * 66 + 14;
            const chipText = `${this.data.category ? this.data.category.charAt(0).toUpperCase() + this.data.category.slice(1) : ''} · ${this.data.country || ''}`.replace(/^ · | · $/, '');
            ctx.font = '500 28px Figtree, system-ui, sans-serif';
            ctx.fillStyle = '#6a686c';
            ctx.fillText(chipText, CARD_W / 2, chipY);

            // QR encodes the profile URL — sparse + reliably scannable.
            // Scanning opens the creator's page where they can follow or copy
            // contact info; encoding the full vCard makes the QR too dense
            // to scan at typical display sizes.
            const qr = await renderStyledQr(this.data.profile_url);
            const qrSize = 500;
            const qrX = CARD_W / 2 - qrSize / 2;
            const qrY = chipY + 40;
            // White padded frame behind QR
            drawRoundedRect(ctx, qrX - 20, qrY - 20, qrSize + 40, qrSize + 40, 24);
            ctx.fillStyle = '#ffffff';
            ctx.fill();
            ctx.strokeStyle = '#f0f0f0';
            ctx.lineWidth = 2;
            ctx.stroke();
            if (qr) ctx.drawImage(qr, qrX, qrY, qrSize, qrSize);

            // CTA below QR
            ctx.fillStyle = '#1f1f1f';
            ctx.font = 'bold 30px Figtree, system-ui, sans-serif';
            ctx.fillText('Scan to visit profile', CARD_W / 2, qrY + qrSize + 70);

            // URL footer
            ctx.fillStyle = '#d60842';
            ctx.font = '600 22px Figtree, system-ui, sans-serif';
            const urlText = this.data.profile_url.replace(/^https?:\/\//, '');
            ctx.fillText(urlText, CARD_W / 2, qrY + qrSize + 108);
        },

        download(format) {
            const canvas = this.$refs.cardCanvas;
            if (!canvas || !this.data) return;
            const mime = format === 'jpeg' ? 'image/jpeg' : 'image/png';
            const quality = format === 'jpeg' ? 0.92 : undefined;
            const dataUrl = canvas.toDataURL(mime, quality);
            const a = document.createElement('a');
            a.href = dataUrl;
            a.download = `${slug}-topping-africa.${format === 'jpeg' ? 'jpg' : 'png'}`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        },
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('creatorQrCard', creatorQrCard);
});
