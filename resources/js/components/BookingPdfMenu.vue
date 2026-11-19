<script setup lang="ts">
import { ChevronDown, FileDown } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import bookings from '@/routes/bookings';

// Download the booking as a PDF, with the fares or without them (for passing on to a customer).
const props = defineProps<{ bookingId: number }>();

const options = [
    {
        price: 1,
        title: 'With price',
        text: 'Fares and total included',
    },
    {
        price: 0,
        title: 'Without price',
        text: 'Flights and passengers only',
    },
];

function href(price: number): string {
    return bookings.pdf.url(props.bookingId, { query: { price } });
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="outline"
                ><FileDown /> PDF <ChevronDown class="opacity-60"
            /></Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-56">
            <DropdownMenuLabel class="text-xs text-muted-foreground"
                >Download booking PDF</DropdownMenuLabel
            >
            <DropdownMenuItem
                v-for="option in options"
                :key="option.price"
                as-child
            >
                <a
                    :href="href(option.price)"
                    download
                    class="flex cursor-pointer flex-col items-start gap-0"
                >
                    <span class="font-semibold">{{ option.title }}</span>
                    <span class="text-xs text-muted-foreground">{{
                        option.text
                    }}</span>
                </a>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
