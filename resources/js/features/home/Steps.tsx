import React from 'react';
import { CalendarDays, Search, ShoppingBasket, type LucideIcon } from 'lucide-react';
import { Reveal } from '../../components/motion/Reveal';
import { SectionHeader } from './SectionHeader';
import { Mark } from '../../components/layout/Logo';

const STEPS: Array<{ number: string; title: string; text: string; accent: string; icon: LucideIcon }> = [
    {
        number: '01',
        title: 'Find something worth cooking',
        text: 'Search by dish, ingredient or cuisine. Ratings use a Bayesian average, so one glowing review can’t game the list.',
        accent: 'text-primary',
        icon: Search,
    },
    {
        number: '02',
        title: 'Drop it into your week',
        text: 'One tap adds a recipe to your plan. Scale the servings and every ingredient scales with it.',
        accent: 'text-turmeric',
        icon: CalendarDays,
    },
    {
        number: '03',
        title: 'Shop one merged list',
        text: 'Ingredients across the whole plan are merged by unit, so two onions here and one there become a single line.',
        accent: 'text-mint',
        icon: ShoppingBasket,
    },
];

export const Steps: React.FC = () => (
    <section className="relative overflow-hidden border-y border-line bg-surface py-16 lg:py-24" aria-labelledby="how-heading">
        <Mark className="pointer-events-none absolute -right-24 -bottom-24 size-[28rem] opacity-[0.06] lg:-right-12" />
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <SectionHeader id="how-heading" eyebrow="How it works" title="From craving to cart, in three taps." />
            <ol className="mt-12 grid grid-cols-1 gap-10 lg:grid-cols-3 lg:gap-8">
                {STEPS.map(({ number, title, text, accent, icon: Icon }, index) => (
                    <Reveal as="li" key={number} delay={index * 120} className="relative border-t border-line pt-6">
                        <div className="flex items-center justify-between">
                            <span className={`font-display text-6xl leading-none font-medium italic ${accent}`}>{number}</span>
                            <span className="inline-flex size-11 items-center justify-center rounded-full border border-line bg-surface-2 text-ink-2">
                                <Icon className="size-5" aria-hidden="true" />
                            </span>
                        </div>
                        <h3 className="mt-6 font-display text-2xl font-semibold text-ink">{title}</h3>
                        <p className="mt-3 text-base leading-relaxed text-ink-2">{text}</p>
                    </Reveal>
                ))}
            </ol>
        </div>
    </section>
);
