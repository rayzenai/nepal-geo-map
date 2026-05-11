// Fuzzy search across provinces, districts, palikas, and their aliases.

import { PROVINCES, DISTRICTS, LOCAL_UNITS } from './data';

export interface SearchHit {
    readonly type: 'province' | 'district' | 'localUnit';
    readonly id: string;
    readonly slug: string;
    readonly nameEn: string;
    readonly nameNe: string;
    readonly score: number;
}

interface Indexed {
    readonly type: SearchHit['type'];
    readonly id: string;
    readonly slug: string;
    readonly nameEn: string;
    readonly nameNe: string;
    readonly keys: readonly string[];
}

const normalize = (v: string): string => v.trim().replace(/\s+/g, ' ').toLowerCase();

const corpus: readonly Indexed[] = (() => {
    const out: Indexed[] = [];
    const push = (type: SearchHit['type']) => (row: { id: string; slug: string; nameEn: string; nameNe: string; aliases?: readonly string[] }) => {
        const keys = new Set<string>();
        for (const k of [row.slug, row.nameEn, row.nameNe, ...(row.aliases ?? [])]) {
            const n = normalize(k);
            if (n) keys.add(n);
        }
        out.push({ type, id: row.id, slug: row.slug, nameEn: row.nameEn, nameNe: row.nameNe, keys: [...keys] });
    };
    PROVINCES.forEach(push('province'));
    DISTRICTS.forEach(push('district'));
    LOCAL_UNITS.forEach(push('localUnit'));
    return out;
})();

function levenshtein(a: string, b: string): number {
    const m = a.length;
    const n = b.length;
    if (m === 0) return n;
    if (n === 0) return m;
    const prev = new Array<number>(n + 1);
    const curr = new Array<number>(n + 1);
    for (let j = 0; j <= n; j++) prev[j] = j;
    for (let i = 1; i <= m; i++) {
        curr[0] = i;
        for (let j = 1; j <= n; j++) {
            const cost = a.charCodeAt(i - 1) === b.charCodeAt(j - 1) ? 0 : 1;
            curr[j] = Math.min(curr[j - 1]! + 1, prev[j]! + 1, prev[j - 1]! + cost);
        }
        for (let j = 0; j <= n; j++) prev[j] = curr[j]!;
    }
    return prev[n]!;
}

const isAscii = (v: string): boolean => /^[\x00-\x7F]*$/.test(v);

function score(needle: string, key: string): number {
    if (!key) return 0;
    if (needle === key) return 1;
    if (key.startsWith(needle) || needle.startsWith(key)) return 0.9;
    if (key.includes(needle) || needle.includes(key)) {
        const ratio = Math.min(needle.length, key.length) / Math.max(needle.length, key.length);
        return 0.6 + 0.15 * ratio;
    }
    if (!isAscii(needle) || !isAscii(key)) return 0;
    const maxLen = Math.max(needle.length, key.length);
    const budget = Math.max(1, Math.floor(maxLen * 0.34));
    const d = levenshtein(needle, key);
    if (d > budget) return 0;
    return 0.6 - 0.2 * (d / Math.max(1, budget));
}

export function search(query: string, limit = 10): readonly SearchHit[] {
    const needle = normalize(query);
    if (!needle) return [];
    const hits: SearchHit[] = [];
    for (const entry of corpus) {
        let best = 0;
        for (const k of entry.keys) {
            const s = score(needle, k);
            if (s > best) best = s;
        }
        if (best > 0) {
            hits.push({
                type: entry.type,
                id: entry.id,
                slug: entry.slug,
                nameEn: entry.nameEn,
                nameNe: entry.nameNe,
                score: best,
            });
        }
    }
    hits.sort((a, b) => b.score - a.score);
    return hits.slice(0, Math.max(0, limit));
}
