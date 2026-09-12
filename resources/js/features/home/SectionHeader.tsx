import React from 'react';
import { Link } from 'react-router-dom';
import { ArrowRight } from 'lucide-react';
import { Reveal } from '../../components/motion/Reveal';

interface SectionHeaderProps {
    id?: string;
    eyebrow: string;
    title: string;
    text?: string;
    action?: { to: string; label: string };
    className?: string;
}

export const SectionHeader: React.FC<SectionHeaderProps> = ({ id, eyebrow, title, text, action, className = '' }) => (
    <Reveal className={`flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between ${className}`}>
        <div>
            <p className="text-xs font-semibold uppercase tracking-[0.22em] text-primary">{eyebrow}</p>
            <h2 id={id} className="mt-3 max-w-2xl font-display text-3xl font-semibold tracking-tight text-ink text-balance sm:text-4xl lg:text-5xl">
                {title}
            </h2>
            {text && <p className="mt-3 max-w-xl text-base text-ink-2">{text}</p>}
        </div>
        {action && (
            <Link to={action.to} className="group inline-flex shrink-0 items-center gap-2 text-sm font-semibold text-ink transition-colors hover:text-primary">
                {action.label}
                <ArrowRight className="size-4 transition-transform group-hover:translate-x-1" aria-hidden="true" />
            </Link>
        )}
    </Reveal>
);
