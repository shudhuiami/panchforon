import React from 'react';
import { ShoppingListItem } from '../../types/api';
import { ShoppingListItemRow } from './ShoppingListItemRow';
import { Scale, Droplets, Hash, Sparkles } from 'lucide-react';

interface ShoppingListGroupProps {
    title: string;
    items: ShoppingListItem[];
    icon?: 'mass' | 'volume' | 'count' | 'unmerged';
    description?: string;
    /** Optional index used to cycle the header chip through the spice palette. */
    index?: number;
}

const spiceTints = [
    { chip: 'bg-saffron text-ink', soft: 'bg-saffron-soft text-saffron-deep', bar: 'bg-saffron' },
    { chip: 'bg-plum text-white', soft: 'bg-plum-soft text-plum-deep', bar: 'bg-plum' },
    { chip: 'bg-mint text-ink', soft: 'bg-mint-soft text-mint-deep', bar: 'bg-mint' },
    { chip: 'bg-chili text-white', soft: 'bg-chili-soft text-chili-deep', bar: 'bg-chili' },
    { chip: 'bg-turmeric text-ink', soft: 'bg-turmeric-soft text-turmeric-deep', bar: 'bg-turmeric' },
];

const iconDefaultIndex: Record<NonNullable<ShoppingListGroupProps['icon']>, number> = {
    mass: 0,
    volume: 1,
    count: 2,
    unmerged: 3,
};

export const ShoppingListGroup: React.FC<ShoppingListGroupProps> = ({
    title,
    items,
    icon = 'count',
    description,
    index,
}) => {
    if (items.length === 0) return null;

    const tint = spiceTints[(index ?? iconDefaultIndex[icon]) % spiceTints.length];
    const isUnmergedGroup = icon === 'unmerged';

    const getIcon = () => {
        switch (icon) {
            case 'mass':
                return <Scale className="w-5 h-5" />;
            case 'volume':
                return <Droplets className="w-5 h-5" />;
            case 'unmerged':
                return <Sparkles className="w-5 h-5" />;
            default:
                return <Hash className="w-5 h-5" />;
        }
    };

    const checkedCount = items.filter((i) => i.is_checked).length;
    const allDone = checkedCount === items.length;

    return (
        <section
            className={`relative bg-paper rounded-3xl p-5 sm:p-7 space-y-5 border-2 border-line shadow-sm overflow-hidden animate-slide-up ${
                isUnmergedGroup ? 'bg-stripes' : ''
            }`}
            aria-labelledby={`shopping-group-${icon}-${index ?? 0}`}
        >
            <div className={`absolute top-0 left-0 right-0 h-1.5 ${tint.bar}`} aria-hidden="true" />

            <header className="flex items-start sm:items-center justify-between gap-4 flex-wrap">
                <div className="flex items-center gap-3.5 min-w-0">
                    <div className={`w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 sticker shadow-sm ${tint.chip}`}>
                        {getIcon()}
                    </div>
                    <div className="min-w-0">
                        <h3
                            id={`shopping-group-${icon}-${index ?? 0}`}
                            className="font-display font-extrabold text-lg sm:text-xl text-ink leading-tight"
                        >
                            {title}
                        </h3>
                        {description && (
                            <p className="text-xs text-ink-2 mt-1 leading-snug">{description}</p>
                        )}
                    </div>
                </div>

                <span
                    className={`inline-flex items-center gap-1.5 text-xs font-extrabold px-3.5 py-1.5 rounded-full tabular-nums transition-colors ${
                        allDone ? 'bg-mint text-ink' : tint.soft
                    }`}
                >
                    {allDone ? 'All done' : `${checkedCount}/${items.length}`}
                    <span className="font-bold opacity-70">{items.length === 1 ? 'item' : 'items'}</span>
                </span>
            </header>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                {items.map((item) => (
                    <ShoppingListItemRow key={item.id} item={item} />
                ))}
            </div>
        </section>
    );
};
