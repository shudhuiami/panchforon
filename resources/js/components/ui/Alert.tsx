import React from 'react';
import { AlertCircle, CheckCircle2, Info, TriangleAlert, X } from 'lucide-react';
import { IconButton } from './IconButton';

export interface AlertProps {
    variant?: 'info' | 'success' | 'warning' | 'error';
    title?: string;
    children: React.ReactNode;
    onClose?: () => void;
    className?: string;
}

const VARIANTS = {
    info: { bar: 'bg-plum', icon: 'text-plum', node: <Info className="size-5" /> },
    success: { bar: 'bg-mint', icon: 'text-mint', node: <CheckCircle2 className="size-5" /> },
    warning: { bar: 'bg-turmeric', icon: 'text-turmeric', node: <TriangleAlert className="size-5" /> },
    error: { bar: 'bg-hot', icon: 'text-hot', node: <AlertCircle className="size-5" /> },
};

export const Alert: React.FC<AlertProps> = ({ variant = 'info', title, children, onClose, className = '' }) => {
    const { bar, icon, node } = VARIANTS[variant];

    return (
        <div role="alert" className={`relative flex animate-slide-up items-start gap-3.5 overflow-hidden rounded-2xl border border-line bg-surface py-4 pr-4 pl-6 ${className}`}>
            <span className={`absolute inset-y-0 left-0 w-1.5 ${bar}`} aria-hidden="true" />
            <span className={`mt-0.5 shrink-0 ${icon}`} aria-hidden="true">
                {node}
            </span>
            <div className="min-w-0 flex-1 text-sm">
                {title && <h5 className="mb-1 font-display text-base leading-tight font-semibold text-ink">{title}</h5>}
                <div className="leading-relaxed text-ink-2">{children}</div>
            </div>
            {onClose && (
                <IconButton label="Dismiss" variant="ghost" size="sm" onClick={onClose}>
                    <X className="size-4" />
                </IconButton>
            )}
        </div>
    );
};
