import React, { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQueries, useQuery } from '@tanstack/react-query';
import { AlertTriangle, CalendarDays, CalendarPlus } from 'lucide-react';
import { ApiError } from '../api/client';
import { mealPlanApi } from '../api/mealPlan';
import { Button } from '../components/ui/Button';
import { Modal } from '../components/ui/Modal';
import { Pagination } from '../components/ui/Pagination';
import { EmptyState } from '../components/common/EmptyState';
import { LoadingSpinner } from '../components/common/LoadingSpinner';
import { StatusPanel } from '../components/common/StatusPanel';
import { CalendarDay, PlanCalendar } from '../features/meal-plan/PlanCalendar';
import { PlanRangeForm } from '../features/meal-plan/PlanRangeForm';
import { PlanSummaryCard } from '../features/meal-plan/PlanSummaryCard';
import { PlanView, PlanViewSwitcher, planViewPanelId, planViewTabId } from '../features/meal-plan/PlanViewSwitcher';
import { IsoDate, addDays, endOfMonth, listRange, startOfMonth, todayIso } from '../features/meal-plan/dates';
import { planKeys, useCreatePlan, usePlanHistory } from '../features/meal-plan/useMealPlan';

const PER_PAGE = 12;

/** Every plan the cook has made, as a month calendar or as a stack of cards. */
export const PlanHistoryPage: React.FC = () => {
    const navigate = useNavigate();
    const createPlan = useCreatePlan();

    const [view, setView] = useState<PlanView>('calendar');
    const [page, setPage] = useState(1);
    const [month, setMonth] = useState<IsoDate>(() => startOfMonth(todayIso()));
    const [newPlanStart, setNewPlanStart] = useState<IsoDate | null>(null);

    const history = usePlanHistory(page, PER_PAGE);
    const plans = history.data?.data ?? [];
    const meta = history.data?.meta;

    /** The calendar needs more than one page of rows to colour a month in. */
    const sweep = useQuery({
        queryKey: ['plans', 'calendar'],
        queryFn: () => mealPlanApi.listPlans({ per_page: 50 }),
        staleTime: 1000 * 60,
    });

    const gridStart = addDays(startOfMonth(month), -7);
    const gridEnd = addDays(endOfMonth(month), 7);
    const overlapping = useMemo(
        () => (sweep.data?.data ?? []).filter((plan) => plan.starts_on <= gridEnd && plan.ends_on >= gridStart),
        [sweep.data, gridStart, gridEnd],
    );

    const details = useQueries({
        queries: overlapping.map((plan) => ({
            queryKey: planKeys.plan(plan.id),
            queryFn: () => mealPlanApi.getPlan(plan.id),
            staleTime: 1000 * 60,
        })),
    });

    const calendarDays = useMemo(() => {
        const map: Record<IsoDate, CalendarDay> = {};

        /** The list arrives active-first, newest-first; applying it backwards lets the newest win a shared day. */
        for (const plan of [...overlapping].reverse()) {
            const from = plan.starts_on > gridStart ? plan.starts_on : gridStart;
            const to = plan.ends_on < gridEnd ? plan.ends_on : gridEnd;
            for (const day of listRange(from, to)) {
                map[day] = { dishes: 0, planId: plan.id, planName: plan.name, isActive: plan.is_active };
            }
        }

        for (const detail of details) {
            const plan = detail.data?.data;
            if (!plan) continue;
            for (const item of plan.items) {
                if (!item.planned_for) continue;
                const entry = map[item.planned_for];
                if (entry && entry.planId === plan.id) entry.dishes += 1;
            }
        }

        return map;
    }, [overlapping, details, gridStart, gridEnd]);

    const openDay = (iso: IsoDate, day: CalendarDay | undefined) => {
        if (day) {
            navigate(day.isActive ? '/meal-plan' : `/plans/${day.planId}`);
            return;
        }
        if (iso >= todayIso()) setNewPlanStart(iso);
    };

    if (history.isLoading) return <LoadingSpinner message="Gathering your plans…" />;
    if (history.error) {
        return <StatusPanel icon={AlertTriangle} tone="danger" title="Couldn’t load your plans" text="Refresh the page or try again in a moment." />;
    }

    const total = meta?.total ?? plans.length;

    return (
        <div className="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
            <header className="flex animate-slide-up flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div className="max-w-2xl">
                    <p className="text-xs font-semibold tracking-[0.22em] text-primary uppercase">Your plans</p>
                    <h1 className="mt-3 font-display text-4xl font-semibold tracking-tight text-ink text-balance sm:text-5xl lg:text-6xl">Every week you’ve cooked.</h1>
                    <p className="mt-4 text-base text-ink-2 sm:text-lg">
                        {total === 0
                            ? 'Plan a stretch of days, fill it with dishes, and it stays here for good.'
                            : `${total} ${total === 1 ? 'plan' : 'plans'} so far. Tap a day to open the plan it belonged to.`}
                    </p>
                </div>
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center lg:shrink-0">
                    <PlanViewSwitcher value={view} onChange={setView} />
                    <Button size="lg" onClick={() => setNewPlanStart(todayIso())}>
                        <CalendarPlus className="size-4" aria-hidden="true" />
                        Start a new plan
                    </Button>
                </div>
            </header>

            <div className="mt-8" role="tabpanel" id={planViewPanelId(view)} aria-labelledby={planViewTabId(view)} tabIndex={-1}>
                {view === 'calendar' ? (
                    <PlanCalendar
                        month={month}
                        onMonthChange={setMonth}
                        days={calendarDays}
                        onSelectDay={openDay}
                        isLoading={sweep.isLoading || details.some((detail) => detail.isLoading)}
                    />
                ) : plans.length === 0 ? (
                    <EmptyState
                        icon={CalendarDays}
                        title="No plans yet"
                        description="Your first plan starts the moment you pick a few days and drop dishes onto them."
                        actionLabel="Start a new plan"
                        onAction={() => setNewPlanStart(todayIso())}
                    />
                ) : (
                    <>
                        <ul className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                            {plans.map((plan) => (
                                <PlanSummaryCard key={plan.id} plan={plan} />
                            ))}
                        </ul>
                        {meta && meta.last_page > 1 && <Pagination currentPage={meta.current_page} totalPages={meta.last_page} onPageChange={setPage} className="mt-8" />}
                    </>
                )}
            </div>

            {view === 'calendar' && (
                <p className="mt-4 text-center text-xs text-ink-3">
                    A day with no plan on it is a day you can start one: tap it and the new plan begins there.
                </p>
            )}

            <Modal isOpen={newPlanStart !== null} onClose={() => setNewPlanStart(null)} title="Start a new plan" maxWidth="md">
                <p className="mb-5 text-sm text-ink-2">
                    The new plan becomes the one you’re cooking from. Your current plan stays here in the history with its shopping list.
                </p>
                <PlanRangeForm
                    idPrefix="new-plan"
                    withName
                    initialStart={newPlanStart ?? todayIso()}
                    initialEnd={addDays(newPlanStart ?? todayIso(), 6)}
                    submitLabel="Create the plan"
                    isPending={createPlan.isPending}
                    errorMessage={createPlan.isError ? (createPlan.error instanceof ApiError ? createPlan.error.message : 'That plan couldn’t be created.') : null}
                    onSubmit={(values) =>
                        createPlan.mutate(values, {
                            onSuccess: () => {
                                setNewPlanStart(null);
                                navigate('/meal-plan');
                            },
                        })
                    }
                    onCancel={() => setNewPlanStart(null)}
                />
            </Modal>
        </div>
    );
};
