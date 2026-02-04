<template>
    <GuestLayout>
        <Head title="Two-Factor Authentication" />

        <div class="p-4 bg-white">
            <div class="mb-4 text-sm text-gray-600">
                Please enter the 6-digit verification code sent to your email
                address.
            </div>

            <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="p-4 bg-white">
                <div>
                    <InputLabel for="code" value="Verification Code" />

                    <TextInput
                        id="code"
                        type="text"
                        class="block w-full mt-1"
                        v-model="form.code"
                        required
                        autofocus
                        autocomplete="off"
                        maxlength="6"
                        placeholder="Enter 6-digit code"
                    />

                    <InputError class="mt-2" :message="form.errors.code" />
                </div>

                <div class="flex items-center justify-between mt-4">
                    <button
                        type="button"
                        class="text-sm text-gray-600 underline cursor-pointer hover:text-gray-900"
                        @click="resendCode"
                        :disabled="resending"
                    >
                        {{ resending ? "Sending..." : "Resend Code" }}
                    </button>

                    <div class="flex items-center space-x-4">
                        <button
                            type="button"
                            class="text-sm text-red-600 underline cursor-pointer hover:text-red-800"
                            @click="logout"
                        >
                            Logout
                        </button>

                        <PrimaryButton
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? "Verifying..." : "Verify" }}
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>

<script setup>
import { ref } from "vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import ToastService from "@/Services/ToastService";
import axios from "axios";

const props = defineProps({
    status: String,
});

const form = useForm({
    code: "",
});

const resending = ref(false);

const submit = () => {
    form.post(route("two-factor.verify"), {
        onSuccess: () => {
            ToastService.success("Successfully verified. Redirecting...");
        },
        onError: () => {
            ToastService.error("Verification failed. Please try again.");
        },
    });
};

const resendCode = async () => {
    resending.value = true;

    await axios
        .post(route("two-factor.resend"))
        .then(() => {
            ToastService.success(
                "A new verification code has been sent to your email."
            );
        })
        .catch(() => {
            ToastService.error("Failed to resend code. Please try again.");
        })
        .finally(() => {
            resending.value = false;
        });
};

const logout = () => {
    router.post(route("logout"));
};
</script>
