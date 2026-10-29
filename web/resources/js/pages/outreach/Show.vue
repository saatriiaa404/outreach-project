<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ApproveOutreachController from '@/actions/App/Http/Controllers/ApproveOutreachController';
import ReturnOutreachController from '@/actions/App/Http/Controllers/ReturnOutreachController';
import Heading from '@/components/Heading.vue';
import SpecialUnlockPanel from '@/components/outreach/SpecialUnlockPanel.vue';
import type { SpecialUnlock } from '@/components/outreach/SpecialUnlockPanel.vue';
import OutreachDocumentPreview from '@/components/outreach/OutreachDocumentPreview.vue';
import type { OutreachDocument } from '@/components/outreach/OutreachDocumentPreview.vue';
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
import { edit, index } from '@/routes/outreaches';

type Row = { label: string; value: string | null };
type Status = 'DRAFT' | 'MENUNGGU_PENGESAHAN' | 'PERLU_PERBAIKAN' | 'SELESAI';

const props = defineProps<{
    outreach: {
        id: number;
        code: string;
        source: string;
        type: string;
        status: Status;
        petugas: string;
        nama: string | null;
    };
    history: {
        id: number;
        type:
            | 'FINALIZED'
            | 'REFINALIZED'
            | 'RETURNED'
            | 'APPROVED'
            | 'SPECIAL_UNLOCKED'
            | 'SPECIAL_UNLOCK_LOCKED'
            | 'SPECIAL_UNLOCK_SUBMITTED';
        actor: string;
        at: string | null;
        description: string | null;
        reason: string | null;
    }[];
    version: {
        id: number;
        number: number;
        status: 'PENDING_APPROVAL' | 'RETURNED' | 'APPROVED';
        finalized_at: string;
        document: OutreachDocument;
        mustahik: { header: Row[]; answers: Row[] } | null;
        kifayah: { fields: Row[]; answers: Row[] } | null;
        signatures: { title: string; url: string | null }[];
    } | null;
    canReturn: boolean;
    canApprove: boolean;
    canEdit: boolean;
    downloads: { title: string; url: string }[];
    specialUnlock: SpecialUnlock;
}>();

const statusLabels: Record<Status, string> = {
    DRAFT: 'Draft',
    MENUNGGU_PENGESAHAN: 'Menunggu Pengesahan',
    PERLU_PERBAIKAN: 'Perlu Perbaikan',
    SELESAI: 'Selesai',
};

const eventLabels: Record<(typeof props.history)[number]['type'], string> = {
    FINALIZED: 'Finalisasi',
    REFINALIZED: 'Finalisasi Ulang',
    RETURNED: 'Dikembalikan',
    APPROVED: 'Disetujui',
    SPECIAL_UNLOCKED: 'Special Unlock dibuka',
    SPECIAL_UNLOCK_LOCKED: 'Lock setelah Special Unlock',
    SPECIAL_UNLOCK_SUBMITTED: 'Lock & Ajukan',
};

const versionLabels = {
    PENDING_APPROVAL: 'menunggu pengesahan',
    RETURNED: 'dikembalikan',
    APPROVED: 'disetujui',
};

const dateTime = (value: string | null) =>
    value
        ? new Date(value).toLocaleString('id-ID', {
              dateStyle: 'long',
              timeStyle: 'short',
          })
        : '';

const returning = ref(false);
const note = ref('');
const processing = ref(false);
const page = usePage();
const errors = computed(() => page.props.errors as Record<string, string>);

// The server checks the note, the status, and the version again.
const submitReturn = () => {
    if (!props.version || note.value.trim() === '') {
        return;
    }

    processing.value = true;
    router.post(
        ReturnOutreachController.url(props.outreach.id),
        { outreach_version_id: props.version.id, note: note.value },
        {
            preserveScroll: true,
            onSuccess: () => (returning.value = false),
            onFinish: () => (processing.value = false),
        },
    );
};

const approving = ref(false);

// The server renders every required PDF before the approval is kept.
const submitApprove = () => {
    if (!props.version) {
        return;
    }

    approving.value = false;
    processing.value = true;
    router.post(
        ApproveOutreachController.url(props.outreach.id),
        { outreach_version_id: props.version.id },
        {
            preserveScroll: true,
            onFinish: () => (processing.value = false),
        },
    );
};

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Outreach', href: index() }],
    },
});
</script>

<template>
    <Head :title="`Tinjau ${outreach.code}`" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                :title="outreach.nama ?? outreach.code"
                :description="`${outreach.code} · ${outreach.source} · ${outreach.type} · Petugas ${outreach.petugas}`"
            />
            <Badge variant="secondary" data-test="status">
                {{ statusLabels[outreach.status] }}
            </Badge>
        </div>

        <section
            v-if="(canReturn || canApprove) && specialUnlock.session"
            class="rounded-md border p-4 text-sm"
            data-test="decision-blocked"
        >
            Admin sedang melakukan Special Unlock pada Outreach ini. Keputusan
            dapat diambil setelah Admin melakukan Lock atau Lock &amp; Ajukan.
        </section>

        <section
            v-else-if="canReturn || canApprove"
            class="rounded-md border p-4"
            data-test="decision"
        >
            <p class="font-medium">Keputusan Kepala</p>
            <p class="mt-1 text-sm text-muted-foreground">
                Tinjau versi yang diajukan di bawah. Data tidak dapat diubah
                dari halaman ini. Sahkan bila sudah sesuai, atau kembalikan
                kepada Petugas dengan catatan bila perlu diperbaiki.
            </p>
            <p
                v-if="errors.approve || errors.return"
                class="mt-2 text-sm text-destructive"
                role="alert"
                data-test="decision-error"
            >
                {{ errors.approve ?? errors.return }}
            </p>
            <p
                v-if="processing && !returning"
                class="mt-2 text-sm"
                role="status"
                data-test="approve-processing"
            >
                Membuat seluruh PDF wajib…
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
                <Button
                    v-if="canApprove"
                    :disabled="processing"
                    data-test="approve"
                    @click="approving = true"
                >
                    Sahkan
                </Button>
                <Button
                    v-if="canReturn"
                    variant="outline"
                    :disabled="processing"
                    data-test="return"
                    @click="returning = true"
                >
                    Kembalikan untuk perbaikan
                </Button>
            </div>
        </section>

        <section v-else-if="canEdit" class="rounded-md border p-4 text-sm">
            Outreach ini dapat Anda ubah.
            <Link :href="edit(outreach.id)" class="underline">
                Buka pengisian
            </Link>
        </section>

        <section
            v-if="downloads.length"
            class="rounded-md border p-4"
            data-test="downloads"
        >
            <p class="font-medium">PDF final</p>
            <p class="mt-1 text-sm text-muted-foreground">
                Dibuat ulang dari versi {{ version?.number }} yang disahkan
                setiap kali diunduh.
            </p>
            <p
                v-if="errors.pdf"
                class="mt-2 text-sm text-destructive"
                role="alert"
                data-test="pdf-error"
            >
                {{ errors.pdf }}
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
                <Button
                    v-for="download in downloads"
                    :key="download.url"
                    variant="outline"
                    as-child
                >
                    <a :href="download.url" data-test="download">{{
                        download.title
                    }}</a>
                </Button>
            </div>
        </section>

        <SpecialUnlockPanel
            :outreach-id="outreach.id"
            :special-unlock="specialUnlock"
        />

        <section class="grid gap-2" data-test="history">
            <h3 class="font-medium">Riwayat pengajuan</h3>
            <p
                v-if="history.length === 0"
                class="text-sm text-muted-foreground"
            >
                Belum pernah diajukan.
            </p>
            <ol v-else class="divide-y rounded-md border text-sm">
                <li v-for="event in history" :key="event.id" class="p-3">
                    <p>
                        <span class="font-medium">{{
                            eventLabels[event.type]
                        }}</span>
                        · {{ event.actor }} · {{ dateTime(event.at) }}
                    </p>
                    <p v-if="event.description" class="text-muted-foreground">
                        {{ event.description }}
                    </p>
                    <p
                        v-if="event.reason"
                        class="mt-1 whitespace-pre-line"
                        data-test="return-note"
                    >
                        Catatan: {{ event.reason }}
                    </p>
                </li>
            </ol>
        </section>

        <p
            v-if="!version && outreach.status === 'PERLU_PERBAIKAN'"
            class="text-sm text-muted-foreground"
            data-test="summary-only"
        >
            Outreach sedang diperbaiki oleh Petugas. Isinya baru dapat ditinjau
            setelah diajukan kembali.
        </p>

        <template v-if="version">
            <p class="text-sm text-muted-foreground" data-test="version">
                Menampilkan versi {{ version.number }} ({{
                    versionLabels[version.status]
                }}) yang difinalisasi {{ dateTime(version.finalized_at) }}. Isi
                versi tidak berubah walaupun data dikoreksi kemudian.
            </p>

            <section v-if="version.signatures.length" class="grid gap-2">
                <h3 class="font-medium">Tanda tangan</h3>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div
                        v-for="signature in version.signatures"
                        :key="signature.title"
                        class="rounded-md border p-3 text-sm"
                    >
                        <p>{{ signature.title }}</p>
                        <img
                            v-if="signature.url"
                            :src="signature.url"
                            :alt="signature.title"
                            class="mt-2 h-20 w-full rounded bg-white object-contain"
                        />
                    </div>
                </div>
            </section>

            <section class="grid gap-2">
                <h3 class="font-medium">Laporan Hasil Outreach</h3>
                <div class="overflow-x-auto rounded-md bg-muted p-2 sm:p-4">
                    <OutreachDocumentPreview :document="version.document" />
                </div>
            </section>

            <section
                v-if="version.mustahik"
                class="grid gap-2"
                data-test="mustahik-result"
            >
                <h3 class="font-medium">Hasil Mustahik</h3>
                <dl
                    class="grid gap-x-6 gap-y-1 rounded-md border p-3 text-sm sm:grid-cols-2"
                >
                    <div
                        v-for="row in [
                            ...version.mustahik.header,
                            ...version.mustahik.answers,
                        ]"
                        :key="row.label"
                        class="flex justify-between gap-3 border-b py-1"
                    >
                        <dt class="text-muted-foreground">{{ row.label }}</dt>
                        <dd class="text-right">{{ row.value ?? '—' }}</dd>
                    </div>
                </dl>
            </section>

            <section
                v-if="version.kifayah"
                class="grid gap-2"
                data-test="kifayah-result"
            >
                <h3 class="font-medium">Hasil Had Kifayah</h3>
                <dl
                    class="grid gap-x-6 gap-y-1 rounded-md border p-3 text-sm sm:grid-cols-2"
                >
                    <div
                        v-for="row in [
                            ...version.kifayah.fields,
                            ...version.kifayah.answers,
                        ]"
                        :key="row.label"
                        class="flex justify-between gap-3 border-b py-1"
                    >
                        <dt class="text-muted-foreground">{{ row.label }}</dt>
                        <dd class="text-right">{{ row.value ?? '—' }}</dd>
                    </div>
                </dl>
            </section>
        </template>
    </div>

    <Dialog v-model:open="returning">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Kembalikan untuk perbaikan?</DialogTitle>
                <DialogDescription>
                    Outreach kembali kepada Petugas {{ outreach.petugas }}
                    dengan status Perlu Perbaikan. Versi
                    {{ version?.number }} tetap tersimpan apa adanya.
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-2">
                <Label for="return-note">Catatan Return (wajib)</Label>
                <textarea
                    id="return-note"
                    v-model="note"
                    rows="4"
                    maxlength="15000"
                    class="min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none md:text-sm"
                    placeholder="Jelaskan apa yang perlu diperbaiki Petugas"
                    data-test="return-note-input"
                />
                <p
                    v-if="errors.note"
                    class="text-sm text-destructive"
                    data-test="note-error"
                >
                    {{ errors.note }}
                </p>
            </div>
            <DialogFooter class="gap-2">
                <Button variant="outline" @click="returning = false">
                    Batal
                </Button>
                <Button
                    :disabled="processing || note.trim() === ''"
                    data-test="confirm-return"
                    @click="submitReturn"
                >
                    Kembalikan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
    <Dialog v-model:open="approving">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Sahkan Outreach ini?</DialogTitle>
                <DialogDescription>
                    Versi {{ version?.number }} disahkan atas nama Anda dengan
                    template tanda tangan Kepala dan tanggal hari ini. Sistem
                    membuat seluruh PDF wajib lebih dulu; bila satu saja gagal,
                    pengesahan dibatalkan dan status tetap Menunggu Pengesahan.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <Button variant="outline" @click="approving = false">
                    Batal
                </Button>
                <Button data-test="confirm-approve" @click="submitApprove">
                    Ya, sahkan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
