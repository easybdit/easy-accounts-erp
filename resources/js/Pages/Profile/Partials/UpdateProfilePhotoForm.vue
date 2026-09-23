<script setup>
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import Avatar from '@/Components/Avatar.vue';

const page = usePage();
const user = page.props.auth.user;

const form = useForm({ avatar: null });
const fileInput = ref(null);
const preview = ref(null);

function pick() {
    fileInput.value?.click();
}

function onChosen(event) {
    const file = event.target.files[0];
    if (!file) return;

    form.avatar = file;
    preview.value = URL.createObjectURL(file);
}

function submit() {
    form.post(route('profile.avatar.update'), {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            preview.value = null;
            if (fileInput.value) fileInput.value.value = '';
        },
    });
}

function remove() {
    form.delete(route('profile.avatar.destroy'), {
        onSuccess: () => {
            preview.value = null;
        },
    });
}
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Profile Photo</h2>
            <p class="mt-1 text-sm text-gray-600">Shown next to your name in the top-right corner. JPG, PNG or WebP, up to 2MB.</p>
        </header>

        <div class="mt-6 flex items-center gap-4">
            <Avatar v-if="!preview" :name="user.name" :photo-url="user.profile_photo_url" size="lg" />
            <img v-else :src="preview" alt="Preview" class="h-20 w-20 shrink-0 rounded-full object-cover" />

            <div class="space-y-2">
                <input ref="fileInput" type="file" accept="image/png,image/jpeg,image/webp" class="hidden" @change="onChosen" />
                <div class="flex flex-wrap gap-2">
                    <SecondaryButton type="button" @click="pick">Choose Photo</SecondaryButton>
                    <PrimaryButton v-if="form.avatar" :loading="form.processing" @click="submit">Save Photo</PrimaryButton>
                    <SecondaryButton v-if="user.profile_photo_url" type="button" @click="remove">Remove Photo</SecondaryButton>
                </div>
                <InputError :message="form.errors.avatar" />
            </div>
        </div>
    </section>
</template>
