<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import KifayahController from '@/actions/App/Http/Controllers/KifayahController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import OutreachStepper from '@/components/outreach/OutreachStepper.vue';
import type { Step } from '@/components/outreach/OutreachStepper.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useAutosave } from '@/composables/useAutosave';
import { index } from '@/routes/outreaches';

type Field = {
    name: string;
    label: string;
    input: 'money' | 'integer';
    group: 'pendapatan' | 'pengeluaran' | 'pendidikan';
    value: number | null;
};

type Question = {
    code: string;
    section: string;
    label: string;
    options: Record<string, string>;
    value: string | null;
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
    identitas: Record<
        'nama' | 'nik' | 'alamat' | 'kelurahan' | 'kecamatan' | 'no_hp',
        string | null
    >;
    fields: Field[];
    questions: Question[];
    missingForFinalization: string[];
    signed: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Outreach', href: index() }],
    },
});

// An empty field stays empty ("not filled"); it is never turned into 0.
const values = reactive<Record<string, string>>(
    Object.fromEntries([
        ...props.fields.map((field) => [
            field.name,
            field.value === null ? '' : String(field.value),
        ]),
        ...props.questions.map((question) => [
            question.code,
            question.value ?? '',
        ]),
    ]),
);

const autosave = useAutosave({
    url: KifayahController.autosave.url(props.outreach.id),
    step: 'kifayah',
    version: props.outreach.edit_version,
    values,
});

watch(
    () => props.outreach.edit_version,
    (version) => autosave.markSaved(version),
);

const goNext = ref(false);
const stepIndex = computed(() =>
    props.steps.findIndex((item) => item.key === props.step),
);
const hasNext = computed(
    () => props.steps[stepIndex.value + 1]?.state !== 'upcoming',
);

const groups = [
    { key: 'pendapatan', title: 'Pendapatan' },
    {
        key: 'pengeluaran',
        title: 'Pengeluaran per Bulan',
        hint: 'Isi 0 bila tidak ada pengeluaran atau kategori tidak berlaku. Kolom kosong berarti belum diisi.',
    },
    { key: 'pendidikan', title: 'Pendidikan' },
];

const sections = computed(() => [
    ...new Set(props.questions.map((question) => question.section)),
]);

const identitasRows = computed(() => [
    ['Nama', props.identitas.nama],
    ['NIK', props.identitas.nik],
    ['Alamat Tempat Tinggal', props.identitas.alamat],
    ['Kelurahan', props.identitas.kelurahan],
    ['Kecamatan', props.identitas.kecamatan],
    ['No. HP', props.identitas.no_hp],
]);

const missing = computed(() => [
    ...props.fields
        .filter((field) => values[field.name] === '')
        .map((field) => field.label),
    ...props.questions
        .filter((question) => values[question.code] === '')
        .map((question) => question.label),
]);

const saveStatus = computed(() => {
    const state = autosave.state.value;

    if (autosave.isDirty.value && (state === 'IDLE' || state === 'SAVED')) {
        return 'Ada perubahan yang belum disimpan.';
    }

    return {
        IDLE: 'Belum ada perubahan.',
        SAVING: 'Menyimpan…',
        SAVED: `Tersimpan otomatis pukul ${autosave.savedAt.value?.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}.`,
        FAILED: `Gagal menyimpan. ${autosave.message.value}`,
        CONFLICT: 'Konflik: data diubah di tab atau sesi lain.',
        CONNECTION_LOST:
            'Koneksi terputus. Perubahan sejak penyimpanan terakhir belum ada di server; akan dicoba lagi.',
    }[state];
});
</script>

<template>
    <Head :title="`Kifayah ${outreach.code}`" />

    <div class="flex flex-1 flex-col gap-6 p-4 lg:flex-row">
        <OutreachStepper
            :outreach-id="outreach.id"
            :steps="steps"
            :step="step"
        />

        <div class="flex max-w-3xl flex-1 flex-col gap-6">
            <Heading
                title="Had Kifayah"
                :description="`${outreach.code} · ${outreach.source} · ${outreach.type} · Draft`"
            />

            <Form
                v-bind="KifayahController.update.form(outreach.id)"
                :options="{ preserveScroll: true }"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <p
                    class="text-sm"
                    :class="
                        ['FAILED', 'CONFLICT', 'CONNECTION_LOST'].includes(
                            autosave.state.value,
                        )
                            ? 'text-destructive'
                            : 'text-muted-foreground'
                    "
                    role="status"
                    aria-live="polite"
                    data-test="autosave-status"
                >
                    {{ saveStatus }}
                </p>

                <Alert
                    v-if="
                        autosave.state.value === 'CONFLICT' ||
                        errors.edit_version
                    "
                    variant="destructive"
                >
                    <AlertDescription class="flex flex-col gap-2">
                        {{ autosave.message.value || errors.edit_version }}
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="w-fit"
                            @click="autosave.reload()"
                        >
                            Muat ulang data terbaru
                        </Button>
                    </AlertDescription>
                </Alert>

                <p
                    v-if="signed"
                    class="rounded-md border border-dashed p-3 text-sm"
                    data-test="signed-notice"
                >
                    Form ini sudah ditandatangani Warga. Mengubah isian akan
                    menghapus tanda tangan tersebut sehingga perlu
                    ditandatangani ulang di langkah Tanda Tangan.
                </p>

                <input
                    type="hidden"
                    name="edit_version"
                    :value="autosave.version.value"
                />
                <input type="hidden" name="next" :value="goNext ? 1 : 0" />

                <section class="grid gap-3 rounded-md border p-4">
                    <h3 class="font-medium">Identitas Mustahik</h3>
                    <p class="text-xs text-muted-foreground">
                        Diambil dari data Outreach dan tidak diketik ulang di
                        sini. Ubah di langkah Data Klien bila perlu.
                    </p>
                    <dl
                        class="grid gap-2 text-sm sm:grid-cols-2"
                        data-test="identitas"
                    >
                        <div
                            v-for="[label, value] in identitasRows"
                            :key="label!"
                        >
                            <dt class="text-muted-foreground">{{ label }}</dt>
                            <dd>{{ value || '–' }}</dd>
                        </div>
                    </dl>
                </section>

                <section
                    v-for="group in groups"
                    :key="group.key"
                    class="grid gap-4 rounded-md border p-4"
                >
                    <h3 class="font-medium">{{ group.title }}</h3>
                    <p v-if="group.hint" class="text-xs text-muted-foreground">
                        {{ group.hint }}
                    </p>
                    <div
                        v-for="field in fields.filter(
                            (item) => item.group === group.key,
                        )"
                        :key="field.name"
                        class="grid gap-2"
                    >
                        <Label :for="field.name">
                            {{ field.label }}
                            <span class="text-destructive">*</span>
                        </Label>
                        <div class="flex items-center gap-2">
                            <span
                                v-if="field.input === 'money'"
                                class="text-sm text-muted-foreground"
                                >Rp</span
                            >
                            <Input
                                :id="field.name"
                                v-model="values[field.name]"
                                :name="field.name"
                                type="number"
                                min="0"
                                step="1"
                                inputmode="numeric"
                                placeholder="Belum diisi"
                            />
                        </div>
                        <InputError
                            :message="
                                errors[field.name] ??
                                autosave.errors.value[field.name]
                            "
                        />
                    </div>
                </section>

                <section
                    v-for="section in sections"
                    :key="section"
                    class="grid gap-4 rounded-md border p-4"
                >
                    <h3 class="font-medium">{{ section }}</h3>
                    <fieldset
                        v-for="question in questions.filter(
                            (item) => item.section === section,
                        )"
                        :key="question.code"
                        class="grid gap-2"
                        :data-test="`question-${question.code}`"
                    >
                        <legend class="mb-2 text-sm font-medium">
                            {{ question.label }}
                            <span class="text-destructive">*</span>
                        </legend>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <label
                                v-for="(label, option) in question.options"
                                :key="option"
                                class="flex items-center gap-2 rounded-md border px-3 py-2 text-sm has-checked:border-primary has-checked:bg-muted"
                            >
                                <input
                                    v-model="values[question.code]"
                                    type="radio"
                                    :name="question.code"
                                    :value="String(option)"
                                />
                                {{ label }}
                            </label>
                        </div>
                        <InputError
                            :message="
                                errors[question.code] ??
                                autosave.errors.value[question.code]
                            "
                        />
                    </fieldset>
                </section>

                <div
                    v-if="missing.length"
                    class="rounded-md border border-dashed p-3 text-sm"
                    data-test="missing-for-finalization"
                >
                    <p class="font-medium">
                        Belum lengkap untuk Finalisasi ({{ missing.length }}):
                    </p>
                    <p class="text-muted-foreground">
                        {{ missing.join(', ') }}
                    </p>
                </div>

                <p class="text-xs text-muted-foreground">
                    * wajib diisi sebelum Finalisasi. Draft tetap dapat disimpan
                    walaupun belum lengkap.
                </p>

                <div class="flex flex-wrap gap-3">
                    <Button
                        variant="outline"
                        :disabled="
                            processing || autosave.state.value === 'SAVING'
                        "
                        @click="goNext = false"
                    >
                        Simpan Draft
                    </Button>
                    <Button
                        v-if="hasNext"
                        :disabled="
                            processing || autosave.state.value === 'SAVING'
                        "
                        @click="goNext = true"
                    >
                        Simpan &amp; Lanjutkan
                    </Button>
                </div>
            </Form>
        </div>
    </div>
</template>
