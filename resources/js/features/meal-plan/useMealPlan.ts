import { useMutation, useQuery, useQueryClient, type QueryClient } from '@tanstack/react-query';
import {
    AddPlanItemInput,
    CreatePlanInput,
    UpdatePlanInput,
    UpdatePlanItemInput,
    mealPlanApi,
} from '../../api/mealPlan';
import { MealPlan } from '../../types/api';
import { useAuth } from '../../context/AuthContext';

/**
 * One place for the planner's query keys.
 * `['mealPlan']` is the active plan every screen shares; `['plans']` is the
 * history list (a prefix, so invalidating it clears every page) and
 * `['plan', id]` one past plan.
 */
export const planKeys = {
    active: ['mealPlan'] as const,
    history: ['plans'] as const,
    historyPage: (page: number, perPage: number) => ['plans', page, perPage] as const,
    plan: (id: number) => ['plan', id] as const,
    planShoppingList: (id: number) => ['plan', id, 'shoppingList'] as const,
    shoppingList: ['shoppingList'] as const,
};

/** The one definition of the active meal plan query; every reader shares its cache. */
export function useMealPlan(enabled = true) {
    return useQuery({
        queryKey: planKeys.active,
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

/** Page of the cook's plan history: the active plan first, then newest first. */
export function usePlanHistory(page = 1, perPage = 12) {
    return useQuery({
        queryKey: planKeys.historyPage(page, perPage),
        queryFn: () => mealPlanApi.listPlans({ page, per_page: perPage }),
        staleTime: 1000 * 60,
    });
}

/** One plan with its items — used by the read-only history detail. */
export function usePlan(id: number | null) {
    return useQuery({
        queryKey: planKeys.plan(id ?? 0),
        queryFn: () => mealPlanApi.getPlan(id!),
        enabled: id !== null,
        staleTime: 1000 * 60,
    });
}

export function usePlanShoppingList(id: number | null, enabled = true) {
    return useQuery({
        queryKey: planKeys.planShoppingList(id ?? 0),
        queryFn: () => mealPlanApi.getPlanShoppingList(id!),
        enabled: id !== null && enabled,
        staleTime: 1000 * 60,
    });
}

/** Anything that changes a plan changes the badges and the history rows too. */
function invalidatePlans(queryClient: QueryClient): void {
    queryClient.invalidateQueries({ queryKey: planKeys.active });
    queryClient.invalidateQueries({ queryKey: planKeys.history });
}

/**
 * The three item edits the planner makes, each writing through to the cached
 * plan first so a dish moves, changes slot or leaves the day without waiting
 * for the round trip; a failure puts the old plan back.
 */
export function usePlanItemActions() {
    const queryClient = useQueryClient();

    const snapshot = async (): Promise<{ previous?: { data: MealPlan } }> => {
        await queryClient.cancelQueries({ queryKey: planKeys.active });
        return { previous: queryClient.getQueryData<{ data: MealPlan }>(planKeys.active) };
    };

    const writePlan = (plan: MealPlan): void => {
        queryClient.setQueryData<{ data: MealPlan }>(planKeys.active, { data: plan });
    };

    const restore = (context?: { previous?: { data: MealPlan } }): void => {
        if (context?.previous) queryClient.setQueryData(planKeys.active, context.previous);
    };

    const addItem = useMutation({
        mutationFn: (input: AddPlanItemInput) => mealPlanApi.addItem(input),
        onSuccess: (response) => writePlan(response.data),
        onSettled: () => invalidatePlans(queryClient),
    });

    const updateItem = useMutation({
        mutationFn: ({ id, changes }: { id: number; changes: UpdatePlanItemInput }) => mealPlanApi.updateItem(id, changes),
        onMutate: async ({ id, changes }) => {
            const context = await snapshot();
            if (context.previous) {
                writePlan({
                    ...context.previous.data,
                    items: context.previous.data.items.map((item) => (item.id === id ? { ...item, ...changes } : item)),
                });
            }
            return context;
        },
        onError: (_error, _variables, context) => restore(context),
        onSettled: () => invalidatePlans(queryClient),
    });

    const removeItem = useMutation({
        mutationFn: (id: number) => mealPlanApi.removeItem(id),
        onMutate: async (id) => {
            const context = await snapshot();
            if (context.previous) {
                const items = context.previous.data.items.filter((item) => item.id !== id);
                writePlan({ ...context.previous.data, items, items_count: items.length });
            }
            return context;
        },
        onError: (_error, _id, context) => restore(context),
        onSettled: () => invalidatePlans(queryClient),
    });

    return { addItem, updateItem, removeItem };
}

/** Rename the active plan or move its range; dishes left outside it come back undated. */
export function useUpdatePlan() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({ id, changes }: { id: number; changes: UpdatePlanInput }) => mealPlanApi.updatePlan(id, changes),
        onSuccess: (response) => {
            if (response.data.is_active) queryClient.setQueryData<{ data: MealPlan }>(planKeys.active, response);
            queryClient.setQueryData<{ data: MealPlan }>(planKeys.plan(response.data.id), response);
        },
        onSettled: () => invalidatePlans(queryClient),
    });
}

/** A new plan always becomes the active one, so the shopping list goes stale too. */
export function useCreatePlan() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (input: CreatePlanInput) => mealPlanApi.createPlan(input),
        onSuccess: (response) => queryClient.setQueryData<{ data: MealPlan }>(planKeys.active, response),
        onSettled: () => {
            invalidatePlans(queryClient);
            queryClient.invalidateQueries({ queryKey: planKeys.shoppingList });
        },
    });
}

export function useActivatePlan() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (id: number) => mealPlanApi.activatePlan(id),
        onSuccess: (response) => queryClient.setQueryData<{ data: MealPlan }>(planKeys.active, response),
        onSettled: () => {
            invalidatePlans(queryClient);
            queryClient.invalidateQueries({ queryKey: planKeys.shoppingList });
        },
    });
}

export function useDeletePlan() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (id: number) => mealPlanApi.deletePlan(id),
        onSettled: (_data, _error, id) => {
            queryClient.removeQueries({ queryKey: planKeys.plan(id) });
            invalidatePlans(queryClient);
            queryClient.invalidateQueries({ queryKey: planKeys.shoppingList });
        },
    });
}

export function useGenerateShoppingList() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: () => mealPlanApi.generateShoppingList(),
        onSuccess: (response) => queryClient.setQueryData(planKeys.shoppingList, response),
        onSettled: () => {
            queryClient.invalidateQueries({ queryKey: planKeys.shoppingList });
            queryClient.invalidateQueries({ queryKey: planKeys.history });
        },
    });
}
