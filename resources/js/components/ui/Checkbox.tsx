import React, { forwardRef } from 'react';
import { Check } from 'lucide-react';

export interface CheckboxProps extends Omit<React.InputHTMLAttributes<HTMLInputElement>, 'type'> {
    label?: React.ReactNode;
    helperText?: string;
    errorMessage?: string;
}

export const Checkbox = forwardRef<HTMLInputElement, CheckboxProps>(({
    label,
    helperText,
    errorMessage,
    className = '',
    id,
    disabled,
    checked,
    ...props
}, ref) => {
    const checkboxId = id || (typeof label === 'string' ? `cb-${label.toLowerCase().replace(/\s+/g, '-')}` : undefined);

    return (
        <div className={`flex items-start gap-3 select-none ${disabled ? 'opacity-60' : ''}`}>
            {/* Larger hit area wrapper: 44px target, control centred */}
            <label
                htmlFor={checkboxId}
                className={`relative flex items-center justify-center shrink-0 -m-2 p-2 rounded-xl ${
                    disabled ? 'cursor-not-allowed' : 'cursor-pointer'
                }`}
            >
                <input
                    ref={ref}
                    type="checkbox"
                    id={checkboxId}
                    disabled={disabled}
                    checked={checked}
                    className="peer sr-only"
                    {...props}
                />
                <span
                    aria-hidden="true"
                    className={`w-6 h-6 rounded-lg border-2 transition-all duration-200 flex items-center justify-center
                        border-line-strong bg-paper text-transparent
                        peer-hover:border-ink
                        peer-checked:bg-saffron peer-checked:border-ink peer-checked:text-ink peer-checked:shadow-pop-sm peer-checked:-translate-x-px peer-checked:-translate-y-px
                        peer-focus-visible:ring-4 peer-focus-visible:ring-saffron/30 peer-focus-visible:border-saffron
                        peer-disabled:bg-cream-2 peer-disabled:border-line peer-disabled:shadow-none peer-disabled:translate-x-0 peer-disabled:translate-y-0
                        ${errorMessage ? 'border-chili' : ''}
                        ${className}`}
                >
                    <Check className="w-4 h-4 stroke-[3.5]" />
                </span>
            </label>

            {(label || helperText || errorMessage) && (
                <div className="text-sm pt-0.5">
                    {label && (
                        <label
                            htmlFor={checkboxId}
                            className={`font-semibold leading-snug ${
                                disabled ? 'text-ink-3 cursor-not-allowed' : 'text-ink cursor-pointer'
                            }`}
                        >
                            {label}
                        </label>
                    )}
                    {helperText && !errorMessage && (
                        <p className="text-xs text-ink-3 mt-0.5">{helperText}</p>
                    )}
                    {errorMessage && (
                        <p className="text-xs text-chili-deep font-semibold mt-0.5">{errorMessage}</p>
                    )}
                </div>
            )}
        </div>
    );
});

Checkbox.displayName = 'Checkbox';
