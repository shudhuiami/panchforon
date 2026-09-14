import React, { useMemo } from 'react';
import { Navigate, useNavigate, useParams } from 'react-router-dom';
import { ArrowLeft, CalendarRange, Check, CheckCheck, SearchX, ShoppingBasket, Trash2, Utensils } from 'lucide-react';
import { MealPlanItem, ShoppingListItem } from '../types/api';
import { Badge } from '../components/ui/Badge';
import { Button } from '../components/ui/Button';
import { LoadingSpinner } from '../components/common/LoadingSpinner';
import { StatusPanel } from '../components/common/StatusPanel';
import { PlanDayColumn } from '../features/meal-plan/PlanDayColumn';
import { PlanDishRow } from '../features/meal-plan/PlanDishRow';
import { planStatus } from '../features/meal-plan/PlanSummaryCard';
import { formatRange, listRange, todayIso } from '../features/meal-plan/dates';
import { useActivatePlan, useDeletePlan, usePlan, usePlanShoppingList } from '../features/meal-plan/useMealPlan';

const formatQuantity = (quantity: number | null | undefined): string => {
    if (quantity === null || quantity === undefined) return '';
    const value = Number(quantity);
    return Number.isInteger(value) ? value.toString() : value.toFixed(1).replace(/\.0$/, '');
};

/** A line of a finished list: it records what happened, so nothing here toggles. */
const PastListRow: React.FC<{ item: ShoppingListItem }> = ({ item }) => (
    <li className={`flex min-h-12 items-center gap-3 rounded-2xl border border-line bg-surface-2 px-3.5 py-2.5 ${item.is_checked ? 'opacity-60' : ''}`}>
        <span
            className={`flex size-5 shrink-0 items-center justify-center rounded-full border-2 ${item.is_checked ? 'border-mint bg-mint text-on-primary' : 'border-line-strong text-transparent'}`}
            aria-hidden="true"
        >
            <Check className="size-3 stroke-[3.5]" />
        </span>
        <span className="min-w-0 flex-1">
            <span className={`block truncate text-sm font-medium capitalize ${item.is_checked ? 'text-ink-3 line-through' : 'text-ink'}`}>{item.display_name}</span>
            <span className="sr-only">{item.is_checked ? 'bought' : 'not bought'}</span>
            {item.source_note && <span className="mt-0.5 block truncate text-[11px] text-ink-3">{item.source_note}</span>}
        </span>
        {((item.quantity !== null && item.quantity !== undefined) || item.unit) && (
            <span className="shrink-0 rounded-full bg-surface-3 px-2.5 py-1 text-xs font-semibold text-ink-2 tabular-nums">
                {formatQuantity(item.quantity)} {item.unit}
            </span>
        )}
    </li>
);

/** A plan from the history, exactly as it was cooked and shopped for. */
export const PlanDetailPage: React.FC = () => {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const planId = Number(id);
    const isValidId = Number.isInteger(planId) && planId > 0;

    const { data, isLoading, error } = usePlan(isValidId ? planId : null);
    const plan = data?.data;
    const shoppingList = usePlanShoppingList(isValidId ? planId : null, !!plan);
    const activatePlan = useActivatePlan();
    const deletePlan = useDeletePlan();

    const byDay = useMemo(() => {
        const map = new Map<string, MealPlanItem[]>();
        for (const item of plan?.items ?? []) {
            const key = item.planned_for ?? '';
            const bucket = map.get(key);
            if (bucket) bucket.push(item);
            else map.set(key, [item]);
        }
        return map;
    }, [plan]);

    if (!isValidId) {
        return <StatusPanel icon={SearchX} title="Plan not found" text="That link doesn’t point at a plan of yours." action={{ label: 'All your plans', to: '/plans' }} />;
    }
    if (isLoading) return <LoadingSpinner message="Opening the plan…" />;
    if (error || !plan) {
        return <StatusPanel icon={SearchX} title="Plan not found" text="It may have been deleted, or it belongs to someone else." action={{ label: 'All your plans', to: '/plans' }} />;
    }

    /** The planner owns the active plan; this page never edits. */
    if (plan.is_active) return <Navigate to="/meal-plan" replace />;

    const status = planStatus(plan);
    const days = listRange(plan.starts_on, plan.ends_on);
    const dayKeys = new Set(days);
    const undated = [...byDay.entries()].filter(([key]) => key === '' || !dayKeys.has(key)).flatMap(([, bucket]) => bucket);
    const items = plan.items ?? [];
    const totalServings = items.reduce((sum, item) => sum + (item.servings || 0), 0);
    const list = shoppingList.data?.data ?? [];
    const bought = list.filter((item) => item.is_checked).length;

    const stats = [
        { value: plan.day_count, label: plan.day_count === 1 ? 'day' : 'days' },
        { value: items.length, label: items.length === 1 ? 'dish' : 'dishes' },
        { value: totalServings, label: 'servings' },
    ];

    return (
        <div className="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
            <header className="animate-slide-up">
                <button
                    type="button"
                    onClick={() => navigate('/plans')}
                    className="inline-flex cursor-pointer items-center gap-1.5 text-sm text-ink-3 transition-colors hover:text-ink focus-visible:outline-3 focus-visible:outline-primary focus-visible:outline-offset-2"
                >
                    <ArrowLeft className="size-4" aria-hidden="true" />
                    All your plans
                </button>

                <div className="mt-4 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div className="max-w-2xl">
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge variant={status === 'upcoming' ? 'cuisine' : 'default'}>{status === 'upcoming' ? 'Coming up' : 'Cooked'}</Badge>
                            <Badge variant="outline">Read-only</Badge>
                        </div>
                        <h1 className="mt-3 font-display text-4xl font-semibold tracking-tight text-ink text-balance sm:text-5xl">{plan.name}</h1>
                        <p className="mt-3 inline-flex items-center gap-2 rounded-full border border-line bg-surface px-3.5 py-2 text-sm font-medium text-ink-2">
                            <CalendarRange className="size-4 text-primary" aria-hidden="true" />
                            {formatRange(plan.starts_on, plan.ends_on)}
                        </p>
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
                        {plan.ends_on >= todayIso() && (
                            <Button
                                size="lg"
                                isLoading={activatePlan.isPending}
                                onClick={() => activatePlan.mutate(plan.id, { onSuccess: () => navigate('/meal-plan') })}
                            >
                                <Utensils className="size-4" aria-hidden="true" />
                                Cook from this plan
                            </Button>
                        )}
                        <Button
                            variant="danger"
                            size="lg"
                            isLoading={deletePlan.isPending}
                            onClick={() => {
                                if (window.confirm('Delete this plan and its shopping list? This cannot be undone.')) {
                                    deletePlan.mutate(plan.id, { onSuccess: () => navigate('/plans') });
                                }
                            }}
                        >
                            <Trash2 className="size-4" aria-hidden="true" />
                            Delete
                        </Button>
                    </div>
                </div>
            </header>

            {undated.length > 0 && (
                <section className="mt-8 rounded-3xl border border-dashed border-line-strong bg-surface-2 p-5 sm:p-6" aria-labelledby="past-undated-heading">
                    <h2 id="past-undated-heading" className="font-display text-xl font-semibold text-ink">
                        Never dated
                    </h2>
                    <p className="mt-1 text-sm text-ink-2">These dishes were picked for this plan but never landed on a day.</p>
                    <ul className="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        {undated.map((item) => (
                            <PlanDishRow key={item.id} item={item} days={days} readOnly />
                        ))}
                    </ul>
                </section>
            )}

            <section className="mt-8" aria-label="Days in this plan">
                <h2 className="mb-4 font-display text-2xl font-semibold text-ink">Day by day</h2>
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                    {days.map((day) => (
                        <PlanDayColumn key={day} iso={day} items={byDay.get(day) ?? []} days={days} readOnly />
                    ))}
                </div>
            </section>

            <section className="mt-12" aria-labelledby="past-list-heading">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 id="past-list-heading" className="font-display text-2xl font-semibold text-ink">
                            The shopping list
                        </h2>
                        <p className="mt-1 text-sm text-ink-3">Saved exactly as it stood when this plan was current.</p>
                    </div>
                    {list.length > 0 && (
                        <span className="inline-flex items-center gap-2 rounded-full bg-surface-2 px-3.5 py-2 text-xs font-semibold text-ink-2 tabular-nums">
                            <CheckCheck className="size-4 text-mint" aria-hidden="true" />
                            {bought} / {list.length} ticked off
                        </span>
                    )}
                </div>

                {shoppingList.isLoading ? (
                    <LoadingSpinner message="Fetching the list…" className="py-12" />
                ) : list.length === 0 ? (
                    <p className="mt-5 flex items-center gap-3 rounded-3xl border border-dashed border-line-strong bg-surface p-6 text-sm text-ink-3">
                        <ShoppingBasket className="size-5 shrink-0 text-ink-3" aria-hidden="true" />
                        No shopping list was built for this plan.
                    </p>
                ) : (
                    <ul className="mt-5 grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-3">
                        {list.map((item) => (
                            <PastListRow key={item.id} item={item} />
                        ))}
                    </ul>
                )}
            </section>
        </div>
    );
};
