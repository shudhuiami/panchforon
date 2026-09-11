import React from 'react';
import { AlertCircle, CheckCircle2, Info, TriangleAlert, X } from 'lucide-react';

export interface AlertProps {
    variant?: 'info' | 'success' | 'warning' | 'error';
    title?: string;
    children: React.ReactNode;
    onClose?: () => void;
    className?: string;
}

export const Alert: React.FC<AlertProps> = ({
    variant = 'info',
    title,
    children,
    onClose,
    className = '',
}) => {
    // Each variant: soft tinted surface, a colored left accent bar, and an icon chip.
    const config = {
        info: {
            container: 'bg-plum-soft/70 border-plum/25',
            bar: 'bg-plum',
            chip: 'bg-plum text-on-primary',
            title: 'text-plum',
            icon: <Info className="w-5 h-5" />,
        },
        success: {
            container: 'bg-mint-soft/80 border-mint/30',
            bar: 'bg-mint',
            chip: 'bg-mint text-ink',
            title: 'text-mint',
            icon: <CheckCircle2 className="w-5 h-5" />,
        },
        warning: {
            container: 'bg-turmeric-soft/80 border-turmeric/40',
            bar: 'bg-turmeric',
            chip: 'bg-turmeric text-ink',
            title: 'text-turmeric',
            icon: <TriangleAlert className="w-5 h-5" />,
        },
        error: {
            container: 'bg-chili-soft/80 border-chili/30',
            bar: 'bg-chili',
            chip: 'bg-hot text-on-primary',
            title: 'text-chili',
            icon: <AlertCircle className="w-5 h-5" />,
        },
    };

    const c = config[variant];

    return (
        <div
            role="alert"
            className={`relative flex items-start gap-3.5 pl-6 pr-4 py-4 rounded-2xl border overflow-hidden animate-slide-up ${c.container} ${className}`}
        >
            <span className={`absolute left-0 top-0 bottom-0 w-2 ${c.bar}`} aria-hidden="true" />
            <span
                className={`shrink-0 w-9 h-9 rounded-xl flex items-center justify-center ${c.chip}`}
                aria-hidden="true"
            >
                {c.icon}
            </span>
            <div className="flex-1 min-w-0 text-sm pt-0.5">
                {title && (
                    <h5 className={`font-display font-extrabold text-base leading-tight mb-1 ${c.title}`}>{title}</h5>
                )}
                <div className="text-ink-2 leading-relaxed">{children}</div>
            </div>
            {onClose && (
                <button
                    type="button"
                    onClick={onClose}
                    className="shrink-0 w-8 h-8 rounded-full text-ink-2 hover:text-ink hover:bg-ink/10 flex items-center justify-center transition-colors cursor-pointer focus-visible:outline-3 focus-visible:outline-saffron"
                    aria-label="Close alert"
                >
                    <X className="w-4 h-4 stroke-[2.5]" />
                </button>
            )}
        </div>
    );
};
