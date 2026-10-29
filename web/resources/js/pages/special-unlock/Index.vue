<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import SpecialUnlockRequestController from '@/actions/App/Http/Controllers/SpecialUnlockRequestController';
import Heading from '@/components/Heading.vue';
import type { SpecialUnlockRequestRow } from '@/components/outreach/SpecialUnlockPanel.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { show } from '@/routes/outreaches';
import { index } from '@/routes/special-unlock-requests';

type Row = SpecialUnlockRequestRow & {
    outreach: { id: number; code: string; status: string };
};

defineProps<{
    requests: {
        data: Row[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    status: string | null;
    statuses: { value: string; label: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Special Unlock', href: index() }],
    },
});

const outreachStatus: Record<string, string> = {
    MENUNGGU_PENGESAHAN: 'Menunggu Pengesahan',
    SELESAI: 'Selesai',
    PERLU_PERBAIKAN: 'Perlu Perbaikan',
    DRAFT: 'Draft',
};

const roleLabels: Record<string, string> = {
    PETUGAS: 'Petugas',
    KEPALA: 'Kepala',
    ADMIN: 'Admin',
};

const dateTime = (value: string | null) =>
    value
        ? new Date(value).toLocaleString('id-ID', {
              dateStyle: 'medium',
              timeStyle: 'short',
          })
        : '';

const processing = ref(false);
const rejecting = ref<Row | null>(null);
const rejectReason = ref('');
const page = usePage();
const errors = computed(() => page.props.errors as Record<string, string>);

const startProcessing = (row: Row) => {
    processing.value = true;
    router.post(
        SpecialUnlockRequestController.process.url(row.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => (processing.value = false),
        },
    );
};

const reject = () => {
    if (!rejecting.value || rejectReason.value.trim() === '') {
        return;
    }

    processing.value = true;
    router.post(
        SpecialUnlockRequestController.reject.url(rejecting.value.id),
        { processed_reason: rejectReason.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                rejecting.value = null;
                rejectReason.value = '';
            },
            onFinish: () => (processing.value = false),
        },
    );
};
</script>

<template>
    <Head title="Special Unlock" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            title="Permintaan Special Unlock"
            description="Antrean administratif, permintaan terlama di atas. Permintaan tidak mengubah status Outreach."
        />

        <nav class="flex flex-wrap gap-2" data-test="status-filter">
            <Link
                v-for="item in [
                    { value: 'OPEN', label: 'Terbuka' },
                    ...statuses,
                    { value: null, label: 'Semua' },
                ]"
                :key="item.value ?? 'all'"
                :href="
                    index({
                        query: {
                            status:
                                item.value === 'OPEN'
                                    ? undefined
                                    : (item.value ?? ''),
                        },
                    })
                "
                class="rounded-full border px-3 py-1 text-sm"
                :class="
                    item.value === status
                        ? 'bg-primary text-primary-foreground'
                        : 'hover:bg-muted'
                "
                >{{ item.label }}</Link
            >
        </nav>

        <p
            v-if="errors.special_unlock"
            class="text-sm text-destructive"
            role="alert"
            data-test="special-unlock-error"
        >
            {{ errors.special_unlock }}
        </p>

        <p
            v-if="requests.data.length === 0"
            class="text-sm text-muted-foreground"
        >
            Tidak ada permintaan.
        </p>

        <ul v-else class="grid gap-3">
            <li
                v-for="row in requests.data"
                :key="row.id"
                class="grid gap-2 rounded-md border p-4 text-sm"
                data-test="request-row"
            >
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <Link
                            :href="show(row.outreach.id)"
                            class="font-mono underline underline-offset-4"
                            >{{ row.outreach.code }}</Link
                        >
                        <span class="text-muted-foreground">
                            ·
                            {{
                                outreachStatus[row.outreach.status] ??
                                row.outreach.status
                            }}</span
                        >
                    </div>
                    <Badge variant="secondary">{{ row.status_label }}</Badge>
                </div>
                <p class="text-muted-foreground">
                    {{ row.requester }} ({{ roleLabels[row.requester_role] }}) ·
                    {{ dateTime(row.created_at) }}
                </p>
                <p class="whitespace-pre-line">Alasan: {{ row.reason }}</p>
                <p v-if="row.admin" class="text-muted-foreground">
                    Admin: {{ row.admin }}
                </p>
                <p v-if="row.processed_reason" class="whitespace-pre-line">
                    Alasan penolakan: {{ row.processed_reason }}
                </p>
                <p
                    v-if="row.status === 'PROCESSING'"
                    class="text-muted-foreground"
                >
                    Selesai otomatis saat Outreach di-Lock atau di-Lock & Ajukan
                    setelah Special Unlock.
                </p>
                <div class="flex flex-wrap gap-2">
                    <Button
                        v-if="row.status === 'PENDING'"
                        size="sm"
                        :disabled="processing"
                        data-test="start-processing"
                        @click="startProcessing(row)"
                    >
                        Mulai proses
                    </Button>
                    <Button
                        v-if="row.status === 'PROCESSING'"
                        size="sm"
                        variant="outline"
                        :disabled="processing"
                        data-test="reject"
                        @click="rejecting = row"
                    >
                        Tolak
                    </Button>
                </div>
            </li>
        </ul>
    </div>

    <Dialog
        :open="rejecting !== null"
        @update:open="(open) => !open && (rejecting = null)"
    >
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Tolak permintaan?</DialogTitle>
                <DialogDescription>
                    Pengaju melihat alasan ini. Status Outreach
                    {{ rejecting?.outreach.code }} tidak berubah.
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-2">
                <Label for="reject-reason">Alasan penolakan (wajib)</Label>
                <textarea
                    id="reject-reason"
                    v-model="rejectReason"
                    rows="4"
                    maxlength="15000"
                    class="min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none md:text-sm"
                    data-test="reject-reason"
                />
                <p
                    v-if="errors.processed_reason"
                    class="text-sm text-destructive"
                >
                    {{ errors.processed_reason }}
                </p>
            </div>
            <DialogFooter class="gap-2">
                <Button variant="outline" @click="rejecting = null">
                    Batal
                </Button>
                <Button
                    :disabled="processing || rejectReason.trim() === ''"
                    data-test="confirm-reject"
                    @click="reject"
                >
                    Tolak permintaan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
