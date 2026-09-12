import React from 'react';
import { useHomeFeed } from '../features/home/useHomeFeed';
import { Hero } from '../features/home/Hero';
import { StatsStrip } from '../features/home/StatsStrip';
import { CuisineRail } from '../features/home/CuisineRail';
import { RecipeRail } from '../features/home/RecipeRail';
import { Steps } from '../features/home/Steps';
import { FreshList } from '../features/home/FreshList';
import { CtaBand } from '../features/home/CtaBand';

export const HomePage: React.FC = () => {
    const { data, isLoading } = useHomeFeed();

    return (
        <>
            <Hero featured={data?.featured ?? null} cuisines={data?.cuisines ?? []} stats={data?.stats} isLoading={isLoading} />
            <StatsStrip stats={data?.stats} />
            <CuisineRail cuisines={data?.cuisines ?? []} isLoading={isLoading} />
            <RecipeRail recipes={data?.top_rated ?? []} isLoading={isLoading} />
            <Steps />
            <FreshList recipes={data?.latest ?? []} isLoading={isLoading} />
            <CtaBand />
        </>
    );
};
