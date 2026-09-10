import React, { forwardRef } from 'react';

export interface RadioProps extends Omit<React.InputHTMLAttributes<HTMLInputElement>, 'type'> {
    label?: React.ReactNode;
    helperText?: string;
}

export const Radio = forwardRef<HTMLInputElement, RadioProps>(({
    label,
    helperText,
    className = '',
    id,
    disabled,
    checked,
    ...props
}, ref) => {
    const radioId = id || (typeof label === 'string' ? `radio-${label.toLowerCase().replace(/\s+/g, '-')}` : undefined);

    return (
        <div className={`flex items-start gap-3 select-none ${disabled ? 'opacity-60' : ''}`}>
            {/* Larger hit area wrapper: 44px target, control centred */}
            <label
                htmlFor={radioId}
                className={`relative flex items-center justify-center shrink-0 -m-2 p-2 rounded-full ${
                    disabled ? 'cursor-not-allowed' : 'cursor-pointer'
                }`}
            >
                <input
                    ref={ref}
                    type="radio"
                    id={radioId}
                    disabled={disabled}
                    checked={checked}
                    className="peer sr-only"
                    {...props}
                />
                <span
                    aria-hidden="true"
                    className={`group/radio w-6 h-6 rounded-full border-2 transition-all duration-200 flex items-center justify-center
                        border-line-strong bg-paper
                        peer-hover:border-ink
                        peer-checked:border-mint-deep peer-checked:bg-mint-soft peer-checked:[&>span]:scale-100 peer-checked:shadow-glow-mint
                        peer-focus-visible:ring-4 peer-focus-visible:ring-mint/30 peer-focus-visible:border-mint-deep
                        peer-disabled:bg-cream-2 peer-disabled:border-line peer-disabled:shadow-none
                        ${className}`}
                >
                    <span className="w-3 h-3 rounded-full bg-mint-deep scale-0 transition-transform duration-200 ease-spring" />
                </span>
            </label>

            {(label || helperText) && (
                <div className="text-sm pt-0.5">
                    {label && (
                        <label
                            htmlFor={radioId}
                            className={`font-semibold leading-snug ${
                                disabled ? 'text-ink-3 cursor-not-allowed' : 'text-ink cursor-pointer'
                            }`}
                        >
                            {label}
                        </label>
                    )}
                    {helperText && (
                        <p className="text-xs text-ink-3 mt-0.5">{helperText}</p>
                    )}
                </div>
            )}
        </div>
    );
});

Radio.displayName = 'Radio';
