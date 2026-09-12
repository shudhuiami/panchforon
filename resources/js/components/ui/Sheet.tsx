import React, { useEffect } from 'react';

export interface SheetProps {
    isOpen: boolean;
    onClose: () => void;
    /** Accessible name for the dialog. */
    label: string;
    children: React.ReactNode;
}

/**
 * Bottom sheet on phones, centred card from md up. Closes on backdrop tap and
 * Escape; locks page scroll while open; leaves room for the home indicator.
 */
export const Sheet: React.FC<SheetProps> = ({ isOpen, onClose, label, children }) => {
    useEffect(() => {
        if (!isOpen) return;
        const onKey = (e: KeyboardEvent) => {
            if (e.key === 'Escape') onClose();
        };
        window.addEventListener('keydown', onKey);
        const previous = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        return () => {
            window.removeEventListener('keydown', onKey);
            document.body.style.overflow = previous;
        };
    }, [isOpen, onClose]);

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-end justify-center md:items-center md:p-6" role="dialog" aria-modal="true" aria-label={label}>
            <div className="fixed inset-0 bg-canvas/70 backdrop-blur-sm animate-fade-in" onClick={onClose} aria-hidden="true" />
            <div className="relative z-10 w-full max-w-md rounded-t-4xl border border-line bg-surface pb-[env(safe-area-inset-bottom)] shadow-xl animate-slide-up md:rounded-4xl">
                <div className="flex justify-center pt-3 md:hidden" aria-hidden="true">
                    <span className="h-1.5 w-12 rounded-full bg-line-strong" />
                </div>
                {children}
            </div>
        </div>
    );
};
