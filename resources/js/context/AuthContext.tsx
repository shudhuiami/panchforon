import React, { createContext, useCallback, useContext, useEffect, useState } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { AuthResponse, CurrentUser } from '../types/api';
import { authApi } from '../api/auth';
import { authEvents, getToken, setToken as persistToken, type AuthEventDetail } from '../api/client';

export interface AuthNotice {
    kind: 'expired' | 'suspended';
    message: string;
}

interface AuthContextType {
    user: CurrentUser | null;
    token: string | null;
    /** True until a stored token has been verified against the API. */
    isLoading: boolean;
    /** Why the last session ended, for the login page to show once. */
    notice: AuthNotice | null;
    clearNotice: () => void;
    login: (email: string, password: string) => Promise<void>;
    register: (name: string, email: string, password: string) => Promise<void>;
    demoLogin: () => Promise<void>;
    /** Trade a one-time social sign-in code for a session. */
    completeSocialLogin: (code: string) => Promise<void>;
    logout: () => Promise<void>;
    /** Replace the cached account after the cook edits it. */
    setUser: (user: CurrentUser) => void;
    /** Adopt a token the API reissued, e.g. after a password change. */
    adoptToken: (token: string) => void;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const queryClient = useQueryClient();
    const [token, setToken] = useState<string | null>(() => getToken());
    const [user, setUser] = useState<CurrentUser | null>(null);
    const [isLoading, setIsLoading] = useState<boolean>(() => getToken() !== null);
    const [notice, setNotice] = useState<AuthNotice | null>(null);

    const forget = useCallback(() => {
        persistToken(null);
        setToken(null);
        setUser(null);
        // Nothing cached for one account may leak into the next one on this tab.
        queryClient.clear();
    }, [queryClient]);

    const remember = useCallback(
        (response: AuthResponse) => {
            persistToken(response.token);
            queryClient.clear();
            setToken(response.token);
            setUser(response.user);
            setNotice(null);
        },
        [queryClient],
    );

    // Verify a stored token exactly once, on mount. Logging in sets the user
    // from the auth response directly, so there is no second /user round trip.
    useEffect(() => {
        if (!getToken()) {
            return;
        }

        let cancelled = false;

        authApi
            .me()
            .then((res) => {
                if (!cancelled) setUser(res.data);
            })
            .catch(() => {
                if (!cancelled) forget();
            })
            .finally(() => {
                if (!cancelled) setIsLoading(false);
            });

        return () => {
            cancelled = true;
        };
    }, [forget]);

    // Any request, anywhere, that comes back 401 or suspended ends the session.
    useEffect(() => {
        const onAuthEvent = (event: Event) => {
            const detail = (event as CustomEvent<AuthEventDetail>).detail;
            forget();
            setNotice(
                detail.type === 'suspended'
                    ? {
                          kind: 'suspended',
                          message: detail.reason
                              ? `Your account has been suspended: ${detail.reason}`
                              : 'Your account has been suspended.',
                      }
                    : { kind: 'expired', message: 'Your session has ended. Please sign in again.' },
            );
        };

        authEvents.addEventListener('auth', onAuthEvent);
        return () => authEvents.removeEventListener('auth', onAuthEvent);
    }, [forget]);

    const login = async (email: string, password: string) => {
        remember(await authApi.login({ email, password }));
    };

    const register = async (name: string, email: string, password: string) => {
        remember(await authApi.register({ name, email, password }));
    };

    const demoLogin = async () => {
        await login('demo@panchforon.com', 'password');
    };

    const adoptToken = useCallback((next: string) => {
        persistToken(next);
        setToken(next);
    }, []);

    const completeSocialLogin = async (code: string) => {
        remember(await authApi.exchangeSocialCode(code));
    };

    const logout = async () => {
        try {
            await authApi.logout();
        } catch {
            // The token is discarded either way.
        } finally {
            forget();
        }
    };

    return (
        <AuthContext.Provider
            value={{ user, token, isLoading, notice, clearNotice: () => setNotice(null), login, register, demoLogin, completeSocialLogin, logout, setUser, adoptToken }}
        >
            {children}
        </AuthContext.Provider>
    );
};

export const useAuth = (): AuthContextType => {
    const context = useContext(AuthContext);
    if (!context) {
        throw new Error('useAuth must be used within an AuthProvider');
    }
    return context;
};
