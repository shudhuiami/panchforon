import React, { forwardRef } from 'react';
import { CircleAlert } from 'lucide-react';

export interface TextareaProps extends React.TextareaHTMLAttributes<HTMLTextAreaElement> {
    label?: string;
    helperText?: string;
    errorMessage?: string;
    hasError?: boolean;
}

export const Textarea = forwardRef<HTMLTextAreaElement, TextareaProps>(({
    label,
    helperText,
    errorMessage,
    hasError = false,
    className = '',
    id,
    disabled,
    rows = 4,
    ...props
}, ref) => {
    const textareaId = id || (label ? `textarea-${label.toLowerCase().replace(/\s+/g, '-')}` : undefined);
    const isInvalid = hasError || !!errorMessage;

    return (
        <div className="w-full group/field">
            {label && (
                <label
                    htmlFor={textareaId}
                    className="block text-xs font-bold uppercase tracking-wide text-ink-2 mb-2 transition-colors group-focus-within/field:text-saffron-deep"
                >
                    {label}
                </label>
            )}

            <textarea
                ref={ref}
                id={textareaId}
                disabled={disabled}
                rows={rows}
                aria-invalid={isInvalid}
                aria-describedby={
                    errorMessage ? `${textareaId}-error` : helperText ? `${textareaId}-helper` : undefined
                }
                className={`w-full bg-paper text-ink font-medium placeholder:text-ink-3 placeholder:font-normal border-2 rounded-2xl px-4 py-3.5 text-base leading-relaxed transition-all duration-200 outline-none resize-y
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

            {errorMessage ? (
                <p
                    id={`${textareaId}-error`}
                    className="mt-2 text-sm text-chili-deep font-semibold flex items-center gap-1.5 animate-fade-in"
                >
                    <CircleAlert className="w-4 h-4 shrink-0" aria-hidden="true" />
                    {errorMessage}
                </p>
            ) : helperText ? (
                <p id={`${textareaId}-helper`} className="mt-2 text-sm text-ink-3">
                    {helperText}
                </p>
            ) : null}
        </div>
    );
});

Textarea.displayName = 'Textarea';
