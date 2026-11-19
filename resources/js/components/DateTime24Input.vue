<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Input } from '@/components/ui/input';

// A date picker plus a 24-hour time box ("16:35"), for flight times. The browser's own
// date-time picker shows am/pm on many systems; airline schedules are written in 24 hours.
// The value is the same "YYYY-MM-DDTHH:MM" string a datetime-local input gives, or '' while
// either half is missing or the time isn't a real one.
const model = defineModel<string>({ required: true });

defineProps<{
    id: string;
    min?: string;
}>();

const date = ref('');
const time = ref('');

function split(value: string) {
    const [d = '', t = ''] = value.split('T');
    date.value = d;
    time.value = t.slice(0, 5);
}
split(model.value);

const validTime = computed(() => /^([01]\d|2[0-3]):[0-5]\d$/.test(time.value));
// Red only once the admin has typed a full time that can't be one, like 25:10.
const badTime = computed(() => time.value.length === 5 && !validTime.value);

watch([date, time], () => {
    model.value =
        date.value && validTime.value ? `${date.value}T${time.value}` : '';
});

// Set from outside (editing a group, or the form reset): show it.
watch(model, (value) => {
    if (value && value !== `${date.value}T${time.value}`) {
        split(value);
    }
});

// Digits only, with the colon put in for you: "1635" becomes "16:35".
function onTimeInput(event: Event) {
    const digits = (event.target as HTMLInputElement).value
        .replace(/\D/g, '')
        .slice(0, 4);
    time.value =
        digits.length > 2 ? `${digits.slice(0, 2)}:${digits.slice(2)}` : digits;
    (event.target as HTMLInputElement).value = time.value;
}

// "935" or "9:35" means 09:35.
function onTimeBlur() {
    const digits = time.value.replace(/\D/g, '');
    if (digits.length === 3) {
        time.value = `0${digits[0]}:${digits.slice(1)}`;
    }
}
</script>

<template>
    <div class="flex gap-2">
        <Input
            :id="id"
            v-model="date"
            type="date"
            :min="min"
            class="min-w-0 flex-1"
        />
        <Input
            :id="`${id}-time`"
            :model-value="time"
            type="text"
            inputmode="numeric"
            placeholder="HH:MM"
            maxlength="5"
            autocomplete="off"
            :aria-label="`${id} time, 24 hours`"
            :aria-invalid="badTime || undefined"
            class="w-[4.75rem] shrink-0 text-center tabular-nums"
            @input="onTimeInput"
            @blur="onTimeBlur"
        />
    </div>
</template>
