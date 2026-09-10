import { request } from './client';
import { MealPlan, MealPlanItem, ShoppingListItem } from '../types/api';

export const mealPlanApi = {
    get: (): Promise<{ data: MealPlan }> =>
        request<{ data: MealPlan }>('/meal-plan'),

    addItem: (recipeId: number, servings: number = 4): Promise<{ data: MealPlan }> =>
        request<{ data: MealPlan }>('/meal-plan/items', {
            method: 'POST',
            body: JSON.stringify({ recipe_id: recipeId, servings }),
        }),

    updateItem: (id: number, servings: number): Promise<{ data: MealPlanItem }> =>
        request<{ data: MealPlanItem }>(`/meal-plan/items/${id}`, {
            method: 'PATCH',
            body: JSON.stringify({ servings }),
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
};