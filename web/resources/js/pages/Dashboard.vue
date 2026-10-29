<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminCharts from '@/components/AdminCharts.vue';
import type { AdminChartData } from '@/components/AdminCharts.vue';
import ColumnFilter from '@/components/ColumnFilter.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import { index as archiveIndex } from '@/routes/archive';
import { edit, index, show } from '@/routes/outreaches';
import { index as specialUnlockIndex } from '@/routes/special-unlock-requests';

type Status = 'DRAFT' | 'MENUNGGU_PENGESAHAN' | 'PERLU_PERBAIKAN' | 'SELESAI';

const props = defineProps<{
    role: 'PETUGAS' | 'KEPALA' | 'ADMIN';
    statuses: { status: Status; total: number }[];
    focus: {
        id: number;
        code: string;
        nama: string | null;
        type: string;
        outreach_date: string;
        status: Status;
        can_edit: boolean;
    }[];
    filters: {
        tanggal: string | null;
        type: number | null;
        status: Status | null;
    };
    filterOptions: {
        tanggal: Record<string, string>;
        types: { id: number; name: string }[];
        statuses: Status[];
    };
    admin: {
        special_unlock_open: number;
        archive_pending: number;
        archive_overdue: number;
    } | null;
    charts: AdminChartData | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const statusLabels: Record<Status, string> = {
    DRAFT: 'Draft',
    MENUNGGU_PENGESAHAN: 'Menunggu Pengesahan',
    PERLU_PERBAIKAN: 'Perlu Perbaikan',
    SELESAI: 'Selesai',
};

const options = computed(() => ({
    tanggal: Object.entries(props.filterOptions.tanggal).map(
        ([value, label]) => ({ value, label }),
    ),
    type: props.filterOptions.types.map((item) => ({
        value: item.id,
        label: item.name,
    })),
    status: props.filterOptions.statuses.map((item) => ({
        value: item,
        label: statusLabels[item],
    })),
}));
const filtered = computed(() =>
    Object.values(props.filters).some((value) => value !== null),
);

const focusTitle = {
    PETUGAS: 'Perlu dikerjakan',
    KEPALA: 'Antrean pengesahan',
    ADMIN: 'Antrean pengesahan Kepala',
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            title="Dashboard"
            :description="
                role === 'PETUGAS'
                    ? 'Outreach milik Anda per status'
                    : role === 'KEPALA'
                      ? 'Antrean pengesahan dan Outreach yang selesai'
                      : 'Semua Outreach, permintaan Special Unlock, dan arsip'
            "
        />

        <section
            class="grid grid-cols-2 gap-3 md:grid-cols-4"
            data-test="status-counts"
        >
            <Link
                v-for="item in statuses"
                :key="item.status"
                :href="index({ query: { status: item.status } })"
                class="rounded-xl border p-4 hover:bg-muted/50"
            >
                <p class="text-sm text-muted-foreground">
                    {{ statusLabels[item.status] }}
                </p>
                <p class="text-3xl font-semibold">{{ item.total }}</p>
            </Link>
        </section>

        <section
            v-if="admin"
            class="grid grid-cols-1 gap-3 md:grid-cols-3"
            data-test="admin-summary"
        >
            <Link
                :href="specialUnlockIndex()"
                class="rounded-xl border p-4 hover:bg-muted/50"
            >
                <p class="text-sm text-muted-foreground">
                    Permintaan Special Unlock terbuka
                </p>
                <p class="text-3xl font-semibold">
                    {{ admin.special_unlock_open }}
                </p>
            </Link>
            <Link
                :href="archiveIndex()"
                class="rounded-xl border p-4 hover:bg-muted/50"
            >
                <p class="text-sm text-muted-foreground">Belum diarsipkan</p>
                <p class="text-3xl font-semibold">
                    {{ admin.archive_pending }}
                </p>
            </Link>
            <Link
                :href="archiveIndex()"
                class="rounded-xl border p-4 hover:bg-muted/50"
                :class="{ 'border-destructive': admin.archive_overdue > 0 }"
            >
                <p class="text-sm text-muted-foreground">
                    Arsip lewat batas waktu
                </p>
                <p class="text-3xl font-semibold">
                    {{ admin.archive_overdue }}
                </p>
            </Link>
        </section>

        <AdminCharts v-if="charts" :charts="charts" />

        <section class="grid gap-2" data-test="focus">
            <div class="flex items-center justify-between gap-2">
                <h3 class="font-medium">{{ focusTitle[role] }}</h3>
                <Link
                    :href="index()"
                    class="text-sm text-muted-foreground underline underline-offset-4"
                    >Lihat semua Outreach</Link
                >
            </div>
            <div class="overflow-x-auto rounded-xl border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left">
                        <tr>
                            <th class="px-4 py-2 font-medium">Kode</th>
                            <th class="px-4 py-2 font-medium">Nama</th>
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
                                    v-if="options.status.length > 1"
                                    label="Status"
                                    param="status"
                                    :options="options.status"
                                    :selected="filters.status"
                                />
                                <span v-else class="font-medium">Status</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="focus.length === 0">
                            <td
                                colspan="5"
                                class="px-4 py-6 text-center text-muted-foreground"
                            >
                                {{
                                    filtered
                                        ? 'Tidak ada yang cocok dengan filter ini.'
                                        : 'Tidak ada.'
                                }}
                            </td>
                        </tr>
                        <tr
                            v-for="item in focus"
                            :key="item.id"
                            class="border-t"
                        >
                            <td class="px-4 py-2 font-mono">
                                <Link
                                    :href="
                                        item.can_edit
                                            ? edit(item.id)
                                            : show(item.id)
                                    "
                                    class="underline underline-offset-4"
                                    >{{ item.code }}</Link
                                >
                            </td>
                            <td class="px-4 py-2">
                                {{ item.nama ?? 'Tanpa nama' }}
                            </td>
                            <td class="px-4 py-2">{{ item.type }}</td>
                            <td class="px-4 py-2">{{ item.outreach_date }}</td>
                            <td class="px-4 py-2">
                                <Badge variant="secondary">{{
                                    statusLabels[item.status]
                                }}</Badge>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
