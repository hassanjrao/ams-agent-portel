<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Building2, UserRound } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Register your agency',
        description:
            'Sign up to book AMS group seats. AMS reviews every agency and emails you once you can book.',
        wide: true,
    },
});
</script>

<template>
    <Head title="Register your agency" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <fieldset class="panel grid gap-4 p-5 sm:grid-cols-2">
            <legend
                class="flex items-center gap-2 px-1 text-sm font-bold text-highlight"
            >
                <Building2 class="size-4" /> Your agency
            </legend>
            <div class="grid gap-1.5 sm:col-span-2">
                <Label for="agency_name">Agency name</Label>
                <Input
                    id="agency_name"
                    name="agency_name"
                    required
                    v-focus
                    :tabindex="1"
                    autocomplete="organization"
                    placeholder="e.g. Al-Madina Travel & Tours"
                />
                <InputError :message="errors.agency_name" />
            </div>
            <div class="grid gap-1.5">
                <Label for="city">City</Label>
                <Input
                    id="city"
                    name="city"
                    required
                    :tabindex="2"
                    autocomplete="address-level2"
                    placeholder="e.g. Multan"
                />
                <InputError :message="errors.city" />
            </div>
            <div class="grid gap-1.5">
                <Label for="phone">Phone / WhatsApp</Label>
                <Input
                    id="phone"
                    name="phone"
                    type="tel"
                    required
                    :tabindex="3"
                    autocomplete="tel"
                    placeholder="0300 1234567"
                />
                <InputError :message="errors.phone" />
            </div>
            <div class="grid gap-1.5 sm:col-span-2">
                <Label for="address"
                    >Office address
                    <span class="font-normal text-muted-foreground"
                        >(optional)</span
                    ></Label
                >
                <Input
                    id="address"
                    name="address"
                    :tabindex="4"
                    autocomplete="street-address"
                />
                <InputError :message="errors.address" />
            </div>
        </fieldset>

        <fieldset class="panel grid gap-4 p-5 sm:grid-cols-2">
            <legend
                class="flex items-center gap-2 px-1 text-sm font-bold text-highlight"
            >
                <UserRound class="size-4" /> Your login
            </legend>
            <div class="grid gap-1.5">
                <Label for="name">Your name</Label>
                <Input
                    id="name"
                    name="name"
                    required
                    :tabindex="5"
                    autocomplete="name"
                    placeholder="Full name"
                />
                <InputError :message="errors.name" />
            </div>
            <div class="grid gap-1.5">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    :tabindex="6"
                    autocomplete="email"
                    placeholder="you@agency.com"
                />
                <InputError :message="errors.email" />
            </div>
            <div class="grid gap-1.5">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="7"
                    autocomplete="new-password"
                    placeholder="Password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>
            <div class="grid gap-1.5">
                <Label for="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    :tabindex="8"
                    autocomplete="new-password"
                    placeholder="Confirm password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>
        </fieldset>

        <Button
            type="submit"
            size="lg"
            class="w-full"
            :tabindex="9"
            :disabled="processing"
            data-test="register-user-button"
        >
            <Spinner v-if="processing" />
            Register agency
        </Button>

        <div class="text-center text-sm text-muted-foreground">
            Already registered?
            <TextLink :href="login()" class="font-semibold" :tabindex="10"
                >Log in</TextLink
            >
        </div>
    </Form>
</template>
