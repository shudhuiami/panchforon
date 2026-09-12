import React, { useEffect, useRef, useState } from 'react';
import { Check, Link2, Share2 } from 'lucide-react';

interface ShareButtonProps {
    title: string;
    text?: string;
    className?: string;
}

const SHARE_TARGETS = [
    { label: 'WhatsApp', href: (url: string, title: string) => `https://wa.me/?text=${encodeURIComponent(`${title} ${url}`)}` },
    { label: 'X', href: (url: string, title: string) => `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}` },
    { label: 'Facebook', href: (url: string) => `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}` },
];

/**
 * Share this page. Phones get the system sheet; everywhere else opens a small
 * menu with a copy-link option and the usual targets.
 */
export const ShareButton: React.FC<ShareButtonProps> = ({ title, text, className = '' }) => {
    const [open, setOpen] = useState(false);
    const [copied, setCopied] = useState(false);
    const rootRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!open) return;
        const onPointer = (e: PointerEvent) => {
            if (!rootRef.current?.contains(e.target as Node)) setOpen(false);
        };
        const onKey = (e: KeyboardEvent) => {
            if (e.key === 'Escape') setOpen(false);
        };
        document.addEventListener('pointerdown', onPointer);
        window.addEventListener('keydown', onKey);
        return () => {
            document.removeEventListener('pointerdown', onPointer);
            window.removeEventListener('keydown', onKey);
        };
    }, [open]);

    const url = typeof window === 'undefined' ? '' : window.location.href;

    const handleClick = async () => {
        if (typeof navigator !== 'undefined' && typeof navigator.share === 'function') {
            try {
                await navigator.share({ title, text, url });
                return;
            } catch {
                // The sheet was dismissed, or sharing is not permitted here.
            }
        }
        setOpen((v) => !v);
    };

    const copyLink = async () => {
        try {
            await navigator.clipboard.writeText(url);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch {
            setCopied(false);
        }
    };

    return (
        <div ref={rootRef} className={`relative ${className}`}>
            <button
                type="button"
                onClick={handleClick}
                aria-haspopup="menu"
                aria-expanded={open}
                className="inline-flex h-10 w-full cursor-pointer items-center justify-center gap-2 rounded-full border border-line bg-surface px-4 text-sm font-medium text-ink-2 transition-colors hover:border-line-strong hover:text-ink focus-visible:outline-3 focus-visible:outline-primary focus-visible:outline-offset-2"
            >
                <Share2 className="size-4" aria-hidden="true" />
                Share
            </button>

            {open && (
                <div role="menu" className="absolute right-0 bottom-full z-50 mb-2 w-56 animate-scale-in rounded-2xl border border-line bg-surface p-2 shadow-xl">
                    <button
                        type="button"
                        role="menuitem"
                        onClick={copyLink}
                        className="flex w-full cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-ink transition-colors hover:bg-surface-2"
                    >
                        {copied ? <Check className="size-4 text-mint" aria-hidden="true" /> : <Link2 className="size-4" aria-hidden="true" />}
                        {copied ? 'Link copied' : 'Copy link'}
                    </button>
                    {SHARE_TARGETS.map((target) => (
                        <a
                            key={target.label}
                            role="menuitem"
                            href={target.href(url, title)}
                            target="_blank"
                            rel="noreferrer noopener"
                            onClick={() => setOpen(false)}
                            className="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-ink transition-colors hover:bg-surface-2"
                        >
                            <Share2 className="size-4 text-ink-3" aria-hidden="true" />
                            {target.label}
                        </a>
                    ))}
                </div>
            )}
        </div>
    );
};
