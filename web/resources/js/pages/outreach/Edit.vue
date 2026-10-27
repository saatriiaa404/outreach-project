<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import OutreachController from '@/actions/App/Http/Controllers/OutreachController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import OutreachStepper from '@/components/outreach/OutreachStepper.vue';
import type { Step } from '@/components/outreach/OutreachStepper.vue';
import SourceTypeChange from '@/components/outreach/SourceTypeChange.vue';
import type { SourceTypeChoices } from '@/components/outreach/SourceTypeChange.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { AutosaveState } from '@/composables/useAutosave';
import { useAutosave } from '@/composables/useAutosave';
import { index } from '@/routes/outreaches';

type Field = {
    name: string;
    label: string;
    input:
        | 'text'
        | 'textarea'
        | 'integer'
        | 'money'
        | 'date'
        | 'select'
        | 'boolean';
    required: boolean;
    requiredIf?: [string, boolean | string];
    options?: Record<string, string>;
    group?: string;
    showsAge?: boolean;
    value: string | number | boolean | null;
};

type Props = {
    outreach: {
        id: number;
        code: string;
        source: string;
        type: string;
        status: string;
        edit_version: number;
        outreach_date: string;
        source_id: number;
        type_id: number;
    };
    sourceTypeChoices: SourceTypeChoices | null;
    steps: Step[];
    step: string;
    fields: Field[];
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Outreach', href: index() }],
    },
});

const values = reactive<Record<string, string>>(
    Object.fromEntries(
        props.fields.map((field) => [
            field.name,
            field.value === null
                ? ''
                : field.input === 'boolean'
                  ? field.value
                      ? '1'
                      : '0'
                  : String(field.value),
        ]),
    ),
);

const autosave = useAutosave({
    url: OutreachController.autosave.url(props.outreach.id),
    step: props.step,
    version: props.outreach.edit_version,
    values,
});

watch(
    () => props.outreach.edit_version,
    (version) => autosave.markSaved(version),
);

const time = (date: Date | null) =>
    date?.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

const saveStatus = computed(() => {
    const labels: Record<AutosaveState, string> = {
        IDLE: 'Belum ada perubahan.',
        SAVING: 'Menyimpan…',
        SAVED: `Tersimpan otomatis pukul ${time(autosave.savedAt.value)}.`,
        FAILED: `Gagal menyimpan. ${autosave.message.value}`,
        CONFLICT: 'Konflik: data diubah di tab atau sesi lain.',
        CONNECTION_LOST:
            'Koneksi terputus. Perubahan sejak penyimpanan terakhir belum ada di server; akan dicoba lagi.',
    };
    const state = autosave.state.value;

    return autosave.isDirty.value && (state === 'IDLE' || state === 'SAVED')
        ? 'Ada perubahan yang belum disimpan.'
        : labels[state];
});

const goNext = ref(false);
const stepIndex = computed(() =>
    props.steps.findIndex((step) => step.key === props.step),
);
const currentStep = computed(() => props.steps[stepIndex.value]);
const hasNext = computed(
    () => props.steps[stepIndex.value + 1]?.state !== 'upcoming',
);

// Conditional fields are shown only when their controlling answer applies.
const isVisible = (field: Field) => {
    if (!field.requiredIf) {
        return true;
    }

    const [controller, expected] = field.requiredIf;

    return expected === '>0'
        ? Number(values[controller] || 0) > 0
        : values[controller] === (expected ? '1' : '0');
};

// Derived total of a group (outreach.md 4.2, 4.3). An empty component is not
// zero, so the total stays open until every component is filled.
const total = (group: string) => {
    const parts = props.fields.filter((field) => field.group === group);
    const empty = parts.filter((field) => values[field.name] === '').length;
    const sum = parts.reduce(
        (acc, field) => acc + Number(values[field.name] || 0),
        0,
    );

    return { sum, empty };
};

// Fields of this step that Finalisasi still needs; Draft saves never do.
const missing = computed(() =>
    props.fields.filter(
        (field) =>
            (field.required || (field.requiredIf && isVisible(field))) &&
            values[field.name] === '',
    ),
);

// Age in full years on the Outreach date (D-045); empty until a valid birth date is entered.
const ageOnOutreachDate = (birthDate: string) => {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(birthDate)) {
        return '';
    }

    const [by, bm, bd] = birthDate.split('-').map(Number);
    const [oy, om, od] = props.outreach.outreach_date.split('-').map(Number);
    const age = oy - by - (om < bm || (om === bm && od < bd) ? 1 : 0);

    return age >= 0 ? `${age} tahun` : '';
};

const rupiah = (amount: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(amount);
</script>

<template>
    <Head :title="`Outreach ${outreach.code}`" />

    <div class="flex flex-1 flex-col gap-6 p-4 lg:flex-row">
        <OutreachStepper
            :outreach-id="outreach.id"
            :steps="steps"
            :step="step"
        />

        <div class="flex max-w-3xl flex-1 flex-col gap-6">
            <Heading
                :title="currentStep.title"
                :description="`${outreach.code} · ${outreach.source} · ${outreach.type} · Draft`"
            />

            <SourceTypeChange
                v-if="step === 'identitas' && sourceTypeChoices"
                :outreach-id="outreach.id"
                :source-id="outreach.source_id"
                :type-id="outreach.type_id"
                :version="autosave.version.value"
                :choices="sourceTypeChoices"
                :before-submit="autosave.save"
            />

            <Form
                v-bind="OutreachController.update.form(outreach.id)"
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
                    v-if="autosave.state.value === 'CONFLICT'"
                    variant="destructive"
                >
                    <AlertDescription class="flex flex-col gap-2">
                        {{ autosave.message.value }}
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

                <Alert v-if="errors.edit_version" variant="destructive">
                    <AlertDescription>{{
                        errors.edit_version
                    }}</AlertDescription>
                </Alert>

                <input type="hidden" name="step" :value="step" />
                <input
                    type="hidden"
                    name="edit_version"
                    :value="autosave.version.value"
                />
                <input type="hidden" name="next" :value="goNext ? 1 : 0" />

                <div
                    v-if="step === 'identitas' && !sourceTypeChoices"
                    class="grid gap-2 text-sm"
                >
                    <p>Source Outreach: {{ outreach.source }}</p>
                    <p>Jenis Outreach: {{ outreach.type }}</p>
                </div>

                <template v-for="field in fields" :key="field.name">
                    <div v-show="isVisible(field)" class="grid gap-2">
                        <Label :for="field.name">
                            {{ field.label }}
                            <span
                                v-if="field.required || field.requiredIf"
                                class="text-destructive"
                                >*</span
                            >
                        </Label>

                        <textarea
                            v-if="field.input === 'textarea'"
                            :id="field.name"
                            v-model="values[field.name]"
                            :name="field.name"
                            rows="3"
                            class="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none md:text-sm"
                        />

                        <Select
                            v-else-if="field.input === 'select'"
                            v-model="values[field.name]"
                            :name="field.name"
                        >
                            <SelectTrigger :id="field.name" class="w-full">
                                <SelectValue placeholder="Pilih" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="(label, key) in field.options"
                                    :key="key"
                                    :value="String(key)"
                                >
                                    {{ label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <div
                            v-else-if="field.input === 'boolean'"
                            :id="field.name"
                            class="flex gap-6 text-sm"
                        >
                            <label class="flex items-center gap-2">
                                <input
                                    v-model="values[field.name]"
                                    type="radio"
                                    :name="field.name"
                                    value="1"
                                />
                                Ya
                            </label>
                            <label class="flex items-center gap-2">
                                <input
                                    v-model="values[field.name]"
                                    type="radio"
                                    :name="field.name"
                                    value="0"
                                />
                                Tidak
                            </label>
                        </div>

                        <Input
                            v-else
                            :id="field.name"
                            v-model="values[field.name]"
                            :name="field.name"
                            :type="
                                field.input === 'date'
                                    ? 'date'
                                    : field.input === 'text'
                                      ? 'text'
                                      : 'number'
                            "
                            :min="field.input === 'text' ? undefined : 0"
                            :step="field.input === 'text' ? undefined : 1"
                            :inputmode="
                                field.input === 'integer' ||
                                field.input === 'money'
                                    ? 'numeric'
                                    : undefined
                            "
                        />

                        <InputError
                            :message="
                                errors[field.name] ??
                                autosave.errors.value[field.name]
                            "
                        />
                    </div>

                    <div v-if="field.showsAge" class="grid gap-2">
                        <Label for="umur">Umur</Label>
                        <Input
                            id="umur"
                            :model-value="ageOnOutreachDate(values[field.name])"
                            readonly
                            placeholder="Terisi otomatis dari Tanggal lahir"
                            class="bg-muted/50"
                        />
                        <p class="text-xs text-muted-foreground">
                            Dihitung terhadap Tanggal Outreach
                            {{ outreach.outreach_date }}.
                        </p>
                    </div>
                </template>

                <div
                    v-if="step === 'keluarga'"
                    class="grid gap-1 rounded-md bg-muted/50 p-3 text-sm"
                >
                    <p
                        v-for="group in [
                            {
                                key: 'pendapatan',
                                label: 'Total Pendapatan Keluarga',
                            },
                            {
                                key: 'pengeluaran',
                                label: 'Total Pengeluaran Keluarga',
                            },
                        ]"
                        :key="group.key"
                    >
                        {{ group.label }}:
                        <strong v-if="total(group.key).empty === 0">{{
                            rupiah(total(group.key).sum)
                        }}</strong>
                        <span v-else class="text-muted-foreground">
                            belum dapat dihitung,
                            {{ total(group.key).empty }} komponen belum diisi
                            (isi 0 bila memang tidak ada)
                        </span>
                    </p>
                </div>

                <div
                    v-if="missing.length"
                    class="rounded-md border border-dashed p-3 text-sm"
                    data-test="missing-for-finalization"
                >
                    <p class="font-medium">
                        Belum lengkap untuk Finalisasi ({{ missing.length }}):
                    </p>
                    <p class="text-muted-foreground">
                        {{ missing.map((field) => field.label).join(', ') }}
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
                        data-test="save-and-continue"
                        @click="goNext = true"
                    >
                        Simpan &amp; Lanjutkan
                    </Button>
                </div>
            </Form>
        </div>
    </div>
</template>
