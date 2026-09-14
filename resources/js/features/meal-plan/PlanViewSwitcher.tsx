import React from 'react';
import { CalendarDays, Rows3 } from 'lucide-react';

export type PlanView = 'calendar' | 'list';

export const PLAN_VIEWS: Array<{ key: PlanView; label: string; icon: typeof CalendarDays }> = [
    { key: 'calendar', label: 'Calendar', icon: CalendarDays },
    { key: 'list', label: 'List', icon: Rows3 },
];

export const planViewTabId = (view: PlanView): string => `plan-view-${view}`;
export const planViewPanelId = (view: PlanView): string => `plan-view-panel-${view}`;

export interface PlanViewSwitcherProps {
    value: PlanView;
    onChange: (view: PlanView) => void;
    className?: string;
}

/** Calendar or list, as a real tab list: arrow keys move between the two. */
export const PlanViewSwitcher: React.FC<PlanViewSwitcherProps> = ({ value, onChange, className = '' }) => {
    const onKeyDown = (event: React.KeyboardEvent<HTMLButtonElement>) => {
        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight' && event.key !== 'Home' && event.key !== 'End') return;
        event.preventDefault();

        const index = PLAN_VIEWS.findIndex((view) => view.key === value);
        const next =
            event.key === 'Home' ? 0 : event.key === 'End' ? PLAN_VIEWS.length - 1 : (index + (event.key === 'ArrowRight' ? 1 : -1) + PLAN_VIEWS.length) % PLAN_VIEWS.length;

        onChange(PLAN_VIEWS[next].key);
        document.getElementById(planViewTabId(PLAN_VIEWS[next].key))?.focus();
    };

    return (
        <div role="tablist" aria-label="How to show your plans" className={`inline-flex items-center gap-1 rounded-full border border-line bg-surface p-1 ${className}`}>
            {PLAN_VIEWS.map(({ key, label, icon: Icon }) => {
                const selected = key === value;
                return (
                    <button
                        key={key}
                        type="button"
                        role="tab"
                        id={planViewTabId(key)}
                        aria-selected={selected}
                        aria-controls={planViewPanelId(key)}
                        tabIndex={selected ? 0 : -1}
                        onClick={() => onChange(key)}
                        onKeyDown={onKeyDown}
                        className={`inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-full px-4 text-sm font-semibold transition-colors focus-visible:outline-3 focus-visible:outline-primary focus-visible:outline-offset-2 ${
                            selected ? 'bg-primary text-on-primary' : 'text-ink-2 hover:bg-surface-2 hover:text-ink'
                        }`}
                    >
                        <Icon className="size-4" aria-hidden="true" />
                        {label}
                    </button>
                );
            })}
        </div>
    );
};
