<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
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
    canCreate: boolean;
};

defineProps<Props>();

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

        <p
            v-if="outreaches.data.length === 0"
            class="text-sm text-muted-foreground"
        >
            {{
                status
                    ? `Tidak ada Outreach berstatus ${statusLabels[status]}.`
                    : 'Belum ada Outreach.'
            }}
        </p>

        <div v-else class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-2 font-medium">Kode</th>
                        <th class="px-4 py-2 font-medium">Nama</th>
                        <th class="px-4 py-2 font-medium">Source</th>
                        <th class="px-4 py-2 font-medium">Jenis</th>
                        <th class="px-4 py-2 font-medium">Tanggal</th>
                        <th class="px-4 py-2 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
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
