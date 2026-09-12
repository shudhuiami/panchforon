import React from 'react';
import { useAuth } from '../../context/AuthContext';
import { useSiteSettings } from '../site/useSiteSettings';
import { ButtonLink } from '../../components/ui/Button';
import { Reveal } from '../../components/motion/Reveal';
import { Mark } from '../../components/layout/Logo';

/** The closing invitation: post a recipe, or join to be able to. */
export const CtaBand: React.FC = () => {
    const { user } = useAuth();
    const settings = useSiteSettings();

    return (
        <section className="px-4 py-10 sm:px-6 lg:px-8 lg:py-16" aria-labelledby="cta-heading">
            <Reveal className="relative mx-auto max-w-7xl overflow-hidden rounded-[2.5rem] bg-primary px-6 py-14 text-on-primary sm:px-12 lg:px-16 lg:py-20">
                <div className="pointer-events-none absolute -top-24 -right-16 size-72 rounded-full bg-turmeric/60 blur-2xl" aria-hidden="true" />
                <div className="pointer-events-none absolute -bottom-28 left-1/3 size-80 rounded-full bg-hot/50 blur-3xl" aria-hidden="true" />
                <Mark className="pointer-events-none absolute -right-10 -bottom-16 size-72 rotate-12 opacity-20 lg:right-16 lg:-bottom-8" />
                <div className="relative grid grid-cols-1 items-center gap-8 lg:grid-cols-[1.2fr_auto]">
                    <div>
                        <p className="text-xs font-semibold tracking-[0.22em] text-on-primary/70 uppercase">Share yours</p>
                        <h2 id="cta-heading" className="mt-3 max-w-2xl font-display text-4xl font-semibold tracking-tight text-balance sm:text-5xl lg:text-6xl">
                            Cooked something great? Put it on the table.
                        </h2>
                        <p className="mt-4 max-w-xl text-base text-on-primary/80 sm:text-lg">
                            Post the recipe, get rated by people who actually cooked it, and help someone find their new favourite dinner.
                        </p>
                    </div>
                    <div className="flex flex-col gap-3 sm:flex-row lg:flex-col">
                        {user ? (
                            <ButtonLink to="/recipes/create" variant="secondary" size="lg">
                                Post a recipe
                            </ButtonLink>
                        ) : (
                            <>
                                {settings.registration_open && (
                                    <ButtonLink to="/register" variant="secondary" size="lg">
                                        Join free
                                    </ButtonLink>
                                )}
                                <ButtonLink to="/login" variant="outline" size="lg" className="border-on-primary/40 text-on-primary hover:bg-on-primary/10">
                                    Sign in
                                </ButtonLink>
                            </>
                        )}
                    </div>
                </div>
            </Reveal>
        </section>
    );
};
