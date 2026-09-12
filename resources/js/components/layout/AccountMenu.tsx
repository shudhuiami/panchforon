import React, { useEffect, useRef, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { ChevronDown, Heart, LogOut, PlusCircle, ShieldCheck, ShoppingBasket, UserRound, type LucideIcon } from 'lucide-react';
import { useAuth } from '../../context/AuthContext';
import { Avatar } from '../ui/Avatar';
import { Sheet } from '../ui/Sheet';
import { ButtonLink } from '../ui/Button';
import { useSiteSettings } from '../../features/site/useSiteSettings';

interface MenuItem {
    label: string;
    icon: LucideIcon;
    to?: string;
    href?: string;
    onSelect?: () => void;
    tone?: 'danger';
}

interface AccountMenuListProps {
    onNavigate: () => void;
    /** The sheet lives where there is no header CTA, so it carries the create link. */
    withCreateLink?: boolean;
}

/**
 * The account actions, rendered by the dropdown and the sheet alike. Meal plan
 * is deliberately absent: the desktop nav pill and the phone "Plan" tab own it.
 */
const AccountMenuList: React.FC<AccountMenuListProps> = ({ onNavigate, withCreateLink = false }) => {
    const { user, logout } = useAuth();
    const navigate = useNavigate();

    if (!user) return null;

    const items: MenuItem[] = [
        ...(withCreateLink ? [{ label: 'Post a recipe', icon: PlusCircle, to: '/recipes/create' } as MenuItem] : []),
        { label: 'Your account', icon: UserRound, to: '/account' },
        { label: 'Saved recipes', icon: Heart, to: '/saved' },
        { label: 'Shopping list', icon: ShoppingBasket, to: '/shopping-list' },
        ...(user.is_admin ? [{ label: 'Admin panel', icon: ShieldCheck, href: '/admin' } as MenuItem] : []),
        {
            label: 'Sign out',
            icon: LogOut,
            tone: 'danger',
            onSelect: async () => {
                await logout();
                navigate('/');
            },
        },
    ];

    const itemClass = (tone?: 'danger') =>
        `flex w-full items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium transition-colors ${
            tone === 'danger' ? 'text-hot hover:bg-hot-soft' : 'text-ink hover:bg-surface-2'
        }`;

    return (
        <div className="p-2">
            <div className="flex items-center gap-3 px-3 py-3">
                <Avatar name={user.name} size="md" />
                <div className="min-w-0">
                    <p className="truncate text-sm font-semibold text-ink">{user.name}</p>
                    <p className="truncate text-xs text-ink-3">{user.email}</p>
                </div>
            </div>
            <ul className="mt-1 space-y-0.5" role="menu">
                {items.map(({ label, icon: Icon, to, href, onSelect, tone }) => (
                    <li key={label} role="none">
                        {to ? (
                            <Link role="menuitem" to={to} onClick={onNavigate} className={itemClass(tone)}>
                                <Icon className="size-4 shrink-0" aria-hidden="true" /> {label}
                            </Link>
                        ) : href ? (
                            <a role="menuitem" href={href} className={itemClass(tone)}>
                                <Icon className="size-4 shrink-0" aria-hidden="true" /> {label}
                            </a>
                        ) : (
                            <button
                                role="menuitem"
                                type="button"
                                onClick={() => {
                                    onNavigate();
                                    onSelect?.();
                                }}
                                className={`${itemClass(tone)} cursor-pointer`}
                            >
                                <Icon className="size-4 shrink-0" aria-hidden="true" /> {label}
                            </button>
                        )}
                    </li>
                ))}
            </ul>
        </div>
    );
};

/** Desktop: avatar button with a popover. */
export const AccountDropdown: React.FC<{ className?: string }> = ({ className = '' }) => {
    const { user } = useAuth();
    const [open, setOpen] = useState(false);
    const rootRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!open) return;
        const onPointer = (e: PointerEvent) => {
            if (!rootRef.current?.contains(e.target as Node)) setOpen(false);
        };
        const onKey = (e: KeyboardEvent) => {
            if (e.key === 'Escape') setOpen(false);
        };
        document.addEventListener('pointerdown', onPointer);
        window.addEventListener('keydown', onKey);
        return () => {
            document.removeEventListener('pointerdown', onPointer);
            window.removeEventListener('keydown', onKey);
        };
    }, [open]);

    if (!user) return null;

    return (
        <div ref={rootRef} className={`relative ${className}`}>
            <button
                type="button"
                onClick={() => setOpen((v) => !v)}
                aria-haspopup="menu"
                aria-expanded={open}
                aria-label="Account menu"
                className="flex cursor-pointer items-center gap-1 rounded-full py-0.5 pr-1.5 pl-0.5 transition-colors hover:bg-surface-2 focus-visible:outline-3 focus-visible:outline-primary focus-visible:outline-offset-2"
            >
                <Avatar name={user.name} size="sm" />
                <ChevronDown className={`size-4 text-ink-3 transition-transform ${open ? 'rotate-180' : ''}`} aria-hidden="true" />
            </button>
            {open && (
                <div className="absolute right-0 top-full z-50 mt-2 w-72 rounded-3xl border border-line bg-surface shadow-xl animate-scale-in">
                    <AccountMenuList onNavigate={() => setOpen(false)} />
                </div>
            )}
        </div>
    );
};

/** Phones: the "Me" tab opens this sheet. */
export const AccountSheet: React.FC<{ isOpen: boolean; onClose: () => void }> = ({ isOpen, onClose }) => {
    const { user } = useAuth();
    const settings = useSiteSettings();

    return (
        <Sheet isOpen={isOpen} onClose={onClose} label="Account">
            {user ? (
                <AccountMenuList onNavigate={onClose} withCreateLink />
            ) : (
                <div className="space-y-3 p-6">
                    <p className="font-display text-2xl font-semibold text-ink">Your kitchen, saved.</p>
                    <p className="text-sm text-ink-2">Sign in to plan your week, keep a shopping list and rate what you cook.</p>
                    <div className="flex flex-col gap-2 pt-2">
                        <ButtonLink to="/login" onClick={onClose}>Sign in</ButtonLink>
                        {settings.registration_open && (
                            <ButtonLink to="/register" variant="secondary" onClick={onClose}>Create an account</ButtonLink>
                        )}
                    </div>
                </div>
            )}
        </Sheet>
    );
};
