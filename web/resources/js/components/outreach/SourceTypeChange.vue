<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import OutreachController from '@/actions/App/Http/Controllers/OutreachController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export type SourceTypeChoices = {
    sources: { id: number; name: string; destructive: boolean }[];
    types: { id: number; name: string }[];
};

const props = defineProps<{
    outreachId: number;
    sourceId: number;
    typeId: number;
    version: number;
    choices: SourceTypeChoices;
    // Saves pending form changes first; resolves false when that failed.
    beforeSubmit: () => Promise<boolean>;
}>();

const source = ref(String(props.sourceId));
const type = ref(String(props.typeId));
const confirming = ref(false);
const processing = ref(false);
const errors = ref<Record<string, string>>({});

const changed = computed(
    () =>
        source.value !== String(props.sourceId) ||
        type.value !== String(props.typeId),
);
const destructive = computed(
    () =>
        props.choices.sources.find((item) => String(item.id) === source.value)
            ?.destructive ?? false,
);

const submit = async (confirmDestructive: boolean) => {
    if (destructive.value && !confirmDestructive) {
        confirming.value = true;

        return;
    }

    confirming.value = false;
    processing.value = true;

    if (!(await props.beforeSubmit())) {
        processing.value = false;

        return;
    }

    router.put(
        OutreachController.changeSourceType.url(props.outreachId),
        {
            source_id: source.value,
            type_id: type.value,
            edit_version: props.version,
            confirm_destructive: confirmDestructive,
        },
        {
            preserveScroll: true,
            onError: (bag) => (errors.value = bag),
            onFinish: () => (processing.value = false),
        },
    );
};
</script>

<template>
    <section class="grid gap-4 rounded-md border p-4">
        <div class="grid gap-2">
            <Label for="change_source_id">Source Outreach</Label>
            <Select v-model="source">
                <SelectTrigger id="change_source_id" class="w-full">
                    <SelectValue placeholder="Pilih Source" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="item in choices.sources"
                        :key="item.id"
                        :value="String(item.id)"
                    >
                        {{ item.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="errors.source_id" />
        </div>

        <div class="grid gap-2">
            <Label for="change_type_id">Jenis Outreach</Label>
            <Select v-model="type">
                <SelectTrigger id="change_type_id" class="w-full">
                    <SelectValue placeholder="Pilih Jenis" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="item in choices.types"
                        :key="item.id"
                        :value="String(item.id)"
                    >
                        {{ item.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="errors.type_id" />
        </div>

        <p
            v-if="changed && destructive"
            class="text-sm text-destructive"
            data-test="source-change-warning"
        >
            Source baru tidak memakai form Mustahik dan Kifayah. Data kedua form
            itu beserta tanda tangannya akan dihapus.
        </p>
        <InputError
            :message="errors.edit_version ?? errors.confirm_destructive"
        />

        <Button
            type="button"
            variant="outline"
            class="w-fit"
            :disabled="!changed || processing"
            data-test="change-source-type"
            @click="submit(false)"
        >
            Ubah Source / Jenis
        </Button>

        <Dialog v-model:open="confirming">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Hapus data Mustahik dan Kifayah?</DialogTitle>
                    <DialogDescription>
                        Source baru tidak memakai form Mustahik dan Kifayah.
                        Jawaban kedua form dan tanda tangannya akan dihapus dan
                        tidak dapat dikembalikan. Data Outreach lainnya tetap
                        tersimpan.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="confirming = false">
                        Batal
                    </Button>
                    <Button
                        variant="destructive"
                        data-test="confirm-source-change"
                        @click="submit(true)"
                    >
                        Ya, ubah Source
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </section>
</template>
