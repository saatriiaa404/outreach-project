<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import { index as archiveIndex } from '@/routes/archive';
import { edit, index, show } from '@/routes/outreaches';
import { index as specialUnlockIndex } from '@/routes/special-unlock-requests';

type Status = 'DRAFT' | 'MENUNGGU_PENGESAHAN' | 'PERLU_PERBAIKAN' | 'SELESAI';

defineProps<{
    role: 'PETUGAS' | 'KEPALA' | 'ADMIN';
    statuses: { status: Status; total: number }[];
    focus: {
        id: number;
        code: string;
        nama: string | null;
        type: string;
        status: Status;
        can_edit: boolean;
    }[];
    admin: {
        special_unlock_open: number;
        archive_pending: number;
        archive_overdue: number;
    } | null;
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

        <section class="grid gap-2" data-test="focus">
            <h3 class="font-medium">{{ focusTitle[role] }}</h3>
            <p v-if="focus.length === 0" class="text-sm text-muted-foreground">
                Tidak ada.
            </p>
            <ul v-else class="divide-y rounded-md border text-sm">
                <li
                    v-for="item in focus"
                    :key="item.id"
                    class="flex flex-wrap items-center justify-between gap-2 p-3"
                >
                    <Link
                        :href="item.can_edit ? edit(item.id) : show(item.id)"
                        class="underline underline-offset-4"
                    >
                        <span class="font-mono">{{ item.code }}</span>
                        · {{ item.nama ?? 'Tanpa nama' }} · {{ item.type }}
                    </Link>
                    <Badge variant="secondary">{{
                        statusLabels[item.status]
                    }}</Badge>
                </li>
            </ul>
        </section>
    </div>
</template>
