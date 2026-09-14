import React, { useState } from 'react';
import { CalendarRange } from 'lucide-react';
import { Button } from '../../components/ui/Button';
import { Input } from '../../components/ui/Input';
import { IsoDate, MAX_PLAN_DAYS, addDays, formatRange, rangeLength, todayIso } from './dates';

export interface PlanRangeValues {
    name?: string;
    starts_on: IsoDate;
    ends_on: IsoDate;
}

export interface PlanRangeFormProps {
    initialName?: string;
    initialStart: IsoDate;
    initialEnd: IsoDate;
    /** The new-plan form names the plan; editing an existing range does not. */
    withName?: boolean;
    submitLabel: string;
    isPending?: boolean;
    errorMessage?: string | null;
    onSubmit: (values: PlanRangeValues) => void;
    onCancel?: () => void;
    /** A plan already under way keeps its own start as the floor. */
    minStart?: IsoDate;
    idPrefix?: string;
}

const PRESETS: Array<{ label: string; days: number }> = [
    { label: '3 days', days: 3 },
    { label: '1 week', days: 7 },
    { label: '2 weeks', days: 14 },
];

/**
 * Start and end of a plan. The pickers refuse days before today, keep the end
 * on or after the start, and cap the span at the API's sixty days.
 */
export const PlanRangeForm: React.FC<PlanRangeFormProps> = ({
    initialName = '',
    initialStart,
    initialEnd,
    withName = false,
    submitLabel,
    isPending = false,
    errorMessage = null,
    onSubmit,
    onCancel,
    minStart,
    idPrefix = 'plan-range',
}) => {
    const floor = minStart ?? todayIso();
    const [name, setName] = useState(initialName);
    const [start, setStart] = useState<IsoDate>(initialStart);
    const [end, setEnd] = useState<IsoDate>(initialEnd);

    const span = rangeLength(start, end);
    const startError = !start ? 'Pick a first day.' : start < floor ? 'A plan starts today or later.' : null;
    const endError = !end ? 'Pick a last day.' : end < start ? 'The last day cannot come before the first.' : span > MAX_PLAN_DAYS ? `A plan covers at most ${MAX_PLAN_DAYS} days.` : null;
    const isValid = !startError && !endError;

    /** Dragging the start past the end takes the end with it. */
    const changeStart = (value: IsoDate) => {
        setStart(value);
        if (value && (!end || end < value)) setEnd(value);
        if (value && end && rangeLength(value, end) > MAX_PLAN_DAYS) setEnd(addDays(value, MAX_PLAN_DAYS - 1));
    };

    return (
        <form
            className="space-y-4"
            onSubmit={(event) => {
                event.preventDefault();
                if (!isValid || isPending) return;
                onSubmit({ ...(withName ? { name: name.trim() || undefined } : {}), starts_on: start, ends_on: end });
            }}
        >
            {withName && (
                <Input
                    id={`${idPrefix}-name`}
                    label="Plan name"
                    value={name}
                    onChange={(event) => setName(event.target.value)}
                    placeholder="Next week"
                    maxLength={80}
                    helperText="Optional — it is only there so you can find it again."
                />
            )}

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Input
                    id={`${idPrefix}-start`}
                    type="date"
                    label="First day"
                    value={start}
                    min={floor}
                    onChange={(event) => changeStart(event.target.value)}
                    errorMessage={startError ?? undefined}
                />
                <Input
                    id={`${idPrefix}-end`}
                    type="date"
                    label="Last day"
                    value={end}
                    min={start || floor}
                    max={start ? addDays(start, MAX_PLAN_DAYS - 1) : undefined}
                    onChange={(event) => setEnd(event.target.value)}
                    errorMessage={endError ?? undefined}
                />
            </div>

            <div className="flex flex-wrap items-center gap-2">
                <span className="text-[11px] font-semibold tracking-[0.14em] text-ink-3 uppercase">Quick spans</span>
                {PRESETS.map((preset) => (
                    <button
                        key={preset.label}
                        type="button"
                        onClick={() => setEnd(addDays(start || floor, preset.days - 1))}
                        aria-pressed={span === preset.days}
                        className={`cursor-pointer rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors focus-visible:outline-3 focus-visible:outline-primary focus-visible:outline-offset-2 ${
                            span === preset.days ? 'border-primary bg-primary-soft text-primary' : 'border-line bg-surface-2 text-ink-2 hover:border-line-strong hover:text-ink'
                        }`}
                    >
                        {preset.label}
                    </button>
                ))}
            </div>

            <p className="flex items-center gap-2 rounded-2xl bg-surface-2 px-4 py-3 text-sm text-ink-2" aria-live="polite">
                <CalendarRange className="size-4 shrink-0 text-primary" aria-hidden="true" />
                {isValid ? (
                    <span>
                        <span className="font-semibold text-ink">
                            {span} {span === 1 ? 'day' : 'days'}
                        </span>{' '}
                        · {formatRange(start, end)}
                    </span>
                ) : (
                    <span>Pick a first and last day to see the span.</span>
                )}
            </p>

            {errorMessage && <p className="text-sm text-hot">{errorMessage}</p>}

            <div className="flex flex-col gap-2 sm:flex-row-reverse">
                <Button type="submit" disabled={!isValid} isLoading={isPending} className="sm:min-w-40">
                    {submitLabel}
                </Button>
                {onCancel && (
                    <Button type="button" variant="ghost" onClick={onCancel}>
                        Cancel
                    </Button>
                )}
            </div>
        </form>
    );
};
