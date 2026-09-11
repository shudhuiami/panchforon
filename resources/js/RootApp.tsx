import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { AuthProvider } from './context/AuthContext';
import { Navbar } from './components/layout/Navbar';
import { Footer } from './components/layout/Footer';
import { HomePage } from './pages/HomePage';
import { RecipeDetailPage } from './pages/RecipeDetailPage';
import { CreateRecipePage } from './pages/CreateRecipePage';
import { EditRecipePage } from './pages/EditRecipePage';
import { MealPlanPage } from './pages/MealPlanPage';
import { ShoppingListPage } from './pages/ShoppingListPage';
import { LoginPage } from './pages/LoginPage';
import { RegisterPage } from './pages/RegisterPage';
import { NotFoundPage } from './pages/NotFoundPage';

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            staleTime: 1000 * 60 * 2, // 2 minutes
            retry: 1,
            refetchOnWindowFocus: false,
        },
    },
});

export const RootApp: React.FC = () => {
    return (
        <QueryClientProvider client={queryClient}>
            <AuthProvider>
                <BrowserRouter>
                    <div className="flex min-h-dvh flex-col bg-canvas text-ink">
                        <Navbar />
                        <main className="flex-1 w-full">
                            <Routes>
                                <Route path="/" element={<HomePage />} />
                                <Route path="/recipes/create" element={<CreateRecipePage />} />
                                <Route path="/recipes/:slug" element={<RecipeDetailPage />} />
                                <Route path="/recipes/:id/edit" element={<EditRecipePage />} />
                                <Route path="/meal-plan" element={<MealPlanPage />} />
                                <Route path="/shopping-list" element={<ShoppingListPage />} />
                                <Route path="/login" element={<LoginPage />} />
                                <Route path="/register" element={<RegisterPage />} />
                                <Route path="*" element={<NotFoundPage />} />
                            </Routes>
                        </main>
                        <Footer />
                    </div>
                </BrowserRouter>
            </AuthProvider>
        </QueryClientProvider>
    );
};
