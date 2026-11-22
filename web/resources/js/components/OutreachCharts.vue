<script setup lang="ts">
import { computed } from 'vue';
import BarChart from '@/components/BarChart.vue';

export type OutreachChartData = {
    month: string;
    month_label: string;
    total: number;
    per_day: { day: number; total: number }[];
    per_type: { label: string; total: number }[];
    per_source: { label: string; total: number }[];
    trend: {
        month: string;
        label: string;
        created: number;
        approved: number;
    }[];
};

const props = defineProps<{ charts: OutreachChartData }>();

const created = [{ key: 'created', name: 'Outreach', color: 'var(--chart-1)' }];

const perDay = computed(() =>
    props.charts.per_day.map((item) => ({
        label: String(item.day),
        tooltip: `${item.day} ${props.charts.month_label}`,
        values: [item.total],
    })),
);
// Ranked categories read best as labelled bars with their count.
const ranked = (items: { label: string; total: number }[]) => {
    const max = Math.max(1, ...items.map((item) => item.total));

    return items.map((item) => ({
        ...item,
        width: `${(item.total / max) * 100}%`,
    }));
};
const trend = computed(() =>
    props.charts.trend.map((item) => ({
        label: item.label,
        values: [item.created, item.approved],
    })),
);
</script>

<template>
    <section class="grid min-w-0 gap-4" data-test="outreach-charts">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h3 class="font-medium">Grafik Outreach</h3>
                <p class="text-sm text-muted-foreground">
                    Dihitung dari tanggal Outreach.
                    {{ charts.total }} Outreach pada {{ charts.month_label }}.
                </p>
            </div>
        </div>

        <div class="min-w-0 rounded-xl border p-4">
            <h4 class="text-sm font-medium">
                Outreach per tanggal — {{ charts.month_label }}
            </h4>
            <BarChart :rows="perDay" :series="created" :label-every="5" />
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="min-w-0 rounded-xl border p-4">
                <h4 class="text-sm font-medium">
                    Per Jenis — {{ charts.month_label }}
                </h4>
                <ul class="mt-3 grid gap-2 text-sm" data-test="chart-per-type">
                    <li
                        v-for="item in ranked(charts.per_type)"
                        :key="item.label"
                        class="grid grid-cols-[minmax(0,10rem)_1fr_2rem] items-center gap-2"
                    >
                        <span class="truncate" :title="item.label">{{
                            item.label
                        }}</span>
                        <span class="h-4 rounded-sm bg-muted">
                            <span
                                class="block h-full rounded-sm bg-chart-1"
                                :style="{ width: item.width }"
                            />
                        </span>
                        <span class="text-right font-medium tabular-nums">{{
                            item.total
                        }}</span>
                    </li>
                </ul>
            </div>
            <div class="min-w-0 rounded-xl border p-4">
                <h4 class="text-sm font-medium">
                    Per Source — {{ charts.month_label }}
                </h4>
                <ul
                    class="mt-3 grid gap-2 text-sm"
                    data-test="chart-per-source"
                >
                    <li
                        v-for="item in ranked(charts.per_source)"
                        :key="item.label"
                        class="grid grid-cols-[minmax(0,10rem)_1fr_2rem] items-center gap-2"
                    >
                        <span class="truncate" :title="item.label">{{
                            item.label
                        }}</span>
                        <span class="h-4 rounded-sm bg-muted">
                            <span
                                class="block h-full rounded-sm bg-chart-1"
                                :style="{ width: item.width }"
                            />
                        </span>
                        <span class="text-right font-medium tabular-nums">{{
                            item.total
                        }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="min-w-0 rounded-xl border p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h4 class="text-sm font-medium">Tren 12 bulan</h4>
                <div class="flex gap-4 text-xs text-muted-foreground">
                    <span class="flex items-center gap-1"
                        ><span class="size-2.5 rounded-sm bg-chart-1" />
                        Dibuat</span
                    >
                    <span class="flex items-center gap-1"
                        ><span class="size-2.5 rounded-sm bg-chart-2" />
                        Disahkan</span
                    >
                </div>
            </div>
            <BarChart
                :rows="trend"
                :series="[
                    { key: 'created', name: 'Dibuat', color: 'var(--chart-1)' },
                    {
                        key: 'approved',
                        name: 'Disahkan',
                        color: 'var(--chart-2)',
                    },
                ]"
            />
        </div>
    </section>
</template>
