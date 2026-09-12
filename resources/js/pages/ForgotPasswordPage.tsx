import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { Mail } from 'lucide-react';
import { authApi } from '../api/auth';
import { ApiError } from '../api/client';
import { AuthLayout } from '../features/auth/AuthLayout';
import { Button } from '../components/ui/Button';
import { Input } from '../components/ui/Input';
import { Alert } from '../components/ui/Alert';

export const ForgotPasswordPage: React.FC = () => {
    const [email, setEmail] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [sentMessage, setSentMessage] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError(null);
        setIsLoading(true);
        try {
            const res = await authApi.forgotPassword(email.trim());
            setSentMessage(res.message);
        } catch (err) {
            setError(err instanceof ApiError ? err.message : 'We couldn’t send the link just now.');
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <AuthLayout eyebrow="Forgot your password" title="We’ll send you a way back in." blurb="Give us the address on your account and we’ll email a link to set a new password.">
            {sentMessage ? (
                <Alert variant="success" title="Check your inbox">
                    {sentMessage}
                </Alert>
            ) : (
                <>
                    {error && (
                        <Alert variant="error" onClose={() => setError(null)} className="mb-4">
                            {error}
                        </Alert>
                    )}
                    <form onSubmit={handleSubmit} className="space-y-5">
                        <Input label="Email" id="forgot-email" type="email" required autoComplete="email" value={email} onChange={(e) => setEmail(e.target.value)} placeholder="name@example.com" leftIcon={<Mail className="size-4" />} />
                        <Button type="submit" size="lg" isLoading={isLoading} className="w-full">
                            Send the reset link
                        </Button>
                    </form>
                </>
            )}
            <p className="mt-6 text-center text-sm text-ink-2">
                Remembered it?{' '}
                <Link to="/login" className="font-semibold text-primary hover:underline">
                    Back to sign in
                </Link>
            </p>
        </AuthLayout>
    );
};
