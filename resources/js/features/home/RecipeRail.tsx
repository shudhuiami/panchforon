import React from 'react';
import { RecipeList } from '../../types/api';
import { RecipeCard } from '../recipes/RecipeCard';
import { Reveal } from '../../components/motion/Reveal';
import { SectionHeader } from './SectionHeader';

const itemClass = 'w-[76vw] max-w-xs shrink-0 snap-start sm:w-80 lg:w-auto lg:max-w-none';

/** Top-rated recipes: a swipeable rail on phones, a grid from lg up. */
export const RecipeRail: React.FC<{ recipes: RecipeList[]; isLoading: boolean }> = ({ recipes, isLoading }) => (
    <section className="py-14 lg:py-20" aria-labelledby="top-heading">
        <div className="mx-auto max-w-7xl lg:px-8">
            <div className="px-4 sm:px-6 lg:px-0">
                <SectionHeader
                    id="top-heading"
                    eyebrow="Cooked, rated, repeated"
                    title="Top rated by the community"
                    text="Scores use a Bayesian average, so a dish needs several good ratings to climb, not one glowing review."
                    action={{ to: '/recipes?sort=bayesian', label: 'See the full ranking' }}
                />
            </div>
            <div className="no-scrollbar mt-8 flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 pb-2 sm:px-6 lg:grid lg:grid-cols-4 lg:gap-6 lg:overflow-visible lg:px-0 lg:pb-0">
                {isLoading
                    ? Array.from({ length: 4 }).map((_, i) => <div key={i} className={`skeleton-shimmer aspect-4/5 rounded-3xl ${itemClass}`} aria-hidden="true" />)
                    : recipes.map((recipe, index) => (
                          <Reveal key={recipe.id} delay={(index % 4) * 80} className={itemClass}>
                              <RecipeCard recipe={recipe} className="h-full" />
                          </Reveal>
                      ))}
            </div>
        </div>
    </section>
);
