import React from 'react';
import { Link } from 'react-router-dom';
import { CalendarDays, X } from 'lucide-react';
import { MealPlanItem, MealSlot } from '../../types/api';
import { IconButton } from '../../components/ui/IconButton';
import { Photo } from '../../components/ui/Photo';
import { QuantityStepper } from '../../components/ui/QuantityStepper';
import { RecipeFallback } from '../recipes/RecipeCard';
import { PlanSelect, UNDATED } from './PlanSelect';
import { MEAL_SLOTS, SLOT_META, slotLabel } from './slots';
import { IsoDate, dayOptionLabel } from './dates';
import { usePlanItemActions } from './useMealPlan';

export interface PlanDishRowProps {
    item: MealPlanItem;
    /** Every day the plan covers, for the "move to" control. */
    days: IsoDate[];
    /** A past plan is a record, not a worksheet: no controls at all. */
    readOnly?: boolean;
}

/**
 * One planned dish. Servings, meal slot, the day it is cooked on and removing
 * it are all saved the moment they change — the cached plan updates first, so
 * a dish jumps to its new day straight away.
 */
export const PlanDishRow: React.FC<PlanDishRowProps> = ({ item, days, readOnly = false }) => {
    const { updateItem, removeItem } = usePlanItemActions();
    const recipe = item.recipe;
    const title = recipe?.title ?? `Recipe #${item.recipe_id}`;
    const to = `/recipes/${recipe?.slug ?? item.recipe_id}`;
    const slot = SLOT_META[item.meal_slot] ?? SLOT_META.dinner;
    const SlotIcon = slot.icon;

    const isLeaving = removeItem.isPending && removeItem.variables === item.id;
    const isSaving = updateItem.isPending && updateItem.variables?.id === item.id;

    return (
        <li
            className={`rounded-2xl border border-line bg-surface-2 p-3 transition-opacity ${isLeaving ? 'pointer-events-none opacity-40' : ''} ${isSaving ? 'opacity-70' : ''}`}
            aria-busy={isSaving || isLeaving}
        >
            <div className="flex items-start gap-3">
                <Link to={to} tabIndex={-1} aria-hidden="true" className="block size-12 shrink-0 overflow-hidden rounded-xl bg-surface-3">
                    <Photo src={recipe?.image_url} alt="" loading="lazy" className="h-full w-full object-cover" fallback={<RecipeFallback recipe={{ id: item.recipe_id, title }} />} />
                </Link>
                <div className="min-w-0 flex-1">
                    <Link to={to} className="line-clamp-2 block font-display text-[15px] leading-tight font-semibold text-ink transition-colors hover:text-primary">
                        {title}
                    </Link>
                    <p className="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] text-ink-3">
                        <span className={`inline-flex items-center gap-1 font-semibold ${slot.tone}`}>
                            <SlotIcon className="size-3" aria-hidden="true" />
                            {slotLabel(item.meal_slot)}
                        </span>
                        <span>
                            {item.servings} {item.servings === 1 ? 'serving' : 'servings'}
                        </span>
                        {recipe?.cuisine && <span>{recipe.cuisine}</span>}
                    </p>
                </div>
                {!readOnly && (
                    <IconButton label={`Remove ${title} from the plan`} variant="ghost" size="sm" onClick={() => removeItem.mutate(item.id)} disabled={isLeaving}>
                        <X className="size-4" />
                    </IconButton>
                )}
            </div>

            {!readOnly && (
                <>
                    <div className="mt-3 grid grid-cols-2 gap-2">
                        <PlanSelect
                            label={`Meal slot for ${title}`}
                            value={item.meal_slot}
                            icon={slot.icon}
                            disabled={isLeaving}
                            onChange={(value) => updateItem.mutate({ id: item.id, changes: { meal_slot: value as MealSlot } })}
                        >
                            {MEAL_SLOTS.map((option) => (
                                <option key={option} value={option}>
                                    {slotLabel(option)}
                                </option>
                            ))}
                        </PlanSelect>
                        <PlanSelect
                            label={`Day for ${title}`}
                            value={item.planned_for ?? UNDATED}
                            icon={CalendarDays}
                            disabled={isLeaving}
                            onChange={(value) =>
                                updateItem.mutate({ id: item.id, changes: { planned_for: value === UNDATED ? null : value } })
                            }
                        >
                            <option value={UNDATED}>Not dated</option>
                            {days.map((day) => (
                                <option key={day} value={day}>
                                    {dayOptionLabel(day)}
                                </option>
                            ))}
                        </PlanSelect>
                    </div>
                    {(updateItem.isError || removeItem.isError) && (
                        <p className="mt-2 text-[11px] text-hot" role="status">
                            That change didn’t save — the dish is back as it was.
                        </p>
                    )}
                    <div className="mt-2.5 flex items-center justify-between gap-2">
                        <span className="text-[10px] font-semibold tracking-[0.14em] text-ink-3 uppercase">Cook for</span>
                        <QuantityStepper
                            value={item.servings}
                            onChange={(value) => updateItem.mutate({ id: item.id, changes: { servings: value } })}
                            min={1}
                            max={50}
                            size="sm"
                            disabled={isLeaving}
                            ariaLabel={`Servings of ${title}`}
                        />
                    </div>
                </>
            )}
        </li>
    );
};
