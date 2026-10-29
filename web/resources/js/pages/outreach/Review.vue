<script setup lang="ts">
import { stepStatusLabel } from '@/lib/outreach';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import FinalizeOutreachController from '@/actions/App/Http/Controllers/FinalizeOutreachController';
import SpecialUnlockController from '@/actions/App/Http/Controllers/SpecialUnlockController';
import FormPreviewController from '@/actions/App/Http/Controllers/FormPreviewController';
import Heading from '@/components/Heading.vue';
import FormDocumentFrame from '@/components/outreach/FormDocumentFrame.vue';
import OutreachDocumentPreview from '@/components/outreach/OutreachDocumentPreview.vue';
import type { OutreachDocument } from '@/components/outreach/OutreachDocumentPreview.vue';
import OutreachStepper from '@/components/outreach/OutreachStepper.vue';
import ReturnNotice from '@/components/outreach/ReturnNotice.vue';
import type { Returned } from '@/components/outreach/ReturnNotice.vue';
import type { Step } from '@/components/outreach/OutreachStepper.vue';
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

const props = defineProps<{
    outreach: {
        id: number;
        code: string;
        source: string;
        type: string;
        status: string;
        edit_version: number;
    };
    steps: Step[];
    step: string;
    returned: Returned | null;
    checklist: { key: string; title: string; missing: string[] }[];
    ready: boolean;
    canFinalize: boolean;
    canLock: boolean;
    document: OutreachDocument;
    mustahik: { header: Row[]; answers: Row[] } | null;
    kifayah: { fields: Row[]; answers: Row[] } | null;
    signatures: { title: string; signed: boolean; url: string | null }[];
}>();

const confirming = ref(false);
const processing = ref(false);
const page = usePage();
const errors = computed(() => page.props.errors as Record<string, string>);
const serverError = computed(
    () =>
        errors.value.finalize ??
        errors.value.edit_version ??
        errors.value.special_unlock,
);

const locking = ref(false);
const submitting = ref(false);
const lockNote = ref('');

// Lock & Ajukan validates like Finalisasi and creates a new version (25.6).
const lockAndSubmit = () => {
    submitting.value = false;
    processing.value = true;
    router.post(
        SpecialUnlockController.submit.url(props.outreach.id),
        { edit_version: props.outreach.edit_version },
        { preserveScroll: true, onFinish: () => (processing.value = false) },
    );
};

// Lock hands the Outreach back to the Petugas with a note (25.5, D-056).
const lock = () => {
    if (lockNote.value.trim() === '') {
        return;
    }

    processing.value = true;
    router.post(
        SpecialUnlockController.lock.url(props.outreach.id),
        { edit_version: props.outreach.edit_version, note: lockNote.value },
        {
            preserveScroll: true,
            onSuccess: () => (locking.value = false),
            onFinish: () => (processing.value = false),
        },
    );
};

// The server checks everything again; the button only reflects this page.
const finalize = () => {
    confirming.value = false;
    processing.value = true;

    router.post(
        FinalizeOutreachController.url(props.outreach.id),
        { edit_version: props.outreach.edit_version },
        { preserveScroll: true, onFinish: () => (processing.value = false) },
    );
};

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Outreach', href: index() }],
    },
});
</script>

<template>
    <Head :title="`Review ${outreach.code}`" />

    <div class="flex flex-1 flex-col gap-6 p-4 lg:flex-row">
        <OutreachStepper
            :outreach-id="outreach.id"
            :steps="steps"
            :step="step"
        />

        <div class="flex min-w-0 flex-1 flex-col gap-6">
            <Heading
                title="Review"
                :description="`${outreach.code} · ${outreach.source} · ${outreach.type} · ${stepStatusLabel(outreach.status)}`"
            />

            <ReturnNotice v-if="returned" :returned="returned" />

            <section
                class="rounded-md border p-4"
                :class="ready ? 'border-green-600' : 'border-destructive'"
                data-test="readiness"
            >
                <p class="font-medium">
                    {{
                        ready
                            ? 'Semua syarat Finalisasi sudah terpenuhi.'
                            : 'Belum dapat difinalisasi. Lengkapi bagian bertanda di bawah.'
                    }}
                </p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Review tidak mengubah data. Setelah Finalisasi, Outreach
                    terkunci dan diajukan kepada Kepala untuk pengesahan.
                </p>
                <p
                    v-if="serverError"
                    class="mt-2 text-sm text-destructive"
                    data-test="finalize-error"
                >
                    {{ serverError }}
                </p>
                <div v-if="canLock" class="mt-3 flex flex-wrap gap-2">
                    <Button
                        :disabled="!ready || processing"
                        data-test="lock-submit"
                        @click="submitting = true"
                    >
                        Lock &amp; Ajukan untuk Pengesahan
                    </Button>
                    <Button
                        variant="outline"
                        :disabled="processing"
                        data-test="lock"
                        @click="locking = true"
                    >
                        Lock (kembalikan ke Petugas)
                    </Button>
                </div>
                <Button
                    v-if="canFinalize"
                    class="mt-3"
                    :disabled="!ready || processing"
                    data-test="finalize"
                    @click="confirming = true"
                >
                    {{
                        outreach.status === 'PERLU_PERBAIKAN'
                            ? 'Finalisasi Ulang'
                            : 'Finalisasi'
                    }}
                </Button>
            </section>

            <section class="grid gap-2" data-test="checklist">
                <h3 class="font-medium">Kelengkapan per bagian</h3>
                <ul class="divide-y rounded-md border">
                    <li
                        v-for="item in checklist"
                        :key="item.key"
                        class="flex flex-wrap items-start justify-between gap-2 p-3 text-sm"
                    >
                        <div class="min-w-0">
                            <p class="font-medium">
                                <span
                                    :class="
                                        item.missing.length
                                            ? 'text-destructive'
                                            : 'text-green-700'
                                    "
                                    >{{ item.missing.length ? '!' : '✓' }}</span
                                >
                                {{ item.title }}
                            </p>
                            <p
                                v-if="item.missing.length"
                                class="text-muted-foreground"
                            >
                                Kurang {{ item.missing.length }}:
                                {{ item.missing.join(', ') }}
                            </p>
                        </div>
                        <Link
                            v-if="item.missing.length"
                            :href="
                                edit(outreach.id, { query: { step: item.key } })
                            "
                            class="text-sm underline"
                        >
                            Lengkapi
                        </Link>
                    </li>
                </ul>
            </section>

            <section v-if="signatures.length" class="grid gap-2">
                <h3 class="font-medium">Tanda tangan</h3>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div
                        v-for="signature in signatures"
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
                        <p v-else class="mt-2 text-destructive">
                            Belum ditandatangani
                        </p>
                    </div>
                </div>
            </section>

            <section class="grid gap-2">
                <h3 class="font-medium">Preview Laporan Hasil Outreach</h3>
                <p class="text-xs text-muted-foreground">
                    Struktur sama dengan PDF; tampilan akhir PDF bisa sedikit
                    berbeda. "belum diisi" hanya muncul di preview.
                </p>
                <div class="overflow-x-auto rounded-md bg-muted p-2 sm:p-4">
                    <OutreachDocumentPreview :document="document" />
                </div>
            </section>

            <section
                v-if="mustahik"
                class="grid gap-2"
                data-test="mustahik-result"
            >
                <h3 class="font-medium">Hasil Mustahik</h3>
                <FormDocumentFrame
                    :title="'Hasil Mustahik'"
                    :src="
                        FormPreviewController.draft.url({
                            outreach: outreach.id,
                            form: 'mustahik',
                        })
                    "
                />
            </section>

            <section
                v-if="kifayah"
                class="grid gap-2"
                data-test="kifayah-result"
            >
                <h3 class="font-medium">Hasil Had Kifayah</h3>
                <FormDocumentFrame
                    :title="'Hasil Had Kifayah'"
                    :src="
                        FormPreviewController.draft.url({
                            outreach: outreach.id,
                            form: 'kifayah',
                        })
                    "
                />
            </section>
        </div>
    </div>

    <Dialog v-model:open="confirming">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Finalisasi Outreach ini?</DialogTitle>
                <DialogDescription>
                    Data, lampiran, dan tanda tangan saat ini dibekukan sebagai
                    versi baru dan diajukan kepada Kepala. Outreach tidak dapat
                    diubah lagi kecuali dikembalikan untuk perbaikan atau
                    melalui Special Unlock.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <Button variant="outline" @click="confirming = false">
                    Batal
                </Button>
                <Button data-test="confirm-finalize" @click="finalize">
                    Ya, finalisasi
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
    <Dialog v-model:open="submitting">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Lock &amp; Ajukan untuk Pengesahan?</DialogTitle>
                <DialogDescription>
                    Hasil koreksi dibekukan sebagai versi baru dan diajukan
                    kepada Kepala. Special Unlock ditutup. Admin tidak melakukan
                    pengesahan.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <Button variant="outline" @click="submitting = false">
                    Batal
                </Button>
                <Button data-test="confirm-lock-submit" @click="lockAndSubmit">
                    Ya, ajukan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="locking">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Lock dan kembalikan ke Petugas?</DialogTitle>
                <DialogDescription>
                    Status menjadi Perlu Perbaikan tanpa versi baru. Petugas
                    melanjutkan dan melakukan Finalisasi Ulang.
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-2">
                <Label for="lock-note">Catatan untuk Petugas (wajib)</Label>
                <textarea
                    id="lock-note"
                    v-model="lockNote"
                    rows="4"
                    maxlength="15000"
                    class="min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none md:text-sm"
                    placeholder="Apa yang perlu dilengkapi Petugas atau Warga"
                    data-test="lock-note"
                />
                <p v-if="errors.note" class="text-sm text-destructive">
                    {{ errors.note }}
                </p>
            </div>
            <DialogFooter class="gap-2">
                <Button variant="outline" @click="locking = false">
                    Batal
                </Button>
                <Button
                    :disabled="processing || lockNote.trim() === ''"
                    data-test="confirm-lock"
                    @click="lock"
                >
                    Lock
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
