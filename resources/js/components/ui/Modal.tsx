import React, { useEffect } from 'react';
import { X } from 'lucide-react';

export interface ModalProps {
    isOpen: boolean;
    onClose: () => void;
    title: string;
    children: React.ReactNode;
    maxWidth?: 'sm' | 'md' | 'lg' | 'xl';
}

export const Modal: React.FC<ModalProps> = ({
    isOpen,
    onClose,
    title,
    children,
    maxWidth = 'md',
}) => {
    useEffect(() => {
        const handleKeyDown = (e: KeyboardEvent) => {
            if (e.key === 'Escape') onClose();
        };
        if (isOpen) {
            window.addEventListener('keydown', handleKeyDown);
            document.body.style.overflow = 'hidden';
        }
        return () => {
            window.removeEventListener('keydown', handleKeyDown);
            document.body.style.overflow = 'unset';
        };
    }, [isOpen, onClose]);

    if (!isOpen) return null;

    const maxWClasses = {
        sm: 'max-w-md',
        md: 'max-w-lg',
        lg: 'max-w-2xl',
        xl: 'max-w-4xl',
    };

    return (
        <div
            className="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-6 overflow-y-auto"
            role="dialog"
            aria-modal="true"
            aria-labelledby="modal-title"
        >
            {/* Ink glass backdrop */}
            <div
                className="fixed inset-0 bg-canvas/70 backdrop-blur-md animate-fade-in"
                onClick={onClose}
                aria-hidden="true"
            />

            {/* Dialog panel: bottom sheet on mobile, floating card on desktop */}
            <div
                className={`relative bg-paper w-full rounded-t-4xl sm:rounded-4xl shadow-xl border border-line z-10 animate-pop-in overflow-hidden max-h-[92dvh] flex flex-col ${maxWClasses[maxWidth]}`}
            >
                {/* Spice ribbon along the top */}
                <div className="h-2 w-full bg-spice-gradient shrink-0" aria-hidden="true" />

                {/* Mobile drag handle hint */}
                <div className="sm:hidden flex justify-center pt-3" aria-hidden="true">
                    <span className="w-12 h-1.5 rounded-full bg-line-strong" />
                </div>

                <div className="flex items-start justify-between gap-4 px-6 pt-5 pb-4 sm:px-8 sm:pt-7 shrink-0">
                    <h3 id="modal-title" className="text-2xl sm:text-3xl font-display font-extrabold text-ink tracking-tight text-balance">
                        {title}
                    </h3>
                    <button
                        type="button"
                        onClick={onClose}
                        className="shrink-0 w-10 h-10 rounded-full bg-surface-3 text-ink flex items-center justify-center hover:bg-hot hover:text-on-primary hover:rotate-90 transition-all duration-300 cursor-pointer focus-visible:outline-3 focus-visible:outline-primary focus-visible:outline-offset-2"
                        aria-label="Close modal"
                    >
                        <X className="w-5 h-5 stroke-[2.5]" />
                    </button>
                </div>

                <div className="px-6 pb-6 sm:px-8 sm:pb-8 overflow-y-auto">{children}</div>
            </div>
        </div>
    );
};
