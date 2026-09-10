import React from 'react';

export interface BadgeProps {
    children: React.ReactNode;
    variant?:
        | 'category'
        | 'sale'
        | 'new'
        | 'out-of-stock'
        | 'in-stock'
        | 'success'
        | 'bayesian'
        | 'cuisine'
        | 'default'
        | 'outline'
        // Legacy palette compatibility
        | 'plum'
        | 'red'
        | 'aureolin'
        | 'yellowgreen'
        | 'kelly';
    size?: 'sm' | 'md';
    className?: string;
}

export const Badge: React.FC<BadgeProps> = ({
    children,
    variant = 'default',
    size = 'sm',
    className = '',
}) => {
    const sizeClasses = {
        sm: 'px-2.5 py-1 text-[0.7rem] gap-1',
        md: 'px-3.5 py-1.5 text-xs gap-1.5',
    };

    // Sticker-style pills: each variant owns a distinct spice.
    // Soft tint + deep text keeps WCAG AA; solid fills use ink or white text.
    const variants = {
        // Category: turmeric
        category:
            'bg-turmeric-soft text-turmeric-deep border-turmeric/40 font-extrabold uppercase tracking-wider',
        // Sale: solid saffron sticker with ink text
        sale:
            'bg-saffron text-ink border-ink font-black uppercase tracking-wider shadow-pop-sm',
        // New: solid chili sticker with white text
        new:
            'bg-chili text-white border-ink font-black uppercase tracking-wider shadow-pop-sm',
        // Out of stock: faint ink tint
        'out-of-stock':
            'bg-ink-3/15 text-ink-2 border-ink-3/30 font-bold',
        // In stock / success: mint
        'in-stock':
            'bg-mint-soft text-mint-deep border-mint/40 font-bold',
        success:
            'bg-mint-soft text-mint-deep border-mint/40 font-bold',
        // Bayesian score: mint with display numerals
        bayesian:
            'bg-mint-soft text-mint-deep border-mint/50 font-display font-extrabold tabular-nums',
        // Cuisine: plum
        cuisine:
            'bg-plum-soft text-plum-deep border-plum/40 font-bold tracking-wide',
        default:
            'bg-cream-2 text-ink-2 border-line-strong font-semibold',
        outline:
            'bg-paper text-ink border-2 border-ink font-bold',

        // Backwards compatibility mappings
        plum:
            'bg-plum-soft text-plum-deep border-plum/40 font-bold',
        red:
            'bg-chili-soft text-chili-deep border-chili/40 font-bold',
        aureolin:
            'bg-turmeric-soft text-turmeric-deep border-turmeric/40 font-bold',
        yellowgreen:
            'bg-mint-soft text-mint-deep border-mint/40 font-bold',
        kelly:
            'bg-mint-soft text-mint-deep border-mint/40 font-bold',
    };

    return (
        <span
            className={`inline-flex items-center rounded-full border leading-none whitespace-nowrap transition-transform duration-150 select-none ${sizeClasses[size]} ${variants[variant]} ${className}`}
        >
            {children}
        </span>
    );
};
