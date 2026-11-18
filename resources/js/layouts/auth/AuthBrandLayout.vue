<script setup lang="ts">
import { BadgeCheck, Clock, Wallet } from '@lucide/vue';
import DeveloperCredit from '@/components/DeveloperCredit.vue';

// Sign-in and sign-up in the website's style: a Haram night photo with the logo and what the
// portal offers on one side, the form on a cream card on the other.
defineProps<{
    title?: string;
    description?: string;
    wide?: boolean;
}>();

const points = [
    { icon: BadgeCheck, text: 'Group seats AMS has bought, at agent fares' },
    { icon: Clock, text: 'Hold seats instantly, pay before the hold ends' },
    { icon: Wallet, text: 'Payments, tickets and your statement in one place' },
];
</script>

<template>
    <div class="grid min-h-dvh bg-background lg:grid-cols-[1fr_1.05fr]">
        <aside class="relative hidden overflow-hidden bg-night lg:block">
            <img
                src="/images/kaaba-night-2.webp"
                alt=""
                class="absolute inset-0 h-full w-full object-cover object-[70%_center] opacity-60"
            />
            <div
                class="absolute inset-0 bg-gradient-to-t from-night via-night/70 to-night/30"
            />
            <div
                class="relative flex h-full flex-col justify-between p-10 xl:p-14"
            >
                <div class="w-fit rounded-2xl bg-white px-4 py-3 shadow-lg">
                    <img
                        src="/images/ams-logo.png"
                        alt="AMS Global Travel & Tours"
                        class="h-12 w-auto"
                    />
                </div>
                <div class="max-w-md text-white">
                    <p
                        class="text-[11px] font-bold tracking-[0.25em] text-gold-light uppercase"
                    >
                        Agent portal
                    </p>
                    <h2
                        class="mt-3 font-display text-4xl leading-tight font-semibold xl:text-5xl"
                    >
                        Umrah &amp; group seats,
                        <span class="text-gold-gradient-light"
                            >ready to sell</span
                        >
                    </h2>
                    <span class="gold-rule mt-6 block h-0.5 w-32" />
                    <ul class="mt-6 flex flex-col gap-3 text-sm text-white/80">
                        <li
                            v-for="point in points"
                            :key="point.text"
                            class="flex items-center gap-3"
                        >
                            <span
                                class="flex size-8 items-center justify-center rounded-full bg-gold/15 text-gold-light ring-1 ring-gold/30"
                            >
                                <component :is="point.icon" class="size-4" />
                            </span>
                            {{ point.text }}
                        </li>
                    </ul>
                </div>
            </div>
        </aside>

        <main class="flex items-center justify-center px-4 py-10 sm:px-8">
            <div class="w-full" :class="wide ? 'max-w-xl' : 'max-w-sm'">
                <div class="mb-8 flex justify-center lg:hidden">
                    <div
                        class="rounded-2xl bg-white px-4 py-3 shadow-sm ring-1 ring-black/5"
                    >
                        <img
                            src="/images/ams-logo.png"
                            alt="AMS Global Travel & Tours"
                            class="h-11 w-auto"
                        />
                    </div>
                </div>
                <div class="mb-7">
                    <h1
                        v-if="title"
                        class="font-display text-3xl leading-tight font-semibold"
                    >
                        {{ title }}
                    </h1>
                    <p
                        v-if="description"
                        class="mt-2 text-sm text-muted-foreground"
                    >
                        {{ description }}
                    </p>
                </div>
                <slot />
                <p class="mt-10 text-center text-xs text-muted-foreground">
                    AMS Global Travel &amp; Tours ·
                    <a
                        href="https://amsglobal.pk"
                        class="font-semibold text-highlight hover:underline"
                        >amsglobal.pk</a
                    >
                </p>
                <DeveloperCredit class="mt-1.5 text-center" />
            </div>
        </main>
    </div>
</template>
