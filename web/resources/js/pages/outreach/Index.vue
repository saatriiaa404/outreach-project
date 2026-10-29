<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import ColumnFilter from '@/components/ColumnFilter.vue';
import Heading from '@/components/Heading.vue';
import OutreachSearchForm from '@/components/outreach/OutreachSearchForm.vue';
import type { SearchCriteria } from '@/components/outreach/OutreachSearchForm.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { create, edit, index, show } from '@/routes/outreaches';

type OutreachRow = {
    id: number;
    code: string;
    nama: string | null;
    source: string;
    type: string;
    outreach_date: string;
    status: 'DRAFT' | 'MENUNGGU_PENGESAHAN' | 'PERLU_PERBAIKAN' | 'SELESAI';
    can_edit: boolean;
    can_open: boolean;
};

type Props = {
    outreaches: {
        data: OutreachRow[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    statuses: OutreachRow['status'][];
    status: OutreachRow['status'] | null;
    filters: {
        tanggal: string | null;
        source: number | null;
        type: number | null;
    };
    dateRanges: Record<string, string>;
    canCreate: boolean;
    search: SearchCriteria;
    searchOptions: {
        sources: { id: number; name: string }[];
        types: { id: number; name: string }[];
    };
};

const props = defineProps<Props>();

const options = computed(() => ({
    tanggal: Object.entries(props.dateRanges).map(([value, label]) => ({
        value,
        label,
    })),
    source: props.searchOptions.sources.map((item) => ({
        value: item.id,
        label: item.name,
    })),
    type: props.searchOptions.types.map((item) => ({
        value: item.id,
        label: item.name,
    })),
    status: props.statuses.map((item) => ({
        value: item,
        label: statusLabels[item],
    })),
}));
const filtered = computed(
    () =>
        props.status !== null ||
        Object.values(props.filters).some((value) => value !== null),
);

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Outreach', href: index() }],
    },
});

const statusLabels: Record<OutreachRow['status'], string> = {
    DRAFT: 'Draft',
    MENUNGGU_PENGESAHAN: 'Menunggu Pengesahan',
    PERLU_PERBAIKAN: 'Perlu Perbaikan',
    SELESAI: 'Selesai',
};
</script>

<template>
    <Head title="Outreach" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Outreach"
                description="Daftar Outreach sesuai hak akses Anda"
            />
            <Button v-if="canCreate" as-child>
                <Link :href="create()">Buat Outreach</Link>
            </Button>
        </div>

        <OutreachSearchForm :search="search" :options="searchOptions" />

        <nav class="flex flex-wrap gap-2" data-test="status-filter">
            <Link
                v-for="item in [null, ...statuses]"
                :key="item ?? 'all'"
                :href="index({ query: { status: item ?? '' } })"
                class="rounded-full border px-3 py-1 text-sm"
                :class="
                    item === status
                        ? 'bg-primary text-primary-foreground'
                        : 'hover:bg-muted'
                "
                >{{ item ? statusLabels[item] : 'Semua' }}</Link
            >
        </nav>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-2 font-medium">Kode</th>
                        <th class="px-4 py-2 font-medium">Nama</th>
                        <th class="px-4 py-2">
                            <ColumnFilter
                                label="Source"
                                param="source"
                                :options="options.source"
                                :selected="filters.source"
                            />
                        </th>
                        <th class="px-4 py-2">
                            <ColumnFilter
                                label="Jenis"
                                param="type"
                                :options="options.type"
                                :selected="filters.type"
                            />
                        </th>
                        <th class="px-4 py-2">
                            <ColumnFilter
                                label="Tanggal"
                                param="tanggal"
                                :options="options.tanggal"
                                :selected="filters.tanggal"
                            />
                        </th>
                        <th class="px-4 py-2">
                            <ColumnFilter
                                label="Status"
                                param="status"
                                all-value=""
                                :options="options.status"
                                :selected="status"
                            />
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="outreaches.data.length === 0">
                        <td
                            colspan="6"
                            class="px-4 py-6 text-center text-muted-foreground"
                            data-test="empty"
                        >
                            {{
                                filtered
                                    ? 'Tidak ada Outreach yang cocok dengan filter ini.'
                                    : 'Belum ada Outreach.'
                            }}
                        </td>
                    </tr>
                    <tr
                        v-for="outreach in outreaches.data"
                        :key="outreach.id"
                        class="border-t"
                    >
                        <td class="px-4 py-2 font-mono">
                            <Link
                                v-if="outreach.can_edit"
                                :href="edit(outreach.id)"
                                class="underline underline-offset-4"
                            >
                                {{ outreach.code }}
                            </Link>
                            <Link
                                v-else-if="outreach.can_open"
                                :href="show(outreach.id)"
                                class="underline underline-offset-4"
                            >
                                {{ outreach.code }}
                            </Link>
                            <span v-else>{{ outreach.code }}</span>
                        </td>
                        <td class="px-4 py-2">{{ outreach.nama ?? '—' }}</td>
                        <td class="px-4 py-2">{{ outreach.source }}</td>
                        <td class="px-4 py-2">{{ outreach.type }}</td>
                        <td class="px-4 py-2">{{ outreach.outreach_date }}</td>
                        <td class="px-4 py-2">
                            <Badge variant="secondary">
                                {{ statusLabels[outreach.status] }}
                            </Badge>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
