import React from 'react';
import { Outlet } from 'react-router-dom';
import { Header } from './Header';
import { Footer } from './Footer';
import { TabBar } from './TabBar';

/** Header, content, footer, and on phones the tab bar. */
export const AppShell: React.FC = () => (
    <div className="flex min-h-dvh flex-col">
        <a
            href="#main"
            className="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-full focus:bg-primary focus:px-4 focus:py-2 focus:text-on-primary"
        >
            Skip to content
        </a>
        <Header />
        <main id="main" className="w-full flex-1 pb-[calc(4rem+env(safe-area-inset-bottom))] lg:pb-0">
            <Outlet />
        </main>
        <Footer />
        <TabBar />
    </div>
);
