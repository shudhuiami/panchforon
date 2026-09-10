import React, { useState } from 'react';
import { Link, NavLink, useNavigate } from 'react-router-dom';
import {
    ShoppingBag,
    Calendar,
    PlusCircle,
    LogOut,
    Menu,
    X,
    User as UserIcon,
    ChefHat,
    Compass,
    Sparkles,
    ArrowRight,
    Zap,
} from 'lucide-react';
import { useAuth } from '../../context/AuthContext';
import { useQuery } from '@tanstack/react-query';
import { mealPlanApi } from '../../api/mealPlan';

const getInitials = (name?: string | null): string => {
    if (!name) return '';
    const parts = name.trim().split(/\s+/).filter(Boolean);
    if (parts.length === 0) return '';
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
};

export const Navbar: React.FC = () => {
    const { user, token, logout, demoLogin } = useAuth();
    const navigate = useNavigate();
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const [isDemoLoading, setIsDemoLoading] = useState(false);

    // Query active meal plan item count when logged in
    const { data: mealPlanData } = useQuery({
        queryKey: ['mealPlan'],
        queryFn: () => mealPlanApi.get(),
        enabled: !!token,
        staleTime: 1000 * 60,
    });

    const mealPlanItemCount = mealPlanData?.data?.items?.length || 0;
    const initials = getInitials(user?.name);

    const handleDemoLogin = async () => {
        setIsDemoLoading(true);
        try {
            await demoLogin();
            navigate('/meal-plan');
        } catch (err) {
            console.error('Demo login failed:', err);
        } finally {
            setIsDemoLoading(false);
        }
    };

    const handleLogout = async () => {
        await logout();
        navigate('/');
    };

    const closeMenu = () => setMobileMenuOpen(false);

    /* Desktop pill tabs */
    const tabClass = ({ isActive }: { isActive: boolean }) =>
        `inline-flex items-center gap-1.5 rounded-full px-4 py-2 text-sm font-bold whitespace-nowrap transition-all duration-200 select-none focus-visible:outline-3 focus-visible:outline-saffron ${
            isActive
                ? 'bg-ink text-white shadow-md'
                : 'text-ink-2 hover:bg-white/80 hover:text-ink'
        }`;

    /* Mobile sheet rows */
    const sheetRowClass = ({ isActive }: { isActive: boolean }) =>
        `flex items-center gap-4 rounded-2xl px-4 py-3.5 text-lg font-bold font-display tracking-tight transition-colors ${
            isActive ? 'bg-ink text-white' : 'text-ink hover:bg-cream-2'
        }`;

    const countBubble = (extra = '') =>
        mealPlanItemCount > 0 ? (
            <span
                className={`inline-flex min-w-5 h-5 items-center justify-center rounded-full bg-chili px-1.5 text-[11px] font-black text-white shadow-glow-chili animate-pop-in ${extra}`}
                aria-label={`${mealPlanItemCount} items in meal plan`}
            >
                {mealPlanItemCount}
            </span>
        ) : null;

    return (
        <header className="sticky top-3 z-40 px-3 sm:px-6 lg:px-8 print:hidden">
            <div className="relative mx-auto max-w-7xl">
                {/* Floating pill */}
                <div className="glass rounded-full shadow-md ring-1 ring-ink/5">
                    <div className="flex h-16 items-center justify-between pl-2.5 pr-2.5 sm:pl-3 sm:pr-3">
                        {/* Brand */}
                        <Link
                            to="/"
                            className="group flex items-center gap-3 rounded-full pr-2 focus-visible:outline-3 focus-visible:outline-saffron"
                            aria-label="Panchforon Home"
                            onClick={closeMenu}
                        >
                            <span className="relative flex h-11 w-11 items-center justify-center rounded-2xl bg-sunrise-gradient text-white shadow-glow-saffron transition-transform duration-300 group-hover:-rotate-6 group-hover:scale-105">
                                <ChefHat className="h-6 w-6" strokeWidth={2.4} />
                                <span className="absolute -right-1 -top-1 h-3.5 w-3.5 rounded-full border-2 border-white bg-mint" aria-hidden="true" />
                            </span>
                            <span className="flex flex-col leading-none">
                                <span className="font-display text-[1.45rem] font-extrabold tracking-tight text-ink">
                                    Panchforon
                                </span>
                                <span className="mt-0.5 hidden text-[11px] font-bold tracking-wide text-ink-3 sm:block">
                                    পাঁচফোড়ন · recipes &amp; meal plans
                                </span>
                            </span>
                        </Link>

                        {/* Desktop tabs */}
                        <nav
                            className="hidden items-center gap-1 rounded-full bg-white/50 p-1 lg:flex"
                            aria-label="Main Navigation"
                        >
                            <NavLink to="/" className={tabClass} end>
                                <Compass className="h-4 w-4" />
                                Discover
                            </NavLink>
                            <NavLink to="/meal-plan" className={tabClass}>
                                <Calendar className="h-4 w-4" />
                                Meal plan
                                {countBubble()}
                            </NavLink>
                            <NavLink to="/shopping-list" className={tabClass}>
                                <ShoppingBag className="h-4 w-4" />
                                Shopping list
                            </NavLink>
                            {token && (
                                <NavLink to="/recipes/create" className={tabClass}>
                                    <Sparkles className="h-4 w-4" />
                                    Share recipe
                                </NavLink>
                            )}
                        </nav>

                        {/* Desktop actions */}
                        <div className="hidden items-center gap-2 lg:flex">
                            {token ? (
                                <>
                                    <Link
                                        to="/recipes/create"
                                        className="inline-flex items-center gap-1.5 rounded-full bg-saffron px-4 py-2 text-sm font-extrabold text-ink shadow-glow-saffron transition-all duration-200 hover:-translate-y-0.5 hover:bg-turmeric focus-visible:outline-3 focus-visible:outline-saffron"
                                    >
                                        <PlusCircle className="h-4 w-4" />
                                        <span>Post recipe</span>
                                    </Link>

                                    <div className="flex items-center gap-2 rounded-full border border-line-strong bg-white/80 py-1 pl-1 pr-3">
                                        <span
                                            className="flex h-8 w-8 items-center justify-center rounded-full bg-plum font-display text-xs font-extrabold text-white shadow-glow-plum"
                                            aria-hidden="true"
                                        >
                                            {initials || <UserIcon className="h-4 w-4" />}
                                        </span>
                                        <span className="max-w-[120px] truncate text-sm font-bold text-ink">
                                            {user?.name || 'Chef'}
                                        </span>
                                    </div>

                                    <button
                                        onClick={handleLogout}
                                        className="flex h-10 w-10 cursor-pointer items-center justify-center rounded-full text-ink-2 transition-colors hover:bg-chili-soft hover:text-chili-deep focus-visible:outline-3 focus-visible:outline-saffron"
                                        title="Sign Out"
                                        aria-label="Sign Out"
                                    >
                                        <LogOut className="h-4.5 w-4.5" />
                                    </button>
                                </>
                            ) : (
                                <>
                                    <button
                                        type="button"
                                        onClick={handleDemoLogin}
                                        disabled={isDemoLoading}
                                        className="inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-line-strong bg-white/80 px-3.5 py-2 text-xs font-extrabold text-ink transition-all hover:border-mint hover:bg-mint-soft hover:text-mint-deep disabled:cursor-not-allowed disabled:opacity-60 focus-visible:outline-3 focus-visible:outline-saffron"
                                    >
                                        <Zap className="h-3.5 w-3.5 text-mint-deep" />
                                        {isDemoLoading ? 'Entering...' : '1-click demo'}
                                    </button>
                                    <Link
                                        to="/login"
                                        className="inline-flex items-center rounded-full px-4 py-2 text-sm font-bold text-ink transition-colors hover:bg-white/80 focus-visible:outline-3 focus-visible:outline-saffron"
                                    >
                                        Log in
                                    </Link>
                                    <Link
                                        to="/register"
                                        className="inline-flex items-center gap-1.5 rounded-full bg-saffron px-5 py-2.5 text-sm font-extrabold text-ink shadow-glow-saffron transition-all duration-200 hover:-translate-y-0.5 hover:bg-turmeric focus-visible:outline-3 focus-visible:outline-saffron"
                                    >
                                        Join free
                                        <ArrowRight className="h-4 w-4" />
                                    </Link>
                                </>
                            )}
                        </div>

                        {/* Mobile controls */}
                        <div className="flex items-center gap-1.5 lg:hidden">
                            <NavLink
                                to="/meal-plan"
                                onClick={closeMenu}
                                className={({ isActive }) =>
                                    `relative flex h-10 w-10 items-center justify-center rounded-full transition-colors focus-visible:outline-3 focus-visible:outline-saffron ${
                                        isActive ? 'bg-ink text-white' : 'text-ink-2 hover:bg-white/80 hover:text-ink'
                                    }`
                                }
                                aria-label="Meal Plan"
                            >
                                <Calendar className="h-5 w-5" />
                                {countBubble('absolute -right-0.5 -top-0.5')}
                            </NavLink>

                            <button
                                onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                                className={`flex h-10 w-10 cursor-pointer items-center justify-center rounded-full transition-all focus-visible:outline-3 focus-visible:outline-saffron ${
                                    mobileMenuOpen ? 'bg-ink text-white rotate-90' : 'bg-white/80 text-ink hover:bg-turmeric-soft'
                                }`}
                                aria-expanded={mobileMenuOpen}
                                aria-controls="mobile-nav-sheet"
                                aria-label="Toggle Menu"
                            >
                                {mobileMenuOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
                            </button>
                        </div>
                    </div>
                </div>

                {/* Mobile sheet */}
                {mobileMenuOpen && (
                    <div
                        id="mobile-nav-sheet"
                        className="absolute left-0 right-0 top-full mt-2 origin-top rounded-3xl border border-line bg-paper p-3 shadow-xl animate-scale-in lg:hidden"
                    >
                        <nav className="flex flex-col gap-1" aria-label="Mobile Navigation">
                            <NavLink to="/" onClick={closeMenu} className={sheetRowClass} end>
                                <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-turmeric-soft text-turmeric-deep">
                                    <Compass className="h-5 w-5" />
                                </span>
                                <span>Discover recipes</span>
                            </NavLink>
                            <NavLink to="/meal-plan" onClick={closeMenu} className={sheetRowClass}>
                                <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-mint-soft text-mint-deep">
                                    <Calendar className="h-5 w-5" />
                                </span>
                                <span className="flex-1">Weekly meal plan</span>
                                {countBubble('h-6 min-w-6 text-xs')}
                            </NavLink>
                            <NavLink to="/shopping-list" onClick={closeMenu} className={sheetRowClass}>
                                <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-plum-soft text-plum-deep">
                                    <ShoppingBag className="h-5 w-5" />
                                </span>
                                <span>Shopping list</span>
                            </NavLink>
                            {token && (
                                <NavLink to="/recipes/create" onClick={closeMenu} className={sheetRowClass}>
                                    <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-saffron-soft text-saffron-deep">
                                        <PlusCircle className="h-5 w-5" />
                                    </span>
                                    <span>Share a recipe</span>
                                </NavLink>
                            )}
                        </nav>

                        <div className="mt-3 rounded-2xl bg-cream-2 p-3">
                            {token ? (
                                <div className="flex items-center justify-between gap-3">
                                    <div className="flex min-w-0 items-center gap-3">
                                        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-plum font-display text-sm font-extrabold text-white">
                                            {initials || <UserIcon className="h-4 w-4" />}
                                        </span>
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-extrabold text-ink">{user?.name || 'Chef'}</p>
                                            <p className="text-xs font-semibold text-ink-3">Signed in</p>
                                        </div>
                                    </div>
                                    <button
                                        onClick={() => {
                                            handleLogout();
                                            closeMenu();
                                        }}
                                        className="inline-flex cursor-pointer items-center gap-1.5 rounded-full bg-white px-3.5 py-2 text-xs font-extrabold text-chili-deep transition-colors hover:bg-chili-soft focus-visible:outline-3 focus-visible:outline-saffron"
                                    >
                                        <LogOut className="h-3.5 w-3.5" />
                                        <span>Sign out</span>
                                    </button>
                                </div>
                            ) : (
                                <div className="flex flex-col gap-2">
                                    <button
                                        type="button"
                                        onClick={() => {
                                            handleDemoLogin();
                                            closeMenu();
                                        }}
                                        disabled={isDemoLoading}
                                        className="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-full border-2 border-mint bg-mint-soft px-4 py-3 text-sm font-extrabold text-mint-deep transition-colors hover:bg-mint hover:text-ink disabled:opacity-60 focus-visible:outline-3 focus-visible:outline-saffron"
                                    >
                                        <Zap className="h-4 w-4" />
                                        {isDemoLoading ? 'Entering...' : '1-click demo login'}
                                    </button>
                                    <div className="grid grid-cols-2 gap-2">
                                        <Link
                                            to="/login"
                                            onClick={closeMenu}
                                            className="inline-flex items-center justify-center rounded-full bg-white px-4 py-3 text-sm font-extrabold text-ink transition-colors hover:bg-cream focus-visible:outline-3 focus-visible:outline-saffron"
                                        >
                                            Log in
                                        </Link>
                                        <Link
                                            to="/register"
                                            onClick={closeMenu}
                                            className="inline-flex items-center justify-center gap-1.5 rounded-full bg-saffron px-4 py-3 text-sm font-extrabold text-ink shadow-glow-saffron transition-colors hover:bg-turmeric focus-visible:outline-3 focus-visible:outline-saffron"
                                        >
                                            Join free
                                            <ArrowRight className="h-4 w-4" />
                                        </Link>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </header>
    );
};
