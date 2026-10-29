<script setup lang="ts">
import { stepStatusLabel } from '@/lib/outreach';
import { Form, Head } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import MustahikController from '@/actions/App/Http/Controllers/MustahikController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import NumberInput from '@/components/NumberInput.vue';
import OutreachStepper from '@/components/outreach/OutreachStepper.vue';
import type { Step } from '@/components/outreach/OutreachStepper.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useAutosave } from '@/composables/useAutosave';
import { index } from '@/routes/outreaches';

type HeaderField = {
    name: string;
    label: string;
    input: 'date' | 'text' | 'textarea';
    max?: number;
    digits?: number;
    value: string | null;
};

type Question = {
    code: string;
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
        status: string;
        edit_version: number;
    };
    steps: Step[];
    step: string;
    petugas: string;
    wilayah: { kelurahan: string; kecamatan: string };
    noTelp: string | null;
    klien: { nama: string | null; nik: string | null; alamat: string | null };
    header: HeaderField[];
    questions: Question[];
    missingForFinalization: string[];
    signed: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Outreach', href: index() }],
    },
});

const values = reactive<Record<string, string>>(
    Object.fromEntries([
        ...props.header.map((field) => [field.name, field.value ?? '']),
        ...props.questions.map((question) => [
            question.code,
            question.value ?? '',
        ]),
    ]),
);

const autosave = useAutosave({
    url: MustahikController.autosave.url(props.outreach.id),
    step: 'mustahik',
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

// Day name of the survey date, as printed on the source form ("Tanggal/Hari").
const dayName = computed(() => {
    const date = values.survey_date;

    return /^\d{4}-\d{2}-\d{2}$/.test(date)
        ? new Date(`${date}T00:00:00`).toLocaleDateString('id-ID', {
              weekday: 'long',
          })
        : '';
});

// Only on the Petugas' explicit request; the Klien is not assumed to be the
// Kepala Keluarga (D-036, D-048).
const copyFromKlien = () => {
    values.kepala_keluarga_nama = props.klien.nama ?? '';
    values.kepala_keluarga_nik_no_kk = props.klien.nik ?? '';
    values.kepala_keluarga_alamat = props.klien.alamat ?? '';
};

const missing = computed(() => [
    ...props.header
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
    <Head :title="`Mustahik ${outreach.code}`" />

    <div class="flex flex-1 flex-col gap-6 p-4 lg:flex-row">
        <OutreachStepper
            :outreach-id="outreach.id"
            :steps="steps"
            :step="step"
        />

        <div class="flex max-w-3xl flex-1 flex-col gap-6">
            <Heading
                title="Mustahik"
                :description="`${outreach.code} · ${outreach.source} · ${outreach.type} · ${stepStatusLabel(outreach.status)}`"
            />

            <Form
                v-bind="MustahikController.update.form(outreach.id)"
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
                    Form ini sudah ditandatangani Petugas. Mengubah isian akan
                    menghapus tanda tangan tersebut sehingga perlu
                    ditandatangani ulang di langkah Tanda Tangan.
                </p>

                <input
                    type="hidden"
                    name="edit_version"
                    :value="autosave.version.value"
                />
                <input type="hidden" name="next" :value="goNext ? 1 : 0" />

                <section class="grid gap-4 rounded-md border p-4">
                    <h3 class="font-medium">Data Survei</h3>
                    <dl class="grid gap-1 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-muted-foreground">Nama Petugas</dt>
                            <dd>{{ petugas }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">
                                Kelurahan / Kecamatan
                            </dt>
                            <dd>
                                {{ wilayah.kelurahan }} /
                                {{ wilayah.kecamatan }}
                            </dd>
                        </div>
                    </dl>

                    <div class="grid gap-2">
                        <Label for="survey_date">
                            Tanggal survei
                            <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="survey_date"
                            v-model="values.survey_date"
                            name="survey_date"
                            type="date"
                            class="w-fit"
                        />
                        <p v-if="dayName" class="text-xs text-muted-foreground">
                            Hari: {{ dayName }}
                        </p>
                        <InputError
                            :message="
                                errors.survey_date ??
                                autosave.errors.value.survey_date
                            "
                        />
                    </div>
                </section>

                <section class="grid gap-4 rounded-md border p-4">
                    <div
                        class="flex flex-wrap items-center justify-between gap-2"
                    >
                        <h3 class="font-medium">Data Kepala Keluarga</h3>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            data-test="copy-from-klien"
                            @click="copyFromKlien"
                        >
                            Salin dari data Klien
                        </Button>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Klien tidak otomatis dianggap Kepala Keluarga. Gunakan
                        tombol salin hanya bila Klien memang Kepala Keluarga;
                        tombol ini menyalin Nama, NIK, dan Alamat Domisili.
                    </p>

                    <template
                        v-for="field in header.filter(
                            (item) => item.name !== 'survey_date',
                        )"
                        :key="field.name"
                    >
                        <div class="grid gap-2">
                            <Label :for="field.name">
                                {{ field.label }}
                                <span class="text-destructive">*</span>
                            </Label>
                            <textarea
                                v-if="field.input === 'textarea'"
                                :id="field.name"
                                v-model="values[field.name]"
                                :name="field.name"
                                rows="2"
                                class="min-h-16 w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none md:text-sm"
                            />
                            <NumberInput
                                v-else-if="field.digits"
                                :id="field.name"
                                v-model="values[field.name]"
                                :name="field.name"
                                :maxlength="field.digits"
                            />
                            <Input
                                v-else
                                :id="field.name"
                                v-model="values[field.name]"
                                :name="field.name"
                                :maxlength="field.max"
                            />
                            <InputError
                                :message="
                                    errors[field.name] ??
                                    autosave.errors.value[field.name]
                                "
                            />
                        </div>
                    </template>

                    <dl class="grid gap-1 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-muted-foreground">
                                Kelurahan / Kecamatan
                            </dt>
                            <dd>
                                {{ wilayah.kelurahan }} /
                                {{ wilayah.kecamatan }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">
                                Nomor Telepon HP
                            </dt>
                            <dd>{{ noTelp || '–' }}</dd>
                            <dd class="text-xs text-muted-foreground">
                                Dari No. telp Outreach, bukan nomor Kepala
                                Keluarga yang terverifikasi.
                            </dd>
                        </div>
                    </dl>
                </section>

                <section class="grid gap-5">
                    <p class="text-sm text-muted-foreground">
                        Pilih satu jawaban untuk setiap pertanyaan. Label dan
                        opsi mengikuti formulir sumber apa adanya; bila rentang
                        tumpang tindih, pilih yang paling mendekati kondisi
                        sebenarnya. Tidak ada jawaban terpilih otomatis dan
                        tidak ada skor.
                    </p>

                    <fieldset
                        v-for="(question, i) in questions"
                        :key="question.code"
                        class="grid gap-2"
                        :data-test="`question-${question.code}`"
                    >
                        <legend class="mb-2 text-sm font-medium">
                            {{ i + 1 }}. {{ question.label }}
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
                        {{
                            outreach.status === 'DRAFT' ||
                            outreach.status === 'PERLU_PERBAIKAN'
                                ? 'Simpan Draft'
                                : 'Simpan perubahan'
                        }}
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
