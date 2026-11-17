<script setup lang="ts">
// The sidebar brand, as on the website footer: the full AMS logo on a white card.
// Collapsed to icons, the sidebar shows the gold "A" monogram instead.
withDefaults(defineProps<{ subtitle?: string }>(), {
    subtitle: 'Agent portal',
});
</script>

<template>
    <div
        class="flex w-full flex-col gap-2 group-data-[collapsible=icon]:hidden"
    >
        <div
            class="rounded-xl bg-white px-3 py-2 shadow-sm ring-1 ring-black/5"
        >
            <img
                src="/images/ams-logo.png"
                alt="AMS Global Travel & Tours"
                class="mx-auto h-11 w-auto"
            />
        </div>
        <div class="flex items-center gap-2 px-1">
            <span class="gold-rule h-px flex-1" />
            <span
                class="text-[10px] font-bold tracking-[0.22em] text-gold-light uppercase"
                >{{ subtitle }}</span
            >
            <span class="gold-rule h-px flex-1 rotate-180" />
        </div>
    </div>
    <img
        src="/favicon.svg"
        alt="AMS Global"
        class="hidden size-8 rounded-lg group-data-[collapsible=icon]:block"
    />
</template>
