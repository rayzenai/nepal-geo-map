// Address formatter, parser, and validator.

import {
    getProvince,
    getDistrict,
    getLocalUnit,
    findByPostalCode,
} from './lookup';

export interface AddressParts {
    /** Strings (e.g. raw form input) are coerced; `''` means no ward. */
    ward?: number | string | null;
    tole?: string | null;
    localUnit?: string | null;
    district?: string | null;
    province?: string | number | null;
    postalCode?: string | null;
    country?: string | null;
}

export interface FormatAddressOptions {
    lang?: 'en' | 'ne';
    style?: 'short' | 'long' | 'postal';
}

export interface ParsedAddress {
    ward: number | null;
    tole: string | null;
    localUnit: string | null;
    district: string | null;
    province: string | null;
    postalCode: string | null;
    confidence: number;
}

export interface AddressValidationResult {
    valid: boolean;
    errors: readonly string[];
}

export function formatAddress(parts: AddressParts, options: FormatAddressOptions = {}): string {
    const lang = options.lang ?? 'en';
    const style = options.style ?? 'short';

    const unit = parts.localUnit ? getLocalUnit(parts.localUnit) : undefined;
    const district = parts.district ? getDistrict(parts.district) : undefined;
    const province =
        parts.province !== undefined && parts.province !== null && parts.province !== ''
            ? getProvince(parts.province)
            : undefined;

    const tokens: (string | null | undefined)[] = [];

    if (parts.ward !== undefined && parts.ward !== null && parts.ward !== '' && !Number.isNaN(Number(parts.ward))) {
        tokens.push(lang === 'ne' ? `वडा ${Number(parts.ward)}` : `Ward ${Number(parts.ward)}`);
    }
    if (parts.tole) tokens.push(String(parts.tole));

    const unitName = unit ? (lang === 'ne' ? unit.nameNe : unit.nameEn) : parts.localUnit ?? null;
    if (unitName) tokens.push(String(unitName));

    if (style === 'postal') {
        const districtName = district ? (lang === 'ne' ? district.nameNe : district.nameEn) : parts.district ?? null;
        const postal = parts.postalCode ?? unit?.postalCode ?? null;
        if (districtName) {
            tokens.push(postal ? `${districtName} ${postal}` : districtName);
        } else if (postal) {
            tokens.push(postal);
        }
        tokens.push(parts.country ?? (lang === 'ne' ? 'नेपाल' : 'Nepal'));
        return joinTokens(tokens);
    }

    const districtName = district ? (lang === 'ne' ? district.nameNe : district.nameEn) : parts.district ?? null;
    if (districtName) tokens.push(String(districtName));

    if (style === 'long') {
        const provinceName = province ? (lang === 'ne' ? province.nameNe : province.nameEn) : parts.province ?? null;
        if (provinceName) tokens.push(String(provinceName));
        tokens.push(parts.country ?? (lang === 'ne' ? 'नेपाल' : 'Nepal'));
    }

    return joinTokens(tokens);
}

const joinTokens = (tokens: (string | null | undefined)[]): string =>
    tokens
        .filter((t): t is string => typeof t === 'string' && t.trim() !== '')
        .map((t) => t.trim())
        .join(', ');

export function parseAddress(raw: string): ParsedAddress {
    const result: ParsedAddress = {
        ward: null,
        tole: null,
        localUnit: null,
        district: null,
        province: null,
        postalCode: null,
        confidence: 0,
    };

    let work = raw.trim();
    if (!work) return result;

    let hits = 0;

    // 1. Postal code — 5 digits.
    const postalMatch = work.match(/\b(\d{5})\b/);
    if (postalMatch) {
        result.postalCode = postalMatch[1]!;
        work = work.replace(postalMatch[0], ' ');
        const unit = findByPostalCode(result.postalCode);
        if (unit) {
            result.localUnit = unit.slug;
            const d = getDistrict(unit.districtId);
            if (d) {
                result.district = d.slug;
                const p = getProvince(d.provinceId);
                if (p) result.province = p.slug;
            }
            hits += 3;
        } else {
            hits++;
        }
    }

    // 2. Ward.
    const wardPatterns = [
        /\b(?:ward|w)[\s.\-]*(?:no[.\s]*)?(\d{1,2})\b/i,
        /वडा[\s.\-]*(?:नं[.\s]*)?(\d{1,2})/,
    ];
    for (const re of wardPatterns) {
        const m = work.match(re);
        if (m) {
            result.ward = Number(m[1]!);
            work = work.replace(m[0], ' ');
            hits++;
            break;
        }
    }

    // 3. Token-by-token entity matching.
    const tokens = work
        .split(/[,;/|\n]+/)
        .map((t) => t.trim())
        .filter((t) => t.length > 0);

    const unresolved: string[] = [];
    for (const token of tokens) {
        const clean = token.replace(/\s+/g, ' ').trim();
        if (!clean) continue;

        if (!result.localUnit) {
            const u = getLocalUnit(clean);
            if (u) {
                result.localUnit = u.slug;
                hits++;
                continue;
            }
        }
        if (!result.district) {
            const d = getDistrict(clean);
            if (d) {
                result.district = d.slug;
                hits++;
                continue;
            }
        }
        if (!result.province) {
            const p = getProvince(clean);
            if (p) {
                result.province = p.slug;
                hits++;
                continue;
            }
        }
        unresolved.push(clean);
    }

    if (!result.tole && unresolved.length && (result.ward !== null || result.localUnit || result.district)) {
        result.tole = unresolved[0]!;
        hits++;
    }

    // 4. Backfill parents from children.
    if (result.localUnit && !result.district) {
        const u = getLocalUnit(result.localUnit);
        if (u) {
            const d = getDistrict(u.districtId);
            if (d) result.district = d.slug;
        }
    }
    if (result.district && !result.province) {
        const d = getDistrict(result.district);
        if (d) {
            const p = getProvince(d.provinceId);
            if (p) result.province = p.slug;
        }
    }

    result.confidence = Math.min(1, hits / 6);
    return result;
}

export function validateAddress(parts: AddressParts): AddressValidationResult {
    const errors: string[] = [];

    const province = parts.province !== undefined && parts.province !== null && parts.province !== ''
        ? getProvince(parts.province)
        : undefined;
    if (parts.province !== undefined && parts.province !== null && parts.province !== '' && !province) {
        errors.push(`Unknown province: ${parts.province}`);
    }

    const district = parts.district ? getDistrict(parts.district) : undefined;
    if (parts.district && !district) {
        errors.push(`Unknown district: ${parts.district}`);
    }
    if (district && province && district.provinceId !== province.id) {
        errors.push(`District ${district.nameEn} does not belong to province ${province.nameEn}`);
    }

    const unit = parts.localUnit ? getLocalUnit(parts.localUnit) : undefined;
    if (parts.localUnit && !unit) {
        errors.push(`Unknown local unit: ${parts.localUnit}`);
    }
    if (unit && district && unit.districtId !== district.id) {
        errors.push(`Local unit ${unit.nameEn} does not belong to district ${district.nameEn}`);
    }

    if (parts.ward !== undefined && parts.ward !== null && parts.ward !== '') {
        const w = Number(parts.ward);
        if (w < 1) errors.push(`Invalid ward number: ${w}`);
        else if (unit && w > unit.wards) errors.push(`Ward ${w} is out of range for ${unit.nameEn} (1-${unit.wards})`);
    }

    if (parts.postalCode && !/^\d{5}$/.test(String(parts.postalCode))) {
        errors.push(`Postal code must be 5 digits: ${parts.postalCode}`);
    }

    return { valid: errors.length === 0, errors };
}
