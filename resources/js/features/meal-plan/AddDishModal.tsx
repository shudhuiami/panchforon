import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { CalendarDays, Check, Plus, SearchX } from 'lucide-react';
import { MealSlot } from '../../types/api';
import { recipesApi } from '../../api/recipes';
import { Modal } from '../../components/ui/Modal';
import { Photo } from '../../components/ui/Photo';
import { LoadingSpinner } from '../../components/common/LoadingSpinner';
import { RecipeFallback } from '../recipes/RecipeCard';
import { SearchBox } from '../recipes/SearchBox';
import { PlanSelect, UNDATED } from './PlanSelect';
import { MEAL_SLOTS, SLOT_META, slotLabel } from './slots';
import { IsoDate, dayOptionLabel, formatDayLong } from './dates';
import { usePlanItemActions } from './useMealPlan';

export interface AddDishModalProps {
    isOpen: boolean;
    onClose: () => void;
    /** The day the dish lands on; `null` drops it in the undated tray. */
    day: IsoDate | null;
    days: IsoDate[];
    defaultSlot?: MealSlot;
}

/** Search the catalogue and drop dishes straight onto one day of the plan. */
export const AddDishModal: React.FC<AddDishModalProps> = ({ isOpen, onClose, day, days, defaultSlot = 'dinner' }) => {
    const { addItem } = usePlanItemActions();
    const [query, setQuery] = useState('');
    const [slot, setSlot] = useState<MealSlot>(defaultSlot);
    const [targetDay, setTargetDay] = useState<string>(day ?? UNDATED);
    const [addedIds, setAddedIds] = useState<number[]>([]);

    useEffect(() => {
        if (!isOpen) return;
        setTargetDay(day ?? UNDATED);
        setAddedIds([]);
    }, [isOpen, day]);

    const { data, isLoading } = useQuery({
        queryKey: ['recipes', { q: query, per_page: 8, sort: 'bayesian', page: 1 }],
        queryFn: () => recipesApi.list({ q: query || undefined, per_page: 8, sort: 'bayesian' }),
        enabled: isOpen,
        staleTime: 1000 * 60,
    });

    const recipes = data?.data ?? [];
    const SlotIcon = SLOT_META[slot].icon;

    return (
        <Modal isOpen={isOpen} onClose={onClose} title="Add a dish" maxWidth="lg">
            <p className="text-sm text-ink-2">
                {targetDay === UNDATED ? 'It will wait in the undated tray until you give it a day.' : `Planned for ${formatDayLong(targetDay)}.`}
            </p>

            <div className="mt-4 grid grid-cols-2 gap-2">
                <PlanSelect label="Day for the new dish" value={targetDay} icon={CalendarDays} onChange={setTargetDay}>
                    <option value={UNDATED}>Not dated</option>
                    {days.map((option) => (
                        <option key={option} value={option}>
                            {dayOptionLabel(option)}
                        </option>
                    ))}
                </PlanSelect>
                <PlanSelect label="Meal slot for the new dish" value={slot} icon={SlotIcon} onChange={(value) => setSlot(value as MealSlot)}>
                    {MEAL_SLOTS.map((option) => (
                        <option key={option} value={option}>
                            {slotLabel(option)}
                        </option>
                    ))}
                </PlanSelect>
            </div>

            <div className="mt-4">
                <SearchBox onSubmit={setQuery} initialValue={query} placeholder="Search the catalogue…" />
            </div>

            {isLoading ? (
                <LoadingSpinner message="Finding dishes…" className="py-10" />
            ) : recipes.length === 0 ? (
                <div className="mt-6 rounded-2xl border border-dashed border-line-strong px-4 py-10 text-center">
                    <SearchX className="mx-auto size-6 text-ink-3" aria-hidden="true" />
                    <p className="mt-2 text-sm text-ink-2">{query ? `Nothing matched “${query}”.` : 'No recipes to show yet.'}</p>
                </div>
            ) : (
                <ul className="mt-4 space-y-2">
                    {recipes.map((recipe) => {
                        const added = addedIds.includes(recipe.id);
                        const isAdding = addItem.isPending && addItem.variables?.recipe_id === recipe.id;

                        return (
                            <li key={recipe.id} className="flex items-center gap-3 rounded-2xl border border-line bg-surface-2 p-2.5">
                                <Link to={`/recipes/${recipe.slug}`} tabIndex={-1} aria-hidden="true" className="block size-12 shrink-0 overflow-hidden rounded-xl bg-surface-3">
                                    <Photo src={recipe.image_url} alt="" loading="lazy" className="h-full w-full object-cover" fallback={<RecipeFallback recipe={recipe} />} />
                                </Link>
                                <div className="min-w-0 flex-1">
                                    <Link to={`/recipes/${recipe.slug}`} className="line-clamp-1 block font-display text-[15px] font-semibold text-ink transition-colors hover:text-primary">
                                        {recipe.title}
                                    </Link>
                                    <p className="text-[11px] text-ink-3">{[recipe.cuisine, `${recipe.servings} servings`].filter(Boolean).join(' · ')}</p>
                                </div>
                                <button
                                    type="button"
                                    disabled={isAdding}
                                    onClick={() =>
                                        addItem.mutate(
                                            {
                                                recipe_id: recipe.id,
                                                servings: recipe.servings || 4,
                                                planned_for: targetDay === UNDATED ? null : targetDay,
                                                meal_slot: slot,
                                            },
                                            { onSuccess: () => setAddedIds((current) => [...current, recipe.id]) },
                                        )
                                    }
                                    aria-label={added ? `${recipe.title} added to the plan` : `Add ${recipe.title} to the plan`}
                                    className={`inline-flex h-10 shrink-0 cursor-pointer items-center gap-1.5 rounded-full px-4 text-xs font-semibold transition-colors disabled:opacity-60 focus-visible:outline-3 focus-visible:outline-primary focus-visible:outline-offset-2 ${
                                        added ? 'bg-mint-soft text-mint' : 'bg-primary text-on-primary hover:bg-primary-hover'
                                    }`}
                                >
                                    {added ? <Check className="size-4 stroke-[3]" aria-hidden="true" /> : <Plus className="size-4 stroke-[3]" aria-hidden="true" />}
                                    <span aria-hidden="true">{added ? 'Added' : 'Add'}</span>
                                </button>
                            </li>
                        );
                    })}
                </ul>
            )}

            {addItem.isError && <p className="mt-3 text-sm text-hot">That dish couldn’t be added just now. Please try again.</p>}

            <p className="mt-5 text-center text-xs text-ink-3">
                Looking for something specific?{' '}
                <Link to="/recipes" className="font-semibold text-primary hover:underline">
                    Browse the whole catalogue
                </Link>
            </p>
        </Modal>
    );
};
