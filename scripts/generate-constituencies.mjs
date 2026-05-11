#!/usr/bin/env node
// Generates `data/federal-constituencies.json` and
// `data/provincial-constituencies.json` from the official 2079 BS / 2022 AD
// Election Commission seat allocations.
//
// Numbers cross-checked against the netajee migration
// `database/migrations/2026_01_13_023318_create_nepal_electoral_geography_tables.php`.
//
// District names from that migration are mapped to package slugs via
// DISTRICT_REMAP (netajee uses "Rukum East" / "Rukum West"; the vendored
// dataset uses "eastern-rukum" / "western-rukum").

import { writeFileSync, readFileSync } from 'node:fs';
import { resolve, dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const root = resolve(here, '..');
const dataDir = join(root, 'data');

const provinces = JSON.parse(readFileSync(join(dataDir, 'provinces.json'), 'utf8'));
const districts = JSON.parse(readFileSync(join(dataDir, 'districts.json'), 'utf8'));

const DISTRICT_REMAP = {
    'Rukum East': 'eastern-rukum',
    'Rukum West': 'western-rukum',
};

const slugify = (s) => s.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');

const districtByName = new Map();
for (const d of districts) {
    districtByName.set(d.nameEn.toLowerCase(), d);
}
const lookupDistrict = (name) => {
    const remapped = DISTRICT_REMAP[name];
    if (remapped) {
        return districts.find((d) => d.slug === remapped);
    }
    return districtByName.get(name.toLowerCase());
};

const provinceByName = new Map(provinces.map((p) => [p.nameEn, p]));

// ──────────────────────────────────────────────────────────────────────
// Federal House of Representatives — 165 first-past-the-post constituencies.
// ──────────────────────────────────────────────────────────────────────
const FEDERAL_COUNTS = {
    Taplejung: 1, Sankhuwasabha: 1, Solukhumbu: 1, Okhaldhunga: 1,
    Khotang: 1, Bhojpur: 1, Dhankuta: 1, Terhathum: 1,
    Panchthar: 1, Ilam: 2, Jhapa: 5, Morang: 6,
    Sunsari: 4, Udayapur: 2,
    Saptari: 4, Siraha: 4, Dhanusha: 4, Mahottari: 4,
    Sarlahi: 4, Rautahat: 4, Bara: 4, Parsa: 4,
    Dolakha: 1, Sindhupalchok: 2, Rasuwa: 1, Nuwakot: 2,
    Dhading: 2, Kathmandu: 10, Bhaktapur: 2, Lalitpur: 3,
    Kavrepalanchok: 2, Ramechhap: 1, Sindhuli: 2, Makwanpur: 2,
    Chitwan: 3,
    Gorkha: 2, Manang: 1, Mustang: 1, Myagdi: 1,
    Kaski: 3, Lamjung: 1, Tanahun: 2, Nawalpur: 2,
    Syangja: 2, Parbat: 1, Baglung: 2,
    'Rukum East': 1, Rolpa: 1, Pyuthan: 1, Gulmi: 2,
    Arghakhanchi: 1, Palpa: 2, Parasi: 2, Rupandehi: 5,
    Kapilvastu: 3, Dang: 3, Banke: 3, Bardiya: 2,
    'Rukum West': 1, Salyan: 1, Dolpa: 1, Humla: 1,
    Jumla: 1, Kalikot: 1, Mugu: 1, Surkhet: 2,
    Dailekh: 2, Jajarkot: 1,
    Bajura: 1, Bajhang: 1, Achham: 2, Doti: 1,
    Kailali: 5, Kanchanpur: 3, Dadeldhura: 1, Baitadi: 1,
    Darchula: 1,
};

const federal = [];
for (const [districtName, count] of Object.entries(FEDERAL_COUNTS)) {
    const district = lookupDistrict(districtName);
    if (!district) {
        throw new Error(`Unknown district in federal constituencies: ${districtName}`);
    }
    for (let i = 1; i <= count; i++) {
        federal.push({
            id: `F.${district.id}.${i}`,
            type: 'federal',
            number: i,
            nameEn: `${districtName}-${i}`,
            nameNe: `${district.nameNe}-${i}`,
            slug: `${district.slug}-${i}`,
            districtId: district.id,
            provinceId: district.provinceId,
        });
    }
}

// ──────────────────────────────────────────────────────────────────────
// Provincial assemblies — 330 first-past-the-post constituencies (2x federal
// per province, minus the upper-house ratio adjustment in the official seat plan).
// ──────────────────────────────────────────────────────────────────────
const PROVINCIAL_COUNTS = {
    Koshi: 56,
    Madhesh: 64,
    Bagmati: 66,
    Gandaki: 36,
    Lumbini: 52,
    Karnali: 24,
    Sudurpashchim: 32,
};

const provincial = [];
for (const [provinceName, count] of Object.entries(PROVINCIAL_COUNTS)) {
    const province = provinceByName.get(provinceName);
    if (!province) {
        throw new Error(`Unknown province: ${provinceName}`);
    }
    for (let i = 1; i <= count; i++) {
        provincial.push({
            id: `PR.${province.id}.${i}`,
            type: 'provincial',
            number: i,
            nameEn: `${provinceName} Provincial-${i}`,
            nameNe: `${province.nameNe} प्रदेश-${i}`,
            slug: `${province.slug}-provincial-${i}`,
            provinceId: province.id,
            districtId: null,
        });
    }
}

const all = [...federal, ...provincial];

writeFileSync(
    join(dataDir, 'constituencies.json'),
    JSON.stringify(all, null, 2),
);

console.log(`Wrote ${federal.length} federal + ${provincial.length} provincial = ${all.length} constituencies`);
console.log(`Federal sanity check: ${federal.length === 165 ? 'OK' : `FAIL (expected 165)`}`);
console.log(`Provincial sanity check: ${provincial.length === 330 ? 'OK' : `FAIL (expected 330)`}`);
