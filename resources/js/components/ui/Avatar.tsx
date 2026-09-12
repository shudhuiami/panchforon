import React from 'react';
import { User as UserIcon } from 'lucide-react';

export function getInitials(name?: string | null): string {
    if (!name) return '';
    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

const sizes = {
    sm: 'size-8 text-xs',
    md: 'size-10 text-sm',
    lg: 'size-14 text-lg',
};

export interface AvatarProps {
    name?: string | null;
    size?: keyof typeof sizes;
    className?: string;
}

/** Initials in a tinted disc. Decorative: the accessible name belongs on the control around it. */
export const Avatar: React.FC<AvatarProps> = ({ name, size = 'md', className = '' }) => (
    <span
        aria-hidden="true"
        className={`inline-flex shrink-0 items-center justify-center rounded-full bg-primary-soft font-display font-semibold text-primary ring-1 ring-primary/30 ${sizes[size]} ${className}`}
    >
        {getInitials(name) || <UserIcon className="size-1/2" />}
    </span>
);
