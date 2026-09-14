import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';

export type Theme = 'dark' | 'light';

const STORAGE_KEY = 'panchforon_theme';

/** Canvas colour per theme, mirrored into <meta name="theme-color"> so the
 *  browser chrome on phones matches the page instead of fighting it. */
const THEME_COLOR: Record<Theme, string> = {
    dark: '#14100D',
    light: '#FBF7F1',
};

interface ThemeContextValue {
    theme: Theme;
    setTheme: (theme: Theme) => void;
    toggleTheme: () => void;
}

const ThemeContext = createContext<ThemeContextValue | undefined>(undefined);

/** Dark is the default; only an explicit choice, remembered here, changes it. */
function readStoredTheme(): Theme {
    try {
        return localStorage.getItem(STORAGE_KEY) === 'light' ? 'light' : 'dark';
    } catch {
        return 'dark';
    }
}

function applyTheme(theme: Theme): void {
    const root = document.documentElement;
    root.dataset.theme = theme;
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', THEME_COLOR[theme]);
    document.querySelector('meta[name="color-scheme"]')?.setAttribute('content', theme);
}

export const ThemeProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    // The inline script in app.blade.php has already stamped the element, so
    // reading it back keeps the first render in step with what is on screen.
    const [theme, setThemeState] = useState<Theme>(() =>
        typeof document !== 'undefined' && document.documentElement.dataset.theme === 'light' ? 'light' : readStoredTheme()
    );

    useEffect(() => {
        applyTheme(theme);
        try {
            localStorage.setItem(STORAGE_KEY, theme);
        } catch {
            // A private window that refuses storage still gets the theme it picked,
            // it just will not be remembered next time.
        }
    }, [theme]);

    const setTheme = useCallback((next: Theme) => setThemeState(next), []);
    const toggleTheme = useCallback(() => setThemeState((current) => (current === 'dark' ? 'light' : 'dark')), []);

    const value = useMemo(() => ({ theme, setTheme, toggleTheme }), [theme, setTheme, toggleTheme]);

    return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
};

export function useTheme(): ThemeContextValue {
    const context = useContext(ThemeContext);
    if (!context) {
        throw new Error('useTheme must be used within a ThemeProvider');
    }
    return context;
}
