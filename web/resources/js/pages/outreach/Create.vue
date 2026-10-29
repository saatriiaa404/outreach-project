<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import OutreachController from '@/actions/App/Http/Controllers/OutreachController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
import { create, index } from '@/routes/outreaches';

type Option = { id: number; name: string };

// Defaults to today in the browser's local date; the Petugas can change it.
const now = new Date();
const today = [
    now.getFullYear(),
    String(now.getMonth() + 1).padStart(2, '0'),
    String(now.getDate()).padStart(2, '0'),
].join('-');

defineProps<{
    sources: Option[];
    types: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Outreach', href: index() },
            { title: 'Buat Outreach', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="Buat Outreach" />

    <div class="flex max-w-xl flex-1 flex-col gap-6 p-4">
        <Heading
            title="Buat Outreach"
            description="Pilih Source, Jenis, dan Tanggal Outreach. Outreach disimpan sebagai Draft."
        />

        <Form
            v-bind="OutreachController.store.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="source_id">Source Outreach</Label>
                <Select name="source_id">
                    <SelectTrigger id="source_id" class="w-full">
                        <SelectValue placeholder="Pilih Source" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="source in sources"
                            :key="source.id"
                            :value="String(source.id)"
                        >
                            {{ source.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="errors.source_id" />
            </div>

            <div class="grid gap-2">
                <Label for="type_id">Jenis Outreach</Label>
                <Select name="type_id">
                    <SelectTrigger id="type_id" class="w-full">
                        <SelectValue placeholder="Pilih Jenis" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="type in types"
                            :key="type.id"
                            :value="String(type.id)"
                        >
                            {{ type.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="errors.type_id" />
            </div>

            <div class="grid gap-2">
                <Label for="outreach_date">Tanggal Outreach</Label>
                <Input
                    id="outreach_date"
                    name="outreach_date"
                    type="date"
                    :default-value="today"
                />
                <InputError :message="errors.outreach_date" />
            </div>

            <Button :disabled="processing" data-test="create-outreach-button">
                Simpan sebagai Draft
            </Button>
        </Form>
    </div>
</template>
