import React from 'react';
import { MealPlanItem } from '../../types/api';
import { PlanDishRow } from './PlanDishRow';
import { IsoDate } from './dates';
import { useDishDropTarget } from './planDrag';

export interface PlanUndatedTrayProps {
    /** Dishes with no day, plus any left pointing outside the plan's range. */
    items: MealPlanItem[];
    days: IsoDate[];
}

/**
 * The shelf above the plan for dishes that have been picked but not dated. It
 * is the drop target for "no day at all", so a dish dragged up here loses its
 * date; a dish that never had one can't be dropped back onto it.
 */
export const PlanUndatedTray: React.FC<PlanUndatedTrayProps> = ({ items, days }) => {
    const { isTarget, isOver, props: dropProps } = useDishDropTarget(null);

    const tone = isOver ? 'border-primary bg-primary-soft ring-2 ring-primary' : isTarget ? 'border-primary/40 bg-surface-2' : 'border-line-strong bg-surface-2';

    return (
        <section {...dropProps} aria-labelledby="undated-heading" className={`mt-8 rounded-3xl border border-dashed p-5 transition-colors sm:p-6 ${tone}`}>
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 id="undated-heading" className="font-display text-xl font-semibold text-ink">
                        Picked, not yet dated
                    </h2>
                    <p className="mt-1 text-sm text-ink-2">
                        {isOver ? 'Drop it here to take its day away.' : 'Give each one a day and it joins the right column below.'}
                    </p>
                </div>
                <span className="rounded-full bg-surface px-3 py-1 text-xs font-semibold text-ink-2 tabular-nums">
                    {items.length} {items.length === 1 ? 'dish' : 'dishes'}
                </span>
            </div>
            <ul className="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                {items.map((item) => (
                    <PlanDishRow key={item.id} item={item} days={days} />
                ))}
            </ul>
        </section>
    );
};
