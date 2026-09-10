import React, { useState } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { Button } from '../components/ui/Button';
import { Input } from '../components/ui/Input';
import { Alert } from '../components/ui/Alert';
import { Sparkles, LogIn, Lock, Mail, ArrowRight, Wand2, KeyRound } from 'lucide-react';

const DEMO_EMAIL = 'demo@panchforon.com';
const DEMO_PASSWORD = 'password';

const SPICE_DOTS = ['bg-saffron', 'bg-chili', 'bg-turmeric', 'bg-mint', 'bg-plum'];

const FLOATING_CHIPS = [
    { emoji: '🗓️', text: 'Plan a whole week in minutes', rotate: '-rotate-3', delay: '' },
    { emoji: '🛒', text: 'Merged grocery lists, zero duplicates', rotate: 'rotate-2', delay: 'animation-delay-300' },
    { emoji: '⭐', text: 'Rate dishes, remember the winners', rotate: '-rotate-2', delay: 'animation-delay-500' },
];

export const LoginPage: React.FC = () => {
    const { login, demoLogin } = useAuth();
    const navigate = useNavigate();
    const location = useLocation();

    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [isDemoLoading, setIsDemoLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const from = (location.state as any)?.from?.pathname || '/meal-plan';

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError(null);
        setIsLoading(true);

        try {
            await login(email, password);
            navigate(from, { replace: true });
        } catch (err: any) {
            setError(err.message || 'Invalid email or password.');
        } finally {
            setIsLoading(false);
        }
    };

    const handleDemoLogin = async () => {
        setError(null);
        setIsDemoLoading(true);
        try {
            await demoLogin();
            navigate(from, { replace: true });
        } catch (err: any) {
            setError(err.message || 'Failed to authenticate with demo account.');
        } finally {
            setIsDemoLoading(false);
        }
    };

    const fillDemoCredentials = () => {
        setError(null);
        setEmail(DEMO_EMAIL);
        setPassword(DEMO_PASSWORD);
    };

    return (
        <div className="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 sm:py-10 lg:py-14">
            <div className="grid gap-6 lg:grid-cols-[1.05fr_1fr] lg:gap-10 xl:gap-16">
                {/* ------------------------------------------------------------ */}
                {/* Left: color-blocked story panel                               */}
                {/* ------------------------------------------------------------ */}
                <aside
                    className="relative hidden overflow-hidden rounded-4xl bg-spice-gradient p-10 text-white shadow-glow-chili lg:flex lg:min-h-[620px] lg:flex-col xl:p-14 animate-fade-in"
                >
                    <div className="absolute inset-0 bg-dots-light opacity-70" />
                    <div className="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-white/10 blur-2xl" />
                    <div className="absolute -bottom-32 -left-16 h-80 w-80 blob-1 bg-turmeric/40 blur-2xl" />

                    <div className="relative flex items-center justify-between">
                        <Link to="/" className="inline-flex items-center gap-3 font-display text-2xl font-extrabold tracking-tight">
                            <span className="flex items-center gap-1 rounded-full bg-white/20 px-2.5 py-2 backdrop-blur">
                                {SPICE_DOTS.map((dot) => (
                                    <span key={dot} className={`h-2.5 w-2.5 rounded-full ${dot} ring-2 ring-white/70`} />
                                ))}
                            </span>
                            Panchforon
                        </Link>
                        <span className="sticker-r rounded-full bg-white px-3 py-1 font-display text-xs font-extrabold uppercase tracking-wider text-chili-deep shadow-pop-sm">
                            Members only
                        </span>
                    </div>

                    <div className="relative mt-auto space-y-6 pt-16">
                        <p className="font-display text-5xl font-extrabold leading-[1.02] tracking-tight text-white xl:text-6xl">
                            Welcome back,
                            <br />
                            <span className="relative inline-block">
                                chef
                                <span className="absolute -bottom-1 left-0 h-3 w-full rounded-full bg-turmeric/80 -z-10" />
                            </span>
                            <span className="ml-3 inline-block animate-wiggle">👋</span>
                        </p>
                        <p className="max-w-md text-base font-medium text-white/85">
                            Your meal plan, grocery list and saved ratings are exactly where you left them. Pick up the ladle.
                        </p>
                    </div>

                    <div className="relative mt-10 flex flex-wrap gap-3">
                        {FLOATING_CHIPS.map((chip) => (
                            <div
                                key={chip.text}
                                className={`glass ${chip.rotate} inline-flex items-center gap-2.5 rounded-full px-4 py-2.5 text-sm font-bold text-ink shadow-lg animate-float ${chip.delay}`}
                            >
                                <span className="text-lg leading-none">{chip.emoji}</span>
                                {chip.text}
                            </div>
                        ))}
                    </div>
                </aside>

                {/* ------------------------------------------------------------ */}
                {/* Right: the form                                               */}
                {/* ------------------------------------------------------------ */}
                <section className="flex items-center animate-slide-up">
                    <div className="pop-sm relative w-full rounded-4xl bg-paper p-6 sm:p-10">
                        {/* Mobile-only logo (left panel is hidden) */}
                        <div className="mb-6 flex items-center justify-between lg:hidden">
                            <Link to="/" className="inline-flex items-center gap-2 font-display text-xl font-extrabold tracking-tight text-ink">
                                <span className="flex items-center gap-1">
                                    {SPICE_DOTS.map((dot) => (
                                        <span key={dot} className={`h-2 w-2 rounded-full ${dot}`} />
                                    ))}
                                </span>
                                Panchforon
                            </Link>
                            <span className="sticker rounded-full bg-turmeric px-2.5 py-1 font-display text-[10px] font-extrabold uppercase tracking-wider text-ink">
                                Sign in
                            </span>
                        </div>

                        <div className="flex items-start gap-4">
                            <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-saffron-soft text-saffron-deep shadow-glow-saffron">
                                <KeyRound className="h-7 w-7" />
                            </div>
                            <div className="space-y-1">
                                <h1 className="font-display text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">
                                    Sign <span className="text-sunrise">in</span>
                                </h1>
                                <p className="text-sm text-ink-2">
                                    Access your weekly meal plan, ratings and merged grocery lists.
                                </p>
                            </div>
                        </div>

                        {/* Demo credentials chip */}
                        <div className="mt-7 rounded-2xl border border-turmeric/50 bg-turmeric-soft p-4">
                            <div className="flex flex-wrap items-center gap-3">
                                <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-turmeric text-ink">
                                    <Sparkles className="h-4 w-4" />
                                </div>
                                <div className="min-w-0 flex-1">
                                    <p className="font-display text-sm font-extrabold text-ink">Just looking around?</p>
                                    <p className="truncate text-xs text-turmeric-deep">
                                        <code className="font-semibold">{DEMO_EMAIL}</code>
                                        <span className="mx-1.5 text-ink-3">/</span>
                                        <code className="font-semibold">{DEMO_PASSWORD}</code>
                                    </p>
                                </div>
                                <div className="flex w-full gap-2 sm:w-auto">
                                    <button
                                        type="button"
                                        onClick={fillDemoCredentials}
                                        className="inline-flex flex-1 items-center justify-center gap-1.5 rounded-full border-2 border-ink bg-paper px-3.5 py-2 text-xs font-extrabold text-ink transition-all hover:-translate-y-0.5 hover:bg-cream cursor-pointer sm:flex-none"
                                    >
                                        <Wand2 className="h-3.5 w-3.5" />
                                        Use demo
                                    </button>
                                    <Button
                                        type="button"
                                        variant="dark"
                                        size="sm"
                                        onClick={handleDemoLogin}
                                        isLoading={isDemoLoading}
                                        className="flex-1 rounded-full sm:flex-none"
                                    >
                                        1-click sign in
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <div className="my-6 flex items-center gap-3" aria-hidden="true">
                            <div className="h-px flex-1 bg-line-strong" />
                            <span className="rounded-full bg-cream-2 px-3 py-1 text-[10px] font-extrabold uppercase tracking-widest text-ink-2">
                                or with email
                            </span>
                            <div className="h-px flex-1 bg-line-strong" />
                        </div>

                        {error && (
                            <Alert
                                variant="error"
                                onClose={() => setError(null)}
                                className="mb-5 animate-pop-in"
                            >
                                {error}
                            </Alert>
                        )}

                        <form onSubmit={handleSubmit} className="space-y-5">
                            <Input
                                label="Email Address"
                                id="login-email"
                                type="email"
                                name="email"
                                required
                                autoComplete="email"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                placeholder="name@example.com"
                                leftIcon={<Mail className="w-4 h-4" />}
                            />

                            <Input
                                label="Password"
                                id="login-password"
                                type="password"
                                name="password"
                                required
                                autoComplete="current-password"
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                                placeholder="••••••••"
                                leftIcon={<Lock className="w-4 h-4" />}
                            />

                            <Button
                                type="submit"
                                variant="primary"
                                size="lg"
                                isLoading={isLoading}
                                className="w-full rounded-full shadow-glow-saffron hover:-translate-y-0.5"
                            >
                                <LogIn className="w-4 h-4" />
                                Sign In
                                <ArrowRight className="w-4 h-4" />
                            </Button>
                        </form>

                        <div className="mt-7 flex flex-col items-center justify-between gap-3 rounded-2xl bg-cream-2 p-4 text-sm sm:flex-row">
                            <span className="text-ink-2">New to the bazaar?</span>
                            <Link
                                to="/register"
                                className="inline-flex items-center gap-1.5 rounded-full bg-plum-soft px-4 py-1.5 font-extrabold text-plum-deep transition-all hover:bg-plum hover:text-white"
                            >
                                Create an account
                                <ArrowRight className="h-4 w-4" />
                            </Link>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    );
};
