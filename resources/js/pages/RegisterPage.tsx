import React, { useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { Check, Lock, Mail, User, UserRoundX } from 'lucide-react';
import { useAuth } from '../context/AuthContext';
import { useSiteSettings } from '../features/site/useSiteSettings';
import { AuthLayout } from '../features/auth/AuthLayout';
import { SocialLogins } from '../features/auth/SocialLogins';
import { Button } from '../components/ui/Button';
import { Input } from '../components/ui/Input';
import { Alert } from '../components/ui/Alert';
import { StatusPanel } from '../components/common/StatusPanel';

const Hint: React.FC<{ met: boolean; children: React.ReactNode }> = ({ met, children }) => (
    <li className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium transition-colors ${met ? 'bg-mint-soft text-mint' : 'bg-surface-2 text-ink-3'}`}>
        <Check className="size-3.5" aria-hidden="true" />
        {children}
    </li>
);

export const RegisterPage: React.FC = () => {
    const { register } = useAuth();
    const settings = useSiteSettings();
    const navigate = useNavigate();
    const location = useLocation();
    const from = (location.state as { from?: { pathname?: string } } | null)?.from?.pathname || '/meal-plan';

    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    if (!settings.registration_open) {
        return (
            <StatusPanel
                icon={UserRoundX}
                title="Registration is closed"
                text="New accounts aren’t being created right now. If you already have one, you can still sign in."
                action={{ label: 'Sign in', to: '/login' }}
            />
        );
    }

    const passwordLongEnough = password.length >= 8;
    const passwordsMatch = passwordConfirmation.length > 0 && password === passwordConfirmation;

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError(null);
        if (!passwordLongEnough) {
            setError('Your password needs at least 8 characters.');
            return;
        }
        if (password !== passwordConfirmation) {
            setError('The passwords don’t match.');
            return;
        }
        setIsLoading(true);
        try {
            await register(name, email, password);
            navigate(from, { replace: true });
        } catch (err) {
            setError(err instanceof Error ? err.message : 'We couldn’t create the account.');
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <AuthLayout eyebrow="Join free" title="Pull up a chair." blurb="Plan meals, post your family recipes and rate dishes with the community.">
            {error && (
                <Alert variant="error" onClose={() => setError(null)} className="mb-4">
                    {error}
                </Alert>
            )}
            <form onSubmit={handleSubmit} className="space-y-5">
                <Input label="Name" id="register-name" type="text" name="name" required autoComplete="name" value={name} onChange={(e) => setName(e.target.value)} placeholder="Rahim Ahmed" leftIcon={<User className="size-4" />} />
                <Input
                    label="Email"
                    id="register-email"
                    type="email"
                    name="email"
                    required
                    autoComplete="email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    placeholder="name@example.com"
                    leftIcon={<Mail className="size-4" />}
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
                        placeholder="At least 8 characters"
                        leftIcon={<Lock className="size-4" />}
                    />
                    <Input
                        label="Confirm password"
                        id="register-password-confirm"
                        type="password"
                        name="password_confirmation"
                        required
                        minLength={8}
                        autoComplete="new-password"
                        value={passwordConfirmation}
                        onChange={(e) => setPasswordConfirmation(e.target.value)}
                        placeholder="Type it again"
                        leftIcon={<Lock className="size-4" />}
                    />
                </div>
                <ul className="flex flex-wrap gap-2" aria-live="polite">
                    <Hint met={passwordLongEnough}>8+ characters</Hint>
                    <Hint met={passwordsMatch}>Passwords match</Hint>
                </ul>
                <Button type="submit" size="lg" isLoading={isLoading} className="w-full">
                    Create account
                </Button>
            </form>
            <SocialLogins />

            <p className="mt-6 text-center text-sm text-ink-2">
                Already have an account?{' '}
                <Link to="/login" className="font-semibold text-primary hover:underline">
                    Sign in
                </Link>
            </p>
        </AuthLayout>
    );
};
