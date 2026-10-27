<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import OutreachDocumentPreview from '@/components/outreach/OutreachDocumentPreview.vue';
import type { OutreachDocument } from '@/components/outreach/OutreachDocumentPreview.vue';
import OutreachStepper from '@/components/outreach/OutreachStepper.vue';
import type { Step } from '@/components/outreach/OutreachStepper.vue';
import { edit, index } from '@/routes/outreaches';

type Row = { label: string; value: string | null };

defineProps<{
    outreach: { id: number; code: string; source: string; type: string };
    steps: Step[];
    step: string;
    checklist: { key: string; title: string; missing: string[] }[];
    ready: boolean;
    document: OutreachDocument;
    mustahik: { header: Row[]; answers: Row[] } | null;
    kifayah: { fields: Row[]; answers: Row[] } | null;
    signatures: { title: string; signed: boolean; url: string | null }[];
}>();

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
                :description="`${outreach.code} · ${outreach.source} · ${outreach.type} · Draft`"
            />

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
                    Review tidak mengubah data. Tombol Finalisasi belum tersedia
                    pada tahap prototype ini.
                </p>
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
</template>
