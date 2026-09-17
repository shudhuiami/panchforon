export type ModerationStatus = 'draft' | 'pending' | 'approved' | 'unpublished';

/** Which meal of the day a planned dish belongs to. */
export type MealSlot = 'breakfast' | 'brunch' | 'lunch' | 'snack' | 'dinner' | 'supper';

/** How hot a dish is. Null on the many recipes with nothing to say about it. */
export type SpiceLevel = 'mild' | 'medium' | 'hot';

/** What an account may do. Only ever sent on the signed-in user's own payload. */
export type UserRole = 'member' | 'creator' | 'admin';

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
    role: UserRole;
    email_verified_at?: string | null;
    recipes_count?: number;
    ratings_count?: number;
}

export interface SocialLogin {
    key: string;
    label: string;
}

export interface SiteSettings {
    site_name: string;
    contact_email: string;
    registration_open: boolean;
    submissions_open: boolean;
    /** Providers this deployment has configured; empty when none are. */
    social_logins: SocialLogin[];
}

export interface AuthResponse {
    user: CurrentUser;
    token: string;
}

export interface Ingredient {
    id: number;
    canonical_name: string;
    /** Bangla name, where one has been recorded. */
    name_bn?: string | null;
    /** Only a fallback now: the recipe row's own unit decides the dimension. */
    default_dimension: 'mass' | 'volume' | 'count' | 'none';
    /** False for things nobody buys, such as water. These never reach a shopping list. */
    is_shoppable: boolean;
    /** The unit symbol this ingredient is usually written in, e.g. "g". */
    preferred_unit?: string | null;
}

export interface RecipeIngredient {
    id: number;
    ingredient_id?: number | null;
    quantity?: number | null;
    unit?: string | null;
    /** Optional in this recipe. Optionality belongs to the row, not the ingredient. */
    is_optional: boolean;
    /** The cook's aside, e.g. "3 medium, halved". */
    note?: string | null;
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
    name_bn?: string | null;
    slug: string;
    cuisine?: string | null;
    category?: string | null;
    image_url?: string | null;
    servings: number;
    prep_minutes?: number | null;
    cook_minutes?: number | null;
    /** prep + cook. Null only when neither time is recorded. */
    total_minutes?: number | null;
    spice_level?: SpiceLevel | null;
    ratings_count: number;
    ratings_avg: number | null;
    bayesian_score: number | null;
    created_at?: string;
    has_video?: boolean;
}

export interface RecipeDetail {
    id: number;
    user_id?: number | null;
    author?: User | null;
    source: 'api' | 'user';
    moderation_status: ModerationStatus;
    external_id?: string | null;
    title: string;
    name_bn?: string | null;
    slug: string;
    cuisine?: string | null;
    category?: string | null;
    instructions: string;
    image_url?: string | null;
    servings: number;
    prep_minutes?: number | null;
    cook_minutes?: number | null;
    /** prep + cook. Null only when neither time is recorded. */
    total_minutes?: number | null;
    spice_level?: SpiceLevel | null;
    /** The eleven-character id, never a URL: it ends up in an iframe src. */
    youtube_video_id?: string | null;
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
    /** The day this dish is cooked on, or null while it sits unscheduled. */
    planned_for: string | null;
    meal_slot: MealSlot;
    recipe?: RecipeList | null;
    created_at?: string;
}

export interface MealPlan {
    id: number;
    name: string;
    is_active: boolean;
    /** ISO dates (YYYY-MM-DD). A plan always covers at least one day. */
    starts_on: string;
    ends_on: string;
    day_count: number;
    items: MealPlanItem[];
    items_count: number;
    shopping_items_count: number;
    created_at?: string;
    updated_at?: string;
}

/** A plan as it appears in the history list, without its items. */
export interface MealPlanSummary {
    id: number;
    name: string;
    is_active: boolean;
    starts_on: string;
    ends_on: string;
    day_count: number;
    items_count: number;
    servings_total: number;
    cuisines: string[];
    shopping_items_count: number;
    shopping_checked_count: number;
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
    /** True only when every recipe row feeding this line was itself optional. */
    is_optional: boolean;
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

/** A rating as its author sees it on their own account. */
export interface MyRating {
    id: number;
    stars: number;
    review?: string | null;
    recipe?: RecipeList | null;
    created_at?: string;
    updated_at?: string;
}

/** A cook as everyone else sees them. */
export interface Cook {
    id: number;
    name: string;
    recipes_count?: number;
    ratings_count?: number;
    created_at?: string;
}

export interface CookProfile {
    cook: Cook;
    recipes: PaginatedResponse<RecipeList>;
}

export type CreatorApplicationStatus = 'pending' | 'approved' | 'declined';

/**
 * An application as its applicant sees it. It deliberately never names the
 * admin who decided — who declined you is not part of the answer.
 */
export interface CreatorApplication {
    id: number;
    status: CreatorApplicationStatus;
    pitch: string;
    youtube_channel_url?: string | null;
    review_note?: string | null;
    reviewed_at?: string | null;
    created_at?: string;
}
