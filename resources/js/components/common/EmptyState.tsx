import React from 'react';
import { LucideIcon, UtensilsCrossed } from 'lucide-react';
import { Button } from '../ui/Button';

export interface EmptyStateProps {
    title: string;
    description: string;
    icon?: LucideIcon;
    actionLabel?: string;
    onAction?: () => void;
    className?: string;
}

export const EmptyState: React.FC<EmptyStateProps> = ({ title, description, icon: Icon = UtensilsCrossed, actionLabel, onAction, className = '' }) => (
    <div
        className={`flex animate-fade-in flex-col items-center justify-center rounded-3xl border border-dashed border-line-strong bg-surface px-6 py-14 text-center sm:py-20 ${className}`}
    >
        <span className="mb-5 inline-flex size-16 items-center justify-center rounded-full bg-surface-2 text-ink-2">
            <Icon className="size-7" aria-hidden="true" />
        </span>
        <h3 className="font-display text-2xl font-semibold text-ink text-balance sm:text-3xl">{title}</h3>
        <p className="mt-2 max-w-md text-base text-ink-2">{description}</p>
        {actionLabel && onAction && (
            <Button onClick={onAction} className="mt-6">
                {actionLabel}
            </Button>
        )}
    </div>
);
