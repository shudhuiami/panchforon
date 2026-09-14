import React, { useEffect, useLayoutEffect, useRef } from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import { Heart, NotebookPen, Star, UserRound, type LucideIcon } from 'lucide-react';

interface AccountSection {
    to: string;
    label: string;
    icon: LucideIcon;
    end?: boolean;
}

const SECTIONS: AccountSection[] = [
    { to: '/account', label: 'Profile', icon: UserRound, end: true },
    { to: '/account/recipes', label: 'My recipes', icon: NotebookPen },
    { to: '/account/ratings', label: 'My ratings', icon: Star },
    { to: '/saved', label: 'Saved', icon: Heart },
];

/**
 * Every section is its own route, so this nav is thrown away and rebuilt on each
 * move between them. Remembering where the indicator was lets the rebuilt one
 * start there and travel, instead of appearing on the new section. It resets
 * when the nav unmounts, so arriving from elsewhere in the site does not animate.
 */
let indicatorIndex = -1;

const sectionIndex = (pathname: string): number =>
    Math.max(0, SECTIONS.findIndex(({ to, end }) => (end ? pathname === to : pathname.startsWith(to))));

/** Phones scroll the rail sideways; keep the section you are on off the edges. */
const keepInView = (track: HTMLElement, item: HTMLElement): void => {
    const overflow = track.scrollWidth - track.clientWidth;
    if (overflow <= 0) return;
    const centred = item.offsetLeft + item.offsetWidth / 2 - track.clientWidth / 2;
    track.scrollLeft = Math.min(Math.max(centred, 0), overflow);
};

/**
 * The account rail: a sticky column of sections from `lg` up, a scrollable row
 * of them below that. One indicator serves both — it is placed from the active
 * item's own box, so it slides down the column or across the row unchanged.
 */
export const AccountNav: React.FC = () => {
    const { pathname } = useLocation();
    const activeIndex = sectionIndex(pathname);
    const fromIndex = useRef(indicatorIndex).current;
    const trackRef = useRef<HTMLDivElement>(null);
    const listRef = useRef<HTMLUListElement>(null);
    const indicatorRef = useRef<HTMLSpanElement>(null);

    useEffect(() => {
        indicatorIndex = activeIndex;
        return () => {
            indicatorIndex = -1;
        };
    }, [activeIndex]);

    useLayoutEffect(() => {
        const track = trackRef.current;
        const list = listRef.current;
        const indicator = indicatorRef.current;
        if (!track || !list || !indicator) return;

        const itemAt = (index: number): HTMLElement | null => {
            const item = list.children.item(index) as HTMLElement | null;
            return item && item.offsetWidth > 0 ? item : null;
        };

        const place = (item: HTMLElement): void => {
            indicator.style.width = `${item.offsetWidth}px`;
            indicator.style.height = `${item.offsetHeight}px`;
            indicator.style.transform = `translate(${item.offsetLeft}px, ${item.offsetTop}px)`;
        };

        const settle = (): void => {
            const active = itemAt(activeIndex);
            if (!active) return;
            place(active);
            keepInView(track, active);
        };

        /* Pin the starting box without a transition and flush it, so the only
           thing the browser animates is the move onto the active section. */
        const start = itemAt(fromIndex) ?? itemAt(activeIndex);
        if (start) {
            indicator.style.transition = 'none';
            place(start);
            void indicator.offsetWidth;
            indicator.style.transition = '';
        }
        settle();

        /* Re-measure when the rail turns from a row into a column, when the
           viewport changes its width, and when the display font lands. */
        const observer = new ResizeObserver(settle);
        observer.observe(list);
        return () => observer.disconnect();
    }, [activeIndex, fromIndex]);

    return (
        <nav aria-label="Account sections" className="lg:sticky lg:top-24 lg:self-start">
            <div ref={trackRef} className="no-scrollbar relative overflow-x-auto rounded-3xl border border-line bg-surface p-1.5 lg:overflow-visible lg:p-2">
                <span
                    ref={indicatorRef}
                    aria-hidden="true"
                    className="pointer-events-none absolute top-0 left-0 rounded-2xl bg-primary-soft transition-[transform,width,height] duration-400 ease-out-expo"
                />
                <ul className="flex gap-1 lg:flex-col">
                    {SECTIONS.map(({ to, label, icon: Icon, end }) => (
                        <li key={to} className="relative shrink-0 lg:w-full">
                            <NavLink
                                to={to}
                                end={end}
                                className={({ isActive }) =>
                                    `flex items-center gap-2.5 rounded-2xl px-4 py-3.5 text-sm font-medium whitespace-nowrap transition-colors focus-visible:outline-3 focus-visible:outline-primary focus-visible:outline-offset-1 lg:gap-3 lg:py-4 ${
                                        isActive ? 'text-primary' : 'text-ink-2 hover:bg-surface-2 hover:text-ink'
                                    }`
                                }
                            >
                                <Icon className="size-[18px] shrink-0" aria-hidden="true" />
                                {label}
                            </NavLink>
                        </li>
                    ))}
                </ul>
            </div>
        </nav>
    );
};
