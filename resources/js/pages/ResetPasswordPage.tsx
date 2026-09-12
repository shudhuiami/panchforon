import React, { useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { Lock, Mail } from 'lucide-react';
import { authApi } from '../api/auth';
import { ApiError } from '../api/client';
import { AuthLayout } from '../features/auth/AuthLayout';
import { Button } from '../components/ui/Button';
import { Input } from '../components/ui/Input';
import { Alert } from '../components/ui/Alert';

export const ResetPasswordPage: React.FC = () => {
    const [params] = useSearchParams();
    const navigate = useNavigate();
    const token = params.get('token') ?? '';

    const [email, setEmail] = useState(params.get('email') ?? '');
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError(null);
        if (password !== passwordConfirmation) {
            setError('The passwords don’t match.');
            return;
        }
        setIsLoading(true);
        try {
            await authApi.resetPassword({ token, email: email.trim(), password, password_confirmation: passwordConfirmation });
            navigate('/login', { replace: true, state: { resetDone: true } });
        } catch (err) {
            setError(err instanceof ApiError ? err.message : 'We couldn’t reset the password.');
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <AuthLayout eyebrow="Reset password" title="Pick a new password." blurb="Choose something at least eight characters long. Every other device will be signed out.">
            {!token && (
                <Alert variant="warning" className="mb-4">
                    This link is missing its token. Request a new one from the forgot-password page.
                </Alert>
            )}
            {error && (
                <Alert variant="error" onClose={() => setError(null)} className="mb-4">
                    {error}
                </Alert>
            )}
            <form onSubmit={handleSubmit} className="space-y-5">
                <Input label="Email" id="reset-email" type="email" required autoComplete="email" value={email} onChange={(e) => setEmail(e.target.value)} leftIcon={<Mail className="size-4" />} />
                <Input
                    label="New password"
                    id="reset-password"
                    type="password"
                    required
                    minLength={8}
                    autoComplete="new-password"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    leftIcon={<Lock className="size-4" />}
                />
                <Input
                    label="Confirm new password"
                    id="reset-password-confirm"
                    type="password"
                    required
                    minLength={8}
                    autoComplete="new-password"
                    value={passwordConfirmation}
                    onChange={(e) => setPasswordConfirmation(e.target.value)}
                    leftIcon={<Lock className="size-4" />}
                />
                <Button type="submit" size="lg" isLoading={isLoading} disabled={!token} className="w-full">
                    Set the new password
                </Button>
            </form>
            <p className="mt-6 text-center text-sm text-ink-2">
                <Link to="/forgot-password" className="font-semibold text-primary hover:underline">
                    Request a fresh link
                </Link>
            </p>
        </AuthLayout>
    );
};
