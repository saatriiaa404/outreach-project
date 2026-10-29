<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/ProfileController';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { getInitials } from '@/composables/useInitials';

export type Profile = {
    id: number;
    name: string;
    phone: string | null;
    address: string | null;
    avatar: string | null;
};

const props = defineProps<{ profile: Profile; maxPhotoKilobytes: number }>();
const emit = defineEmits<{ saved: [] }>();

const page = usePage();
const errors = computed(() => page.props.errors as Record<string, string>);

const form = reactive({ phone: '', address: '' });
const photo = ref<File | null>(null);
const removePhoto = ref(false);
const preview = ref<string | null>(null);
const processing = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

const reset = () => {
    form.phone = props.profile.phone ?? '';
    form.address = props.profile.address ?? '';
    photo.value = null;
    removePhoto.value = false;
    setPreview(null);
};

const setPreview = (file: File | null) => {
    if (preview.value) {
        URL.revokeObjectURL(preview.value);
    }

    preview.value = file ? URL.createObjectURL(file) : null;
};

watch(() => props.profile, reset, { immediate: true });
onBeforeUnmount(() => setPreview(null));

const shown = computed(
    () => preview.value ?? (removePhoto.value ? null : props.profile.avatar),
);

const choose = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;

    if (file) {
        photo.value = file;
        removePhoto.value = false;
        setPreview(file);
    }
};

const clearPhoto = () => {
    photo.value = null;
    removePhoto.value = true;
    setPreview(null);

    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const submit = () => {
    processing.value = true;
    router.post(
        ProfileController.update.url(props.profile.id),
        {
            phone: form.phone,
            address: form.address,
            photo: photo.value,
            remove_photo: removePhoto.value,
        },
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => emit('saved'),
            onFinish: () => (processing.value = false),
        },
    );
};
</script>

<template>
    <form class="grid gap-4" @submit.prevent="submit">
        <div class="flex flex-wrap items-center gap-4">
            <Avatar class="size-20 overflow-hidden rounded-full">
                <AvatarImage v-if="shown" :src="shown" :alt="profile.name" />
                <AvatarFallback class="text-lg">
                    {{ getInitials(profile.name) }}
                </AvatarFallback>
            </Avatar>
            <div class="grid gap-2">
                <div class="flex flex-wrap gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        data-test="profile-photo-choose"
                        @click="fileInput?.click()"
                    >
                        {{ shown ? 'Ganti foto' : 'Pilih foto' }}
                    </Button>
                    <Button
                        v-if="shown"
                        type="button"
                        variant="ghost"
                        size="sm"
                        data-test="profile-photo-remove"
                        @click="clearPhoto"
                    >
                        Hapus foto
                    </Button>
                </div>
                <p class="text-xs text-muted-foreground">
                    PNG, JPG, atau WEBP, maksimal
                    {{ maxPhotoKilobytes / 1024 }} MB.
                </p>
                <input
                    ref="fileInput"
                    type="file"
                    accept="image/png,image/jpeg,image/webp"
                    class="hidden"
                    data-test="profile-photo"
                    @change="choose"
                />
                <InputError :message="errors.photo" />
            </div>
        </div>

        <div class="grid gap-1">
            <Label :for="`profile-phone-${profile.id}`">Nomor telepon</Label>
            <Input
                :id="`profile-phone-${profile.id}`"
                v-model="form.phone"
                type="tel"
                inputmode="tel"
                maxlength="20"
                placeholder="mis. 081234567890"
            />
            <InputError :message="errors.phone" />
        </div>

        <div class="grid gap-1">
            <Label :for="`profile-address-${profile.id}`"
                >Alamat domisili</Label
            >
            <textarea
                :id="`profile-address-${profile.id}`"
                v-model="form.address"
                rows="3"
                maxlength="500"
                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
            />
            <InputError :message="errors.address" />
        </div>

        <div>
            <Button :disabled="processing" data-test="profile-submit"
                >Simpan profil</Button
            >
        </div>
    </form>
</template>
