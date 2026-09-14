import React from 'react';
import { ArrowRight, CalendarRange, ShoppingBasket, Users, Utensils } from 'lucide-react';
import { MealPlanSummary } from '../../types/api';
import { Badge } from '../../components/ui/Badge';
import { ButtonLink } from '../../components/ui/Button';
import { IsoDate, formatRange, todayIso } from './dates';

export type PlanStatus = 'active' | 'upcoming' | 'past';

/** Active is what the API says; the rest is decided by the plan's last day. */
export function planStatus(plan: Pick<MealPlanSummary, 'is_active' | 'ends_on'>, reference: IsoDate = todayIso()): PlanStatus {
    if (plan.is_active) return 'active';
    return plan.ends_on < reference ? 'past' : 'upcoming';
}

const STATUS_LABEL: Record<PlanStatus, string> = { active: 'Cooking now', upcoming: 'Coming up', past: 'Cooked' };

/** One plan in the history list: its range, what was in it and how far the shopping got. */
export const PlanSummaryCard: React.FC<{ plan: MealPlanSummary }> = ({ plan }) => {
    const status = planStatus(plan);
    const shoppingTotal = plan.shopping_items_count;
    const shoppingDone = plan.shopping_checked_count;
    const percent = shoppingTotal > 0 ? Math.round((shoppingDone / shoppingTotal) * 100) : 0;
    const to = plan.is_active ? '/meal-plan' : `/plans/${plan.id}`;

    const stats = [
        { icon: CalendarRange, label: plan.day_count === 1 ? 'day' : 'days', value: plan.day_count },
        { icon: Utensils, label: plan.items_count === 1 ? 'dish' : 'dishes', value: plan.items_count },
        { icon: Users, label: 'servings', value: plan.servings_total },
    ];

    return (
        <li className={`flex flex-col gap-4 rounded-3xl border p-5 sm:p-6 ${status === 'active' ? 'border-primary/50 bg-primary-soft/25' : 'border-line bg-surface'}`}>
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge variant={status === 'active' ? 'primary' : status === 'upcoming' ? 'cuisine' : 'default'}>{STATUS_LABEL[status]}</Badge>
                        {plan.cuisines.slice(0, 2).map((cuisine) => (
                            <Badge key={cuisine} variant="category">
                                {cuisine}
                            </Badge>
                        ))}
                        {plan.cuisines.length > 2 && <span className="text-[11px] text-ink-3">+{plan.cuisines.length - 2} more</span>}
                    </div>
                    <h3 className="mt-2.5 font-display text-xl leading-tight font-semibold text-ink">{plan.name}</h3>
                    <p className="mt-1 text-sm text-ink-2">{formatRange(plan.starts_on, plan.ends_on)}</p>
                </div>
            </div>

            <dl className="flex flex-wrap gap-x-5 gap-y-2">
                {stats.map(({ icon: Icon, label, value }) => (
                    <div key={label} className="flex items-baseline gap-1.5">
                        <Icon className="size-3.5 self-center text-ink-3" aria-hidden="true" />
                        <dd className="font-display text-lg font-semibold text-ink tabular-nums">{value}</dd>
                        <dt className="text-xs text-ink-3">{label}</dt>
                    </div>
                ))}
            </dl>

            <div>
                <div className="flex items-center justify-between gap-2 text-xs">
                    <span className="inline-flex items-center gap-1.5 text-ink-2">
                        <ShoppingBasket className="size-3.5 text-mint" aria-hidden="true" />
                        Shopping list
                    </span>
                    <span className="text-ink-3 tabular-nums">{shoppingTotal > 0 ? `${shoppingDone} / ${shoppingTotal} ticked` : 'Not built yet'}</span>
                </div>
                <div
                    className="mt-2 h-2 overflow-hidden rounded-full bg-surface-3"
                    role="progressbar"
                    aria-valuenow={percent}
                    aria-valuemin={0}
                    aria-valuemax={100}
                    aria-label={`Shopping progress for ${plan.name}`}
                >
                    <span className="block h-full rounded-full bg-mint transition-[width] duration-500" style={{ width: `${percent}%` }} />
                </div>
            </div>

            <ButtonLink to={to} variant={status === 'active' ? 'primary' : 'secondary'} size="sm" className="self-start">
                {status === 'active' ? 'Open the planner' : 'View this plan'}
                <ArrowRight className="size-4" aria-hidden="true" />
            </ButtonLink>
        </li>
    );
};
