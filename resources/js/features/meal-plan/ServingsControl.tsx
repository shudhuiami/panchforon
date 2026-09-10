import React from 'react';
import { Minus, Plus, Users } from 'lucide-react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { mealPlanApi } from '../../api/mealPlan';

interface ServingsControlProps {
    itemId: number;
    currentServings: number;
    min?: number;
    max?: number;
}

export const ServingsControl: React.FC<ServingsControlProps> = ({
    itemId,
    currentServings,
    min = 1,
    max = 50,
}) => {
    const queryClient = useQueryClient();

    const mutation = useMutation({
        mutationFn: (newServings: number) => mealPlanApi.updateItem(itemId, newServings),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['mealPlan'] });
        },
    });

    const handleIncrement = () => {
        if (currentServings < max && !mutation.isPending) {
            mutation.mutate(currentServings + 1);
        }
    };

    const handleDecrement = () => {
        if (currentServings > min && !mutation.isPending) {
            mutation.mutate(currentServings - 1);
        }
    };

    const stepperButton =
        'w-10 h-10 shrink-0 flex items-center justify-center rounded-full bg-saffron-soft text-saffron-deep hover:bg-saffron hover:text-ink hover:scale-105 active:scale-95 disabled:opacity-30 disabled:hover:bg-saffron-soft disabled:hover:text-saffron-deep disabled:hover:scale-100 disabled:cursor-not-allowed transition-all duration-200 cursor-pointer focus-visible:outline-3 focus-visible:outline-saffron focus-visible:outline-offset-2';

    return (
        <div
            className={`inline-flex items-center gap-1 bg-paper rounded-full p-1.5 border-2 border-line-strong shadow-sm transition-opacity ${
                mutation.isPending ? 'opacity-70' : ''
            }`}
            aria-busy={mutation.isPending}
        >
            <button
                type="button"
                onClick={handleDecrement}
                disabled={currentServings <= min || mutation.isPending}
                className={stepperButton}
                title="Decrease servings"
                aria-label="Decrease servings"
            >
                <Minus className="w-4 h-4 stroke-[3]" />
            </button>

            <div className="px-2 min-w-[4.5rem] flex flex-col items-center justify-center leading-none select-none">
                <span
                    className="font-display font-extrabold text-2xl text-ink tabular-nums leading-none"
                    aria-live="polite"
                >
                    {currentServings}
                </span>
                <span className="mt-1 inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider text-ink-3">
                    <Users className="w-3 h-3" aria-hidden="true" />
                    {currentServings === 1 ? 'serving' : 'servings'}
                </span>
            </div>

            <button
                type="button"
                onClick={handleIncrement}
                disabled={currentServings >= max || mutation.isPending}
                className={stepperButton}
                title="Increase servings"
                aria-label="Increase servings"
            >
                <Plus className="w-4 h-4 stroke-[3]" />
            </button>
        </div>
    );
};
