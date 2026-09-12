import React from 'react';
import { CuisineCount } from '../../types/api';

const Chip: React.FC<{ active: boolean; onClick: () => void; children: React.ReactNode }> = ({ active, onClick, children }) => (
    <button
        type="button"
        onClick={onClick}
        aria-pressed={active}
        className={`inline-flex h-9 shrink-0 cursor-pointer items-center gap-1.5 rounded-full border px-3.5 text-sm font-medium transition-colors focus-visible:outline-3 focus-visible:outline-primary focus-visible:outline-offset-2 ${
            active ? 'border-primary bg-primary text-on-primary' : 'border-line bg-surface text-ink-2 hover:border-line-strong hover:text-ink'
        }`}
    >
        {children}
    </button>
);

interface CuisineChipsProps {
    cuisines: CuisineCount[];
    selected: string;
    onSelect: (cuisine: string) => void;
}

/** One row of cuisine toggles; swipes on phones, wraps on wider screens. */
export const CuisineChips: React.FC<CuisineChipsProps> = ({ cuisines, selected, onSelect }) => {
    if (cuisines.length === 0) return null;

    return (
        <div role="group" aria-label="Filter by cuisine" className="no-scrollbar -mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0">
            <Chip active={selected === ''} onClick={() => onSelect('')}>
                All cuisines
            </Chip>
            {cuisines.map((c) => (
                <Chip key={c.cuisine} active={selected === c.cuisine} onClick={() => onSelect(selected === c.cuisine ? '' : c.cuisine)}>
                    {c.cuisine}
                    <span className={selected === c.cuisine ? 'opacity-70' : 'text-ink-3'}>{c.count}</span>
                </Chip>
            ))}
        </div>
    );
};
