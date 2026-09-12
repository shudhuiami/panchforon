import React, { useState } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { Button } from '../components/ui/Button';
import { Input } from '../components/ui/Input';
import { Alert } from '../components/ui/Alert';
import { Sparkles, UserPlus, Lock, Mail, User, ArrowRight, PartyPopper, Check } from 'lucide-react';

const SPICE_DOTS = ['bg-saffron', 'bg-chili', 'bg-turmeric', 'bg-mint', 'bg-plum'];

const FLOATING_CHIPS = [
    { emoji: '🍛', text: 'Publish your family recipes', rotate: 'rotate-2', delay: '' },
    { emoji: '🧑‍🍳', text: 'Rate & review community dishes', rotate: '-rotate-3', delay: 'animation-delay-300' },
    { emoji: '🧺', text: 'One grocery list for the whole week', rotate: 'rotate-1', delay: 'animation-delay-500' },
];

export const RegisterPage: React.FC = () => {
    const { register, demoLogin } = useAuth();
    const navigate = useNavigate();
    const location = useLocation();
    const from = (location.state as { from?: { pathname?: string } } | null)?.from?.pathname || '/meal-plan';

    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [isDemoLoading, setIsDemoLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError(null);

        if (password !== passwordConfirmation) {
            setError('Passwords do not match.');
            return;
        }

        if (password.length < 8) {
            setError('Password must be at least 8 characters long.');
            return;
        }

        setIsLoading(true);

        try {
            await register(name, email, password);
            navigate(from, { replace: true });
        } catch (err: any) {
            setError(err.message || 'Failed to create account.');
        } finally {
            setIsLoading(false);
        }
    };

    const handleDemoLogin = async () => {
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

    const passwordLongEnough = password.length >= 8;
    const passwordsMatch = passwordConfirmation.length > 0 && password === passwordConfirmation;

    return (
        <div className="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 sm:py-10 lg:py-14">
            <div className="grid gap-6 lg:grid-cols-[1.05fr_1fr] lg:gap-10 xl:gap-16">
                {/* ------------------------------------------------------------ */}
                {/* Left: plum color-blocked story panel                          */}
                {/* ------------------------------------------------------------ */}
                <aside className="relative hidden overflow-hidden rounded-4xl bg-plum-gradient p-10 text-white shadow-glow-plum lg:flex lg:min-h-[680px] lg:flex-col xl:p-14 animate-fade-in">
                    <div className="absolute inset-0 bg-dots-light opacity-70" />
                    <div className="absolute -left-20 -top-20 h-72 w-72 rounded-full bg-white/10 blur-2xl" />
                    <div className="absolute -bottom-28 -right-20 h-80 w-80 blob-2 bg-mint/40 blur-2xl" />

                    <div className="relative flex items-center justify-between">
                        <Link to="/" className="inline-flex items-center gap-3 font-display text-2xl font-extrabold tracking-tight">
                            <span className="flex items-center gap-1 rounded-full bg-white/20 px-2.5 py-2 backdrop-blur">
                                {SPICE_DOTS.map((dot) => (
                                    <span key={dot} className={`h-2.5 w-2.5 rounded-full ${dot} ring-2 ring-white/70`} />
                                ))}
                            </span>
                            Panchforon
                        </Link>
                        <span className="sticker rounded-full bg-turmeric px-3 py-1 font-display text-xs font-extrabold uppercase tracking-wider text-ink shadow-pop-sm">
                            Free forever
                        </span>
                    </div>

                    <div className="relative mt-auto space-y-6 pt-16">
                        <p className="font-display text-5xl font-extrabold leading-[1.02] tracking-tight text-white xl:text-6xl">
                            Join the
                            <br />
                            <span className="relative inline-block">
                                bazaar
                                <span className="absolute -bottom-1 left-0 h-3 w-full rounded-full bg-mint/80 -z-10" />
                            </span>
                            <span className="ml-3 inline-block animate-wiggle">🎉</span>
                        </p>
                        <p className="max-w-md text-base font-medium text-white/85">
                            A community kitchen where regional recipes get planned, rated and shopped for together.
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
                            <span className="sticker-r rounded-full bg-plum px-2.5 py-1 font-display text-[10px] font-extrabold uppercase tracking-wider text-white">
                                Join free
                            </span>
                        </div>

                        <div className="flex items-start gap-4">
                            <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-plum-soft text-plum-deep shadow-glow-plum">
                                <PartyPopper className="h-7 w-7" />
                            </div>
                            <div className="space-y-1">
                                <h1 className="font-display text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">
                                    Create your <span className="text-spice">account</span>
                                </h1>
                                <p className="text-sm text-ink-2">
                                    Plan meals, publish regional recipes and rate dishes with the community.
                                </p>
                            </div>
                        </div>

                        {/* Demo shortcut */}
                        <div className="mt-7 flex flex-col gap-3 rounded-2xl border border-turmeric/50 bg-turmeric-soft p-4 sm:flex-row sm:items-center sm:justify-between">
                            <span className="inline-flex items-center gap-2 text-sm font-bold text-ink">
                                <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-turmeric text-ink">
                                    <Sparkles className="h-4 w-4" />
                                </span>
                                Just evaluating the app?
                            </span>
                            <Button
                                type="button"
                                variant="secondary"
                                size="sm"
                                onClick={handleDemoLogin}
                                isLoading={isDemoLoading}
                                className="rounded-full"
                            >
                                Use demo account
                            </Button>
                        </div>

                        {error && (
                            <Alert
                                variant="error"
                                onClose={() => setError(null)}
                                className="mt-5 animate-pop-in"
                            >
                                {error}
                            </Alert>
                        )}

                        <form onSubmit={handleSubmit} className="mt-6 space-y-5">
                            <Input
                                label="Full Name"
                                id="register-name"
                                type="text"
                                name="name"
                                required
                                autoComplete="name"
                                value={name}
                                onChange={(e) => setName(e.target.value)}
                                placeholder="Rahim Ahmed"
                                leftIcon={<User className="w-4 h-4" />}
                            />

                            <Input
                                label="Email Address"
                                id="register-email"
                                type="email"
                                name="email"
                                required
                                autoComplete="email"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                placeholder="name@example.com"
                                leftIcon={<Mail className="w-4 h-4" />}
                            />

                            <div className="grid gap-5 sm:grid-cols-2">
                                <Input
                                    label="Password"
                                    id="register-password"
                                    type="password"
                                    name="password"
                                    required
                                    minLength={8}
                                    autoComplete="new-password"
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    placeholder="Minimum 8 characters"
                                    leftIcon={<Lock className="w-4 h-4" />}
                                />

                                <Input
                                    label="Confirm Password"
                                    id="register-password-confirm"
                                    type="password"
                                    name="password_confirmation"
                                    required
                                    minLength={8}
                                    autoComplete="new-password"
                                    value={passwordConfirmation}
                                    onChange={(e) => setPasswordConfirmation(e.target.value)}
                                    placeholder="Re-type password"
                                    leftIcon={<Lock className="w-4 h-4" />}
                                />
                            </div>

                            {/* Live password hints */}
                            <ul className="flex flex-wrap gap-2 text-xs font-bold" aria-live="polite">
                                <li
                                    className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 transition-colors ${
                                        passwordLongEnough ? 'bg-mint-soft text-mint-deep' : 'bg-cream-2 text-ink-3'
                                    }`}
                                >
                                    <Check className="h-3.5 w-3.5" />
                                    8+ characters
                                </li>
                                <li
                                    className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 transition-colors ${
                                        passwordsMatch ? 'bg-mint-soft text-mint-deep' : 'bg-cream-2 text-ink-3'
                                    }`}
                                >
                                    <Check className="h-3.5 w-3.5" />
                                    Passwords match
                                </li>
                            </ul>

                            <Button
                                type="submit"
                                variant="primary"
                                size="lg"
                                isLoading={isLoading}
                                className="w-full rounded-full shadow-glow-saffron hover:-translate-y-0.5"
                            >
                                <UserPlus className="w-4 h-4" />
                                Create Account
                                <ArrowRight className="w-4 h-4" />
                            </Button>
                        </form>

                        <div className="mt-7 flex flex-col items-center justify-between gap-3 rounded-2xl bg-cream-2 p-4 text-sm sm:flex-row">
                            <span className="text-ink-2">Already have an account?</span>
                            <Link
                                to="/login"
                                className="inline-flex items-center gap-1.5 rounded-full bg-saffron-soft px-4 py-1.5 font-extrabold text-saffron-deep transition-all hover:bg-saffron hover:text-ink"
                            >
                                Sign in
                                <ArrowRight className="h-4 w-4" />
                            </Link>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    );
};
