import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { savesApi } from '../../api/saves';
import { useAuth } from '../../context/AuthContext';

const IDS_KEY = ['saved-recipe-ids'];

/** Every recipe id the signed-in cook has saved, fetched once for the whole app. */
export function useSavedRecipeIds(): Set<number> {
    const { user } = useAuth();
    const { data } = useQuery({
        queryKey: IDS_KEY,
        queryFn: () => savesApi.ids(),
        enabled: user !== null,
        select: (res) => new Set(res.data),
        staleTime: 1000 * 60,
    });

    return data ?? new Set<number>();
}

/** The saved recipes themselves, for the wishlist page. */
export function useSavedRecipes(page = 1) {
    return useQuery({
        queryKey: ['saved-recipes', page],
        queryFn: () => savesApi.list(page),
    });
}

/**
 * Toggling a heart. The id list updates before the request lands and rolls
 * back if it fails, so the heart never lags behind the tap.
 */
export function useToggleSave() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({ recipeId, isSaved }: { recipeId: number; isSaved: boolean }) => (isSaved ? savesApi.unsave(recipeId) : savesApi.save(recipeId)),
        onMutate: async ({ recipeId, isSaved }) => {
            await queryClient.cancelQueries({ queryKey: IDS_KEY });
            const previous = queryClient.getQueryData<{ data: number[] }>(IDS_KEY);
            if (previous) {
                queryClient.setQueryData<{ data: number[] }>(IDS_KEY, {
                    data: isSaved ? previous.data.filter((id) => id !== recipeId) : [...previous.data, recipeId],
                });
            }
            return { previous };
        },
        onError: (_error, _variables, context) => {
            if (context?.previous) queryClient.setQueryData(IDS_KEY, context.previous);
        },
        onSettled: () => {
            queryClient.invalidateQueries({ queryKey: IDS_KEY });
            queryClient.invalidateQueries({ queryKey: ['saved-recipes'] });
        },
    });
}
