import React from 'react';
import { useLocation } from 'react-router-dom';
import { AccountNav } from './AccountNav';

interface AccountLayoutProps {
    title: string;
    blurb: string;
    children: React.ReactNode;
}

/** The shared frame for every account screen: the section rail, then the section. */
export const AccountLayout: React.FC<AccountLayoutProps> = ({ title, blurb, children }) => {
    const { pathname } = useLocation();

    return (
        <div className="mx-auto grid w-full max-w-7xl gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-12 lg:px-8 lg:py-12">
            <AccountNav />
            {/* Keyed on the path so the enter animation replays whenever a section opens. */}
            <div key={pathname} className="min-w-0 animate-section-in">
                <header className="max-w-2xl">
                    <p className="text-xs font-semibold tracking-[0.22em] text-primary uppercase">Your account</p>
                    <h1 className="mt-3 font-display text-4xl font-semibold tracking-tight text-ink text-balance sm:text-5xl">{title}</h1>
                    <p className="mt-4 text-base text-ink-2 sm:text-lg">{blurb}</p>
                </header>
                <div className="mt-8">{children}</div>
            </div>
        </div>
    );
};
