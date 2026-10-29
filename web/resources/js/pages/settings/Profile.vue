<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import ProfileForm from '@/components/ProfileForm.vue';
import type { Profile } from '@/components/ProfileForm.vue';
import { edit } from '@/routes/profile';

defineProps<{
    profile: Profile & { email: string };
    maxPhotoKilobytes: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Profil', href: edit() }],
    },
});
</script>

<template>
    <Head title="Profil" />

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Profil"
            description="Foto, nomor telepon, dan alamat domisili boleh dikosongkan. Nama dan email diubah oleh Admin."
        />

        <dl class="grid gap-1 text-sm">
            <div class="flex gap-2">
                <dt class="w-28 text-muted-foreground">Nama lengkap</dt>
                <dd class="font-medium" data-test="profile-name">
                    {{ profile.name }}
                </dd>
            </div>
            <div class="flex gap-2">
                <dt class="w-28 text-muted-foreground">Email</dt>
                <dd>{{ profile.email }}</dd>
            </div>
        </dl>

        <ProfileForm
            :profile="profile"
            :max-photo-kilobytes="maxPhotoKilobytes"
        />
    </div>
</template>
