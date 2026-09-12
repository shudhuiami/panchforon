import React, { useState } from 'react';
import { NavLink } from 'react-router-dom';
import { CalendarDays, Compass, Heart, Home, UserRound } from 'lucide-react';
import { useAuth } from '../../context/AuthContext';
import { useMealPlanCount } from '../../features/meal-plan/useMealPlan';
import { CountBadge } from '../ui/Badge';
import { Avatar } from '../ui/Avatar';
import { AccountSheet } from './AccountMenu';

const tabClass = (active: boolean) =>
    `relative flex h-full flex-col items-center justify-center gap-1 text-[11px] font-medium transition-colors ${
        active ? 'text-primary' : 'text-ink-3'
    }`;

/**
 * The phone navigation: a fixed bar in the thumb zone, sitting above the home
 * indicator, with the account sheet behind the last tab.
 */
export const TabBar: React.FC = () => {
    const { user } = useAuth();
    const planCount = useMealPlanCount();
    const [accountOpen, setAccountOpen] = useState(false);

    return (
        <>
            <nav aria-label="Tabs" className="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-canvas/90 pb-[env(safe-area-inset-bottom)] backdrop-blur-xl backdrop-saturate-150 lg:hidden">
                <ul className="grid h-16 grid-cols-5">
                    <li>
                        <NavLink to="/" end className={({ isActive }) => tabClass(isActive)}>
                            <Home className="size-6" aria-hidden="true" />
                            Home
                        </NavLink>
                    </li>
                    <li>
                        <NavLink to="/recipes" className={({ isActive }) => tabClass(isActive)}>
                            <Compass className="size-6" aria-hidden="true" />
                            Browse
                        </NavLink>
                    </li>
                    <li>
                        <NavLink to="/meal-plan" className={({ isActive }) => tabClass(isActive)}>
                            <span className="relative">
                                <CalendarDays className="size-6" aria-hidden="true" />
                                <CountBadge count={planCount} className="absolute -right-2.5 -top-1.5" />
                            </span>
                            Plan
                        </NavLink>
                    </li>
                    <li>
                        <NavLink to="/saved" className={({ isActive }) => tabClass(isActive)}>
                            <Heart className="size-6" aria-hidden="true" />
                            Saved
                        </NavLink>
                    </li>
                    <li>
                        <button
                            type="button"
                            onClick={() => setAccountOpen(true)}
                            aria-haspopup="dialog"
                            aria-expanded={accountOpen}
                            className={`${tabClass(accountOpen)} w-full cursor-pointer`}
                        >
                            {user ? <Avatar name={user.name} size="sm" className="size-6 text-[10px]" /> : <UserRound className="size-6" aria-hidden="true" />}
                            {user ? 'Me' : 'Sign in'}
                        </button>
                    </li>
                </ul>
            </nav>
            <AccountSheet isOpen={accountOpen} onClose={() => setAccountOpen(false)} />
        </>
    );
};
