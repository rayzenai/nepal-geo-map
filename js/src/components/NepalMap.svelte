<script lang="ts">
    import { DISTRICTS, NEPAL_DISTRICTS_SVG, PROVINCES } from '../data';

    type Props = {
        /** Counts keyed by district slug — drives the heatmap intensity. */
        districtCounts?: Record<string, number>;
        /** Slug to highlight externally (e.g. from URL state). */
        highlightSlug?: string | null;
        onDistrictClick?: (slug: string) => void;
        onDistrictHover?: (slug: string | null) => void;
        /**
         * Remap from the package's canonical district slug to whatever your DB
         * uses. Anything not listed passes through unchanged. The map keys
         * outside this component (`districtCounts`, `highlightSlug`, callback
         * arguments) use the *remapped* slug.
         *
         * Example: `{ 'eastern-rukum': 'rukum-east', 'western-rukum': 'rukum-west' }`
         */
        slugRemap?: Record<string, string>;
        /** Hex map keyed by province number `1..7`. */
        provinceColors?: Partial<Record<number, string>>;
        /** Heatmap base hue (HSL `H`). Defaults to teal-ish. */
        heatHue?: number;
        class?: string;
    };

    let {
        districtCounts = {},
        highlightSlug = null,
        onDistrictClick,
        onDistrictHover,
        slugRemap = {},
        provinceColors = {},
        heatHue = 199,
        class: className = '',
    }: Props = $props();

    const DEFAULT_PROVINCE_COLORS: Record<number, string> = {
        1: '#C7D2FE',
        2: '#BBF7D0',
        3: '#FEF08A',
        4: '#FBCFE8',
        5: '#FED7AA',
        6: '#A5F3FC',
        7: '#DDD6FE',
    };

    const provinceColor = (provinceNum: number): string =>
        provinceColors[provinceNum] ?? DEFAULT_PROVINCE_COLORS[provinceNum] ?? '#D1D5DB';

    const provinceNumberById = new Map(PROVINCES.map((p) => [p.id, p.number]));

    const remap = (slug: string): string => slugRemap[slug] ?? slug;

    // Hand-drawn outlines (pre-projected), so the map keeps the familiar
    // Nepal shape. Caller can resize freely via CSS / `class`.
    const VIEW_BOX = NEPAL_DISTRICTS_SVG.viewBox;
    const [vbX, vbY, vbW, vbH] = VIEW_BOX.split(' ').map(Number);

    type DistrictPath = {
        slug: string;
        d: string;
        provinceNum: number;
    };

    // id -> district, declared *before* `districtPaths` so the eager `.map()`
    // below can read it without tripping the TDZ in bundled output.
    const districtById = new Map(DISTRICTS.map((d) => [d.id, d]));

    const districtPaths: DistrictPath[] = NEPAL_DISTRICTS_SVG.districts.map(({ id, d }) => {
        const district = districtById.get(id);
        return {
            slug: remap(district?.slug ?? id),
            d,
            provinceNum: (district && provinceNumberById.get(district.provinceId)) ?? 0,
        };
    });

    let hoveredDistrict: string | null = $state(null);
    let tooltipX = $state(0);
    let tooltipY = $state(0);

    const activeSlug = $derived(highlightSlug ?? hoveredDistrict);

    const maxCount = $derived(Math.max(1, ...Object.values(districtCounts)));

    function formatName(slug: string): string {
        return slug
            .split('-')
            .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
            .join(' ');
    }

    function heatColor(intensity: number): string {
        const l = 60 - intensity * 30;
        return `hsl(${heatHue}, 70%, ${Math.round(l)}%)`;
    }

    function fillFor(slug: string, provinceNum: number): string {
        const count = districtCounts[slug] ?? 0;
        if (count > 0) {
            return heatColor(Math.min(count / maxCount, 1));
        }
        return provinceColor(provinceNum);
    }

    function activePath(): DistrictPath | undefined {
        return activeSlug ? districtPaths.find((p) => p.slug === activeSlug) : undefined;
    }

    function handleMouseMove(e: MouseEvent) {
        tooltipX = e.clientX;
        tooltipY = e.clientY;
    }

    function handleEnter(slug: string) {
        hoveredDistrict = slug;
        onDistrictHover?.(slug);
    }

    function handleLeave() {
        hoveredDistrict = null;
        onDistrictHover?.(null);
    }

    function handleKeydown(e: KeyboardEvent, slug: string) {
        if (!onDistrictClick) return;
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            onDistrictClick(slug);
        }
    }
</script>

<div class="relative {className}">
    <svg
        viewBox={VIEW_BOX}
        class="h-full w-full"
        preserveAspectRatio="xMidYMid meet"
        role="img"
        aria-label="Map of Nepal showing districts"
        onmousemove={handleMouseMove}
    >
        <!-- Base layer (closes sub-pixel gaps between districts). -->
        <g class="pointer-events-none">
            {#each districtPaths as p (`${p.slug}-base`)}
                <path
                    d={p.d}
                    fill={fillFor(p.slug, p.provinceNum)}
                    stroke={fillFor(p.slug, p.provinceNum)}
                    stroke-width="0.5"
                />
            {/each}
        </g>

        <!-- Interactive district fills. -->
        <g>
            {#each districtPaths as p (p.slug)}
                {@const count = districtCounts[p.slug] ?? 0}
                {#if onDistrictClick}
                    <path
                        id={p.slug}
                        d={p.d}
                        fill={fillFor(p.slug, p.provinceNum)}
                        stroke="#ffffff"
                        stroke-width="0.5"
                        style="opacity: 0.85; transition: all 0.15s ease;"
                        class="cursor-pointer"
                        role="button"
                        tabindex="0"
                        aria-label={formatName(p.slug) +
                            (count > 0 ? ` — ${count} item${count === 1 ? '' : 's'}` : '')}
                        onmouseenter={() => handleEnter(p.slug)}
                        onmouseleave={handleLeave}
                        onclick={() => onDistrictClick?.(p.slug)}
                        onkeydown={(e) => handleKeydown(e, p.slug)}
                        onfocus={() => handleEnter(p.slug)}
                        onblur={handleLeave}
                    >
                        <title
                            >{formatName(p.slug)}{count > 0
                                ? ` — ${count} item${count === 1 ? '' : 's'}`
                                : ''}</title
                        >
                    </path>
                {:else}
                    <path
                        id={p.slug}
                        d={p.d}
                        fill={fillFor(p.slug, p.provinceNum)}
                        stroke="#ffffff"
                        stroke-width="0.5"
                        style="opacity: 0.85; transition: all 0.15s ease;"
                        role="img"
                        aria-label={formatName(p.slug) +
                            (count > 0 ? ` — ${count} item${count === 1 ? '' : 's'}` : '')}
                        onmouseenter={() => handleEnter(p.slug)}
                        onmouseleave={handleLeave}
                    >
                        <title
                            >{formatName(p.slug)}{count > 0
                                ? ` — ${count} item${count === 1 ? '' : 's'}`
                                : ''}</title
                        >
                    </path>
                {/if}
            {/each}
        </g>

        <!-- Hover dim overlay clipped to country shape, with cutout for active district. -->
        {#if activeSlug}
            {@const active = activePath()}
            {#if active}
                <defs>
                    <clipPath id="country-clip">
                        {#each districtPaths as p, i (`clip-${i}`)}
                            <path d={p.d} />
                        {/each}
                    </clipPath>
                    <mask id="hover-mask">
                        <rect x={vbX} y={vbY} width={vbW} height={vbH} fill="white" />
                        <path d={active.d} fill="black" />
                    </mask>
                </defs>
                <rect
                    x={vbX}
                    y={vbY}
                    width={vbW}
                    height={vbH}
                    fill="rgba(0,0,0,0.4)"
                    mask="url(#hover-mask)"
                    clip-path="url(#country-clip)"
                    class="pointer-events-none"
                />
                <path
                    d={active.d}
                    fill={fillFor(active.slug, active.provinceNum)}
                    stroke="#ffffff"
                    stroke-width="1.5"
                    class="pointer-events-none"
                />
            {/if}
        {/if}
    </svg>

    {#if activeSlug}
        {@const count = districtCounts[activeSlug] ?? 0}
        <div
            class="pointer-events-none fixed z-50 rounded-lg bg-foreground px-3 py-2 text-sm text-background shadow-lg"
            style="left: {tooltipX + 14}px; top: {tooltipY + 14}px;"
        >
            <p class="font-medium">{formatName(activeSlug)}</p>
            <p class="text-xs opacity-70">
                {count > 0 ? `${count} item${count === 1 ? '' : 's'}` : 'No items yet'}
            </p>
        </div>
    {/if}
</div>
