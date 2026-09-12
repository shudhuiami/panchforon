import React from 'react';
import { Link } from 'react-router-dom';
import { HomeCuisine } from '../../types/api';
import { SectionHeader } from './SectionHeader';
import { Photo } from '../../components/ui/Photo';

const TINTS = ['bg-spice-gradient', 'bg-plum-gradient', 'bg-mint-gradient', 'bg-sunrise-gradient', 'bg-ink-gradient'];

const CuisineCard: React.FC<{ cuisine: HomeCuisine; index: number }> = ({ cuisine, index }) => (
    <Link
        to={`/recipes?cuisine=${encodeURIComponent(cuisine.cuisine)}`}
        className="group relative block h-44 w-60 overflow-hidden rounded-3xl border border-line bg-surface-2 transition-[transform,border-color] duration-500 ease-out hover:-translate-y-1.5 hover:border-primary/50 focus-visible:outline-3 focus-visible:outline-primary focus-visible:outline-offset-4 sm:h-52 sm:w-72"
    >
        <Photo
            src={cuisine.image_url}
            loading="lazy"
            className="absolute inset-0 h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
            fallback={<div className={`absolute inset-0 ${TINTS[index % TINTS.length]} bg-dots-light`} />}
        />
        <div className="absolute inset-0 bg-linear-to-t from-canvas/85 via-canvas/25 to-transparent" />
        <span className="absolute top-4 left-4 rounded-full bg-canvas/55 px-2.5 py-1 text-[11px] font-semibold text-ink backdrop-blur">
            {cuisine.count} {cuisine.count === 1 ? 'recipe' : 'recipes'}
        </span>
        <span className="absolute inset-x-5 bottom-5 font-display text-2xl leading-none font-medium text-ink italic sm:text-3xl">
            {cuisine.cuisine}
        </span>
    </Link>
);

export const CuisineRail: React.FC<{ cuisines: HomeCuisine[]; isLoading: boolean }> = ({ cuisines, isLoading }) => (
    <section className="py-14 lg:py-20" aria-labelledby="cuisines-heading">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <SectionHeader
                id="cuisines-heading"
                eyebrow="Browse by cuisine"
                title="Where do you want to eat tonight?"
                action={{ to: '/recipes', label: 'All recipes' }}
            />
        </div>
        <div className="rail mt-8 pb-4">
            {isLoading
                ? Array.from({ length: 5 }).map((_, i) => <div key={i} className="skeleton-shimmer h-44 w-60 rounded-3xl sm:h-52 sm:w-72" aria-hidden="true" />)
                : cuisines.map((cuisine, index) => <CuisineCard key={cuisine.cuisine} cuisine={cuisine} index={index} />)}
        </div>
    </section>
);
