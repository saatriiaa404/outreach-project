<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import SignatureController from '@/actions/App/Http/Controllers/SignatureController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import OutreachStepper from '@/components/outreach/OutreachStepper.vue';
import type { Step } from '@/components/outreach/OutreachStepper.vue';
import SignaturePad from '@/components/outreach/SignaturePad.vue';
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

type Party = {
    form: 'mustahik' | 'kifayah';
    title: string;
    document: string;
    signer: string | null;
    signed_at: string | null;
    url: string | null;
    formMissing: number;
};

const props = defineProps<{
    outreach: {
        id: number;
        code: string;
        source: string;
        type: string;
        edit_version: number;
    };
    steps: Step[];
    step: string;
    parties: Party[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Outreach', href: index() }],
    },
});

const errors = reactive<Record<string, string>>({});
const processing = ref<string | null>(null);
const pendingClear = ref<Party | null>(null);

const stepIndex = computed(() =>
    props.steps.findIndex((item) => item.key === props.step),
);
const previous = computed(() => props.steps[stepIndex.value - 1]);
const next = computed(() => {
    const item = props.steps[stepIndex.value + 1];

    return item && item.state !== 'upcoming' ? item : null;
});

const signedAt = (iso: string) =>
    new Date(iso).toLocaleString('id-ID', {
        dateStyle: 'long',
        timeStyle: 'short',
    });

const sign = (party: Party, image: string) => {
    processing.value = party.form;
    delete errors[party.form];

    router.post(
        SignatureController.store.url([props.outreach.id, party.form]),
        { image, edit_version: props.outreach.edit_version },
        {
            preserveScroll: true,
            onError: (bag) =>
                (errors[party.form] =
                    bag.image ??
                    bag.edit_version ??
                    'Tanda tangan gagal disimpan.'),
            onFinish: () => (processing.value = null),
        },
    );
};

const clear = () => {
    const party = pendingClear.value;
    pendingClear.value = null;

    if (!party) {
        return;
    }

    router.delete(
        SignatureController.destroy.url([props.outreach.id, party.form]),
        {
            data: { edit_version: props.outreach.edit_version },
            preserveScroll: true,
            onError: (bag) =>
                (errors[party.form] =
                    bag.edit_version ?? 'Tanda tangan gagal dihapus.'),
        },
    );
};
</script>

<template>
    <Head :title="`Tanda Tangan ${outreach.code}`" />

    <div class="flex flex-1 flex-col gap-6 p-4 lg:flex-row">
        <OutreachStepper
            :outreach-id="outreach.id"
            :steps="steps"
            :step="step"
        />

        <div class="flex max-w-3xl flex-1 flex-col gap-6">
            <Heading
                title="Tanda Tangan"
                :description="`${outreach.code} · ${outreach.source} · ${outreach.type} · Draft`"
            />

            <p class="text-sm text-muted-foreground">
                Tanda tangan dibuat langsung di perangkat untuk Outreach ini.
                Bila data pada form yang ditandatangani berubah, tanda tangan
                terkait dihapus dan perlu dibuat ulang. Perubahan lampiran saja
                tidak menghapus tanda tangan.
            </p>

            <section
                v-for="party in parties"
                :key="party.form"
                class="grid gap-3 rounded-md border p-4"
                :data-test="`party-${party.form}`"
            >
                <div
                    class="flex flex-wrap items-baseline justify-between gap-2"
                >
                    <h3 class="font-medium">
                        {{ party.title }}
                        <span class="text-destructive">*</span>
                    </h3>
                    <span class="text-xs text-muted-foreground">{{
                        party.document
                    }}</span>
                </div>
                <p class="text-sm">
                    Penanda tangan:
                    <strong>{{
                        party.signer || 'Nama Klien belum diisi'
                    }}</strong>
                </p>

                <p
                    v-if="party.formMissing > 0"
                    class="rounded-md border border-dashed p-2 text-sm text-muted-foreground"
                >
                    {{ party.document }} masih kurang
                    {{ party.formMissing }} isian. Tanda tangan yang dibuat
                    sekarang akan terhapus bila isian form diubah.
                </p>

                <template v-if="party.url">
                    <img
                        :src="party.url"
                        :alt="`${party.title} oleh ${party.signer}`"
                        class="h-32 w-full max-w-md rounded-md border bg-white object-contain"
                    />
                    <p class="text-xs text-muted-foreground">
                        Ditandatangani {{ signedAt(party.signed_at!) }}
                    </p>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        class="w-fit"
                        data-test="signature-redo"
                        @click="pendingClear = party"
                    >
                        Hapus dan tanda tangani ulang
                    </Button>
                </template>
                <SignaturePad
                    v-else
                    :label="party.title"
                    :processing="processing === party.form"
                    @save="(image) => sign(party, image)"
                />

                <InputError :message="errors[party.form]" />
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
        :open="pendingClear !== null"
        @update:open="(open) => !open && (pendingClear = null)"
    >
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Hapus tanda tangan?</DialogTitle>
                <DialogDescription>
                    {{ pendingClear?.title }} akan dihapus dan perlu dibuat
                    ulang sebelum Finalisasi.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <Button variant="outline" @click="pendingClear = null">
                    Batal
                </Button>
                <Button variant="destructive" @click="clear">Hapus</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
