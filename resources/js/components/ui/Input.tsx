import React, { forwardRef } from 'react';
import { CircleAlert } from 'lucide-react';

export interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
    label?: string;
    helperText?: string;
    errorMessage?: string;
    hasError?: boolean;
    leftIcon?: React.ReactNode;
    rightIcon?: React.ReactNode;
}

export const Input = forwardRef<HTMLInputElement, InputProps>(({
    label,
    helperText,
    errorMessage,
    hasError = false,
    leftIcon,
    rightIcon,
    className = '',
    id,
    disabled,
    ...props
}, ref) => {
    const inputId = id || (label ? `input-${label.toLowerCase().replace(/\s+/g, '-')}` : undefined);
    const isInvalid = hasError || !!errorMessage;

    return (
        <div className="w-full group/field">
            {label && (
                <label
                    htmlFor={inputId}
                    className="block text-xs font-bold uppercase tracking-wide text-ink-2 mb-2 transition-colors group-focus-within/field:text-saffron"
                >
                    {label}
                </label>
            )}

            <div className="relative flex items-center">
                {leftIcon && (
                    <div
                        className={`absolute left-4 pointer-events-none flex items-center transition-colors ${
                            isInvalid ? 'text-chili' : 'text-ink-3 group-focus-within/field:text-saffron'
                        }`}
                    >
                        {leftIcon}
                    </div>
                )}

                <input
                    ref={ref}
                    id={inputId}
                    disabled={disabled}
                    aria-invalid={isInvalid}
                    aria-describedby={
                        errorMessage ? `${inputId}-error` : helperText ? `${inputId}-helper` : undefined
                    }
                    className={`w-full min-h-12 bg-paper text-ink font-medium placeholder:text-ink-3 placeholder:font-normal border-2 rounded-2xl px-4 py-3 text-base transition-all duration-200 outline-none
                        ${leftIcon ? 'pl-11' : ''}
                        ${rightIcon ? 'pr-11' : ''}
                        ${
                            isInvalid
                                ? 'border-chili bg-chili-soft/30 focus:border-chili focus:ring-4 focus:ring-chili/20'
                                : 'border-line hover:border-line-strong focus:border-saffron focus:ring-4 focus:ring-saffron/20'
                        }
                        disabled:bg-cream-2 disabled:text-ink-3 disabled:border-line disabled:cursor-not-allowed
                        focus-visible:outline-none
                        ${className}`}
                    {...props}
                />

                {rightIcon && (
                    <div
                        className={`absolute right-4 flex items-center transition-colors ${
                            isInvalid ? 'text-chili' : 'text-ink-3'
                        }`}
                    >
                        {rightIcon}
                    </div>
                )}
            </div>

            {errorMessage ? (
                <p
                    id={`${inputId}-error`}
                    className="mt-2 text-sm text-chili font-semibold flex items-center gap-1.5 animate-fade-in"
                >
                    <CircleAlert className="w-4 h-4 shrink-0" aria-hidden="true" />
                    {errorMessage}
                </p>
            ) : helperText ? (
                <p id={`${inputId}-helper`} className="mt-2 text-sm text-ink-3">
                    {helperText}
                </p>
            ) : null}
        </div>
    );
});

Input.displayName = 'Input';
