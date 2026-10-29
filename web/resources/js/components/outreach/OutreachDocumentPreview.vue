<script setup lang="ts">
export type DocumentRow = {
    no: number | null;
    label: string;
    value: string | null;
    total?: boolean;
};

export type OutreachDocument = {
    letterhead: string[];
    title: string;
    type: string;
    opening: string;
    sections: { title: string; rows: DocumentRow[] }[];
    narratives: { title: string; value: string | null }[];
    approval: { lines: string[] };
    attachments: {
        label: string;
        name: string;
        is_image: boolean;
        url: string;
    }[];
};

// Same logical structure as the PDF (Workflow v2.1 17.3); not pixel-perfect.
defineProps<{ document: OutreachDocument }>();
</script>

<template>
    <div class="grid gap-4" data-test="document-preview">
        <article
            class="mx-auto w-full max-w-[210mm] rounded-sm border bg-white p-6 text-[13px] leading-snug text-neutral-900 shadow-sm sm:p-10"
        >
            <header class="border-b-2 border-neutral-900 pb-3 text-center">
                <p
                    v-for="(line, i) in document.letterhead"
                    :key="line"
                    :class="
                        i === 0
                            ? 'text-sm'
                            : i < 3
                              ? 'text-base font-bold'
                              : 'text-xs'
                    "
                >
                    {{ line }}
                </p>
            </header>

            <h2 class="mt-5 text-center text-base font-bold underline">
                {{ document.title }}
            </h2>
            <p class="text-center font-semibold" data-test="document-type">
                {{ document.type }}
            </p>

            <p class="mt-5">{{ document.opening }}</p>

            <section
                v-for="section in document.sections"
                :key="section.title"
                class="mt-5"
            >
                <h3 class="mb-1 pl-8">{{ section.title }}</h3>
                <table class="w-full border-collapse border border-neutral-900">
                    <tbody>
                        <tr
                            v-for="row in section.rows"
                            :key="row.label"
                            :class="{ 'font-bold': row.total }"
                        >
                            <td
                                class="w-8 border border-neutral-900 px-1 align-top"
                            >
                                {{ row.no }}
                            </td>
                            <td
                                class="w-[38%] border border-neutral-900 px-1 align-top"
                            >
                                {{ row.label }}
                            </td>
                            <td
                                class="w-3 border border-neutral-900 text-center align-top"
                            >
                                :
                            </td>
                            <td
                                class="border border-neutral-900 px-1 align-top whitespace-pre-line"
                            >
                                <template v-if="row.value !== null">{{
                                    row.value
                                }}</template>
                                <span
                                    v-else
                                    class="text-xs text-red-700 italic"
                                    data-test="unfilled"
                                    >belum diisi</span
                                >
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section
                v-for="narrative in document.narratives"
                :key="narrative.title"
                class="mt-6 pl-8"
            >
                <h3>{{ narrative.title }} :</h3>
                <p
                    v-if="narrative.value !== null"
                    class="mt-1 whitespace-pre-line"
                >
                    {{ narrative.value }}
                </p>
                <p v-else class="mt-1 text-xs text-red-700 italic">
                    belum diisi
                </p>
            </section>

            <div class="mt-10 ml-auto w-60 text-center">
                <p v-for="line in document.approval.lines" :key="line">
                    {{ line }}
                </p>
                <p class="mt-10 text-xs text-neutral-500 italic">
                    Tanda tangan, nama, dan NIP Kepala diisi saat pengesahan.
                </p>
            </div>
        </article>

        <article
            v-for="attachment in document.attachments"
            :key="attachment.url"
            class="mx-auto w-full max-w-[210mm] rounded-sm border bg-white p-6 text-[13px] text-neutral-900 shadow-sm sm:p-10"
        >
            <h3 class="mb-3 font-bold">Lampiran: {{ attachment.label }}</h3>
            <img
                v-if="attachment.is_image"
                :src="attachment.url"
                :alt="attachment.label"
                class="mx-auto max-h-[60vh] object-contain"
            />
            <p v-else data-test="pdf-reference">
                Lampiran berupa berkas PDF:
                <strong>{{ attachment.name }}</strong
                >. Berkas tersimpan sebagai lampiran dan tidak dicetak ulang di
                dokumen ini.
            </p>
        </article>
    </div>
</template>
