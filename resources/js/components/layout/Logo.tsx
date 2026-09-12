import React from 'react';
import { Link } from 'react-router-dom';

/**
 * The brand mark: five spice dots. Panch phoron is the Bengali five-spice
 * blend, so the mark is literal and needs no font to render — the same SVG
 * serves the favicon and the app icons.
 */
export const Mark: React.FC<{ className?: string }> = ({ className = 'size-8' }) => (
    <svg viewBox="0 0 64 64" className={className} aria-hidden="true" focusable="false">
        <rect width="64" height="64" rx="16" fill="var(--surface-2)" />
        <circle cx="32" cy="17" r="6" fill="var(--primary)" />
        <circle cx="46.3" cy="27.4" r="6" fill="var(--turmeric)" />
        <circle cx="40.8" cy="44.1" r="6" fill="var(--hot)" />
        <circle cx="23.2" cy="44.1" r="6" fill="var(--mint)" />
        <circle cx="17.7" cy="27.4" r="6" fill="var(--plum)" />
    </svg>
);

export const Logo: React.FC<{ className?: string; wordmark?: boolean }> = ({ className = '', wordmark = true }) => (
    <Link to="/" className={`inline-flex items-center gap-2.5 rounded-full focus-visible:outline-3 focus-visible:outline-primary focus-visible:outline-offset-3 ${className}`} aria-label="Panchforon home">
        <Mark />
        {wordmark && <span className="font-display text-xl font-semibold tracking-tight text-ink">Panchforon</span>}
    </Link>
);
