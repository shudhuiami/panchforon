import React from 'react';
import { CalendarDays, ShoppingBasket, Star, type LucideIcon } from 'lucide-react';

const PROMISES: Array<{ icon: LucideIcon; tone: string; text: string }> = [
    { icon: CalendarDays, tone: 'text-primary', text: 'Plan the week in a tap and scale every dish to the table.' },
    { icon: ShoppingBasket, tone: 'text-mint', text: 'One shopping list, merged by unit, nothing bought twice.' },
    { icon: Star, tone: 'text-turmeric', text: 'Rate what you cooked so the ranking stays honest.' },
];

interface AuthLayoutProps {
    eyebrow: string;
    title: string;
    blurb: string;
    children: React.ReactNode;
}

/** The sign-in and registration frame: a statement panel beside the form card. */
export const AuthLayout: React.FC<AuthLayoutProps> = ({ eyebrow, title, blurb, children }) => (
    <div className="mx-auto grid w-full max-w-6xl gap-6 px-4 py-6 sm:px-6 lg:grid-cols-[1.05fr_1fr] lg:items-stretch lg:gap-10 lg:py-14">
        <aside className="relative hidden animate-fade-in overflow-hidden rounded-[2rem] border border-line bg-surface p-10 lg:flex lg:flex-col lg:justify-between xl:p-14" aria-hidden="true">
            <div className="aurora top-[-30%] left-[-20%] size-[28rem] animate-aurora-a bg-primary/40" />
            <div className="aurora right-[-25%] bottom-[-30%] size-[26rem] animate-aurora-b bg-plum/35" />
            <div className="absolute inset-0 bg-dots opacity-20" />
            <p className="relative font-display text-5xl leading-[1.02] font-semibold tracking-tight text-ink text-balance xl:text-6xl">
                Five spices. One kitchen. Your whole week, <span className="text-spice italic">planned</span>.
            </p>
            <ul className="relative mt-12 space-y-4">
                {PROMISES.map(({ icon: Icon, tone, text }) => (
                    <li key={text} className="flex items-center gap-3 text-sm text-ink-2">
                        <span className="inline-flex size-9 shrink-0 items-center justify-center rounded-full bg-canvas/60 backdrop-blur">
                            <Icon className={`size-4 ${tone}`} />
                        </span>
                        {text}
                    </li>
                ))}
            </ul>
        </aside>

        <section className="flex animate-slide-up items-center">
            <div className="w-full rounded-[2rem] border border-line bg-surface p-6 sm:p-10">
                <p className="text-xs font-semibold tracking-[0.22em] text-primary uppercase">{eyebrow}</p>
                <h1 className="mt-3 font-display text-3xl font-semibold tracking-tight text-ink text-balance sm:text-4xl">{title}</h1>
                <p className="mt-3 text-sm text-ink-2 sm:text-base">{blurb}</p>
                <div className="mt-8">{children}</div>
            </div>
        </section>
    </div>
);
