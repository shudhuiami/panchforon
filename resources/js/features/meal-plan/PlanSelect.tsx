import React from 'react';
import { ChevronDown, type LucideIcon } from 'lucide-react';

/** The day select's value for a dish with no date; `<option>` values are strings, `null` is not one. */
export const UNDATED = 'undated';

export interface PlanSelectProps {
    value: string;
    onChange: (value: string) => void;
    /** Accessible name; the control is too small for a visible label beside it. */
    label: string;
    icon?: LucideIcon;
    disabled?: boolean;
    className?: string;
    children: React.ReactNode;
}

/**
 * The compact pill select the planner uses for meal slots and for moving a
 * dish to another day. Native `<select>` on purpose: it is the one control
 * every phone already knows how to show well.
 */
export const PlanSelect: React.FC<PlanSelectProps> = ({ value, onChange, label, icon: Icon, disabled = false, className = '', children }) => (
    <div className={`relative ${className}`}>
        {Icon && <Icon className="pointer-events-none absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-ink-3" aria-hidden="true" />}
        <select
            value={value}
            onChange={(event) => onChange(event.target.value)}
            aria-label={label}
            title={label}
            disabled={disabled}
            className={`h-10 w-full cursor-pointer appearance-none rounded-full border border-line bg-surface pr-8 text-xs font-semibold text-ink transition-colors hover:border-line-strong disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-3 focus-visible:outline-primary focus-visible:outline-offset-2 ${
                Icon ? 'pl-9' : 'pl-3.5'
            }`}
        >
            {children}
        </select>
        <ChevronDown className="pointer-events-none absolute top-1/2 right-2.5 size-3.5 -translate-y-1/2 text-ink-3" aria-hidden="true" />
    </div>
);
