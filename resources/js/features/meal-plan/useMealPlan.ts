import { useQuery } from '@tanstack/react-query';
import { mealPlanApi } from '../../api/mealPlan';
import { useAuth } from '../../context/AuthContext';

/** The one definition of the active meal plan query; every reader shares its cache. */
export function useMealPlan(enabled = true) {
    return useQuery({
        queryKey: ['mealPlan'],
        queryFn: () => mealPlanApi.get(),
        enabled,
        staleTime: 1000 * 60,
    });
}

/** Number of recipes in the signed-in user's plan, for the nav badges. */
export function useMealPlanCount(): number {
    const { user } = useAuth();
    const { data } = useMealPlan(user !== null);
    return data?.data.items.length ?? 0;
}
