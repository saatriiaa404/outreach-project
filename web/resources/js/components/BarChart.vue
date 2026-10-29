<script setup lang="ts">
import { GroupedBar } from '@unovis/ts';
import {
    VisAxis,
    VisGroupedBar,
    VisTooltip,
    VisXYContainer,
} from '@unovis/vue';
import { computed } from 'vue';
import { ChartContainer } from '@/components/ui/chart';
import type { ChartConfig } from '@/components/ui/chart';

export type BarRow = { label: string; tooltip?: string; values: number[] };
export type BarSeries = { key: string; name: string; color: string };

// Vertical bars over days or months (D-068); ranked categories are drawn
// as labelled bars in AdminCharts.
const props = withDefaults(
    defineProps<{
        rows: BarRow[];
        series: BarSeries[];
        // Show every nth category label so long axes stay readable.
        labelEvery?: number;
    }>(),
    { labelEvery: 1 },
);

const config = computed<ChartConfig>(() =>
    Object.fromEntries(
        props.series.map((item) => [
            item.key,
            { label: item.name, color: item.color },
        ]),
    ),
);

const x = (_: BarRow, i: number) => i;
const y = computed(() =>
    props.series.map((_, index) => (row: BarRow) => row.values[index] ?? 0),
);
const colors = computed(() => props.series.map((item) => item.color));
const max = computed(() =>
    Math.max(1, ...props.rows.flatMap((row) => row.values)),
);

const categoryTick = (value: number | Date) => {
    const index = Number(value);

    return index % props.labelEvery === 0 || index === props.rows.length - 1
        ? (props.rows[index]?.label ?? '')
        : '';
};
const valueTick = (value: number | Date) =>
    Number.isInteger(Number(value)) ? String(value) : '';

const height = 260;

// The tooltip is HTML; labels come from master data, so they are escaped.
const escape = (text: string) =>
    text.replace(
        /[&<>"']/g,
        (char) =>
            ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
            })[char]!,
    );

const tooltip = {
    [GroupedBar.selectors.bar]: (row: BarRow) =>
        `<div class="rounded-md border bg-background px-2 py-1 text-xs shadow">
            <p class="font-medium">${escape(row.tooltip ?? row.label)}</p>
            ${props.series
                .map(
                    (item, index) =>
                        `<p>${escape(item.name)}: <b>${row.values[index] ?? 0}</b></p>`,
                )
                .join('')}
        </div>`,
};
</script>

<template>
    <ChartContainer
        :config="config"
        class="aspect-auto"
        :style="{ height: `${height}px` }"
    >
        <VisXYContainer
            :data="rows"
            :height="height"
            :y-domain="[0, max]"
            :padding="{ top: 8 }"
        >
            <VisGroupedBar
                :x="x"
                :y="y"
                :color="colors"
                :rounded-corners="3"
                :group-padding="0.2"
            />
            <VisAxis
                type="x"
                :tick-line="false"
                :domain-line="false"
                :grid-line="false"
                :tick-values="rows.map((_, i) => i)"
                :tick-format="categoryTick"
            />
            <VisAxis
                type="y"
                :tick-line="false"
                :domain-line="false"
                :num-ticks="Math.min(max, 5)"
                :tick-format="valueTick"
            />
            <VisTooltip :triggers="tooltip" />
        </VisXYContainer>
    </ChartContainer>
</template>
