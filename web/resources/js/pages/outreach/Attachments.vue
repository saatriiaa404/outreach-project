<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import OutreachAttachmentController from '@/actions/App/Http/Controllers/OutreachAttachmentController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import OutreachStepper from '@/components/outreach/OutreachStepper.vue';
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
import { Input } from '@/components/ui/input';
import { edit, index } from '@/routes/outreaches';

type Category = {
    value: string;
    label: string;
    baseline: boolean;
    accept: string;
};

type Attachment = {
    id: number;
    category: string;
    title: string | null;
    name: string;
    is_image: boolean;
    size_bytes: number;
    url: string;
};

const props = defineProps<{
    outreach: {
        id: number;
        code: string;
        source: string;
        type: string;
        status: string;
    };
    steps: Step[];
    step: string;
    categories: Category[];
    attachments: Attachment[];
    maxKilobytes: number;
    missingForFinalization: string[];
    additionalHint: string | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Outreach', href: index() }],
    },
});

const stepIndex = computed(() =>
    props.steps.findIndex((item) => item.key === props.step),
);
const previous = computed(() => props.steps[stepIndex.value - 1]);
const next = computed(() => {
    const item = props.steps[stepIndex.value + 1];

    return item && item.state !== 'upcoming' ? item : null;
});

const byCategory = (category: string) =>
    props.attachments.filter((item) => item.category === category);

// Per-category upload state; errors come from the browser check or the server.
const errors = reactive<Record<string, string>>({});
const progress = reactive<Record<string, number | null>>({});
const titles = reactive<Record<string, string>>({});
const replacing = ref<Attachment | null>(null);
const pendingDelete = ref<Attachment | null>(null);

const size = (bytes: number) =>
    bytes >= 1024 * 1024
        ? `${(bytes / 1024 / 1024).toFixed(1)} MB`
        : `${Math.ceil(bytes / 1024)} KB`;

const upload = (category: Category, event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = '';

    if (!file) {
        return;
    }

    // Quick feedback only; the server checks the content again (security.md).
    if (!category.accept.split(',').includes(file.type)) {
        errors[category.value] = category.baseline
            ? `${category.label} harus berupa gambar JPG, PNG, atau WEBP.`
            : 'Lampiran tambahan harus berupa gambar JPG, PNG, WEBP, atau PDF.';

        return;
    }

    if (file.size > props.maxKilobytes * 1024) {
        errors[category.value] = 'Ukuran berkas maksimal 10 MB.';

        return;
    }

    const replaces =
        replacing.value?.category === category.value
            ? replacing.value.id
            : null;

    delete errors[category.value];
    progress[category.value] = 0;

    router.post(
        OutreachAttachmentController.store.url(props.outreach.id),
        {
            category: category.value,
            file,
            title: titles[category.value] || null,
            replaces,
        },
        {
            forceFormData: true,
            preserveScroll: true,
            onProgress: (event) =>
                (progress[category.value] = event?.percentage ?? null),
            onSuccess: () => {
                titles[category.value] = '';
                replacing.value = null;
            },
            onError: (bag) =>
                (errors[category.value] =
                    bag.file ??
                    bag.category ??
                    bag.title ??
                    'Lampiran gagal disimpan.'),
            onFinish: () => (progress[category.value] = null),
        },
    );
};

const remove = () => {
    const attachment = pendingDelete.value;
    pendingDelete.value = null;

    if (attachment) {
        router.delete(
            OutreachAttachmentController.destroy.url([
                props.outreach.id,
                attachment.id,
            ]),
            { preserveScroll: true },
        );
    }
};
</script>

<template>
    <Head :title="`Lampiran ${outreach.code}`" />

    <div class="flex flex-1 flex-col gap-6 p-4 lg:flex-row">
        <OutreachStepper
            :outreach-id="outreach.id"
            :steps="steps"
            :step="step"
        />

        <div class="flex max-w-3xl flex-1 flex-col gap-6">
            <Heading
                title="Lampiran"
                :description="`${outreach.code} · ${outreach.source} · ${outreach.type} · ${outreach.status === 'PERLU_PERBAIKAN' ? 'Perlu Perbaikan' : 'Draft'}`"
            />

            <p class="text-sm text-muted-foreground">
                Empat lampiran dasar wajib berupa gambar (JPG, PNG, atau WEBP)
                sebelum Finalisasi. Draft tetap dapat disimpan walaupun lampiran
                belum lengkap. Ukuran maksimal 10 MB per berkas.
            </p>

            <div
                v-if="missingForFinalization.length"
                class="rounded-md border border-dashed p-3 text-sm"
                data-test="missing-attachments"
            >
                <p class="font-medium">
                    Belum lengkap untuk Finalisasi ({{
                        missingForFinalization.length
                    }}):
                </p>
                <p class="text-muted-foreground">
                    {{ missingForFinalization.join(', ') }}
                </p>
            </div>

            <section
                v-for="category in categories"
                :key="category.value"
                class="grid gap-3 rounded-md border p-4"
                :data-test="`category-${category.value}`"
            >
                <div
                    class="flex flex-wrap items-baseline justify-between gap-2"
                >
                    <h3 class="font-medium">
                        {{ category.label }}
                        <span v-if="category.baseline" class="text-destructive"
                            >*</span
                        >
                    </h3>
                    <span
                        v-if="category.baseline"
                        class="text-xs"
                        :class="
                            byCategory(category.value).length
                                ? 'text-muted-foreground'
                                : 'text-destructive'
                        "
                    >
                        {{
                            byCategory(category.value).length
                                ? 'Tersedia'
                                : 'Belum ada'
                        }}
                    </span>
                </div>

                <p
                    v-if="!category.baseline && additionalHint"
                    class="text-sm"
                    data-test="additional-hint"
                >
                    Disarankan untuk Jenis {{ outreach.type }}:
                    {{ additionalHint }}.
                    <span
                        class="ml-1 rounded bg-muted px-1.5 py-0.5 text-xs text-muted-foreground"
                        title="Persyaratan lampiran tambahan per Jenis belum dikonfirmasi Kelurahan"
                        >PENDING VALIDATION</span
                    >
                </p>
                <p
                    v-if="!category.baseline"
                    class="text-xs text-muted-foreground"
                >
                    Opsional dan tidak memblokir Finalisasi. Lampiran PDF
                    tersimpan dan muncul di PDF Outreach sebagai halaman rujukan
                    berisi nama berkas.
                </p>

                <ul v-if="byCategory(category.value).length" class="grid gap-3">
                    <li
                        v-for="item in byCategory(category.value)"
                        :key="item.id"
                        class="flex items-center gap-3"
                    >
                        <a
                            :href="item.url"
                            target="_blank"
                            rel="noopener"
                            class="shrink-0"
                        >
                            <img
                                v-if="item.is_image"
                                :src="item.url"
                                :alt="`${category.label}: ${item.name}`"
                                class="size-16 rounded border object-cover"
                            />
                            <span
                                v-else
                                class="flex size-16 items-center justify-center rounded border text-xs font-medium"
                                >PDF</span
                            >
                        </a>
                        <div class="min-w-0 flex-1 text-sm">
                            <p class="truncate">
                                {{ item.title || item.name }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ size(item.size_bytes) }}
                                <template v-if="!item.is_image">
                                    · tampil sebagai halaman rujukan di PDF
                                    Outreach
                                </template>
                            </p>
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="replacing = item"
                        >
                            Ganti
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="pendingDelete = item"
                        >
                            Hapus
                        </Button>
                    </li>
                </ul>

                <p
                    v-if="replacing?.category === category.value"
                    class="text-sm"
                >
                    Mengganti
                    <strong>{{ replacing.title || replacing.name }}</strong
                    >. Pilih berkas pengganti, atau
                    <button
                        type="button"
                        class="underline"
                        @click="replacing = null"
                    >
                        batal</button
                    >.
                </p>

                <Input
                    v-if="!category.baseline"
                    v-model="titles[category.value]"
                    placeholder="Keterangan (opsional), mis. Foto tambahan rumah"
                    maxlength="255"
                />

                <div class="flex flex-wrap items-center gap-2">
                    <label
                        class="inline-flex cursor-pointer items-center rounded-md border px-3 py-2 text-sm hover:bg-muted"
                    >
                        Ambil foto
                        <input
                            type="file"
                            class="sr-only"
                            accept="image/jpeg,image/png,image/webp"
                            capture="environment"
                            :disabled="progress[category.value] != null"
                            @change="upload(category, $event)"
                        />
                    </label>
                    <label
                        class="inline-flex cursor-pointer items-center rounded-md border px-3 py-2 text-sm hover:bg-muted"
                    >
                        Pilih berkas
                        <input
                            type="file"
                            class="sr-only"
                            :accept="category.accept"
                            :disabled="progress[category.value] != null"
                            :data-test="`upload-${category.value}`"
                            @change="upload(category, $event)"
                        />
                    </label>
                    <span
                        v-if="progress[category.value] != null"
                        class="text-sm text-muted-foreground"
                        role="status"
                    >
                        Mengunggah… {{ progress[category.value] }}%
                    </span>
                </div>

                <InputError :message="errors[category.value]" />
            </section>

            <div class="flex flex-wrap gap-3">
                <Button v-if="previous" variant="outline" as-child>
                    <Link
                        :href="
                            edit(outreach.id, { query: { step: previous.key } })
                        "
                    >
                        Kembali
                    </Link>
                </Button>
                <Button v-if="next" as-child>
                    <Link
                        :href="edit(outreach.id, { query: { step: next.key } })"
                    >
                        Lanjutkan
                    </Link>
                </Button>
            </div>
        </div>
    </div>

    <Dialog
        :open="pendingDelete !== null"
        @update:open="(open) => !open && (pendingDelete = null)"
    >
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Hapus lampiran?</DialogTitle>
                <DialogDescription>
                    {{ pendingDelete?.title || pendingDelete?.name }} akan
                    dilepas dari Outreach ini. Bila berkas yang sama diunggah
                    lagi, lampiran lama dipulihkan.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <Button variant="outline" @click="pendingDelete = null">
                    Batal
                </Button>
                <Button variant="destructive" @click="remove">Hapus</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
