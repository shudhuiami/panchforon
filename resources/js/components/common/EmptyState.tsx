import React from 'react';
import { LucideIcon, Sparkles, UtensilsCrossed } from 'lucide-react';
import { Button } from '../ui/Button';

export interface EmptyStateProps {
    title: string;
    description: string;
    icon?: LucideIcon;
    actionLabel?: string;
    onAction?: () => void;
    className?: string;
}

export const EmptyState: React.FC<EmptyStateProps> = ({
    title,
    description,
    icon: Icon = UtensilsCrossed,
    actionLabel,
    onAction,
    className = '',
}) => (
    <div
        className={`relative flex flex-col items-center justify-center py-16 px-6 sm:py-20 text-center bg-paper rounded-4xl border-2 border-dashed border-line-strong my-8 overflow-hidden animate-fade-in ${className}`}
    >
        {/* Ambient color blobs */}
        <div className="absolute -top-16 -left-16 w-48 h-48 rounded-full bg-turmeric-soft blur-2xl opacity-80" aria-hidden="true" />
        <div className="absolute -bottom-20 -right-12 w-56 h-56 rounded-full bg-plum-soft blur-2xl opacity-80" aria-hidden="true" />

        {/* Rotated sticker icon block with a tiny sparkle */}
        <div className="relative mb-7">
            <div className="w-24 h-24 rounded-3xl bg-turmeric-soft border-2 border-ink shadow-pop-saffron sticker flex items-center justify-center text-turmeric animate-float">
                <Icon className="w-11 h-11 stroke-[2.25]" />
            </div>
            <span
                className="absolute -top-3 -right-3 w-9 h-9 rounded-full bg-hot text-on-primary flex items-center justify-center border-2 border-canvas sticker-r"
                aria-hidden="true"
            >
                <Sparkles className="w-4 h-4" />
            </span>
        </div>

        <h3 className="relative text-2xl sm:text-3xl font-display font-extrabold text-ink tracking-tight mb-3 text-balance">
            {title}
        </h3>
        <p className="relative text-base text-ink-2 max-w-md mb-8 leading-relaxed text-pretty">{description}</p>
        {actionLabel && onAction && (
            <Button onClick={onAction} variant="primary" size="lg" className="relative">
                {actionLabel}
            </Button>
        )}
    </div>
);
