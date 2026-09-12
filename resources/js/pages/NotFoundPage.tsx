import React from 'react';
import { ButtonLink } from '../components/ui/Button';

export const NotFoundPage: React.FC = () => (
    <section className="mx-auto flex min-h-[70vh] w-full max-w-3xl animate-slide-up flex-col items-center justify-center px-4 py-16 text-center sm:px-6">
        <p className="font-display text-[7rem] leading-none font-medium text-spice italic select-none sm:text-[11rem]" aria-hidden="true">
            404
        </p>
        <h1 className="mt-4 font-display text-3xl font-semibold tracking-tight text-ink text-balance sm:text-4xl">This page isn’t on the menu.</h1>
        <p className="mx-auto mt-3 max-w-md text-base text-ink-2 sm:text-lg">The link may be old, or the recipe was taken down. Everything else is still cooking.</p>
        <div className="mt-8 flex flex-col gap-3 sm:flex-row">
            <ButtonLink to="/recipes">Browse recipes</ButtonLink>
            <ButtonLink to="/" variant="outline">
                Back home
            </ButtonLink>
        </div>
    </section>
);
