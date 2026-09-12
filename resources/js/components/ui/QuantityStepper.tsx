import React from 'react';
import { Minus, Plus } from 'lucide-react';

export interface QuantityStepperProps {
    value: number;
    onChange: (newValue: number) => void;
    min?: number;
    max?: number;
    step?: number;
    disabled?: boolean;
    size?: 'sm' | 'md' | 'lg';
    className?: string;
    ariaLabel?: string;
}

const SIZES = {
    sm: { container: 'h-9 gap-0.5 p-1', button: 'size-7', input: 'w-9 text-sm', icon: 'size-3' },
    md: { container: 'h-12 gap-1 p-1.5', button: 'size-9', input: 'w-11 text-lg', icon: 'size-3.5' },
    lg: { container: 'h-14 gap-1 p-1.5', button: 'size-11', input: 'w-14 text-2xl', icon: 'size-4' },
};

const buttonClass =
    'flex cursor-pointer items-center justify-center rounded-full bg-surface-3 text-ink transition-colors hover:bg-primary hover:text-on-primary active:scale-90 disabled:cursor-not-allowed disabled:opacity-35 disabled:hover:bg-surface-3 disabled:hover:text-ink focus-visible:z-10 focus-visible:outline-3 focus-visible:outline-primary';

export const QuantityStepper: React.FC<QuantityStepperProps> = ({
    value,
    onChange,
    min = 1,
    max = 99,
    step = 1,
    disabled = false,
    size = 'md',
    className = '',
    ariaLabel = 'Quantity',
}) => {
    const clamp = (next: number) => Math.min(Math.max(next, min), max);
    const sizes = SIZES[size];

    return (
        <div className={`inline-flex items-center rounded-full border border-line bg-surface-2 ${sizes.container} ${disabled ? 'opacity-50' : ''} ${className}`}>
            <button
                type="button"
                onClick={() => onChange(clamp(value - step))}
                disabled={disabled || value <= min}
                aria-label={`Decrease ${ariaLabel}`}
                className={`${sizes.button} ${buttonClass}`}
            >
                <Minus className={`${sizes.icon} stroke-[3]`} aria-hidden="true" />
            </button>
            <input
                type="number"
                value={value}
                min={min}
                max={max}
                step={step}
                disabled={disabled}
                onChange={(e) => {
                    const parsed = Number.parseInt(e.target.value, 10);
                    if (!Number.isNaN(parsed)) onChange(clamp(parsed));
                }}
                aria-label={ariaLabel}
                className={`${sizes.input} h-full bg-transparent text-center font-display font-semibold text-ink tabular-nums focus-visible:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none`}
            />
            <button
                type="button"
                onClick={() => onChange(clamp(value + step))}
                disabled={disabled || value >= max}
                aria-label={`Increase ${ariaLabel}`}
                className={`${sizes.button} ${buttonClass}`}
            >
                <Plus className={`${sizes.icon} stroke-[3]`} aria-hidden="true" />
            </button>
        </div>
    );
};
