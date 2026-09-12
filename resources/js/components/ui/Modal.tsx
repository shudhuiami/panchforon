import React, { useEffect } from 'react';
import { X } from 'lucide-react';
import { IconButton } from './IconButton';

export interface ModalProps {
    isOpen: boolean;
    onClose: () => void;
    title: string;
    children: React.ReactNode;
    maxWidth?: 'sm' | 'md' | 'lg' | 'xl';
}

const MAX_WIDTHS = { sm: 'max-w-md', md: 'max-w-lg', lg: 'max-w-2xl', xl: 'max-w-4xl' };

/** A bottom sheet on phones, a centred dialog from `sm` up. */
export const Modal: React.FC<ModalProps> = ({ isOpen, onClose, title, children, maxWidth = 'md' }) => {
    useEffect(() => {
        if (!isOpen) return;
        const onKey = (e: KeyboardEvent) => {
            if (e.key === 'Escape') onClose();
        };
        window.addEventListener('keydown', onKey);
        document.body.style.overflow = 'hidden';
        return () => {
            window.removeEventListener('keydown', onKey);
            document.body.style.overflow = '';
        };
    }, [isOpen, onClose]);

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-end justify-center overflow-y-auto sm:items-center sm:p-6" role="dialog" aria-modal="true" aria-labelledby="modal-title">
            <div className="fixed inset-0 animate-fade-in bg-canvas/70 backdrop-blur-md" onClick={onClose} aria-hidden="true" />
            <div
                className={`relative z-10 flex max-h-[92dvh] w-full animate-pop-in flex-col overflow-hidden rounded-t-3xl border border-line bg-surface shadow-xl sm:rounded-3xl ${MAX_WIDTHS[maxWidth]}`}
            >
                <div className="flex justify-center pt-3 sm:hidden" aria-hidden="true">
                    <span className="h-1.5 w-12 rounded-full bg-line-strong" />
                </div>
                <div className="flex shrink-0 items-start justify-between gap-4 px-6 pt-5 pb-4 sm:px-8 sm:pt-7">
                    <h3 id="modal-title" className="font-display text-2xl font-semibold tracking-tight text-ink text-balance sm:text-3xl">
                        {title}
                    </h3>
                    <IconButton label="Close" variant="surface" onClick={onClose}>
                        <X className="size-5" />
                    </IconButton>
                </div>
                <div className="overflow-y-auto px-6 pb-[calc(1.5rem+env(safe-area-inset-bottom))] sm:px-8 sm:pb-8">{children}</div>
            </div>
        </div>
    );
};
