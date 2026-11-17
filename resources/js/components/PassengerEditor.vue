<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import NativeSelect from '@/components/NativeSelect.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { passengerLabel, TITLES } from '@/lib/passengers';
import type { PassengerDraft } from '@/types/portal';

// One row per passenger: names as printed in the passport, passport details optional.
// AMS staff also see a ticket number column.
const passengers = defineModel<PassengerDraft[]>({ required: true });

defineProps<{
    errors: Partial<Record<string, string>>;
    withTickets?: boolean;
}>();

function err(
    errors: Partial<Record<string, string>>,
    i: number,
    field: string,
) {
    return errors[`passengers.${i}.${field}`];
}
</script>

<template>
    <div class="flex flex-col gap-3">
        <div
            v-for="(passenger, i) in passengers"
            :key="i"
            class="rounded-xl border bg-card p-4"
        >
            <div class="mb-3 flex items-center gap-2">
                <span
                    class="flex size-6 items-center justify-center rounded-full bg-sand text-[11px] font-bold text-gold-dark dark:bg-secondary dark:text-gold-light"
                    >{{ i + 1 }}</span
                >
                <span class="text-sm font-semibold">{{
                    passengerLabel(passengers, i)
                }}</span>
            </div>
            <div class="grid gap-3 sm:grid-cols-[6.5rem_1fr_1fr]">
                <div class="grid gap-1.5">
                    <Label :for="`title-${i}`" class="text-xs">Title</Label>
                    <NativeSelect :id="`title-${i}`" v-model="passenger.title">
                        <option
                            v-for="title in TITLES[passenger.type]"
                            :key="title"
                            :value="title"
                        >
                            {{ title }}
                        </option>
                    </NativeSelect>
                </div>
                <div class="grid gap-1.5">
                    <Label :for="`given-${i}`" class="text-xs"
                        >Given name</Label
                    >
                    <Input
                        :id="`given-${i}`"
                        v-model="passenger.given_name"
                        class="uppercase"
                        autocomplete="off"
                        placeholder="MUHAMMAD ALI"
                    />
                    <InputError :message="err(errors, i, 'given_name')" />
                </div>
                <div class="grid gap-1.5">
                    <Label :for="`surname-${i}`" class="text-xs">Surname</Label>
                    <Input
                        :id="`surname-${i}`"
                        v-model="passenger.surname"
                        class="uppercase"
                        autocomplete="off"
                        placeholder="KHAN"
                    />
                    <InputError :message="err(errors, i, 'surname')" />
                </div>
            </div>
            <div
                class="mt-3 grid gap-3 sm:grid-cols-3"
                :class="withTickets ? 'lg:grid-cols-4' : ''"
            >
                <div class="grid gap-1.5">
                    <Label :for="`passport-${i}`" class="text-xs"
                        >Passport no.
                        <span class="font-normal text-muted-foreground"
                            >(optional)</span
                        ></Label
                    >
                    <Input
                        :id="`passport-${i}`"
                        v-model="passenger.passport_number"
                        class="uppercase"
                        autocomplete="off"
                    />
                    <InputError :message="err(errors, i, 'passport_number')" />
                </div>
                <div class="grid gap-1.5">
                    <Label :for="`dob-${i}`" class="text-xs"
                        >Date of birth</Label
                    >
                    <Input
                        :id="`dob-${i}`"
                        v-model="passenger.date_of_birth"
                        type="date"
                    />
                    <InputError :message="err(errors, i, 'date_of_birth')" />
                </div>
                <div class="grid gap-1.5">
                    <Label :for="`expiry-${i}`" class="text-xs"
                        >Passport expiry</Label
                    >
                    <Input
                        :id="`expiry-${i}`"
                        v-model="passenger.passport_expiry"
                        type="date"
                    />
                    <InputError :message="err(errors, i, 'passport_expiry')" />
                </div>
                <div v-if="withTickets" class="grid gap-1.5">
                    <Label :for="`ticket-${i}`" class="text-xs"
                        >Ticket number</Label
                    >
                    <Input
                        :id="`ticket-${i}`"
                        v-model="passenger.ticket_number"
                        autocomplete="off"
                    />
                    <InputError :message="err(errors, i, 'ticket_number')" />
                </div>
            </div>
        </div>
    </div>
</template>
