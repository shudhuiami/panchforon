import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { MealPlanItem } from '../../types/api';
import { Alert } from '../../components/ui/Alert';
import { IsoDate } from './dates';
import { usePlanItemActions } from './useMealPlan';

/**
 * Dragging a dish from one day of the plan onto another.
 *
 * This is the HTML5 drag API, so it is an enhancement for pointer devices
 * only — touch fires no drag events at all. Every card keeps its day and meal
 * selects, and those remain the way a phone or a keyboard moves a dish.
 *
 * The payload is a MIME type of our own carrying the item id, so a dragged
 * file, image or selection from another app never reads as a dish. The data
 * itself is blacked out until the drop, so while the dish is in the air a
 * target decides whether it is a legal destination from `types` plus the dish
 * held here in context.
 */
const DISH_MIME = 'application/x-panchforon-dish';

interface DraggedDish {
    id: number;
    /** The day it sits on right now; `null` while it waits in the undated tray. */
    plannedFor: IsoDate | null;
}

interface PlanDragValue {
    /** The dish in the air, or `null` when nothing is being dragged. */
    dragged: DraggedDish | null;
    /** False outside a provider — a read-only plan never becomes a board. */
    enabled: boolean;
    canDropOn: (day: IsoDate | null) => boolean;
    startDrag: (event: React.DragEvent<HTMLElement>, item: MealPlanItem, title: string) => void;
    endDrag: () => void;
    dropOn: (event: React.DragEvent<HTMLElement>, day: IsoDate | null) => void;
    moveFailed: boolean;
}

/** What a card or column gets with no provider above it: nothing drags, nothing accepts. */
const INERT: PlanDragValue = {
    dragged: null,
    enabled: false,
    canDropOn: () => false,
    startDrag: () => {},
    endDrag: () => {},
    dropOn: () => {},
    moveFailed: false,
};

const PlanDragContext = createContext<PlanDragValue>(INERT);

/** True when the drag carries one of our dishes rather than a file or foreign selection. */
function carriesDish(event: React.DragEvent<HTMLElement>): boolean {
    return event.dataTransfer.types.includes(DISH_MIME);
}

export interface PlanDragProviderProps {
    children: React.ReactNode;
}

/**
 * Holds the dish being dragged and saves the move. The write is the same
 * optimistic `updateItem` the selects use, so the card lands on its new day
 * at once and goes back if the API refuses.
 */
export const PlanDragProvider: React.FC<PlanDragProviderProps> = ({ children }) => {
    const { updateItem } = usePlanItemActions();
    const [dragged, setDragged] = useState<DraggedDish | null>(null);

    const startDrag = useCallback((event: React.DragEvent<HTMLElement>, item: MealPlanItem, title: string) => {
        event.dataTransfer.setData(DISH_MIME, String(item.id));
        event.dataTransfer.setData('text/plain', title);
        event.dataTransfer.effectAllowed = 'move';
        setDragged({ id: item.id, plannedFor: item.planned_for ?? null });
    }, []);

    const endDrag = useCallback(() => setDragged(null), []);

    const canDropOn = useCallback((day: IsoDate | null) => dragged !== null && dragged.plannedFor !== day, [dragged]);

    const dropOn = useCallback(
        (event: React.DragEvent<HTMLElement>, day: IsoDate | null) => {
            event.preventDefault();
            const id = Number.parseInt(event.dataTransfer.getData(DISH_MIME), 10);
            setDragged(null);

            /** A payload we did not write, or the dish is already on this day: nothing to save. */
            if (dragged === null || dragged.id !== id || dragged.plannedFor === day) return;

            updateItem.mutate({ id, changes: { planned_for: day } });
        },
        [dragged, updateItem],
    );

    const value = useMemo<PlanDragValue>(
        () => ({ dragged, enabled: true, canDropOn, startDrag, endDrag, dropOn, moveFailed: updateItem.isError }),
        [dragged, canDropOn, startDrag, endDrag, dropOn, updateItem.isError],
    );

    return <PlanDragContext.Provider value={value}>{children}</PlanDragContext.Provider>;
};

export interface DishDragSource {
    /** Spread onto the card; empty when dragging is off, so the card stays inert. */
    props: {
        draggable?: boolean;
        onDragStart?: (event: React.DragEvent<HTMLElement>) => void;
        onDragEnd?: () => void;
        'aria-grabbed'?: boolean;
    };
    isDragging: boolean;
    /** True only where a drag can actually happen — the grip is drawn on that. */
    enabled: boolean;
}

/** Makes one dish card a drag source. */
export function useDishDrag(item: MealPlanItem, title: string, enabled = true): DishDragSource {
    const context = useContext(PlanDragContext);
    const on = enabled && context.enabled;
    const isDragging = on && context.dragged?.id === item.id;

    return on
        ? {
              enabled: true,
              isDragging,
              props: {
                  draggable: true,
                  onDragStart: (event) => context.startDrag(event, item, title),
                  onDragEnd: context.endDrag,
                  'aria-grabbed': isDragging,
              },
          }
        : { enabled: false, isDragging: false, props: {} };
}

export interface DishDropTarget {
    props: {
        onDragEnter: (event: React.DragEvent<HTMLElement>) => void;
        onDragOver: (event: React.DragEvent<HTMLElement>) => void;
        onDragLeave: (event: React.DragEvent<HTMLElement>) => void;
        onDrop: (event: React.DragEvent<HTMLElement>) => void;
    };
    /** A dish is in the air and this is somewhere it could land. */
    isTarget: boolean;
    /** …and the pointer is over it right now. */
    isOver: boolean;
}

/**
 * Makes a day column — or the undated tray, with `day` of `null` — accept a
 * dish. The day the dish already sits on is never a target: it neither lights
 * up nor takes the drop, so a dish dropped back where it started saves nothing.
 */
export function useDishDropTarget(day: IsoDate | null, enabled = true): DishDropTarget {
    const { dragged, canDropOn, dropOn } = useContext(PlanDragContext);
    const [isOver, setIsOver] = useState(false);
    const isTarget = enabled && canDropOn(day);

    /** A drag that ends elsewhere — or is cancelled — may never send this target a `dragleave`. */
    useEffect(() => {
        if (dragged === null) setIsOver(false);
    }, [dragged]);

    return {
        isTarget,
        isOver: isTarget && isOver,
        props: {
            onDragEnter: (event) => {
                if (!isTarget || !carriesDish(event)) return;
                event.preventDefault();
                setIsOver(true);
            },
            /** Only a cancelled `dragover` accepts a drop, so the source day simply never cancels one. */
            onDragOver: (event) => {
                if (!carriesDish(event)) return;
                if (!isTarget) {
                    event.dataTransfer.dropEffect = 'none';
                    return;
                }
                event.preventDefault();
                event.dataTransfer.dropEffect = 'move';
                setIsOver(true);
            },
            onDragLeave: (event) => {
                /** Crossing onto a card inside the column is not leaving the column. */
                if (event.currentTarget.contains(event.relatedTarget as Node | null)) return;
                setIsOver(false);
            },
            onDrop: (event) => {
                setIsOver(false);
                if (!isTarget || !carriesDish(event)) return;
                dropOn(event, day);
            },
        },
    };
}

export interface PlanMoveAlertProps {
    className?: string;
}

/** The dragged dish puts itself back when the move fails; this says why. */
export const PlanMoveAlert: React.FC<PlanMoveAlertProps> = ({ className = '' }) => {
    const { moveFailed } = useContext(PlanDragContext);

    if (!moveFailed) return null;

    return (
        <Alert variant="error" className={className}>
            That dish couldn’t be moved — it’s back on the day it came from.
        </Alert>
    );
};
