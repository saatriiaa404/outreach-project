<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import FinalizeOutreachController from '@/actions/App/Http/Controllers/FinalizeOutreachController';
import Heading from '@/components/Heading.vue';
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
    document: OutreachDocument;
    mustahik: { header: Row[]; answers: Row[] } | null;
    kifayah: { fields: Row[]; answers: Row[] } | null;
    signatures: { title: string; signed: boolean; url: string | null }[];
}>();

const confirming = ref(false);
const processing = ref(false);
const page = usePage();
const serverError = computed(
    () =>
        (page.props.errors as Record<string, string>).finalize ??
        (page.props.errors as Record<string, string>).edit_version,
);

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
                :description="`${outreach.code} · ${outreach.source} · ${outreach.type} · ${outreach.status === 'PERLU_PERBAIKAN' ? 'Perlu Perbaikan' : 'Draft'}`"
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
                <Button
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
                <dl
                    class="grid gap-x-6 gap-y-1 rounded-md border p-3 text-sm sm:grid-cols-2"
                >
                    <div
                        v-for="row in [...mustahik.header, ...mustahik.answers]"
                        :key="row.label"
                        class="flex justify-between gap-3 border-b py-1"
                    >
                        <dt class="text-muted-foreground">{{ row.label }}</dt>
                        <dd
                            class="text-right"
                            :class="{
                                'text-destructive italic': row.value === null,
                            }"
                        >
                            {{ row.value ?? 'belum diisi' }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section
                v-if="kifayah"
                class="grid gap-2"
                data-test="kifayah-result"
            >
                <h3 class="font-medium">Hasil Had Kifayah</h3>
                <dl
                    class="grid gap-x-6 gap-y-1 rounded-md border p-3 text-sm sm:grid-cols-2"
                >
                    <div
                        v-for="row in [...kifayah.fields, ...kifayah.answers]"
                        :key="row.label"
                        class="flex justify-between gap-3 border-b py-1"
                    >
                        <dt class="text-muted-foreground">{{ row.label }}</dt>
                        <dd
                            class="text-right"
                            :class="{
                                'text-destructive italic': row.value === null,
                            }"
                        >
                            {{ row.value ?? 'belum diisi' }}
                        </dd>
                    </div>
                </dl>
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
</template>
