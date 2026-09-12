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

export const fieldLabelClass = 'mb-2 block text-xs font-semibold tracking-[0.14em] text-ink-3 uppercase';

/** Shared box styling for text fields, so inputs and textareas match. */
export const fieldClass = (invalid: boolean): string =>
    `w-full rounded-2xl border bg-surface-2 text-ink outline-none transition-[border-color,box-shadow] duration-200 placeholder:text-ink-3 disabled:cursor-not-allowed disabled:opacity-60 ${
        invalid ? 'border-hot focus:border-hot focus:ring-4 focus:ring-hot/15' : 'border-line hover:border-line-strong focus:border-primary/70 focus:ring-4 focus:ring-primary/15'
    }`;

export const FieldMessage: React.FC<{ id?: string; error?: string; helper?: string }> = ({ id, error, helper }) =>
    error ? (
        <p id={id ? `${id}-error` : undefined} className="mt-2 flex items-center gap-1.5 text-sm text-hot">
            <CircleAlert className="size-4 shrink-0" aria-hidden="true" />
            {error}
        </p>
    ) : helper ? (
        <p id={id ? `${id}-helper` : undefined} className="mt-2 text-sm text-ink-3">
            {helper}
        </p>
    ) : null;

export const Input = forwardRef<HTMLInputElement, InputProps>(
    ({ label, helperText, errorMessage, hasError = false, leftIcon, rightIcon, className = '', id, ...props }, ref) => {
        const inputId = id || (label ? `input-${label.toLowerCase().replace(/\s+/g, '-')}` : undefined);
        const isInvalid = hasError || !!errorMessage;

        return (
            <div className="w-full">
                {label && (
                    <label htmlFor={inputId} className={fieldLabelClass}>
                        {label}
                    </label>
                )}
                <div className="relative flex items-center">
                    {leftIcon && <span className={`pointer-events-none absolute left-4 flex items-center ${isInvalid ? 'text-hot' : 'text-ink-3'}`}>{leftIcon}</span>}
                    <input
                        ref={ref}
                        id={inputId}
                        aria-invalid={isInvalid}
                        aria-describedby={errorMessage ? `${inputId}-error` : helperText ? `${inputId}-helper` : undefined}
                        className={`${fieldClass(isInvalid)} min-h-12 px-4 py-3 text-base ${leftIcon ? 'pl-11' : ''} ${rightIcon ? 'pr-11' : ''} ${className}`}
                        {...props}
                    />
                    {rightIcon && <span className={`absolute right-4 flex items-center ${isInvalid ? 'text-hot' : 'text-ink-3'}`}>{rightIcon}</span>}
                </div>
                <FieldMessage id={inputId} error={errorMessage} helper={helperText} />
            </div>
        );
    },
);

Input.displayName = 'Input';
