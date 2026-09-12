import React from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { mealPlanApi } from '../../api/mealPlan';
import { QuantityStepper } from '../../components/ui/QuantityStepper';

interface ServingsControlProps {
    itemId: number;
    currentServings: number;
    min?: number;
    max?: number;
}

/** The stepper for a planned dish; every change is saved straight away. */
export const ServingsControl: React.FC<ServingsControlProps> = ({ itemId, currentServings, min = 1, max = 50 }) => {
    const queryClient = useQueryClient();
    const mutation = useMutation({
        mutationFn: (servings: number) => mealPlanApi.updateItem(itemId, servings),
        onSuccess: () => queryClient.invalidateQueries({ queryKey: ['mealPlan'] }),
    });

    return (
        <div className={`inline-flex items-center gap-3 transition-opacity ${mutation.isPending ? 'opacity-70' : ''}`} aria-busy={mutation.isPending}>
            <QuantityStepper value={currentServings} onChange={(value) => mutation.mutate(value)} min={min} max={max} disabled={mutation.isPending} size="sm" ariaLabel="Servings" />
            <span className="text-xs text-ink-3" aria-live="polite">
                {currentServings === 1 ? 'serving' : 'servings'}
            </span>
        </div>
    );
};
