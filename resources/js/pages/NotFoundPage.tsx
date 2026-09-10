import React from 'react';
import { Link } from 'react-router-dom';
import { ChefHat, ArrowLeft, Compass, Soup, Flame, Sparkles } from 'lucide-react';

export const NotFoundPage: React.FC = () => {
    return (
        <section className="relative flex min-h-[calc(100vh-8rem)] items-center justify-center overflow-hidden px-4 py-16 sm:px-6">
            {/* Ambient color blobs */}
            <div className="blob-1 pointer-events-none absolute -left-24 top-10 h-72 w-72 bg-turmeric/35 blur-3xl animate-float" aria-hidden="true" />
            <div className="blob-2 pointer-events-none absolute -right-20 bottom-0 h-80 w-80 bg-plum/25 blur-3xl animate-float animation-delay-300" aria-hidden="true" />
            <div className="pointer-events-none absolute left-1/2 top-1/2 h-64 w-64 -translate-x-1/2 -translate-y-1/2 rounded-full bg-chili/15 blur-3xl" aria-hidden="true" />

            <div className="relative w-full max-w-3xl text-center animate-slide-up">
                {/* Giant 404 with floating stickers */}
                <div className="relative mx-auto inline-block">
                    <h1
                        className="font-display text-[7rem] font-extrabold leading-[0.85] tracking-tighter text-spice select-none sm:text-[11rem] lg:text-[14rem]"
                        aria-label="404, page not found"
                    >
                        404
                    </h1>

                    <span
                        className="[--float-rot:-5deg] pop absolute -left-6 top-2 inline-flex items-center gap-1.5 rounded-full bg-turmeric px-3 py-1.5 text-xs font-extrabold text-ink animate-float sm:-left-16 sm:top-6 sm:text-sm"
                        aria-hidden="true"
                    >
                        <ChefHat className="h-4 w-4" />
                        Lost in the bazaar
                    </span>

                    <span
                        className="[--float-rot:4deg] pop absolute -right-4 bottom-2 inline-flex items-center gap-1.5 rounded-full bg-mint px-3 py-1.5 text-xs font-extrabold text-ink animate-float animation-delay-200 sm:-right-14 sm:bottom-6 sm:text-sm"
                        aria-hidden="true"
                    >
                        <Soup className="h-4 w-4" />
                        Simmered away
                    </span>

                    <span
                        className="absolute -top-4 right-2 flex h-10 w-10 items-center justify-center rounded-2xl bg-chili text-white shadow-glow-chili animate-wiggle sm:-top-6 sm:right-6"
                        aria-hidden="true"
                    >
                        <Flame className="h-5 w-5" />
                    </span>
                </div>

                <h2 className="mt-6 text-balance font-display text-2xl font-extrabold tracking-tight text-ink sm:text-4xl">
                    Recipe or page not found
                </h2>
                <p className="mx-auto mt-3 max-w-md text-balance text-base font-medium leading-relaxed text-ink-2 sm:text-lg">
                    The dish you are looking for might have been moved, eaten, or never made it onto the menu.
                    Let&apos;s get you back to something tasty.
                </p>

                <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <Link
                        to="/"
                        className="inline-flex w-full items-center justify-center gap-2 rounded-full bg-ink px-6 py-3.5 text-sm font-extrabold text-white shadow-lg transition-all duration-200 hover:-translate-y-0.5 hover:bg-plum hover:shadow-glow-plum focus-visible:outline-3 focus-visible:outline-saffron sm:w-auto"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Back home
                    </Link>
                    <Link
                        to="/"
                        className="inline-flex w-full items-center justify-center gap-2 rounded-full bg-saffron px-6 py-3.5 text-sm font-extrabold text-ink shadow-glow-saffron transition-all duration-200 hover:-translate-y-0.5 hover:bg-turmeric focus-visible:outline-3 focus-visible:outline-saffron sm:w-auto"
                    >
                        <Compass className="h-4 w-4" />
                        Browse recipes
                    </Link>
                </div>

                <p className="mt-8 inline-flex items-center gap-2 rounded-full border border-line-strong bg-paper/80 px-4 py-2 text-xs font-bold text-ink-3">
                    <Sparkles className="h-3.5 w-3.5 text-saffron-deep" />
                    Tip: every recipe lives under /recipes/your-recipe-name
                </p>
            </div>
        </section>
    );
};
