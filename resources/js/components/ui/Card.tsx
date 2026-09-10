import React from 'react';

export interface CardProps extends React.HTMLAttributes<HTMLDivElement> {
    variant?: 'default' | 'almond' | 'elevated' | 'outline';
    isHoverable?: boolean;
    children: React.ReactNode;
}

export const Card: React.FC<CardProps> = ({
    variant = 'default',
    isHoverable = false,
    className = '',
    children,
    ...props
}) => {
    const variantClasses = {
        default: 'bg-paper border border-line shadow-sm',
        almond: 'bg-cream-2 border border-line-strong/70 shadow-sm',
        elevated: 'bg-paper border border-line/60 shadow-lg',
        outline: 'bg-paper pop-sm',
    };

    const hoverClasses = isHoverable
        ? variant === 'outline'
            ? 'pop-hover cursor-pointer'
            : 'transition-all duration-300 ease-out hover:-translate-y-1.5 hover:shadow-xl hover:border-saffron/50 cursor-pointer'
        : '';

    return (
        <div
            className={`rounded-3xl overflow-hidden ${variantClasses[variant]} ${hoverClasses} ${className}`}
            {...props}
        >
            {children}
        </div>
    );
};

export const CardHeader: React.FC<React.HTMLAttributes<HTMLDivElement>> = ({
    className = '',
    children,
    ...props
}) => (
    <div className={`px-6 pt-6 pb-4 sm:px-7 border-b border-line ${className}`} {...props}>
        {children}
    </div>
);

export const CardContent: React.FC<React.HTMLAttributes<HTMLDivElement>> = ({
    className = '',
    children,
    ...props
}) => (
    <div className={`p-6 sm:p-7 ${className}`} {...props}>
        {children}
    </div>
);

export const CardFooter: React.FC<React.HTMLAttributes<HTMLDivElement>> = ({
    className = '',
    children,
    ...props
}) => (
    <div className={`px-6 pb-6 pt-4 sm:px-7 border-t border-line bg-cream/70 ${className}`} {...props}>
        {children}
    </div>
);
