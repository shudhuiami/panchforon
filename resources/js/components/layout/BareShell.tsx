import React from 'react';
import { Outlet } from 'react-router-dom';
import { Logo } from './Logo';

/** Sign-in and registration: just the mark and the form, no chrome to compete with it. */
export const BareShell: React.FC = () => (
    <div className="flex min-h-dvh flex-col pt-[env(safe-area-inset-top)] pb-[env(safe-area-inset-bottom)]">
        <div className="mx-auto flex h-16 w-full max-w-7xl items-center px-4 sm:px-6 lg:px-8">
            <Logo />
        </div>
        <main id="main" className="flex-1">
            <Outlet />
        </main>
    </div>
);
