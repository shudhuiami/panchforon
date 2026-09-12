import React from 'react';
import { Droplets, Hash, Scale, Sparkles, type LucideIcon } from 'lucide-react';
import { ShoppingListItem } from '../../types/api';
import { ShoppingListItemRow } from './ShoppingListItemRow';

export type ShoppingListGroupKind = 'mass' | 'volume' | 'count' | 'unmerged';

const KINDS: Record<ShoppingListGroupKind, { icon: LucideIcon; tone: string }> = {
    mass: { icon: Scale, tone: 'text-primary' },
    volume: { icon: Droplets, tone: 'text-plum' },
    count: { icon: Hash, tone: 'text-mint' },
    unmerged: { icon: Sparkles, tone: 'text-turmeric' },
};

interface ShoppingListGroupProps {
    kind: ShoppingListGroupKind;
    title: string;
    description?: string;
    items: ShoppingListItem[];
}

export const ShoppingListGroup: React.FC<ShoppingListGroupProps> = ({ kind, title, description, items }) => {
    if (items.length === 0) return null;

    const { icon: Icon, tone } = KINDS[kind];
    const checkedCount = items.filter((i) => i.is_checked).length;
    const allDone = checkedCount === items.length;

    return (
        <section className="animate-slide-up rounded-3xl border border-line bg-surface p-5 sm:p-7" aria-labelledby={`shopping-group-${kind}`}>
            <header className="flex flex-wrap items-center justify-between gap-4">
                <div className="flex min-w-0 items-center gap-3.5">
                    <span className="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-surface-2">
                        <Icon className={`size-5 ${tone}`} aria-hidden="true" />
                    </span>
                    <div className="min-w-0">
                        <h3 id={`shopping-group-${kind}`} className="font-display text-xl font-semibold text-ink">
                            {title}
                        </h3>
                        {description && <p className="mt-0.5 text-xs text-ink-3">{description}</p>}
                    </div>
                </div>
                <span className={`rounded-full px-3 py-1 text-xs font-semibold tabular-nums ${allDone ? 'bg-mint-soft text-mint' : 'bg-surface-2 text-ink-2'}`}>
                    {allDone ? 'All done' : `${checkedCount} / ${items.length}`}
                </span>
            </header>
            <div className="mt-5 grid grid-cols-1 gap-2 md:grid-cols-2">
                {items.map((item) => (
                    <ShoppingListItemRow key={item.id} item={item} />
                ))}
            </div>
        </section>
    );
};
