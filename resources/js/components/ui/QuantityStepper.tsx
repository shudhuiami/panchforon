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
    const handleDecrement = () => {
        if (!disabled && value > min) {
            onChange(Math.max(min, value - step));
        }
    };

    const handleIncrement = () => {
        if (!disabled && value < max) {
            onChange(Math.min(max, value + step));
        }
    };

    const handleDirectChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const parsed = parseInt(e.target.value, 10);
        if (!isNaN(parsed)) {
            const clamped = Math.min(Math.max(parsed, min), max);
            onChange(clamped);
        }
    };

    const sizeStyles = {
        sm: {
            container: 'h-9 p-1 gap-0.5',
            btn: 'w-7 h-7',
            input: 'w-9 text-sm',
            icon: 'w-3 h-3',
        },
        md: {
            container: 'h-12 p-1.5 gap-1',
            btn: 'w-9 h-9',
            input: 'w-11 text-lg',
            icon: 'w-3.5 h-3.5',
        },
        lg: {
            container: 'h-14 p-1.5 gap-1',
            btn: 'w-11 h-11',
            input: 'w-14 text-2xl',
            icon: 'w-4 h-4',
        },
    };

    const isMin = value <= min;
    const isMax = value >= max;

    const btnBase =
        'flex items-center justify-center rounded-full bg-saffron-soft text-saffron-deep hover:bg-saffron hover:text-ink active:scale-90 transition-all duration-150 cursor-pointer disabled:opacity-35 disabled:cursor-not-allowed disabled:hover:bg-saffron-soft disabled:hover:text-saffron-deep disabled:active:scale-100 focus-visible:outline-3 focus-visible:outline-saffron focus-visible:z-10';

    return (
        <div
            className={`inline-flex items-center rounded-full border-2 border-line-strong bg-paper ${sizeStyles[size].container} ${
                disabled ? 'opacity-50 cursor-not-allowed bg-cream-2' : ''
            } ${className}`}
        >
            <button
                type="button"
                onClick={handleDecrement}
                disabled={disabled || isMin}
                aria-label={`Decrease ${ariaLabel}`}
                className={`${sizeStyles[size].btn} ${btnBase}`}
            >
                <Minus className={`${sizeStyles[size].icon} stroke-[3]`} />
            </button>

            <input
                type="number"
                value={value}
                min={min}
                max={max}
                step={step}
                disabled={disabled}
                onChange={handleDirectChange}
                aria-label={ariaLabel}
                className={`${sizeStyles[size].input} h-full text-center font-display font-extrabold text-ink bg-transparent tabular-nums focus-visible:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none`}
            />

            <button
                type="button"
                onClick={handleIncrement}
                disabled={disabled || isMax}
                aria-label={`Increase ${ariaLabel}`}
                className={`${sizeStyles[size].btn} ${btnBase}`}
            >
                <Plus className={`${sizeStyles[size].icon} stroke-[3]`} />
            </button>
        </div>
    );
};
