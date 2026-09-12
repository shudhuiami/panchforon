import React from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { useSiteSettings } from '../../features/site/useSiteSettings';
import { Logo } from './Logo';

const linkClass = 'text-sm text-ink-2 transition-colors hover:text-ink';

/**
 * Every link here goes somewhere real. Legal and about pages arrive with the
 * CMS, and are added to the columns then rather than as placeholders now.
 */
export const Footer: React.FC = () => {
    const { user } = useAuth();
    const settings = useSiteSettings();
    const year = new Date().getFullYear();

    return (
        <footer className="mt-16 border-t border-line bg-surface/60">
            <div className="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-[1.4fr_1fr_1fr] lg:px-8">
                <div className="space-y-4">
                    <Logo />
                    <p className="max-w-sm text-sm text-ink-2">
                        Community recipes with deep South Asian roots, honest ratings, and a meal planner that turns a week of
                        cooking into one merged shopping list.
                    </p>
                    <p className="text-xs text-ink-3">
                        Recipe data partly sourced from{' '}
                        <a href="https://www.themealdb.com" rel="noreferrer" target="_blank" className="underline decoration-line-strong underline-offset-4 hover:text-ink">
                            TheMealDB
                        </a>
                        .
                    </p>
                </div>

                <nav aria-labelledby="footer-explore">
                    <h2 id="footer-explore" className="font-display text-base font-semibold text-ink">Explore</h2>
                    <ul className="mt-4 space-y-2.5">
                        <li><Link to="/recipes" className={linkClass}>Browse recipes</Link></li>
                        <li><Link to="/meal-plan" className={linkClass}>Weekly meal plan</Link></li>
                        <li><Link to="/shopping-list" className={linkClass}>Shopping list</Link></li>
                    </ul>
                </nav>

                <nav aria-labelledby="footer-account">
                    <h2 id="footer-account" className="font-display text-base font-semibold text-ink">Account</h2>
                    <ul className="mt-4 space-y-2.5">
                        {user ? (
                            <>
                                <li><Link to="/recipes/create" className={linkClass}>Post a recipe</Link></li>
                                {user.is_admin && <li><a href="/admin" className={linkClass}>Admin panel</a></li>}
                            </>
                        ) : (
                            <>
                                <li><Link to="/login" className={linkClass}>Sign in</Link></li>
                                {settings.registration_open && <li><Link to="/register" className={linkClass}>Create an account</Link></li>}
                            </>
                        )}
                        {settings.contact_email && (
                            <li><a href={`mailto:${settings.contact_email}`} className={linkClass}>Contact</a></li>
                        )}
                    </ul>
                </nav>
            </div>
            <div className="border-t border-line">
                <p className="mx-auto max-w-7xl px-4 py-5 text-xs text-ink-3 sm:px-6 lg:px-8">
                    © {year} {settings.site_name}. Built with Laravel 13 &amp; React 19.
                </p>
            </div>
        </footer>
    );
};
