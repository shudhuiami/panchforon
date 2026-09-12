import React from 'react';

export type BadgeVariant =
    | 'default'
    | 'outline'
    | 'primary'
    | 'cuisine'
    | 'category'
    | 'success'
    | 'danger'
    | 'bayesian';

export interface BadgeProps {
    children: React.ReactNode;
    variant?: BadgeVariant;
    size?: 'sm' | 'md';
    className?: string;
}

const sizes = {
    sm: 'px-2.5 py-1 text-[0.7rem] gap-1',
    md: 'px-3.5 py-1.5 text-xs gap-1.5',
};

/**
 * Tinted pill + bright text of the same hue. Every pair clears WCAG AA on the
 * dark surfaces; solid fills carry the dark ink colour for the same reason.
 */
const variants: Record<BadgeVariant, string> = {
    default: 'bg-surface-2 text-ink-2 border-line font-semibold',
    outline: 'bg-transparent text-ink border-line-strong font-semibold',
    primary: 'bg-primary text-on-primary border-transparent font-bold uppercase tracking-wider',
    cuisine: 'bg-plum-soft text-plum border-plum/30 font-semibold tracking-wide',
    category: 'bg-turmeric-soft text-turmeric border-turmeric/30 font-bold uppercase tracking-wider',
    success: 'bg-success-soft text-success border-success/30 font-semibold',
    danger: 'bg-hot-soft text-hot border-hot/30 font-semibold',
    bayesian: 'bg-mint-soft text-mint border-mint/30 font-display font-semibold tabular-nums',
};

export const Badge: React.FC<BadgeProps> = ({ children, variant = 'default', size = 'sm', className = '' }) => (
    <span
        className={`inline-flex items-center rounded-full border leading-none whitespace-nowrap select-none ${sizes[size]} ${variants[variant]} ${className}`}
    >
        {children}
    </span>
);

/** A small numeric marker for nav items; hidden at zero. */
export const CountBadge: React.FC<{ count: number; className?: string }> = ({ count, className = '' }) =>
    count > 0 ? (
        <span
            className={`inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-primary px-1.5 text-[11px] font-bold leading-none text-on-primary ${className}`}
            aria-label={`${count} in your plan`}
        >
            {count > 99 ? '99+' : count}
        </span>
    ) : null;
