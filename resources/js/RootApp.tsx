import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { AuthProvider } from './context/AuthContext';
import { AppShell } from './components/layout/AppShell';
import { BareShell } from './components/layout/BareShell';
import { RequireAuth } from './features/auth/RequireAuth';
import { HomePage } from './pages/HomePage';
import { RecipesPage } from './pages/RecipesPage';
import { RecipeDetailPage } from './pages/RecipeDetailPage';
import { CreateRecipePage } from './pages/CreateRecipePage';
import { EditRecipePage } from './pages/EditRecipePage';
import { MealPlanPage } from './pages/MealPlanPage';
import { WishlistPage } from './pages/WishlistPage';
import { ShoppingListPage } from './pages/ShoppingListPage';
import { LoginPage } from './pages/LoginPage';
import { RegisterPage } from './pages/RegisterPage';
import { NotFoundPage } from './pages/NotFoundPage';

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            staleTime: 1000 * 60 * 2,
            retry: 1,
            refetchOnWindowFocus: false,
        },
    },
});

export const RootApp: React.FC = () => (
    <QueryClientProvider client={queryClient}>
        <AuthProvider>
            <BrowserRouter>
                <div className="bg-canvas text-ink">
                    <Routes>
                        <Route element={<AppShell />}>
                            <Route path="/" element={<HomePage />} />
                            <Route path="/recipes" element={<RecipesPage />} />
                            <Route path="/recipes/:slug" element={<RecipeDetailPage />} />
                            <Route element={<RequireAuth />}>
                                <Route path="/recipes/create" element={<CreateRecipePage />} />
                                <Route path="/recipes/:slug/edit" element={<EditRecipePage />} />
                                <Route path="/saved" element={<WishlistPage />} />
                                <Route path="/meal-plan" element={<MealPlanPage />} />
                                <Route path="/shopping-list" element={<ShoppingListPage />} />
                            </Route>
                            <Route path="*" element={<NotFoundPage />} />
                        </Route>
                        <Route element={<BareShell />}>
                            <Route path="/login" element={<LoginPage />} />
                            <Route path="/register" element={<RegisterPage />} />
                        </Route>
                    </Routes>
                </div>
            </BrowserRouter>
        </AuthProvider>
    </QueryClientProvider>
);
