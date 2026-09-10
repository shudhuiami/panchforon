import React, { createContext, useContext, useEffect, useState } from 'react';
import { User } from '../types/api';
import { authApi } from '../api/auth';

interface AuthContextType {
    user: User | null;
    token: string | null;
    isLoading: boolean;
    login: (email: string, password: string) => Promise<void>;
    register: (name: string, email: string, password: string) => Promise<void>;
    demoLogin: () => Promise<void>;
    logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const [user, setUser] = useState<User | null>(null);
    const [token, setToken] = useState<string | null>(() => localStorage.getItem('panchforon_token'));
    const [isLoading, setIsLoading] = useState<boolean>(true);

    useEffect(() => {
        if (!token) {
            setIsLoading(false);
            return;
        }

        authApi
            .me()
            .then((res) => {
                setUser(res.data);
            })
            .catch(() => {
                localStorage.removeItem('panchforon_token');
                setToken(null);
                setUser(null);
            })
            .finally(() => {
                setIsLoading(false);
            });
    }, [token]);

    const login = async (email: string, password: string) => {
        const res = await authApi.login({ email, password });
        localStorage.setItem('panchforon_token', res.token);
        setToken(res.token);
        setUser(res.user);
    };

    const register = async (name: string, email: string, password: string) => {
        const res = await authApi.register({ name, email, password });
        localStorage.setItem('panchforon_token', res.token);
        setToken(res.token);
        setUser(res.user);
    };

    const demoLogin = async () => {
        await login('demo@panchforon.com', 'password');
    };

    const logout = async () => {
        try {
            await authApi.logout();
        } catch {
            // ignore network/auth errors on logout
        } finally {
            localStorage.removeItem('panchforon_token');
            setToken(null);
            setUser(null);
        }
    };

    return (
        <AuthContext.Provider
            value={{
                user,
                token,
                isLoading,
                login,
                register,
                demoLogin,
                logout,
            }}
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