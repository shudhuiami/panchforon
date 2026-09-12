import React, { useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { Lock, Mail, Sparkles } from 'lucide-react';
import { useAuth } from '../context/AuthContext';
import { useSiteSettings } from '../features/site/useSiteSettings';
import { AuthLayout } from '../features/auth/AuthLayout';
import { SocialLogins } from '../features/auth/SocialLogins';
import { Button } from '../components/ui/Button';
import { Input } from '../components/ui/Input';
import { Alert } from '../components/ui/Alert';

export const LoginPage: React.FC = () => {
    const { login, demoLogin, notice, clearNotice } = useAuth();
    const settings = useSiteSettings();
    const navigate = useNavigate();
    const location = useLocation();
    const state = location.state as { from?: { pathname?: string }; resetDone?: boolean } | null;
    const from = state?.from?.pathname || '/meal-plan';
    const [showResetDone, setShowResetDone] = useState(Boolean(state?.resetDone));

    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [isDemoLoading, setIsDemoLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError(null);
        setIsLoading(true);
        try {
            await login(email, password);
            navigate(from, { replace: true });
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Invalid email or password.');
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
        } catch (err) {
            setError(err instanceof Error ? err.message : 'The demo account is unavailable right now.');
        } finally {
            setIsDemoLoading(false);
        }
    };

    return (
        <AuthLayout eyebrow="Welcome back" title="Sign in to your kitchen." blurb="Your meal plan, shopping list and ratings are exactly where you left them.">
            <div className="space-y-4">
                {showResetDone && (
                    <Alert variant="success" onClose={() => setShowResetDone(false)}>
                        Your password has been reset. Sign in with the new one.
                    </Alert>
                )}
                {notice && (
                    <Alert variant="warning" onClose={clearNotice}>
                        {notice.message}
                    </Alert>
                )}
                {error && (
                    <Alert variant="error" onClose={() => setError(null)}>
                        {error}
                    </Alert>
                )}
            </div>

            <form onSubmit={handleSubmit} className="mt-4 space-y-5">
                <Input
                    label="Email"
                    id="login-email"
                    type="email"
                    name="email"
                    required
                    autoComplete="email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    placeholder="name@example.com"
                    leftIcon={<Mail className="size-4" />}
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
                    leftIcon={<Lock className="size-4" />}
                />
                <div className="flex justify-end">
                    <Link to="/forgot-password" className="text-sm text-ink-3 transition-colors hover:text-primary">
                        Forgot your password?
                    </Link>
                </div>
                <Button type="submit" size="lg" isLoading={isLoading} className="w-full">
                    Sign in
                </Button>
            </form>

            <SocialLogins />

            <div className="my-6 flex items-center gap-3" aria-hidden="true">
                <span className="h-px flex-1 bg-line" />
                <span className="text-xs tracking-[0.18em] text-ink-3 uppercase">or</span>
                <span className="h-px flex-1 bg-line" />
            </div>

            <Button type="button" variant="secondary" size="lg" onClick={handleDemoLogin} isLoading={isDemoLoading} className="w-full">
                <Sparkles className="size-4 text-turmeric" aria-hidden="true" />
                Try the demo account
            </Button>

            <p className="mt-6 text-center text-sm text-ink-2">
                New here?{' '}
                {settings.registration_open ? (
                    <Link to="/register" className="font-semibold text-primary hover:underline">
                        Create a free account
                    </Link>
                ) : (
                    'Registration is closed for now.'
                )}
            </p>
        </AuthLayout>
    );
};
