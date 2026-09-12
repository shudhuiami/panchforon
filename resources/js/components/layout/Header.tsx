import React from 'react';
import { NavLink, useLocation, useNavigate } from 'react-router-dom';
import { Plus, Search } from 'lucide-react';
import { useAuth } from '../../context/AuthContext';
import { useSiteSettings } from '../../features/site/useSiteSettings';
import { useMealPlanCount } from '../../features/meal-plan/useMealPlan';
import { SearchBox } from '../../features/recipes/SearchBox';
import { ButtonLink } from '../ui/Button';
import { CountBadge } from '../ui/Badge';
import { IconButton } from '../ui/IconButton';
import { Logo } from './Logo';
import { AccountDropdown } from './AccountMenu';
import { useScrolled } from './useScrolled';

/** Pages that own a search control of their own; the header stays out of their way. */
const PAGES_WITH_SEARCH = ['/', '/recipes'];

/**
 * The top bar. Below the `lg` breakpoint it is a slim strip (logo + search)
 * and the TabBar carries navigation; from `lg` up it shows the full desktop
 * chrome. The wrapping divs matter: Button bakes in `inline-flex`, so a
 * `hidden` on the button itself would lose the cascade.
 */
export const Header: React.FC = () => {
    const { user } = useAuth();
    const settings = useSiteSettings();
    const planCount = useMealPlanCount();
    const scrolled = useScrolled(24);
    const { pathname } = useLocation();
    const navigate = useNavigate();

    const showSearch = !PAGES_WITH_SEARCH.includes(pathname);

    const links = [
        { to: '/', label: 'Home', end: true },
        { to: '/recipes', label: 'Recipes' },
        ...(user ? [{ to: '/meal-plan', label: 'Meal plan', badge: planCount }, { to: '/saved', label: 'Saved' }] : []),
    ];

    return (
        <header
            className={`sticky top-0 z-40 pt-[env(safe-area-inset-top)] transition-[background-color,border-color,box-shadow] duration-300 ${
                scrolled ? 'glass border-b border-line shadow-md' : 'border-b border-transparent bg-transparent'
            }`}
        >
            <div className="mx-auto flex h-16 max-w-7xl items-center gap-3 px-4 sm:px-6 lg:px-8">
                <Logo />

                <nav aria-label="Primary" className="ml-4 hidden lg:block">
                    <ul className="flex items-center gap-1">
                        {links.map(({ to, label, end, badge }) => (
                            <li key={to}>
                                <NavLink
                                    to={to}
                                    end={end}
                                    className={({ isActive }) =>
                                        `relative inline-flex h-10 items-center gap-2 rounded-full px-4 text-sm font-medium transition-colors ${
                                            isActive ? 'bg-primary-soft text-primary' : 'text-ink-2 hover:bg-surface-2 hover:text-ink'
                                        }`
                                    }
                                >
                                    {label}
                                    {badge !== undefined && <CountBadge count={badge} />}
                                </NavLink>
                            </li>
                        ))}
                    </ul>
                </nav>

                <div className="ml-auto flex items-center gap-2">
                    {showSearch && (
                        <>
                            <SearchBox
                                className="hidden w-64 xl:flex"
                                onSubmit={(q) => navigate(q ? `/recipes?q=${encodeURIComponent(q)}` : '/recipes')}
                            />
                            <IconButton
                                label="Search recipes"
                                className="xl:hidden"
                                onClick={() => navigate('/recipes', { state: { focusSearch: true } })}
                            >
                                <Search className="size-5" />
                            </IconButton>
                        </>
                    )}

                    <div className="hidden items-center gap-2 lg:flex">
                        {user ? (
                            <>
                                <ButtonLink to="/recipes/create" size="sm">
                                    <Plus className="size-4" aria-hidden="true" /> Post a recipe
                                </ButtonLink>
                                <AccountDropdown />
                            </>
                        ) : (
                            <>
                                <ButtonLink to="/login" variant="ghost" size="sm">
                                    Sign in
                                </ButtonLink>
                                {settings.registration_open && <ButtonLink to="/register" size="sm">Join free</ButtonLink>}
                            </>
                        )}
                    </div>
                </div>
            </div>
        </header>
    );
};
