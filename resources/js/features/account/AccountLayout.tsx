import React from 'react';
import { AccountTabs } from './AccountTabs';

interface AccountLayoutProps {
    title: string;
    blurb: string;
    children: React.ReactNode;
}

/** The shared frame for every account screen. */
export const AccountLayout: React.FC<AccountLayoutProps> = ({ title, blurb, children }) => (
    <div className="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
        <header className="mb-6 max-w-2xl animate-slide-up">
            <p className="text-xs font-semibold tracking-[0.22em] text-primary uppercase">Your account</p>
            <h1 className="mt-3 font-display text-4xl font-semibold tracking-tight text-ink text-balance sm:text-5xl">{title}</h1>
            <p className="mt-4 text-base text-ink-2 sm:text-lg">{blurb}</p>
        </header>
        <AccountTabs />
        <div className="mt-8">{children}</div>
    </div>
);
