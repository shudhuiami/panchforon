import React, { useMemo, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import {
    AlertTriangle,
    CalendarRange,
    EyeOff,
    History,
    PenLine,
    Plus,
    Scale,
    ShoppingBasket,
    SplitSquareHorizontal,
    Utensils,
    type LucideIcon,
} from 'lucide-react';
import { ApiError } from '../api/client';
import { MealPlanItem } from '../types/api';
import { Button } from '../components/ui/Button';
import { Alert } from '../components/ui/Alert';
import { LoadingSpinner } from '../components/common/LoadingSpinner';
import { EmptyState } from '../components/common/EmptyState';
import { StatusPanel } from '../components/common/StatusPanel';
import { Reveal } from '../components/motion/Reveal';
import { AddDishModal } from '../features/meal-plan/AddDishModal';
import { PlanDayColumn } from '../features/meal-plan/PlanDayColumn';
import { PlanRangeForm } from '../features/meal-plan/PlanRangeForm';
import { PlanUndatedTray } from '../features/meal-plan/PlanUndatedTray';
import { PlanDragProvider, PlanMoveAlert } from '../features/meal-plan/planDrag';
import { IsoDate, clampIso, formatRange, listRange, todayIso } from '../features/meal-plan/dates';
import { useGenerateShoppingList, useMealPlan, useUpdatePlan } from '../features/meal-plan/useMealPlan';

const MERGE_RULES: Array<{ icon: LucideIcon; tone: string; title: string; text: string }> = [
    { icon: Scale, tone: 'text-primary', title: 'Scaled', text: 'Portions multiply cleanly with the servings you set here.' },
    { icon: SplitSquareHorizontal, tone: 'text-plum', title: 'Kept apart', text: 'Weights never merge with volumes; 200 g of chicken stays separate from a cup of it.' },
    { icon: PenLine, tone: 'text-turmeric', title: 'As written', text: 'Pinches and “to taste” lines stay exactly as the cook wrote them.' },
];

export const MealPlanPage: React.FC = () => {
    const navigate = useNavigate();
    const { data, isLoading, error } = useMealPlan();
    const updatePlan = useUpdatePlan();
    const generateList = useGenerateShoppingList();

    const [isEditingRange, setIsEditingRange] = useState(false);
    const [hideEmptyDays, setHideEmptyDays] = useState(false);
    const [addTarget, setAddTarget] = useState<{ day: IsoDate | null } | null>(null);

    const plan = data?.data;
    const items = useMemo(() => plan?.items ?? [], [plan]);
    const days = useMemo(() => (plan ? listRange(plan.starts_on, plan.ends_on) : []), [plan]);

    const byDay = useMemo(() => {
        const map = new Map<string, MealPlanItem[]>();
        for (const item of items) {
            const key = item.planned_for ?? '';
            const bucket = map.get(key);
            if (bucket) bucket.push(item);
            else map.set(key, [item]);
        }
        return map;
    }, [items]);

    if (isLoading) return <LoadingSpinner message="Loading your plan…" />;
    if (error || !plan) {
        return <StatusPanel icon={AlertTriangle} tone="danger" title="Couldn’t load your plan" text="Refresh the page or try again in a moment." />;
    }

    /** Undated dishes, plus any the API left pointing at a day this range no longer covers. */
    const dayKeys = new Set(days);
    const undated = [...byDay.entries()].filter(([key]) => key === '' || !dayKeys.has(key)).flatMap(([, bucket]) => bucket);
    const scheduledCount = items.length - undated.length;
    const totalServings = items.reduce((sum, item) => sum + (item.servings || 0), 0);
    const visibleDays = hideEmptyDays ? days.filter((day) => (byDay.get(day)?.length ?? 0) > 0) : days;

    const stats = [
        { value: plan.day_count, label: plan.day_count === 1 ? 'day' : 'days' },
        { value: items.length, label: items.length === 1 ? 'dish' : 'dishes' },
        { value: totalServings, label: 'servings' },
        ...(undated.length > 0 ? [{ value: undated.length, label: 'not dated' }] : []),
    ];

    /** A plan already under way keeps its own first day as the floor; a future one can only come forward to today. */
    const rangeFloor = plan.starts_on < todayIso() ? plan.starts_on : todayIso();
    const defaultAddDay = clampIso(todayIso(), plan.starts_on, plan.ends_on);

    return (
        <div className="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
            <header className="flex animate-slide-up flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div className="max-w-2xl">
                    <div className="flex flex-wrap items-center gap-x-3 gap-y-2">
                        <p className="text-xs font-semibold tracking-[0.22em] text-primary uppercase">Meal plan</p>
                        <Link to="/plans" className="inline-flex items-center gap-1.5 text-xs font-semibold text-ink-3 transition-colors hover:text-ink">
                            <History className="size-3.5" aria-hidden="true" />
                            All your plans
                        </Link>
                    </div>
                    <h1 className="mt-3 font-display text-4xl font-semibold tracking-tight text-ink text-balance sm:text-5xl lg:text-6xl">{plan.name}</h1>

                    <div className="mt-4 flex flex-wrap items-center gap-3">
                        <span className="inline-flex items-center gap-2 rounded-full border border-line bg-surface px-3.5 py-2 text-sm font-medium text-ink-2">
                            <CalendarRange className="size-4 text-primary" aria-hidden="true" />
                            {formatRange(plan.starts_on, plan.ends_on)}
                        </span>
                        <Button variant="ghost" size="sm" onClick={() => setIsEditingRange((open) => !open)} aria-expanded={isEditingRange} aria-controls="plan-range-editor">
                            <PenLine className="size-4" aria-hidden="true" />
                            {isEditingRange ? 'Close the dates' : 'Change the dates'}
                        </Button>
                    </div>

                    <dl className="mt-5 flex flex-wrap gap-x-6 gap-y-2">
                        {stats.map(({ value, label }) => (
                            <div key={label} className="flex items-baseline gap-1.5">
                                <dd className="font-display text-2xl font-semibold text-ink tabular-nums">{value}</dd>
                                <dt className="text-sm text-ink-3">{label}</dt>
                            </div>
                        ))}
                    </dl>
                </div>

                <div className="flex flex-col gap-3 sm:flex-row lg:shrink-0">
                    {items.length > 0 && (
                        <Button
                            size="lg"
                            onClick={() => generateList.mutate(undefined, { onSuccess: () => navigate('/shopping-list') })}
                            isLoading={generateList.isPending}
                        >
                            <ShoppingBasket className="size-4" aria-hidden="true" />
                            Build the shopping list
                        </Button>
                    )}
                    <Button variant="outline" size="lg" onClick={() => setAddTarget({ day: defaultAddDay })}>
                        <Plus className="size-4" aria-hidden="true" />
                        Add dishes
                    </Button>
                </div>
            </header>

            {generateList.isError && (
                <Alert variant="error" className="mt-6">
                    The shopping list couldn’t be built just now. Please try again.
                </Alert>
            )}

            {isEditingRange && (
                <section id="plan-range-editor" className="mt-6 rounded-3xl border border-line bg-surface p-5 sm:p-7" aria-label="Plan dates">
                    <h2 className="font-display text-xl font-semibold text-ink">When are you cooking?</h2>
                    <p className="mt-1 mb-5 text-sm text-ink-2">
                        Dishes that fall outside a shorter range aren’t lost — they move to the undated tray at the top of the plan.
                    </p>
                    <PlanRangeForm
                        idPrefix="plan-range"
                        initialStart={plan.starts_on}
                        initialEnd={plan.ends_on}
                        minStart={rangeFloor}
                        submitLabel="Save the dates"
                        isPending={updatePlan.isPending}
                        errorMessage={updatePlan.isError ? (updatePlan.error instanceof ApiError ? updatePlan.error.message : 'Those dates couldn’t be saved.') : null}
                        onSubmit={(values) =>
                            updatePlan.mutate(
                                { id: plan.id, changes: { starts_on: values.starts_on, ends_on: values.ends_on } },
                                { onSuccess: () => setIsEditingRange(false) },
                            )
                        }
                        onCancel={() => setIsEditingRange(false)}
                    />
                </section>
            )}

            <PlanDragProvider>
                <PlanMoveAlert className="mt-6" />

                {undated.length > 0 && <PlanUndatedTray items={undated} days={days} />}

                <section className="mt-8" aria-label="Days in this plan">
                    {items.length === 0 ? (
                        <EmptyState
                            icon={Utensils}
                            title="Nothing planned yet"
                            description="Pick the days you’re cooking for, then drop a dish onto each one. Every dish you add here ends up on one shopping list."
                            actionLabel="Find dishes"
                            onAction={() => setAddTarget({ day: defaultAddDay })}
                        />
                    ) : (
                        <>
                            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <h2 className="font-display text-2xl font-semibold text-ink">
                                        {scheduledCount} {scheduledCount === 1 ? 'dish' : 'dishes'} across {plan.day_count} {plan.day_count === 1 ? 'day' : 'days'}
                                    </h2>
                                    <p className="mt-1 hidden text-sm text-ink-3 pointer-fine:block">
                                        Drag a dish onto another day to move it, or use the day menu on the card.
                                    </p>
                                </div>
                                {days.length > 7 && (
                                    <Button variant="ghost" size="sm" onClick={() => setHideEmptyDays((hidden) => !hidden)} aria-pressed={hideEmptyDays}>
                                        <EyeOff className="size-4" aria-hidden="true" />
                                        {hideEmptyDays ? 'Show every day' : 'Hide empty days'}
                                    </Button>
                                )}
                            </div>
                            {visibleDays.length === 0 ? (
                                <p className="rounded-3xl border border-dashed border-line-strong bg-surface p-8 text-center text-sm text-ink-3">
                                    No day has a dish on it yet.
                                </p>
                            ) : (
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                                    {visibleDays.map((day) => (
                                        <PlanDayColumn key={day} iso={day} items={byDay.get(day) ?? []} days={days} onAddDish={(iso) => setAddTarget({ day: iso })} />
                                    ))}
                                </div>
                            )}
                        </>
                    )}
                </section>
            </PlanDragProvider>

            <Reveal as="section" className="mt-12 rounded-3xl border border-line bg-surface p-6 sm:p-8" aria-labelledby="merge-heading">
                <h2 id="merge-heading" className="font-display text-2xl font-semibold text-ink">
                    How the shopping list is built
                </h2>
                <ul className="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-3">
                    {MERGE_RULES.map(({ icon: Icon, tone, title, text }) => (
                        <li key={title} className="flex gap-3">
                            <span className="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-surface-2">
                                <Icon className={`size-4 ${tone}`} aria-hidden="true" />
                            </span>
                            <span>
                                <span className="block font-semibold text-ink">{title}</span>
                                <span className="mt-1 block text-sm text-ink-2">{text}</span>
                            </span>
                        </li>
                    ))}
                </ul>
            </Reveal>

            <AddDishModal isOpen={addTarget !== null} onClose={() => setAddTarget(null)} day={addTarget?.day ?? null} days={days} />
        </div>
    );
};
