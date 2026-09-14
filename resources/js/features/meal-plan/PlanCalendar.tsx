import React, { useEffect, useRef, useState } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { IconButton } from '../../components/ui/IconButton';
import { Button } from '../../components/ui/Button';
import {
    IsoDate,
    addDays,
    addMonths,
    formatDayLong,
    formatDayNumber,
    formatMonthTitle,
    fromIso,
    isToday,
    monthGrid,
    startOfMonth,
    todayIso,
    weekdayLabels,
} from './dates';

export interface CalendarDay {
    /** Dishes planned for that day across every plan. */
    dishes: number;
    planId: number;
    planName: string;
    isActive: boolean;
}

export interface PlanCalendarProps {
    /** Any day inside the month on show. */
    month: IsoDate;
    onMonthChange: (month: IsoDate) => void;
    days: Record<IsoDate, CalendarDay>;
    onSelectDay?: (iso: IsoDate, day: CalendarDay | undefined) => void;
    isLoading?: boolean;
}

const WEEKDAYS = weekdayLabels();

/**
 * A month of the cook's planning history. Every day is a real button with a
 * spoken name ("Tuesday 16 September, 2 dishes"); arrows walk the grid, Home
 * and End jump to the ends of a week and PageUp/PageDown change month.
 */
export const PlanCalendar: React.FC<PlanCalendarProps> = ({ month, onMonthChange, days, onSelectDay, isLoading = false }) => {
    const weeks = monthGrid(month);
    const [focusedDay, setFocusedDay] = useState<IsoDate>(() => {
        const today = todayIso();
        return today.slice(0, 7) === month.slice(0, 7) ? today : startOfMonth(month);
    });
    const buttonRefs = useRef(new Map<IsoDate, HTMLButtonElement>());
    const shouldFocus = useRef(false);

    useEffect(() => {
        if (!shouldFocus.current) return;
        shouldFocus.current = false;
        buttonRefs.current.get(focusedDay)?.focus();
    }, [focusedDay]);

    const moveFocus = (iso: IsoDate) => {
        shouldFocus.current = true;
        setFocusedDay(iso);
        if (iso.slice(0, 7) !== month.slice(0, 7)) onMonthChange(startOfMonth(iso));
    };

    const goToMonth = (next: IsoDate, keepFocus = false) => {
        onMonthChange(next);
        const candidate = startOfMonth(next);
        if (keepFocus) shouldFocus.current = true;
        setFocusedDay(candidate);
    };

    const onKeyDown = (event: React.KeyboardEvent<HTMLButtonElement>, iso: IsoDate) => {
        const moves: Record<string, number> = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 };
        if (moves[event.key] !== undefined) {
            event.preventDefault();
            moveFocus(addDays(iso, moves[event.key]));
            return;
        }
        if (event.key === 'Home' || event.key === 'End') {
            event.preventDefault();
            const weekday = (fromIso(iso).getDay() + 6) % 7;
            moveFocus(addDays(iso, event.key === 'Home' ? -weekday : 6 - weekday));
            return;
        }
        if (event.key === 'PageUp' || event.key === 'PageDown') {
            event.preventDefault();
            goToMonth(addMonths(month, event.key === 'PageUp' ? -1 : 1), true);
        }
    };

    /** Exactly one day is tabbable; if the remembered one scrolled out of the grid, the 1st takes over. */
    const tabbableDay = weeks.some((week) => week.some((cell) => cell.iso === focusedDay)) ? focusedDay : startOfMonth(month);

    return (
        <div className="rounded-3xl border border-line bg-surface p-4 sm:p-6">
            <div className="flex items-center justify-between gap-3">
                <IconButton label="Previous month" variant="surface" size="sm" onClick={() => goToMonth(addMonths(month, -1))}>
                    <ChevronLeft className="size-4" />
                </IconButton>
                <h3 className="font-display text-lg font-semibold text-ink sm:text-xl" aria-live="polite">
                    {formatMonthTitle(month)}
                </h3>
                <IconButton label="Next month" variant="surface" size="sm" onClick={() => goToMonth(addMonths(month, 1))}>
                    <ChevronRight className="size-4" />
                </IconButton>
            </div>

            <table className="mt-4 w-full table-fixed border-separate border-spacing-1">
                <caption className="sr-only">Days you planned dishes for, {formatMonthTitle(month)}</caption>
                <thead>
                    <tr>
                        {WEEKDAYS.map((weekday) => (
                            <th key={weekday.long} scope="col" className="pb-1 text-[11px] font-semibold text-ink-3">
                                <span className="sr-only">{weekday.long}</span>
                                <span aria-hidden="true">{weekday.short}</span>
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {weeks.map((week) => (
                        <tr key={week[0].iso}>
                            {week.map((cell) => {
                                const entry = days[cell.iso];
                                const dishes = entry?.dishes ?? 0;
                                const today = isToday(cell.iso);
                                const tabbable = cell.iso === tabbableDay;

                                return (
                                    <td key={cell.iso} className="p-0 align-top">
                                        <button
                                            type="button"
                                            ref={(node) => {
                                                if (node) buttonRefs.current.set(cell.iso, node);
                                                else buttonRefs.current.delete(cell.iso);
                                            }}
                                            tabIndex={tabbable ? 0 : -1}
                                            aria-current={today ? 'date' : undefined}
                                            aria-label={`${formatDayLong(cell.iso)}, ${dishes === 0 ? 'no dishes' : `${dishes} ${dishes === 1 ? 'dish' : 'dishes'}`}${entry ? `, ${entry.planName}` : ''}`}
                                            onClick={() => onSelectDay?.(cell.iso, entry)}
                                            onFocus={() => setFocusedDay(cell.iso)}
                                            onKeyDown={(event) => onKeyDown(event, cell.iso)}
                                            className={`flex min-h-14 w-full cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border p-1 transition-colors focus-visible:outline-3 focus-visible:outline-primary focus-visible:outline-offset-2 sm:min-h-16 ${
                                                today ? 'border-primary' : entry ? 'border-line' : 'border-transparent'
                                            } ${entry ? (entry.isActive ? 'bg-primary-soft hover:bg-surface-3' : 'bg-surface-2 hover:bg-surface-3') : 'hover:bg-surface-2'} ${
                                                cell.inMonth ? '' : 'opacity-40'
                                            }`}
                                        >
                                            <span className={`font-display text-sm font-semibold tabular-nums ${today ? 'text-primary' : 'text-ink'}`} aria-hidden="true">
                                                {formatDayNumber(cell.iso)}
                                            </span>
                                            {dishes > 0 ? (
                                                <span
                                                    className="rounded-full bg-surface px-1.5 text-[10px] font-bold text-ink-2 tabular-nums"
                                                    aria-hidden="true"
                                                >
                                                    {dishes}
                                                </span>
                                            ) : (
                                                <span className="h-4" aria-hidden="true" />
                                            )}
                                        </button>
                                    </td>
                                );
                            })}
                        </tr>
                    ))}
                </tbody>
            </table>

            <div className="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-4">
                <ul className="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-ink-3">
                    <li className="flex items-center gap-1.5">
                        <span className="size-3 rounded bg-primary-soft" aria-hidden="true" />
                        Active plan
                    </li>
                    <li className="flex items-center gap-1.5">
                        <span className="size-3 rounded bg-surface-2" aria-hidden="true" />
                        Past plan
                    </li>
                    <li className="flex items-center gap-1.5">
                        <span className="size-3 rounded border border-primary" aria-hidden="true" />
                        Today
                    </li>
                </ul>
                <Button variant="ghost" size="sm" onClick={() => goToMonth(startOfMonth(todayIso()))}>
                    This month
                </Button>
            </div>

            {isLoading && (
                <p className="mt-3 text-center text-xs text-ink-3" role="status">
                    Counting dishes…
                </p>
            )}
        </div>
    );
};
