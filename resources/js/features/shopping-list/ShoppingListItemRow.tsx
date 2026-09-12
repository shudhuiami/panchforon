import React from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Check } from 'lucide-react';
import { ShoppingListItem } from '../../types/api';
import { mealPlanApi } from '../../api/mealPlan';

const formatQuantity = (quantity: number | null | undefined): string => {
    if (quantity === null || quantity === undefined) return '';
    const n = Number(quantity);
    return Number.isInteger(n) ? n.toString() : n.toFixed(1).replace(/\.0$/, '');
};

/** One grocery line; tapping it toggles the tick optimistically. */
export const ShoppingListItemRow: React.FC<{ item: ShoppingListItem }> = ({ item }) => {
    const queryClient = useQueryClient();

    const toggleMutation = useMutation({
        mutationFn: (checked: boolean) => mealPlanApi.toggleShoppingItem(item.id, checked),
        onMutate: async (checked) => {
            await queryClient.cancelQueries({ queryKey: ['shoppingList'] });
            const previous = queryClient.getQueryData<{ data: ShoppingListItem[] }>(['shoppingList']);
            if (previous) {
                queryClient.setQueryData<{ data: ShoppingListItem[] }>(['shoppingList'], {
                    ...previous,
                    data: previous.data.map((i) => (i.id === item.id ? { ...i, is_checked: checked } : i)),
                });
            }
            return { previous };
        },
        onError: (_error, _checked, context) => {
            if (context?.previous) queryClient.setQueryData(['shoppingList'], context.previous);
        },
        onSettled: () => queryClient.invalidateQueries({ queryKey: ['shoppingList'] }),
    });

    const toggle = () => toggleMutation.mutate(!item.is_checked);
    const showQuantity = (item.quantity !== null && item.quantity !== undefined) || !!item.unit;

    return (
        <div
            role="checkbox"
            aria-checked={item.is_checked}
            tabIndex={0}
            onClick={toggle}
            onKeyDown={(e) => {
                if (e.key === ' ' || e.key === 'Enter') {
                    e.preventDefault();
                    toggle();
                }
            }}
            className={`group flex min-h-14 cursor-pointer items-center gap-3.5 rounded-2xl border px-3.5 py-3 transition-[border-color,opacity,background-color] duration-300 select-none focus-visible:outline-3 focus-visible:outline-primary focus-visible:outline-offset-2 ${
                item.is_checked ? 'border-transparent bg-surface-2/50 opacity-60' : 'border-line bg-surface-2 hover:border-mint/60'
            }`}
        >
            <span
                className={`flex size-6 shrink-0 items-center justify-center rounded-full border-2 transition-colors duration-300 ${
                    item.is_checked ? 'border-mint bg-mint text-on-primary' : 'border-line-strong text-transparent group-hover:border-mint'
                }`}
                aria-hidden="true"
            >
                <Check className="size-3.5 stroke-[3.5]" />
            </span>
            <span className="min-w-0 flex-1">
                <span className="flex items-center gap-2">
                    <span className={`truncate text-[15px] font-medium capitalize transition-colors ${item.is_checked ? 'text-ink-3 line-through' : 'text-ink'}`}>{item.display_name}</span>
                    {item.is_unmerged && <span className="shrink-0 rounded-full bg-hot-soft px-2 py-0.5 text-[10px] font-semibold tracking-wider text-hot uppercase">As written</span>}
                </span>
                {item.source_note && <span className="mt-0.5 block truncate text-[11px] text-ink-3">{item.source_note}</span>}
            </span>
            {showQuantity && (
                <span className={`shrink-0 rounded-full px-3 py-1 text-xs font-semibold tabular-nums ${item.is_checked ? 'bg-surface-3 text-ink-3 line-through' : 'bg-turmeric-soft text-turmeric'}`}>
                    {formatQuantity(item.quantity)} {item.unit}
                </span>
            )}
        </div>
    );
};
