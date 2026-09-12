import React, { forwardRef } from 'react';
import { FieldMessage, fieldClass, fieldLabelClass } from './Input';

export interface TextareaProps extends React.TextareaHTMLAttributes<HTMLTextAreaElement> {
    label?: string;
    helperText?: string;
    errorMessage?: string;
    hasError?: boolean;
}

export const Textarea = forwardRef<HTMLTextAreaElement, TextareaProps>(({ label, helperText, errorMessage, hasError = false, className = '', id, rows = 4, ...props }, ref) => {
    const textareaId = id || (label ? `textarea-${label.toLowerCase().replace(/\s+/g, '-')}` : undefined);
    const isInvalid = hasError || !!errorMessage;

    return (
        <div className="w-full">
            {label && (
                <label htmlFor={textareaId} className={fieldLabelClass}>
                    {label}
                </label>
            )}
            <textarea
                ref={ref}
                id={textareaId}
                rows={rows}
                aria-invalid={isInvalid}
                aria-describedby={errorMessage ? `${textareaId}-error` : helperText ? `${textareaId}-helper` : undefined}
                className={`${fieldClass(isInvalid)} resize-y px-4 py-3.5 text-base leading-relaxed ${className}`}
                {...props}
            />
            <FieldMessage id={textareaId} error={errorMessage} helper={helperText} />
        </div>
    );
});

Textarea.displayName = 'Textarea';
