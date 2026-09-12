const API_BASE = '/api';
const TOKEN_KEY = 'panchforon_token';

export type AuthEventDetail = { type: 'unauthenticated' } | { type: 'suspended'; reason: string | null };

/**
 * Auth failures are detected here, at the one place every request passes
 * through, and announced so the AuthProvider can end the session and explain
 * why. Components never have to inspect status codes themselves.
 */
export const authEvents = new EventTarget();

function announce(detail: AuthEventDetail): void {
    authEvents.dispatchEvent(new CustomEvent<AuthEventDetail>('auth', { detail }));
}

export class ApiError extends Error {
    constructor(
        message: string,
        public status: number,
        public errors?: Record<string, string[]>,
        public code?: string,
        public reason: string | null = null,
    ) {
        super(message);
        this.name = 'ApiError';
    }
}

export function getToken(): string | null {
    return localStorage.getItem(TOKEN_KEY);
}

export function setToken(token: string | null): void {
    if (token) {
        localStorage.setItem(TOKEN_KEY, token);
    } else {
        localStorage.removeItem(TOKEN_KEY);
    }
}

export async function request<T>(endpoint: string, options: RequestInit = {}): Promise<T> {
    const token = getToken();

    const headers: Record<string, string> = {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(options.headers as Record<string, string>),
    };

    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    const response = await fetch(`${API_BASE}${endpoint}`, { ...options, headers });

    if (response.status === 204) {
        return {} as T;
    }

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const error = new ApiError(
            data.message || `Request failed with status ${response.status}`,
            response.status,
            data.errors,
            data.code,
            data.reason ?? null,
        );

        if (response.status === 401 && token) {
            announce({ type: 'unauthenticated' });
        } else if (response.status === 403 && data.code === 'account_suspended') {
            announce({ type: 'suspended', reason: data.reason ?? null });
        }

        throw error;
    }

    return data as T;
}
