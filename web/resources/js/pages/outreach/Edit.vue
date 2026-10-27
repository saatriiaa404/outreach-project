<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import OutreachController from '@/actions/App/Http/Controllers/OutreachController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
import { edit, index } from '@/routes/outreaches';

type StepState = 'complete' | 'incomplete' | 'untouched' | 'upcoming';

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
    };
    steps: { key: string; title: string; state: StepState }[];
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

const sum = (group: string) =>
    props.fields
        .filter((field) => field.group === group)
        .reduce((total, field) => total + Number(values[field.name] || 0), 0);

const rupiah = (amount: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(amount);

const stateMarker: Record<StepState, string> = {
    complete: '✓',
    incomplete: '!',
    untouched: '○',
    upcoming: '–',
};

const stateLabel: Record<StepState, string> = {
    complete: 'selesai',
    incomplete: 'belum lengkap',
    untouched: 'belum disentuh',
    upcoming: 'belum tersedia',
};
</script>

<template>
    <Head :title="`Outreach ${outreach.code}`" />

    <div class="flex flex-1 flex-col gap-6 p-4 lg:flex-row">
        <nav class="lg:w-60 lg:shrink-0" aria-label="Langkah pengisian">
            <p class="text-sm text-muted-foreground lg:hidden">
                Langkah {{ stepIndex + 1 }} dari {{ steps.length }}:
                <span class="font-medium text-foreground">{{
                    currentStep.title
                }}</span>
            </p>
            <ol class="hidden space-y-1 lg:block">
                <li v-for="(item, i) in steps" :key="item.key">
                    <span
                        v-if="item.state === 'upcoming'"
                        class="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-muted-foreground"
                        :title="stateLabel[item.state]"
                    >
                        <span class="w-4 text-center">–</span>
                        {{ i + 1 }}. {{ item.title }}
                    </span>
                    <Link
                        v-else
                        :href="edit(outreach.id, { query: { step: item.key } })"
                        class="flex items-center gap-2 rounded-md px-3 py-2 text-sm hover:bg-muted"
                        :class="{ 'bg-muted font-medium': item.key === step }"
                        :title="stateLabel[item.state]"
                    >
                        <span class="w-4 text-center">{{
                            item.key === step ? '●' : stateMarker[item.state]
                        }}</span>
                        {{ i + 1 }}. {{ item.title }}
                    </Link>
                </li>
            </ol>
        </nav>

        <div class="flex max-w-3xl flex-1 flex-col gap-6">
            <Heading
                :title="currentStep.title"
                :description="`${outreach.code} · ${outreach.source} · ${outreach.type} · Draft`"
            />

            <Form
                v-bind="OutreachController.update.form(outreach.id)"
                :options="{ preserveScroll: true }"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <Alert v-if="errors.edit_version" variant="destructive">
                    <AlertDescription>{{
                        errors.edit_version
                    }}</AlertDescription>
                </Alert>

                <input type="hidden" name="step" :value="step" />
                <input
                    type="hidden"
                    name="edit_version"
                    :value="outreach.edit_version"
                />
                <input type="hidden" name="next" :value="goNext ? 1 : 0" />

                <div v-if="step === 'identitas'" class="grid gap-2 text-sm">
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

                        <InputError :message="errors[field.name]" />
                    </div>
                </template>

                <div
                    v-if="step === 'keluarga'"
                    class="grid gap-1 rounded-md bg-muted/50 p-3 text-sm"
                >
                    <p>
                        Total Pendapatan Keluarga:
                        <strong>{{ rupiah(sum('pendapatan')) }}</strong>
                    </p>
                    <p>
                        Total Pengeluaran Keluarga:
                        <strong>{{ rupiah(sum('pengeluaran')) }}</strong>
                    </p>
                </div>

                <p class="text-xs text-muted-foreground">
                    * wajib diisi sebelum Finalisasi. Draft tetap dapat disimpan
                    walaupun belum lengkap.
                </p>

                <div class="flex flex-wrap gap-3">
                    <Button
                        variant="outline"
                        :disabled="processing"
                        @click="goNext = false"
                    >
                        Simpan Draft
                    </Button>
                    <Button
                        v-if="hasNext"
                        :disabled="processing"
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
