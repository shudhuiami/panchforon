export type ModerationStatus = 'pending' | 'approved' | 'unpublished';

/** Another person, as shown on their recipes and reviews. */
export interface User {
    id: number;
    name: string;
    created_at?: string;
}

/** The signed-in account. */
export interface CurrentUser extends User {
    email: string;
    is_admin: boolean;
    email_verified_at?: string | null;
    recipes_count?: number;
    ratings_count?: number;
}

export interface SiteSettings {
    site_name: string;
    contact_email: string;
    registration_open: boolean;
    submissions_open: boolean;
}

export interface AuthResponse {
    user: CurrentUser;
    token: string;
}

export interface Ingredient {
    id: number;
    canonical_name: string;
    default_dimension: 'mass' | 'volume' | 'count' | 'none';
}

export interface RecipeIngredient {
    id: number;
    ingredient_id?: number | null;
    quantity?: number | null;
    unit?: string | null;
    raw_text: string;
    position: number;
    ingredient?: Ingredient | null;
}

export interface RecipeStat {
    ratings_count: number;
    ratings_avg: number | null;
    bayesian_score: number | null;
    updated_at?: string | null;
}

export interface Rating {
    id: number;
    recipe_id: number;
    stars: number;
    review?: string | null;
    created_at?: string;
    updated_at?: string;
    user?: User;
}

export interface RecipeList {
    id: number;
    source: 'api' | 'user';
    moderation_status: ModerationStatus;
    title: string;
    slug: string;
    cuisine?: string | null;
    category?: string | null;
    image_url?: string | null;
    servings: number;
    ratings_count: number;
    ratings_avg: number | null;
    bayesian_score: number | null;
    created_at?: string;
}

export interface RecipeDetail {
    id: number;
    user_id?: number | null;
    author?: User | null;
    source: 'api' | 'user';
    moderation_status: ModerationStatus;
    external_id?: string | null;
    title: string;
    slug: string;
    cuisine?: string | null;
    category?: string | null;
    instructions: string;
    image_url?: string | null;
    servings: number;
    source_url?: string | null;
    ingredients: RecipeIngredient[];
    stat?: RecipeStat | null;
    ratings: Rating[];
    user_rating?: number | null;
    created_at?: string;
    updated_at?: string;
}

export interface MealPlanItem {
    id: number;
    meal_plan_id: number;
    recipe_id: number;
    servings: number;
    recipe?: RecipeList | null;
    created_at?: string;
}

export interface MealPlan {
    id: number;
    name: string;
    is_active: boolean;
    items: MealPlanItem[];
    created_at?: string;
    updated_at?: string;
}

export interface ShoppingListItem {
    id: number;
    meal_plan_id: number;
    ingredient_id?: number | null;
    display_name: string;
    quantity?: number | null;
    unit?: string | null;
    is_unmerged: boolean;
    source_note?: string | null;
    is_checked: boolean;
    created_at?: string;
    updated_at?: string;
}

export interface CuisineCount {
    cuisine: string;
    count: number;
}

export interface CategoryCount {
    category: string;
    count: number;
}

export interface PaginatedResponse<T> {
    data: T[];
    links?: {
        first?: string;
        last?: string;
        prev?: string | null;
        next?: string | null;
    };
    meta?: {
        current_page: number;
        from: number | null;
        last_page: number;
        per_page: number;
        to: number | null;
        total: number;
    };
}

export interface HomeStats {
    recipes: number;
    cuisines: number;
    ratings: number;
    cooks: number;
}

export interface HomeCuisine extends CuisineCount {
    image_url?: string | null;
}

/** The home page in one payload. */
export interface HomeFeed {
    stats: HomeStats;
    featured: RecipeList | null;
    top_rated: RecipeList[];
    latest: RecipeList[];
    cuisines: HomeCuisine[];
}
