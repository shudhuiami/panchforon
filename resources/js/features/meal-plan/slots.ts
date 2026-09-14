import { Cookie, Croissant, Moon, Sandwich, type LucideIcon } from 'lucide-react';
import { MealSlot } from '../../types/api';

/** Slot order is the order of the day, not alphabetical: the planner reads top to bottom. */
export const MEAL_SLOTS: MealSlot[] = ['breakfast', 'lunch', 'dinner', 'snack'];

export const DEFAULT_MEAL_SLOT: MealSlot = 'dinner';

export interface SlotMeta {
    label: string;
    icon: LucideIcon;
    /** Semantic text token; the soft fill of the same hue pairs with it. */
    tone: string;
    fill: string;
}

export const SLOT_META: Record<MealSlot, SlotMeta> = {
    breakfast: { label: 'Breakfast', icon: Croissant, tone: 'text-turmeric', fill: 'bg-turmeric-soft' },
    lunch: { label: 'Lunch', icon: Sandwich, tone: 'text-mint', fill: 'bg-mint-soft' },
    dinner: { label: 'Dinner', icon: Moon, tone: 'text-primary', fill: 'bg-primary-soft' },
    snack: { label: 'Snack', icon: Cookie, tone: 'text-plum', fill: 'bg-plum-soft' },
};

export function slotLabel(slot: MealSlot): string {
    return SLOT_META[slot]?.label ?? 'Dinner';
}

export function slotRank(slot: MealSlot): number {
    const index = MEAL_SLOTS.indexOf(slot);
    return index === -1 ? MEAL_SLOTS.length : index;
}
