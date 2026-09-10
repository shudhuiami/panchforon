import React from 'react';
import { ShoppingListItem } from '../../types/api';
import { Check, Sparkles, PenLine } from 'lucide-react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { mealPlanApi } from '../../api/mealPlan';

interface ShoppingListItemRowProps {
    item: ShoppingListItem;
}

export const ShoppingListItemRow: React.FC<ShoppingListItemRowProps> = ({ item }) => {
    const queryClient = useQueryClient();

    const toggleMutation = useMutation({
        mutationFn: (checked: boolean) => mealPlanApi.toggleShoppingItem(item.id, checked),
        onMutate: async (newChecked) => {
            await queryClient.cancelQueries({ queryKey: ['shoppingList'] });
            const previousList = queryClient.getQueryData<{ data: ShoppingListItem[] }>(['shoppingList']);

            if (previousList) {
                queryClient.setQueryData<{ data: ShoppingListItem[] }>(['shoppingList'], {
                    ...previousList,
                    data: previousList.data.map((i) =>
                        i.id === item.id ? { ...i, is_checked: newChecked } : i
                    ),
                });
            }

            return { previousList };
        },
        onError: (_err, _newChecked, context) => {
            if (context?.previousList) {
                queryClient.setQueryData(['shoppingList'], context.previousList);
            }
        },
        onSettled: () => {
            queryClient.invalidateQueries({ queryKey: ['shoppingList'] });
        },
    });

    const handleToggle = () => {
        toggleMutation.mutate(!item.is_checked);
    };

    const formatQty = (qty: number | null | undefined): string => {
        if (qty === null || qty === undefined) return '';
        const num = Number(qty);
        return Number.isInteger(num) ? num.toString() : num.toFixed(1).replace(/\.0$/, '');
    };

    const isMerged = item.source_note && item.source_note.toLowerCase().includes('merged from');
    const hasQuantity = item.quantity !== null && item.quantity !== undefined;
    const showQuantityPill = hasQuantity || !!item.unit;

    return (
        <div
            onClick={handleToggle}
            onKeyDown={(e) => {
                if (e.key === ' ' || e.key === 'Enter') {
                    e.preventDefault();
                    handleToggle();
                }
            }}
            role="checkbox"
            aria-checked={item.is_checked}
            tabIndex={0}
            className={`group relative flex items-center gap-3.5 min-h-14 px-3.5 py-3 rounded-2xl border-2 transition-all duration-300 cursor-pointer select-none focus-visible:outline-3 focus-visible:outline-saffron focus-visible:outline-offset-2 ${
                item.is_checked
                    ? 'bg-cream-2/60 border-transparent opacity-60'
                    : 'bg-paper border-line hover:border-mint hover:shadow-glow-mint hover:-translate-y-0.5'
            }`}
        >
            {/* Round custom checkbox */}
            <span
                className={`relative w-7 h-7 rounded-full flex items-center justify-center border-2 shrink-0 transition-all duration-300 ${
                    item.is_checked
                        ? 'bg-mint border-mint text-ink scale-100'
                        : 'bg-paper border-line-strong text-transparent group-hover:border-mint group-hover:bg-mint-soft'
                }`}
                aria-hidden="true"
            >
                <Check
                    className={`w-4 h-4 stroke-[3.5] transition-transform duration-300 ${
                        item.is_checked ? 'scale-100 animate-pop-in' : 'scale-0'
                    }`}
                />
            </span>

            {/* Name + note */}
            <div className="flex flex-col min-w-0 flex-1">
                <span className="flex items-center gap-2 min-w-0">
                    <span
                        className={`relative text-[15px] font-bold capitalize truncate transition-colors duration-300 ${
                            item.is_checked ? 'text-ink-3' : 'text-ink'
                        }`}
                    >
                        {item.display_name}
                        <span
                            className={`absolute left-0 top-1/2 h-[2px] bg-ink-3 rounded-full transition-all duration-300 ease-out ${
                                item.is_checked ? 'w-full' : 'w-0'
                            }`}
                            aria-hidden="true"
                        />
                    </span>

                    {item.is_unmerged && (
                        <span className="inline-flex items-center gap-1 shrink-0 rounded-full bg-chili-soft text-chili-deep text-[10px] font-extrabold uppercase tracking-wider px-2 py-0.5 sticker-r">
                            <PenLine className="w-3 h-3" aria-hidden="true" />
                            As written
                        </span>
                    )}
                </span>

                {item.source_note && (
                    <span className="text-[11px] text-ink-3 truncate flex items-center gap-1 mt-0.5 font-medium">
                        {isMerged && (
                            <Sparkles className="w-3 h-3 text-turmeric-deep fill-turmeric shrink-0" aria-hidden="true" />
                        )}
                        {item.source_note}
                    </span>
                )}
            </div>

            {/* Quantity pill */}
            {showQuantityPill && (
                <span
                    className={`shrink-0 inline-flex items-baseline gap-1 rounded-full px-3 py-1.5 text-xs font-extrabold tabular-nums transition-colors duration-300 ${
                        item.is_checked
                            ? 'bg-cream-2 text-ink-3 line-through'
                            : 'bg-turmeric-soft text-turmeric-deep'
                    }`}
                >
                    {hasQuantity && <span className="font-display text-sm">{formatQty(item.quantity)}</span>}
                    {item.unit && <span className="uppercase tracking-wide">{item.unit}</span>}
                </span>
            )}
        </div>
    );
};
