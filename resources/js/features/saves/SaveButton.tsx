import React from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { Heart } from 'lucide-react';
import { useAuth } from '../../context/AuthContext';
import { useSavedRecipeIds, useToggleSave } from './useSavedRecipes';

interface SaveButtonProps {
    recipeId: number;
    recipeTitle: string;
    /** `floating` rides the corner of a card image; `inline` sits in a row of buttons. */
    variant?: 'floating' | 'inline';
    className?: string;
}

/**
 * The heart. Signed out it sends the cook to sign in and comes back to where
 * they were, rather than failing quietly.
 */
export const SaveButton: React.FC<SaveButtonProps> = ({ recipeId, recipeTitle, variant = 'floating', className = '' }) => {
    const { user } = useAuth();
    const navigate = useNavigate();
    const location = useLocation();
    const savedIds = useSavedRecipeIds();
    const toggle = useToggleSave();

    const isSaved = savedIds.has(recipeId);

    const handleClick = (e: React.MouseEvent) => {
        e.preventDefault();
        e.stopPropagation();
        if (!user) {
            navigate('/login', { state: { from: location } });
            return;
        }
        toggle.mutate({ recipeId, isSaved });
    };

    const base =
        variant === 'floating'
            ? `inline-flex size-9 items-center justify-center rounded-full backdrop-blur transition-colors ${isSaved ? 'bg-hot text-on-primary' : 'bg-canvas/60 text-ink hover:bg-canvas/80'}`
            : `inline-flex h-10 items-center gap-2 rounded-full border px-4 text-sm font-medium transition-colors ${
                  isSaved ? 'border-hot bg-hot-soft text-hot' : 'border-line bg-surface text-ink-2 hover:border-line-strong hover:text-ink'
              }`;

    return (
        <button
            type="button"
            onClick={handleClick}
            aria-pressed={isSaved}
            aria-label={isSaved ? `Remove ${recipeTitle} from your saved recipes` : `Save ${recipeTitle} for later`}
            title={isSaved ? 'Saved' : 'Save for later'}
            className={`cursor-pointer active:scale-90 focus-visible:outline-3 focus-visible:outline-primary focus-visible:outline-offset-2 ${base} ${className}`}
        >
            <Heart className={`size-4 transition-transform ${isSaved ? 'scale-110 fill-current' : ''}`} aria-hidden="true" />
            {variant === 'inline' && (isSaved ? 'Saved' : 'Save')}
        </button>
    );
};
