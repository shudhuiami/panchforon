import React from 'react';
import { HomeStats } from '../../types/api';
import { useInView } from '../../components/motion/useInView';
import { useCountUp } from '../../components/motion/useCountUp';

const Stat: React.FC<{ value: number; label: string; accent: string; active: boolean }> = ({ value, label, accent, active }) => {
    const shown = useCountUp(value, active);

    return (
        <div className="flex items-baseline gap-2 px-4 py-5 sm:px-6 lg:py-7">
            <span className={`font-display text-4xl font-semibold tabular-nums lg:text-5xl ${accent}`}>{shown.toLocaleString()}</span>
            <span className="text-sm text-ink-2">{label}</span>
        </div>
    );
};

/** Live numbers, counting up as they scroll in. */
export const StatsStrip: React.FC<{ stats?: HomeStats }> = ({ stats }) => {
    const { ref, inView } = useInView<HTMLElement>({ rootMargin: '0px', threshold: 0.4 });
    if (!stats) return null;

    const items = [
        { value: stats.recipes, label: 'recipes', accent: 'text-primary' },
        { value: stats.cuisines, label: 'cuisines', accent: 'text-turmeric' },
        { value: stats.ratings, label: 'ratings', accent: 'text-mint' },
        { value: stats.cooks, label: 'home cooks', accent: 'text-plum' },
    ];

    return (
        <section ref={ref} className="border-y border-line bg-surface/60" aria-label="Community in numbers">
            <div className="mx-auto grid max-w-7xl grid-cols-2 divide-x divide-line lg:grid-cols-4 lg:px-8">
                {items.map((item) => (
                    <Stat key={item.label} {...item} active={inView} />
                ))}
            </div>
        </section>
    );
};
