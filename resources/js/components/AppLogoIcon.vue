<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <!-- The AMS globe mark, same file as the website's favicon. -->
    <img
        src="/images/ams-icon.png"
        alt="AMS Global"
        :class="[className, 'object-contain']"
        v-bind="$attrs"
    />
</template>
