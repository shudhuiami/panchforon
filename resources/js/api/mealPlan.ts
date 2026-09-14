import { request } from './client';
import { MealPlan, MealPlanItem, MealPlanSummary, MealSlot, PaginatedResponse, ShoppingListItem } from '../types/api';

/** A dish being dropped into the plan. Omitting `planned_for` lets the API pick the plan's first day. */
export interface AddPlanItemInput {
    recipe_id: number;
    servings?: number;
    /** `null` parks the dish in the undated tray. */
    planned_for?: string | null;
    meal_slot?: MealSlot;
}

/** At least one field, or the API rejects the change. */
export interface UpdatePlanItemInput {
    servings?: number;
    planned_for?: string | null;
    meal_slot?: MealSlot;
}

export interface CreatePlanInput {
    name?: string;
    starts_on: string;
    ends_on: string;
}

export interface UpdatePlanInput {
    name?: string;
    starts_on?: string;
    ends_on?: string;
}

export const mealPlanApi = {
    /** The active plan. The API makes one covering this week if the cook has none. */
    get: (): Promise<{ data: MealPlan }> =>
        request<{ data: MealPlan }>('/meal-plan'),

    addItem: (input: AddPlanItemInput): Promise<{ data: MealPlan }> =>
        request<{ data: MealPlan }>('/meal-plan/items', {
            method: 'POST',
            body: JSON.stringify(input),
        }),

    updateItem: (id: number, changes: UpdatePlanItemInput): Promise<{ data: MealPlanItem }> =>
        request<{ data: MealPlanItem }>(`/meal-plan/items/${id}`, {
            method: 'PATCH',
            body: JSON.stringify(changes),
        }),

    removeItem: (id: number): Promise<{ message: string }> =>
        request<{ message: string }>(`/meal-plan/items/${id}`, {
            method: 'DELETE',
        }),

    generateShoppingList: (): Promise<{ data: ShoppingListItem[] }> =>
        request<{ data: ShoppingListItem[] }>('/meal-plan/shopping-list', {
            method: 'POST',
        }),

    getShoppingList: (): Promise<{ data: ShoppingListItem[] }> =>
        request<{ data: ShoppingListItem[] }>('/meal-plan/shopping-list'),

    toggleShoppingItem: (id: number, isChecked?: boolean): Promise<{ data: ShoppingListItem }> =>
        request<{ data: ShoppingListItem }>(`/shopping-list/items/${id}`, {
            method: 'PATCH',
            body: isChecked !== undefined ? JSON.stringify({ is_checked: isChecked }) : JSON.stringify({}),
        }),

    /** Every plan the cook has made, active one first, then newest range first. */
    listPlans: (params: { page?: number; per_page?: number } = {}): Promise<PaginatedResponse<MealPlanSummary>> => {
        const search = new URLSearchParams();
        if (params.page) search.set('page', String(params.page));
        if (params.per_page) search.set('per_page', String(params.per_page));

        const query = search.toString();
        return request<PaginatedResponse<MealPlanSummary>>(`/meal-plans${query ? `?${query}` : ''}`);
    },

    createPlan: (input: CreatePlanInput): Promise<{ data: MealPlan }> =>
        request<{ data: MealPlan }>('/meal-plans', {
            method: 'POST',
            body: JSON.stringify(input),
        }),

    getPlan: (id: number): Promise<{ data: MealPlan }> =>
        request<{ data: MealPlan }>(`/meal-plans/${id}`),

    /** Dishes left outside a narrowed range come back undated rather than deleted. */
    updatePlan: (id: number, changes: UpdatePlanInput): Promise<{ data: MealPlan }> =>
        request<{ data: MealPlan }>(`/meal-plans/${id}`, {
            method: 'PATCH',
            body: JSON.stringify(changes),
        }),

    deletePlan: (id: number): Promise<{ message: string }> =>
        request<{ message: string }>(`/meal-plans/${id}`, {
            method: 'DELETE',
        }),

    activatePlan: (id: number): Promise<{ data: MealPlan }> =>
        request<{ data: MealPlan }>(`/meal-plans/${id}/activate`, {
            method: 'POST',
        }),

    /** A past plan's saved list, read-only. */
    getPlanShoppingList: (id: number): Promise<{ data: ShoppingListItem[] }> =>
        request<{ data: ShoppingListItem[] }>(`/meal-plans/${id}/shopping-list`),
};
