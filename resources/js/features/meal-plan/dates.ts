/**
 * Plain-date helpers for the planner.
 *
 * Plan dates are calendar days with no time and no zone: the API sends and
 * accepts `YYYY-MM-DD`. The one trap is `new Date('2026-09-14')`, which the
 * language reads as UTC midnight — west of Greenwich that renders as the 13th.
 * So nothing here ever hands an ISO string to the `Date` constructor: parsing
 * splits the parts and builds a *local* midnight, and formatting reads the
 * local getters instead of `toISOString()`. Day arithmetic that must be exact
 * (differences, ranges) goes through `Date.UTC` on the parts, which is immune
 * to the 23- and 25-hour days daylight saving produces.
 */

/** A calendar day as `YYYY-MM-DD`. */
export type IsoDate = string;

/** Longest span the API accepts for one plan. */
export const MAX_PLAN_DAYS = 60;

const MS_PER_DAY = 86_400_000;
const ISO_PATTERN = /^\d{4}-\d{2}-\d{2}$/;

const pad = (value: number): string => String(value).padStart(2, '0');

/**
 * @returns [year, month (1-12), day]
 */
function parts(iso: IsoDate): [number, number, number] {
    const [year, month, day] = iso.split('-').map(Number);
    return [year || 1970, month || 1, day || 1];
}

export function isIsoDate(value: unknown): value is IsoDate {
    return typeof value === 'string' && ISO_PATTERN.test(value);
}

/** The local calendar day of a `Date`, never shifted by the zone offset. */
export function toIso(date: Date): IsoDate {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

/** Local midnight on that day — safe to format, never to serialise. */
export function fromIso(iso: IsoDate): Date {
    const [year, month, day] = parts(iso);
    return new Date(year, month - 1, day);
}

export function todayIso(): IsoDate {
    return toIso(new Date());
}

export function addDays(iso: IsoDate, days: number): IsoDate {
    const date = fromIso(iso);
    date.setDate(date.getDate() + days);
    return toIso(date);
}

/** Whole days from `from` to `to`; negative when `to` is earlier. */
export function daysBetween(from: IsoDate, to: IsoDate): number {
    const [fy, fm, fd] = parts(from);
    const [ty, tm, td] = parts(to);
    return Math.round((Date.UTC(ty, tm - 1, td) - Date.UTC(fy, fm - 1, fd)) / MS_PER_DAY);
}

/** Days covered by an inclusive range; 1 for a single-day plan. */
export function rangeLength(start: IsoDate, end: IsoDate): number {
    return daysBetween(start, end) + 1;
}

/** Every day of an inclusive range, oldest first. Guarded against a bad range. */
export function listRange(start: IsoDate, end: IsoDate, limit = 366): IsoDate[] {
    const length = rangeLength(start, end);
    if (!Number.isFinite(length) || length < 1) return [];

    const days: IsoDate[] = [];
    for (let offset = 0; offset < Math.min(length, limit); offset++) {
        days.push(addDays(start, offset));
    }
    return days;
}

/** ISO dates sort lexicographically, so plain comparison is the date comparison. */
export function isBefore(a: IsoDate, b: IsoDate): boolean {
    return a < b;
}

export function isWithin(iso: IsoDate, start: IsoDate, end: IsoDate): boolean {
    return iso >= start && iso <= end;
}

export function isPastDay(iso: IsoDate, reference: IsoDate = todayIso()): boolean {
    return iso < reference;
}

export function isToday(iso: IsoDate, reference: IsoDate = todayIso()): boolean {
    return iso === reference;
}

export function clampIso(iso: IsoDate, min: IsoDate, max: IsoDate): IsoDate {
    if (iso < min) return min;
    if (iso > max) return max;
    return iso;
}

export function startOfMonth(iso: IsoDate): IsoDate {
    const [year, month] = parts(iso);
    return `${year}-${pad(month)}-01`;
}

/** First of the month `months` away — day 1 throughout, so nothing overflows. */
export function addMonths(iso: IsoDate, months: number): IsoDate {
    const [year, month] = parts(iso);
    const date = new Date(year, month - 1 + months, 1);
    return toIso(date);
}

export function endOfMonth(iso: IsoDate): IsoDate {
    const [year, month] = parts(iso);
    return toIso(new Date(year, month, 0));
}

export function formatDay(iso: IsoDate): string {
    return fromIso(iso).toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short' });
}

/** "Tuesday 16 September" — the spoken form, for accessible names. */
export function formatDayLong(iso: IsoDate): string {
    return fromIso(iso).toLocaleDateString(undefined, { weekday: 'long', day: 'numeric', month: 'long' });
}

export function formatShort(iso: IsoDate): string {
    return fromIso(iso).toLocaleDateString(undefined, { day: 'numeric', month: 'short' });
}

export function formatWeekday(iso: IsoDate): string {
    return fromIso(iso).toLocaleDateString(undefined, { weekday: 'long' });
}

export function formatMonthTitle(iso: IsoDate): string {
    return fromIso(iso).toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
}

export function formatDayNumber(iso: IsoDate): string {
    return String(parts(iso)[2]);
}

/**
 * "16 – 22 Sep 2026". `Intl` collapses the repeated halves in whatever order
 * the viewer's locale writes dates; the fallback spells both ends out for the
 * rare engine without `formatRange`.
 */
export function formatRange(start: IsoDate, end: IsoDate): string {
    const from = fromIso(start);
    const to = fromIso(end);
    const options: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'short', year: 'numeric' };

    if (start === end) return from.toLocaleDateString(undefined, options);

    const formatter = new Intl.DateTimeFormat(undefined, options);
    if (typeof formatter.formatRange === 'function') return formatter.formatRange(from, to);

    return `${formatter.format(from)} – ${formatter.format(to)}`;
}

/** "Today", "Tomorrow", "Yesterday", else the weekday. */
export function relativeDayLabel(iso: IsoDate, reference: IsoDate = todayIso()): string {
    const distance = daysBetween(reference, iso);
    if (distance === 0) return 'Today';
    if (distance === 1) return 'Tomorrow';
    if (distance === -1) return 'Yesterday';
    return formatWeekday(iso);
}

/** "Today · 16 Sep" near the present, "Tue 16 Sep" further out. */
export function dayOptionLabel(iso: IsoDate, reference: IsoDate = todayIso()): string {
    const distance = daysBetween(reference, iso);
    if (distance >= -1 && distance <= 1) return `${relativeDayLabel(iso, reference)} · ${formatShort(iso)}`;
    return formatDay(iso);
}

export interface MonthCell {
    iso: IsoDate;
    /** False for the leading and trailing days that pad the grid. */
    inMonth: boolean;
}

/** Monday-first weeks covering the whole month, as complete rows of seven. */
export function monthGrid(monthIso: IsoDate): MonthCell[][] {
    const first = startOfMonth(monthIso);
    const [year, month] = parts(first);
    const daysInMonth = parts(endOfMonth(monthIso))[2];
    const leading = (fromIso(first).getDay() + 6) % 7;
    const gridStart = addDays(first, -leading);
    const total = Math.ceil((leading + daysInMonth) / 7) * 7;

    const weeks: MonthCell[][] = [];
    for (let index = 0; index < total; index += 7) {
        weeks.push(
            Array.from({ length: 7 }, (_, offset) => {
                const iso = addDays(gridStart, index + offset);
                const [cellYear, cellMonth] = parts(iso);
                return { iso, inMonth: cellYear === year && cellMonth === month };
            }),
        );
    }
    return weeks;
}

/** Monday-first weekday initials in the viewer's locale. */
export function weekdayLabels(): Array<{ short: string; long: string }> {
    const monday = '2024-01-01';
    return Array.from({ length: 7 }, (_, offset) => {
        const date = fromIso(addDays(monday, offset));
        return {
            short: date.toLocaleDateString(undefined, { weekday: 'narrow' }),
            long: date.toLocaleDateString(undefined, { weekday: 'long' }),
        };
    });
}
