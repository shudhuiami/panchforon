import React from 'react';

export interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: 'primary' | 'secondary' | 'ghost' | 'destructive' | 'outline' | 'dark' | 'plum' | 'red' | 'kelly' | 'aureolin' | 'danger';
    size?: 'sm' | 'md' | 'lg';
    isLoading?: boolean;
    isError?: boolean;
}

export const Button: React.FC<ButtonProps> = ({
    children,
    variant = 'primary',
    size = 'md',
    isLoading = false,
    isError = false,
    className = '',
    disabled,
    ...props
}) => {
    // Sleek modern architectural radius (rounded-xl) replacing excessive capsule pills
    const baseClasses =
        'inline-flex items-center justify-center font-bold rounded-xl transition-all duration-200 cursor-pointer select-none active:scale-[0.97] focus-visible:outline-2 focus-visible:outline-brand-primary focus-visible:outline-offset-2 disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100';

    const sizeClasses = {
        sm: 'px-3.5 py-1.5 text-xs gap-1.5 font-bold',
        md: 'px-5 py-2.5 text-sm gap-2 font-extrabold',
        lg: 'px-7 py-3.5 text-base gap-2.5 font-black tracking-wide shadow-sm',
    };

    // Ultra-high-contrast, punchy button variants
    const variantClasses = {
        // High-Contrast Option A: Deep #0F0A05 black ink on vibrant Pumpkin #FC7100
        primary:
            'bg-brand-primary text-neutral-950 hover:bg-brand-accent shadow-md shadow-brand-primary/25 hover:shadow-lg hover:shadow-brand-primary/35 font-black tracking-wide',
        // High-Contrast Midnight: Pure white on rich deep obsidian
        dark:
            'bg-neutral-950 text-white hover:bg-neutral-800 shadow-md border border-neutral-800 font-bold',
        // Warm Almond neutral secondary with high-contrast text
        secondary:
            'bg-brand-surface text-neutral-950 hover:bg-brand-surface-light border border-neutral-300 font-bold shadow-2xs',
        ghost:
            'bg-transparent text-neutral-950 hover:bg-neutral-200/60 font-bold',
        // High-Contrast Destructive: Pure white on deep red
        destructive:
            'bg-brand-hot-dark text-white hover:bg-brand-hot shadow-md font-bold',
        danger:
            'bg-brand-hot-dark text-white hover:bg-brand-hot shadow-md font-bold',
        // High-Contrast Outline: Bold 2px border with sharp dark ink, inverting on hover
        outline:
            'border-2 border-neutral-950 bg-white text-neutral-950 hover:bg-neutral-950 hover:text-white font-black transition-colors',
        // Backwards compatibility mappings
        plum:
            'bg-brand-primary text-neutral-950 hover:bg-brand-accent shadow-md shadow-brand-primary/25 font-black',
        red:
            'bg-brand-hot-dark text-white hover:bg-brand-hot shadow-md font-bold',
        kelly:
            'bg-brand-success-dark text-white hover:bg-brand-success shadow-md font-bold',
        aureolin:
            'bg-brand-surface text-neutral-950 hover:bg-brand-surface-light border border-neutral-300 font-bold',
    };

    const errorClasses = isError
        ? 'ring-2 ring-brand-hot-dark ring-offset-2 border-brand-hot-dark'
        : '';

    return (
        <button
            className={`${baseClasses} ${sizeClasses[size]} ${variantClasses[variant]} ${errorClasses} ${className}`}
            disabled={disabled || isLoading}
            aria-busy={isLoading}
            {...props}
        >
            {isLoading && (
                <svg
                    className="animate-spin -ml-1 mr-2 h-4 w-4 text-current"
                    fill="none"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                    <path
                        className="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    />
                </svg>
            )}
            {children}
        </button>
    );
};