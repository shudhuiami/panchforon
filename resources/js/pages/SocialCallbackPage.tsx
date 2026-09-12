import React, { useEffect, useRef, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { AuthLayout } from '../features/auth/AuthLayout';
import { Alert } from '../components/ui/Alert';
import { ButtonLink } from '../components/ui/Button';
import { LoadingSpinner } from '../components/common/LoadingSpinner';

/**
 * Where a provider sends the browser back to. It trades the one-time code
 * for a session and gets out of the way.
 */
export const SocialCallbackPage: React.FC = () => {
    const [params] = useSearchParams();
    const navigate = useNavigate();
    const { completeSocialLogin } = useAuth();
    const [error, setError] = useState<string | null>(params.get('error'));
    const attempted = useRef(false);

    const code = params.get('code');

    useEffect(() => {
        if (!code || attempted.current) return;
        attempted.current = true;

        completeSocialLogin(code)
            .then(() => navigate('/', { replace: true }))
            .catch((err: unknown) => setError(err instanceof Error ? err.message : 'That sign-in could not be completed.'));
    }, [code, completeSocialLogin, navigate]);

    if (!error && code) {
        return <LoadingSpinner message="Signing you in…" />;
    }

    return (
        <AuthLayout eyebrow="Sign in" title="That didn’t go through." blurb="The provider sent us back without a usable sign-in.">
            <Alert variant="error">{error ?? 'That sign-in link is missing its code.'}</Alert>
            <div className="mt-6">
                <ButtonLink to="/login" size="lg" className="w-full">
                    Back to sign in
                </ButtonLink>
            </div>
        </AuthLayout>
    );
};
