import React from 'react';
import { Moon, Sun } from 'lucide-react';
import { useTheme } from '../../context/ThemeContext';

interface ThemeToggleProps {
    className?: string;
}

/**
 * Dark ⇄ light. The two icons are stacked and cross-faded rather than
 * swapped, so the button never jumps as the label changes.
 */
export const ThemeToggle: React.FC<ThemeToggleProps> = ({ className = '' }) => {
    const { theme, toggleTheme } = useTheme();
    const isDark = theme === 'dark';

    return (
        <button
            type="button"
            onClick={toggleTheme}
            aria-label={isDark ? 'Switch to the light theme' : 'Switch to the dark theme'}
            title={isDark ? 'Light theme' : 'Dark theme'}
            className={`relative inline-flex size-10 cursor-pointer items-center justify-center rounded-full border border-line text-ink-2 transition-colors hover:border-line-strong hover:bg-surface-2 hover:text-ink focus-visible:outline-3 focus-visible:outline-primary focus-visible:outline-offset-2 ${className}`}
        >
            <Sun
                className={`absolute size-5 transition-all duration-300 ${isDark ? 'scale-100 rotate-0 opacity-100' : 'scale-50 -rotate-90 opacity-0'}`}
                aria-hidden="true"
            />
            <Moon
                className={`absolute size-5 transition-all duration-300 ${isDark ? 'scale-50 rotate-90 opacity-0' : 'scale-100 rotate-0 opacity-100'}`}
                aria-hidden="true"
            />
        </button>
    );
};
