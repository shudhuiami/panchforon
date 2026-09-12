import React from 'react';
import { LucideIcon } from 'lucide-react';
import { ButtonLink } from '../ui/Button';

export interface StatusPanelProps {
    icon: LucideIcon;
    title: string;
    text: string;
    tone?: 'neutral' | 'danger';
    action?: { label: string; to: string };
    className?: string;
}

/** The one card for "not found", "not allowed" and similar full-page states. */
export const StatusPanel: React.FC<StatusPanelProps> = ({ icon: Icon, title, text, tone = 'neutral', action, className = '' }) => (
    <div className={`mx-auto w-full max-w-lg animate-slide-up px-4 py-16 sm:py-24 ${className}`}>
        <div className="rounded-3xl border border-line bg-surface p-8 text-center sm:p-10">
            <span
                className={`mx-auto mb-5 inline-flex size-16 items-center justify-center rounded-full ${
                    tone === 'danger' ? 'bg-hot-soft text-hot' : 'bg-surface-2 text-ink-2'
                }`}
            >
                <Icon className="size-7" aria-hidden="true" />
            </span>
            <h2 className="font-display text-3xl font-semibold tracking-tight text-ink text-balance">{title}</h2>
            <p className="mt-2 text-base text-ink-2">{text}</p>
            {action && (
                <ButtonLink to={action.to} className="mt-6">
                    {action.label}
                </ButtonLink>
            )}
        </div>
    </div>
);
