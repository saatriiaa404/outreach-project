<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import ArchiveController from '@/actions/App/Http/Controllers/ArchiveController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
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
import { Label } from '@/components/ui/label';
import { index } from '@/routes/archive';
import { show } from '@/routes/outreaches';

type Row = {
    outreach_id: number;
    code: string;
    nama: string;
    type: string;
    version_id: number;
    version_number: number;
    approved_at: string;
    deadline: string;
    overdue: boolean;
    record: {
        id: number;
        external_system: string;
        archive_reference: string | null;
        archived_at: string | null;
        verified_at: string | null;
    } | null;
};

defineProps<{
    rows: Row[];
    filter: 'pending' | 'archived' | 'all';
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Arsip', href: index() }],
    },
});

const page = usePage();
const errors = computed(() => page.props.errors as Record<string, string>);
const processing = ref(false);
const editing = ref<Row | null>(null);
const form = reactive({
    external_system: '',
    archive_reference: '',
    archived_at: '',
});

const today = () => new Date().toISOString().slice(0, 10);
const date = (value: string | null) =>
    value
        ? new Date(value).toLocaleDateString('id-ID', { dateStyle: 'long' })
        : '';

const open = (row: Row) => {
    editing.value = row;
    form.external_system = row.record?.external_system ?? '';
    form.archive_reference = row.record?.archive_reference ?? '';
    form.archived_at = row.record?.archived_at ?? today();
};

const submit = () => {
    const row = editing.value;

    if (!row) {
        return;
    }

    processing.value = true;
    const options = {
        preserveScroll: true,
        onSuccess: () => (editing.value = null),
        onFinish: () => (processing.value = false),
    };

    if (row.record) {
        router.put(ArchiveController.update.url(row.record.id), form, options);
    } else {
        router.post(
            ArchiveController.store.url(row.outreach_id),
            { ...form, outreach_version_id: row.version_id },
            options,
        );
    }
};

const verify = (row: Row) => {
    processing.value = true;
    router.post(
        ArchiveController.verify.url(row.record!.id),
        {},
        { preserveScroll: true, onFinish: () => (processing.value = false) },
    );
};
</script>

<template>
    <Head title="Arsip" />

    <div class="flex max-w-5xl flex-1 flex-col gap-6 p-4">
        <Heading
            title="Penandaan arsip"
            description="Unduh PDF final, unggah manual ke E-Surat/E-Arsip, lalu tandai di sini paling lambat satu bulan sejak pengesahan. Data dan lampiran tetap di aplikasi; ID internal bukan nomor arsip resmi."
        />

        <nav class="flex flex-wrap gap-2" data-test="archive-filter">
            <Link
                v-for="item in [
                    { value: 'pending', label: 'Belum diarsipkan' },
                    { value: 'archived', label: 'Sudah diarsipkan' },
                    { value: 'all', label: 'Semua' },
                ]"
                :key="item.value"
                :href="index({ query: { filter: item.value } })"
                class="rounded-full border px-3 py-1 text-sm"
                :class="
                    item.value === filter
                        ? 'bg-primary text-primary-foreground'
                        : 'hover:bg-muted'
                "
                >{{ item.label }}</Link
            >
        </nav>

        <p
            v-if="errors.archive"
            class="text-sm text-destructive"
            role="alert"
            data-test="archive-error"
        >
            {{ errors.archive }}
        </p>

        <p v-if="rows.length === 0" class="text-sm text-muted-foreground">
            Tidak ada Outreach pada daftar ini.
        </p>

        <ul v-else class="grid gap-3">
            <li
                v-for="row in rows"
                :key="row.version_id"
                class="grid gap-2 rounded-md border p-4 text-sm"
                data-test="archive-row"
            >
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <Link
                            :href="show(row.outreach_id)"
                            class="font-mono underline underline-offset-4"
                            >{{ row.code }}</Link
                        >
                        · {{ row.nama }} · {{ row.type }} · versi
                        {{ row.version_number }}
                    </div>
                    <Badge
                        v-if="row.record"
                        :variant="
                            row.record.verified_at ? 'default' : 'secondary'
                        "
                    >
                        {{
                            row.record.verified_at
                                ? 'Diarsipkan, terverifikasi'
                                : 'Diarsipkan'
                        }}
                    </Badge>
                    <Badge v-else-if="row.overdue" variant="destructive"
                        >Lewat batas waktu</Badge
                    >
                    <Badge v-else variant="secondary">Belum diarsipkan</Badge>
                </div>
                <p class="text-muted-foreground">
                    Disahkan {{ date(row.approved_at) }} · batas arsip
                    {{ date(row.deadline) }}
                </p>
                <p v-if="row.record">
                    {{ row.record.external_system }}
                    <template v-if="row.record.archive_reference">
                        · referensi {{ row.record.archive_reference }}</template
                    >
                    · diarsipkan {{ date(row.record.archived_at) }}
                    <template v-if="row.record.verified_at">
                        · diverifikasi {{ date(row.record.verified_at) }}
                    </template>
                </p>
                <div class="flex flex-wrap gap-2">
                    <Button
                        size="sm"
                        :variant="row.record ? 'outline' : 'default'"
                        :disabled="processing"
                        data-test="archive-open"
                        @click="open(row)"
                    >
                        {{ row.record ? 'Koreksi' : 'Tandai diarsipkan' }}
                    </Button>
                    <Button
                        v-if="row.record && !row.record.verified_at"
                        size="sm"
                        variant="outline"
                        :disabled="processing"
                        data-test="archive-verify"
                        @click="verify(row)"
                    >
                        Tandai terverifikasi
                    </Button>
                </div>
            </li>
        </ul>
    </div>

    <Dialog
        :open="editing !== null"
        @update:open="(value) => !value && (editing = null)"
    >
        <DialogContent>
            <DialogHeader>
                <DialogTitle>
                    {{
                        editing?.record
                            ? 'Koreksi data arsip'
                            : 'Tandai sudah diarsipkan'
                    }}
                </DialogTitle>
                <DialogDescription>
                    {{ editing?.code }} versi {{ editing?.version_number }}.
                    Perubahan dicatat pada audit.
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-3">
                <div class="grid gap-1">
                    <Label for="external-system">Sistem arsip</Label>
                    <Input
                        id="external-system"
                        v-model="form.external_system"
                        maxlength="100"
                        placeholder="Nama sistem tempat PDF diunggah"
                        data-test="external-system"
                    />
                    <InputError :message="errors.external_system" />
                </div>
                <div class="grid gap-1">
                    <Label for="archive-reference"
                        >Nomor referensi (opsional)</Label
                    >
                    <Input
                        id="archive-reference"
                        v-model="form.archive_reference"
                        maxlength="255"
                    />
                    <InputError :message="errors.archive_reference" />
                </div>
                <div class="grid gap-1">
                    <Label for="archived-at">Tanggal diarsipkan</Label>
                    <Input
                        id="archived-at"
                        v-model="form.archived_at"
                        type="date"
                        :max="today()"
                    />
                    <InputError :message="errors.archived_at" />
                </div>
            </div>
            <DialogFooter class="gap-2">
                <Button variant="outline" @click="editing = null">Batal</Button>
                <Button
                    :disabled="
                        processing || !form.external_system || !form.archived_at
                    "
                    data-test="archive-submit"
                    @click="submit"
                >
                    Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
