import React from 'react';
import { ExternalLink, Send, ChefHat, ArrowRight, Sparkles, Calendar, ShoppingBag, Compass, PlusCircle, Heart } from 'lucide-react';
import { Link } from 'react-router-dom';

const SPICES: { name: string; className: string }[] = [
    { name: 'Saffron', className: 'bg-saffron' },
    { name: 'Chili', className: 'bg-chili' },
    { name: 'Turmeric', className: 'bg-turmeric' },
    { name: 'Mint', className: 'bg-mint' },
    { name: 'Plum', className: 'bg-plum' },
];

const footerLinkClass =
    'group inline-flex items-center gap-2 text-sm font-bold text-ink-2 transition-colors hover:text-ink focus-visible:outline-3 focus-visible:outline-saffron rounded-md';

export const Footer: React.FC = () => {
    return (
        <footer className="mt-auto print:hidden">
            {/* ---- Top band: CTA card overlapping the link grid ---- */}
            <div className="relative z-10 px-4 pt-20 sm:px-6 lg:px-8">
                <div className="relative mx-auto max-w-7xl overflow-hidden rounded-4xl bg-ink-gradient bg-dots-light text-white shadow-xl">
                    {/* Color glows */}
                    <div className="pointer-events-none absolute -left-24 -top-24 h-72 w-72 rounded-full bg-saffron/30 blur-3xl" aria-hidden="true" />
                    <div className="pointer-events-none absolute -bottom-28 right-10 h-80 w-80 rounded-full bg-plum/40 blur-3xl" aria-hidden="true" />
                    <div className="pointer-events-none absolute -right-16 top-6 h-48 w-48 rounded-full bg-mint/25 blur-3xl" aria-hidden="true" />

                    {/* Floating stickers (desktop only) */}
                    <div
                        className="[--float-rot:-4deg] absolute right-10 top-10 hidden items-center gap-2 rounded-full bg-turmeric px-4 py-2 text-xs font-extrabold text-ink shadow-glow-turmeric animate-float lg:inline-flex"
                        aria-hidden="true"
                    >
                        <Sparkles className="h-4 w-4" /> New recipes weekly
                    </div>
                    <div
                        className="[--float-rot:3deg] absolute bottom-10 right-24 hidden items-center gap-2 rounded-full bg-mint px-4 py-2 text-xs font-extrabold text-ink shadow-glow-mint animate-float animation-delay-300 lg:inline-flex"
                        aria-hidden="true"
                    >
                        <ShoppingBag className="h-4 w-4" /> Smart grocery lists
                    </div>

                    <div className="relative grid gap-8 px-6 py-12 sm:px-10 sm:py-16 lg:grid-cols-[1.4fr_1fr] lg:px-16 lg:py-20">
                        <div>
                            <span className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[11px] font-extrabold uppercase tracking-[0.18em] text-white/80">
                                <ChefHat className="h-3.5 w-3.5 text-turmeric" />
                                Your kitchen, upgraded
                            </span>
                            <h2 className="mt-5 text-balance font-display text-4xl font-extrabold leading-[1.02] tracking-tight text-white sm:text-5xl lg:text-6xl">
                                Cook something{' '}
                                <span className="text-sunrise">wonderful</span> tonight.
                            </h2>
                            <p className="mt-5 max-w-xl text-base font-medium leading-relaxed text-white/70 sm:text-lg">
                                Pick a dish, drop it into your weekly plan, and let Panchforon write the shopping list.
                                Scaled portions, Bengali ingredient aliases, and community-rated recipes included.
                            </p>
                            <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                                <Link
                                    to="/"
                                    className="inline-flex items-center justify-center gap-2 rounded-full bg-saffron px-6 py-3.5 text-sm font-extrabold text-ink shadow-glow-saffron transition-all duration-200 hover:-translate-y-0.5 hover:bg-turmeric focus-visible:outline-3 focus-visible:outline-turmeric"
                                >
                                    <Compass className="h-4.5 w-4.5" />
                                    Browse recipes
                                    <ArrowRight className="h-4 w-4" />
                                </Link>
                                <Link
                                    to="/recipes/create"
                                    className="inline-flex items-center justify-center gap-2 rounded-full border-2 border-white/25 bg-white/5 px-6 py-3.5 text-sm font-extrabold text-white backdrop-blur transition-all duration-200 hover:-translate-y-0.5 hover:border-white/60 hover:bg-white/10 focus-visible:outline-3 focus-visible:outline-turmeric"
                                >
                                    <PlusCircle className="h-4.5 w-4.5 text-turmeric" />
                                    Share a recipe
                                </Link>
                            </div>
                        </div>

                        {/* Stacked feature chips */}
                        <ul className="grid gap-3 self-center sm:grid-cols-3 lg:grid-cols-1" aria-label="Highlights">
                            <li className="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 p-3.5 backdrop-blur-sm">
                                <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-turmeric text-ink">
                                    <Compass className="h-5 w-5" />
                                </span>
                                <div>
                                    <p className="font-display text-base font-extrabold leading-tight text-white">Discover</p>
                                    <p className="text-xs font-semibold text-white/60">Bayesian-ranked favourites</p>
                                </div>
                            </li>
                            <li className="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 p-3.5 backdrop-blur-sm lg:ml-6">
                                <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-mint text-ink">
                                    <Calendar className="h-5 w-5" />
                                </span>
                                <div>
                                    <p className="font-display text-base font-extrabold leading-tight text-white">Plan</p>
                                    <p className="text-xs font-semibold text-white/60">Seven days, one glance</p>
                                </div>
                            </li>
                            <li className="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 p-3.5 backdrop-blur-sm lg:ml-12">
                                <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-chili text-white">
                                    <ShoppingBag className="h-5 w-5" />
                                </span>
                                <div>
                                    <p className="font-display text-base font-extrabold leading-tight text-white">Shop</p>
                                    <p className="text-xs font-semibold text-white/60">Auto-merged grocery list</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {/* ---- Link grid on cream-2 ---- */}
            <div className="-mt-24 bg-cream-2 pt-40 pb-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-4 lg:gap-8">
                        {/* Column 1: Brand */}
                        <div className="space-y-4">
                            <Link to="/" className="inline-flex items-center gap-3 rounded-full focus-visible:outline-3 focus-visible:outline-saffron" aria-label="Panchforon Home">
                                <span className="flex h-11 w-11 items-center justify-center rounded-2xl bg-sunrise-gradient text-white shadow-glow-saffron">
                                    <ChefHat className="h-6 w-6" strokeWidth={2.4} />
                                </span>
                                <span className="font-display text-2xl font-extrabold tracking-tight text-ink">Panchforon</span>
                            </Link>

                            <p className="text-sm font-medium leading-relaxed text-ink-2">
                                Elevated recipe curation inspired by the timeless five-spice blend. Dimension-aware grocery
                                isolation, dynamic portion scaling, and Bayesian quality scoring.
                            </p>

                            <div className="flex flex-wrap gap-1.5 pt-1">
                                {SPICES.map((spice) => (
                                    <span
                                        key={spice.name}
                                        className="inline-flex items-center gap-1.5 rounded-full border border-line-strong bg-paper px-2.5 py-1 text-[11px] font-extrabold text-ink"
                                    >
                                        <span className={`h-2.5 w-2.5 rounded-full ${spice.className}`} aria-hidden="true" />
                                        {spice.name}
                                    </span>
                                ))}
                            </div>
                        </div>

                        {/* Column 2: Explore */}
                        <div>
                            <h4 className="mb-4 flex items-center gap-2 text-[11px] font-extrabold uppercase tracking-[0.18em] text-saffron-deep">
                                <span className="h-2 w-2 rounded-full bg-saffron" aria-hidden="true" />
                                Explore
                            </h4>
                            <ul className="space-y-3">
                                <li>
                                    <Link to="/" className={footerLinkClass}>
                                        <Compass className="h-4 w-4 text-ink-3 transition-colors group-hover:text-saffron-deep" />
                                        Discover Recipes
                                    </Link>
                                </li>
                                <li>
                                    <Link to="/meal-plan" className={footerLinkClass}>
                                        <Calendar className="h-4 w-4 text-ink-3 transition-colors group-hover:text-mint-deep" />
                                        Weekly Meal Planner
                                    </Link>
                                </li>
                                <li>
                                    <Link to="/shopping-list" className={footerLinkClass}>
                                        <ShoppingBag className="h-4 w-4 text-ink-3 transition-colors group-hover:text-plum-deep" />
                                        Smart Grocery List
                                    </Link>
                                </li>
                                <li>
                                    <Link to="/recipes/create" className={footerLinkClass}>
                                        <PlusCircle className="h-4 w-4 text-ink-3 transition-colors group-hover:text-chili-deep" />
                                        Post Community Recipe
                                    </Link>
                                </li>
                            </ul>
                        </div>

                        {/* Column 3: Culinary engine */}
                        <div>
                            <h4 className="mb-4 flex items-center gap-2 text-[11px] font-extrabold uppercase tracking-[0.18em] text-plum-deep">
                                <span className="h-2 w-2 rounded-full bg-plum" aria-hidden="true" />
                                Culinary Engine
                            </h4>
                            <ul className="space-y-2.5 text-sm font-medium text-ink-2">
                                <li className="flex items-start gap-2.5">
                                    <span className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-turmeric" aria-hidden="true" />
                                    <span>Bayesian Weighted Rating: (v/(v+m))R + (m/(v+m))C</span>
                                </li>
                                <li className="flex items-start gap-2.5">
                                    <span className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-mint" aria-hidden="true" />
                                    <span>Dimensional Unit Normalisation (Mass, Volume, Count)</span>
                                </li>
                                <li className="flex items-start gap-2.5">
                                    <span className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-chili" aria-hidden="true" />
                                    <span>Fractional Ingredient Scaling with Bengali Aliases</span>
                                </li>
                                <li className="pt-2">
                                    <a
                                        href="https://www.themealdb.com"
                                        target="_blank"
                                        rel="noreferrer"
                                        className="inline-flex items-center gap-1.5 rounded-full bg-plum-soft px-3 py-1.5 text-xs font-extrabold text-plum-deep transition-colors hover:bg-plum hover:text-white focus-visible:outline-3 focus-visible:outline-saffron"
                                    >
                                        <span>TheMealDB Dataset Integration</span>
                                        <ExternalLink className="h-3.5 w-3.5" />
                                    </a>
                                </li>
                            </ul>
                        </div>

                        {/* Column 4: Newsletter */}
                        <div className="pop-hover pop rounded-3xl bg-paper p-5">
                            <div className="mb-3 flex items-center gap-2.5">
                                <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-turmeric-soft text-turmeric-deep">
                                    <Sparkles className="h-4.5 w-4.5" />
                                </span>
                                <h4 className="font-display text-lg font-extrabold tracking-tight text-ink">Stay inspired</h4>
                            </div>
                            <p className="mb-4 text-sm font-medium leading-relaxed text-ink-2">
                                Seasonal recipes and kitchen intelligence, once a week. No spam, only spice.
                            </p>
                            <form onSubmit={(e) => e.preventDefault()} className="space-y-2">
                                <input
                                    type="email"
                                    placeholder="you@kitchen.com"
                                    className="w-full rounded-full border-2 border-line-strong bg-cream px-4 py-2.5 text-sm font-semibold text-ink placeholder:text-ink-3 transition-colors focus:border-saffron focus-visible:outline-3 focus-visible:outline-saffron"
                                    aria-label="Email for newsletter"
                                />
                                <button
                                    type="submit"
                                    className="flex w-full cursor-pointer items-center justify-center gap-2 rounded-full bg-ink px-4 py-2.5 text-sm font-extrabold text-white transition-all hover:bg-plum active:scale-[0.98] focus-visible:outline-3 focus-visible:outline-saffron"
                                >
                                    <Send className="h-4 w-4" />
                                    <span>Subscribe</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    {/* ---- Bottom row ---- */}
                    <div className="mt-14 flex flex-col items-center justify-between gap-5 border-t-2 border-dashed border-line-strong pt-8 text-xs font-semibold text-ink-2 md:flex-row">
                        <div className="flex items-center gap-3">
                            <span className="inline-flex items-center gap-1.5">
                                Made with <Heart className="h-3.5 w-3.5 fill-chili text-chili" aria-label="love" /> and the five spices
                            </span>
                            <span className="flex items-center gap-1" aria-hidden="true">
                                {SPICES.map((spice, i) => (
                                    <span
                                        key={spice.name}
                                        className={`h-3 w-3 rounded-full ${spice.className} ${i === 0 ? '' : '-ml-1'} ring-2 ring-cream-2`}
                                    />
                                ))}
                            </span>
                        </div>

                        <p className="text-center">© {new Date().getFullYear()} Panchforon. Built with Laravel 12 &amp; React 19.</p>

                        <div className="flex items-center gap-1">
                            <Link to="/" className="rounded-full px-3 py-1.5 transition-colors hover:bg-paper hover:text-ink focus-visible:outline-3 focus-visible:outline-saffron">
                                Terms
                            </Link>
                            <Link to="/" className="rounded-full px-3 py-1.5 transition-colors hover:bg-paper hover:text-ink focus-visible:outline-3 focus-visible:outline-saffron">
                                Privacy
                            </Link>
                            <Link to="/" className="rounded-full px-3 py-1.5 transition-colors hover:bg-paper hover:text-ink focus-visible:outline-3 focus-visible:outline-saffron">
                                Help Center
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    );
};
