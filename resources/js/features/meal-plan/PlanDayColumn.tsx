import React from 'react';
import { Plus } from 'lucide-react';
import { MealPlanItem } from '../../types/api';
import { Button } from '../../components/ui/Button';
import { PlanDishRow } from './PlanDishRow';
import { MEAL_SLOTS, SLOT_META, slotLabel } from './slots';
import { IsoDate, formatDayLong, formatShort, isPastDay, isToday, relativeDayLabel } from './dates';
import { useDishDropTarget } from './planDrag';

export interface PlanDayColumnProps {
    iso: IsoDate;
    /** Only the dishes planned for this day. */
    items: MealPlanItem[];
    days: IsoDate[];
    onAddDish?: (iso: IsoDate) => void;
    readOnly?: boolean;
}

/**
 * One day of the plan: a card on a phone, a column in the desktop grid, and —
 * on a pointer device — somewhere a dragged dish can land. While a dish is in
 * the air every day it could move to is outlined and the one under the pointer
 * fills in; the day it came from is not a target and stays as it was.
 */
export const PlanDayColumn: React.FC<PlanDayColumnProps> = ({ iso, items, days, onAddDish, readOnly = false }) => {
    const today = isToday(iso);
    const past = isPastDay(iso);
    const headingId = `plan-day-${iso}`;
    const dishes = items.length;
    const { isTarget, isOver, props: dropProps } = useDishDropTarget(iso, !readOnly);

    const grouped = MEAL_SLOTS.map((slot) => ({ slot, items: items.filter((item) => item.meal_slot === slot) })).filter((group) => group.items.length > 0);
    const unknownSlot = items.filter((item) => !MEAL_SLOTS.includes(item.meal_slot));

    const tone = isOver
        ? 'border-primary bg-primary-soft ring-2 ring-primary'
        : isTarget
          ? 'border-dashed border-primary/40 bg-surface'
          : today
            ? 'border-primary/50 bg-primary-soft/30'
            : past
              ? 'border-line bg-surface/60'
              : 'border-line bg-surface';

    return (
        <section aria-labelledby={headingId} {...dropProps} className={`flex flex-col rounded-3xl border p-4 transition-colors ${tone}`}>
            <header className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className={`text-[11px] font-semibold tracking-[0.14em] uppercase ${today ? 'text-primary' : 'text-ink-3'}`}>{relativeDayLabel(iso)}</p>
                    <h3 id={headingId} className="mt-0.5 font-display text-lg leading-tight font-semibold text-ink">
                        <span className="sr-only">{formatDayLong(iso)}</span>
                        <span aria-hidden="true">{formatShort(iso)}</span>
                    </h3>
                </div>
                <span className={`shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold tabular-nums ${dishes > 0 ? 'bg-surface-2 text-ink-2' : 'bg-surface-2 text-ink-3'}`}>
                    {dishes} {dishes === 1 ? 'dish' : 'dishes'}
                </span>
            </header>

            {dishes === 0 ? (
                <p
                    className={`mt-4 rounded-2xl border border-dashed px-3 py-5 text-center text-xs ${isOver ? 'border-primary font-semibold text-primary' : 'border-line-strong text-ink-3'}`}
                >
                    {isOver ? 'Drop it here' : readOnly ? 'Nothing was cooked this day.' : 'Nothing planned yet.'}
                </p>
            ) : (
                <div className="mt-4 space-y-4">
                    {grouped.map(({ slot, items: slotItems }) => (
                        <div key={slot}>
                            <p className={`mb-2 flex items-center gap-1.5 text-[10px] font-semibold tracking-[0.16em] uppercase ${SLOT_META[slot].tone}`}>
                                {slotLabel(slot)}
                                <span className="text-ink-3 tabular-nums">{slotItems.length}</span>
                            </p>
                            <ul className="space-y-2">
                                {slotItems.map((item) => (
                                    <PlanDishRow key={item.id} item={item} days={days} readOnly={readOnly} />
                                ))}
                            </ul>
                        </div>
                    ))}
                    {unknownSlot.length > 0 && (
                        <ul className="space-y-2">
                            {unknownSlot.map((item) => (
                                <PlanDishRow key={item.id} item={item} days={days} readOnly={readOnly} />
                            ))}
                        </ul>
                    )}
                </div>
            )}

            {isOver && dishes > 0 && (
                <p className="mt-3 rounded-2xl border border-dashed border-primary px-3 py-3 text-center text-xs font-semibold text-primary" aria-hidden="true">
                    Drop it here
                </p>
            )}

            {!readOnly && onAddDish && (
                <Button variant="secondary" size="sm" className="mt-4 w-full" onClick={() => onAddDish(iso)}>
                    <Plus className="size-4" aria-hidden="true" />
                    <span className="sr-only">Add a dish to {formatDayLong(iso)}</span>
                    <span aria-hidden="true">Add a dish</span>
                </Button>
            )}
        </section>
    );
};
