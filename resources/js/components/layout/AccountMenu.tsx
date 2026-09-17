import React, { useEffect, useRef, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { ChefHat, ChevronDown, Heart, LayoutDashboard, LogOut, PlusCircle, ShieldCheck, ShoppingBasket, UserRound, type LucideIcon } from 'lucide-react';
import { useAuth } from '../../context/AuthContext';
import { Avatar } from '../ui/Avatar';
import { Sheet } from '../ui/Sheet';
import { ButtonLink } from '../ui/Button';
import { useSiteSettings } from '../../features/site/useSiteSettings';

type MenuTone = 'primary' | 'danger';

interface MenuItem {
    label: string;
    icon: LucideIcon;
    to?: string;
    href?: string;
    onSelect?: () => void;
    tone?: MenuTone;
}

interface AccountMenuListProps {
    onNavigate: () => void;
}

/**
 * The account actions, rendered by the dropdown and the sheet alike. Posting is
 * the only thing here that makes something, so it leads and it is tinted. Meal
 * plan is deliberately absent: the desktop nav pill and the phone "Plan" tab own it.
 *
 * Only a creator sees "Post a recipe", because only a creator may. A member gets
 * the way in instead, in the same slot, so the menu never offers a door that is
 * locked. The studio sits behind the same test, for the same reason.
 */
const AccountMenuList: React.FC<AccountMenuListProps> = ({ onNavigate }) => {
    const { user, logout } = useAuth();
    const navigate = useNavigate();

    if (!user) return null;

    const canPostRecipes = user.role === 'creator' || user.role === 'admin';

    const items: MenuItem[] = [
        canPostRecipes
            ? { label: 'Post a recipe', icon: PlusCircle, to: '/recipes/create', tone: 'primary' }
            : { label: 'Become a creator', icon: ChefHat, to: '/become-a-creator', tone: 'primary' },
        /* The studio is a Filament panel, not a React route, so it needs a real
           document load rather than a router link. */
        ...(canPostRecipes ? [{ label: 'Creator studio', icon: LayoutDashboard, href: '/studio' } as MenuItem] : []),
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

    const tones: Record<MenuTone, string> = {
        primary: 'bg-primary-soft font-semibold text-primary hover:bg-primary hover:text-on-primary',
        danger: 'font-medium text-hot hover:bg-hot-soft',
    };

    const itemClass = (tone?: MenuTone) =>
        `flex w-full items-center gap-3 rounded-2xl px-3 py-2.5 text-sm transition-colors ${
            tone ? tones[tone] : 'font-medium text-ink hover:bg-surface-2'
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
                <AccountMenuList onNavigate={onClose} />
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
