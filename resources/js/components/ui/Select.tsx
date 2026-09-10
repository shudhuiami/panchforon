import React, { forwardRef } from 'react';
import { ChevronDown, CircleAlert } from 'lucide-react';

export interface SelectOption {
    value: string | number;
    label: string;
}

export interface SelectProps extends React.SelectHTMLAttributes<HTMLSelectElement> {
    label?: string;
    options?: SelectOption[];
    helperText?: string;
    errorMessage?: string;
    hasError?: boolean;
}

export const Select = forwardRef<HTMLSelectElement, SelectProps>(({
    label,
    options,
    children,
    helperText,
    errorMessage,
    hasError = false,
    className = '',
    id,
    disabled,
    ...props
}, ref) => {
    const selectId = id || (label ? `select-${label.toLowerCase().replace(/\s+/g, '-')}` : undefined);
    const isInvalid = hasError || !!errorMessage;

    return (
        <div className="w-full group/field">
            {label && (
                <label
                    htmlFor={selectId}
                    className="block text-xs font-bold uppercase tracking-wide text-ink-2 mb-2 transition-colors group-focus-within/field:text-saffron-deep"
                >
                    {label}
                </label>
            )}

            <div className="relative flex items-center">
                <select
                    ref={ref}
                    id={selectId}
                    disabled={disabled}
                    aria-invalid={isInvalid}
                    aria-describedby={
                        errorMessage ? `${selectId}-error` : helperText ? `${selectId}-helper` : undefined
                    }
                    className={`w-full min-h-12 appearance-none bg-paper text-ink font-medium border-2 rounded-2xl pl-4 pr-13 py-3 text-base transition-all duration-200 cursor-pointer outline-none
                        ${
                            isInvalid
                                ? 'border-chili bg-chili-soft/30 focus:border-chili focus:ring-4 focus:ring-chili/20'
                                : 'border-line hover:border-line-strong focus:border-saffron focus:ring-4 focus:ring-saffron/20'
                        }
                        disabled:bg-cream-2 disabled:text-ink-3 disabled:border-line disabled:cursor-not-allowed
                        focus-visible:outline-none
                        ${className}`}
                    {...props}
                >
                    {options
                        ? options.map((opt) => (
                              <option key={opt.value} value={opt.value}>
                                  {opt.label}
                              </option>
                          ))
                        : children}
                </select>

                {/* Chevron chip: soft tint square that flips saffron on focus */}
                <div
                    className={`absolute right-2 pointer-events-none flex items-center justify-center w-8 h-8 rounded-xl transition-colors ${
                        isInvalid
                            ? 'bg-chili-soft text-chili-deep'
                            : 'bg-saffron-soft text-saffron-deep group-focus-within/field:bg-saffron group-focus-within/field:text-ink'
                    }`}
                >
                    <ChevronDown className="w-4 h-4 stroke-[2.5]" />
                </div>
            </div>

            {errorMessage ? (
                <p
                    id={`${selectId}-error`}
                    className="mt-2 text-sm text-chili-deep font-semibold flex items-center gap-1.5 animate-fade-in"
                >
                    <CircleAlert className="w-4 h-4 shrink-0" aria-hidden="true" />
                    {errorMessage}
                </p>
            ) : helperText ? (
                <p id={`${selectId}-helper`} className="mt-2 text-sm text-ink-3">
                    {helperText}
                </p>
            ) : null}
        </div>
    );
});

Select.displayName = 'Select';
