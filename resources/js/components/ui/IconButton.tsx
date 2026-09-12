import React from 'react';

const variants = {
    ghost: 'text-ink hover:bg-surface-2',
    surface: 'bg-surface-2 text-ink border border-line hover:bg-surface-3',
    primary: 'bg-primary text-on-primary hover:bg-primary-hover',
};

const sizes = {
    sm: 'size-9',
    md: 'size-11',
};

export interface IconButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
    /** Required: an icon alone has no name. */
    label: string;
    variant?: keyof typeof variants;
    size?: keyof typeof sizes;
}

export const IconButton: React.FC<IconButtonProps> = ({
    label,
    variant = 'ghost',
    size = 'md',
    className = '',
    type = 'button',
    children,
    ...props
}) => (
    <button
        type={type}
        aria-label={label}
        title={label}
        className={`inline-flex shrink-0 cursor-pointer items-center justify-center rounded-full transition-colors duration-200 focus-visible:outline-3 focus-visible:outline-primary focus-visible:outline-offset-2 disabled:opacity-50 ${variants[variant]} ${sizes[size]} ${className}`}
        {...props}
    >
        {children}
    </button>
);
