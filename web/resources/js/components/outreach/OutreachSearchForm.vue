<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import OutreachSearchController from '@/actions/App/Http/Controllers/OutreachSearchController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type SearchCriteria = {
    nama?: string;
    nik?: string;
    code?: string;
    source_id?: number;
    type_id?: number;
    date_from?: string;
    date_to?: string;
};

const props = defineProps<{
    search: SearchCriteria;
    options: {
        sources: { id: number; name: string }[];
        types: { id: number; name: string }[];
    };
}>();

const active = computed(() => Object.keys(props.search).length);
const open = ref(active.value > 0);
const page = usePage();
const errors = computed(() => page.props.errors as Record<string, string>);

const form = reactive({
    nama: props.search.nama ?? '',
    nik: props.search.nik ?? '',
    code: props.search.code ?? '',
    source_id: props.search.source_id ? String(props.search.source_id) : '',
    type_id: props.search.type_id ? String(props.search.type_id) : '',
    date_from: props.search.date_from ?? '',
    date_to: props.search.date_to ?? '',
});

// Sent in the request body and kept in the session, never in the URL (D-061).
const submit = () => router.post(OutreachSearchController.store.url(), form);
const clear = () => router.delete(OutreachSearchController.destroy.url());

const select =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm';
</script>

<template>
    <section class="rounded-md border" data-test="search">
        <button
            type="button"
            class="flex w-full items-center justify-between px-4 py-2 text-left text-sm font-medium"
            :aria-expanded="open"
            @click="open = !open"
        >
            <span>
                Pencarian
                <span v-if="active" class="text-muted-foreground"
                    >· {{ active }} kriteria aktif</span
                >
            </span>
            <span aria-hidden="true">{{ open ? '−' : '+' }}</span>
        </button>
        <form
            v-if="open"
            class="grid gap-3 border-t p-4 sm:grid-cols-2 lg:grid-cols-3"
            @submit.prevent="submit"
        >
            <div class="grid gap-1">
                <Label for="search-nama">Nama</Label>
                <Input id="search-nama" v-model="form.nama" />
            </div>
            <div class="grid gap-1">
                <Label for="search-nik">NIK (sama persis)</Label>
                <Input
                    id="search-nik"
                    v-model="form.nik"
                    autocomplete="off"
                    inputmode="numeric"
                />
            </div>
            <div class="grid gap-1">
                <Label for="search-code">ID internal</Label>
                <Input
                    id="search-code"
                    v-model="form.code"
                    placeholder="OUT-… atau DRAFT-…"
                />
            </div>
            <div class="grid gap-1">
                <Label for="search-source">Source</Label>
                <select
                    id="search-source"
                    v-model="form.source_id"
                    :class="select"
                >
                    <option value="">Semua</option>
                    <option
                        v-for="source in options.sources"
                        :key="source.id"
                        :value="String(source.id)"
                    >
                        {{ source.name }}
                    </option>
                </select>
            </div>
            <div class="grid gap-1">
                <Label for="search-type">Jenis</Label>
                <select id="search-type" v-model="form.type_id" :class="select">
                    <option value="">Semua</option>
                    <option
                        v-for="type in options.types"
                        :key="type.id"
                        :value="String(type.id)"
                    >
                        {{ type.name }}
                    </option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div class="grid gap-1">
                    <Label for="search-from">Tanggal dari</Label>
                    <Input
                        id="search-from"
                        v-model="form.date_from"
                        type="date"
                    />
                </div>
                <div class="grid gap-1">
                    <Label for="search-to">sampai</Label>
                    <Input id="search-to" v-model="form.date_to" type="date" />
                </div>
                <InputError class="col-span-2" :message="errors.date_to" />
            </div>
            <div class="flex flex-wrap gap-2 sm:col-span-2 lg:col-span-3">
                <Button type="submit" data-test="search-submit">Cari</Button>
                <Button
                    v-if="active"
                    type="button"
                    variant="outline"
                    data-test="search-clear"
                    @click="clear"
                    >Hapus pencarian</Button
                >
            </div>
        </form>
    </section>
</template>
