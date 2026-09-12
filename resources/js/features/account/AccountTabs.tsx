import React from 'react';
import { NavLink } from 'react-router-dom';

const TABS = [
    { to: '/account', label: 'Profile', end: true },
    { to: '/account/recipes', label: 'My recipes' },
    { to: '/account/ratings', label: 'My ratings' },
    { to: '/saved', label: 'Saved' },
];

/** One row of tabs across the account screens. */
export const AccountTabs: React.FC = () => (
    <nav aria-label="Account sections" className="no-scrollbar -mx-4 flex gap-2 overflow-x-auto px-4 sm:mx-0 sm:px-0">
        {TABS.map(({ to, label, end }) => (
            <NavLink
                key={to}
                to={to}
                end={end}
                className={({ isActive }) =>
                    `inline-flex h-9 shrink-0 items-center rounded-full border px-4 text-sm font-medium transition-colors focus-visible:outline-3 focus-visible:outline-primary focus-visible:outline-offset-2 ${
                        isActive ? 'border-primary bg-primary text-on-primary' : 'border-line bg-surface text-ink-2 hover:border-line-strong hover:text-ink'
                    }`
                }
            >
                {label}
            </NavLink>
        ))}
    </nav>
);
