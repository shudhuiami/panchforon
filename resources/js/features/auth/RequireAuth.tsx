import React from 'react';
import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { LoadingSpinner } from '../../components/common/LoadingSpinner';

/**
 * Layout route for pages that need a signed-in user. Waits for a stored
 * token to be verified rather than flashing a signed-out page, and sends the
 * visitor to login with the location to come back to.
 */
export const RequireAuth: React.FC = () => {
    const { user, isLoading } = useAuth();
    const location = useLocation();

    if (isLoading) {
        return <LoadingSpinner message="Checking your session..." />;
    }

    if (!user) {
        return <Navigate to="/login" replace state={{ from: location }} />;
    }

    return <Outlet />;
};
