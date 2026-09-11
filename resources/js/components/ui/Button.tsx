import React from 'react';
import { Link, type LinkProps } from 'react-router-dom';
import { Loader2 } from 'lucide-react';

export type ButtonVariant = 'primary' | 'secondary' | 'ghost' | 'outline' | 'danger';
export type ButtonSize = 'sm' | 'md' | 'lg';

export interface ButtonStyleProps {
    variant?: ButtonVariant;
    size?: ButtonSize;
    className?: string;
}

const base =
    'inline-flex items-center justify-center gap-2 rounded-full font-semibold whitespace-nowrap select-none ' +
    'transition-[background-color,color,border-color,box-shadow,transform] duration-200 ease-out-expo cursor-pointer ' +
    'active:scale-[0.98] focus-visible:outline-3 focus-visible:outline-primary focus-visible:outline-offset-3 ' +
    'disabled:opacity-50 disabled:pointer-events-none aria-disabled:opacity-50 aria-disabled:pointer-events-none';

const sizes: Record<ButtonSize, string> = {
    sm: 'h-9 px-4 text-sm',
    md: 'h-11 px-5 text-sm',
    lg: 'h-13 px-7 text-base',
};

/**
 * Fills carry dark ink text: every fill colour clears 4.5:1 against it, which
 * white does not manage on the lighter accents.
 */
const variants: Record<ButtonVariant, string> = {
    primary: 'bg-primary text-on-primary hover:bg-primary-hover shadow-glow-primary',
    secondary: 'bg-surface-2 text-ink border border-line hover:bg-surface-3 hover:border-line-strong',
    ghost: 'bg-transparent text-ink hover:bg-surface-2',
    outline: 'bg-transparent text-ink border-2 border-line-strong hover:border-ink hover:bg-surface-2',
    danger: 'bg-hot text-on-primary hover:bg-hot-deep hover:text-white',
};

/**
 * The one source of button geometry. Use it directly when something that is
 * not a <button> or a router <Link> needs to look like one (an <a href> to
 * an external page, a form <input type="submit">).
 */
export function buttonClassName({ variant = 'primary', size = 'md', className = '' }: ButtonStyleProps = {}): string {
    return `${base} ${sizes[size]} ${variants[variant]} ${className}`.trim();
}

export interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement>, ButtonStyleProps {
    isLoading?: boolean;
}

export const Button: React.FC<ButtonProps> = ({
    children,
    variant,
    size,
    className,
    isLoading = false,
    disabled,
    type = 'button',
    ...props
}) => (
    <button
        type={type}
        className={buttonClassName({ variant, size, className })}
        disabled={disabled || isLoading}
        aria-busy={isLoading || undefined}
        {...props}
    >
        {isLoading && <Loader2 className="size-4 animate-spin" aria-hidden="true" />}
        {children}
    </button>
);

export interface ButtonLinkProps extends LinkProps, ButtonStyleProps {}

/**
 * A router link styled as a button. Replaces the <Link><Button/></Link>
 * nesting that put a button inside an anchor.
 */
export const ButtonLink: React.FC<ButtonLinkProps> = ({ children, variant, size, className, ...props }) => (
    <Link className={buttonClassName({ variant, size, className })} {...props}>
        {children}
    </Link>
);
