<script setup lang="ts">
// "Developed by" line, linking to the developer's WhatsApp, as on amsglobal.pk.
const developer = { name: 'Hassan Javaid Rao', whatsapp: '923035209409' };
</script>

<template>
    <p class="text-xs text-muted-foreground">
        Developed by
        <a
            :href="`https://wa.me/${developer.whatsapp}`"
            target="_blank"
            rel="noopener noreferrer"
            class="font-semibold text-highlight hover:underline"
            >{{ developer.name }}</a
        >
    </p>
</template>
