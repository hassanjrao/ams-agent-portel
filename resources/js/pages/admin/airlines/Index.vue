<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Pencil, Plus } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import admin from '@/routes/admin';

type Airline = {
    id: number;
    name: string;
    code: string;
    is_active: boolean;
    flight_groups_count: number;
};

defineProps<{ airlines: Airline[] }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Airlines', href: admin.airlines.index() }],
    },
});

const open = ref(false);
const editingId = ref<number | null>(null);
const form = useForm({ name: '', code: '', is_active: true });

function openForm(airline?: Airline) {
    editingId.value = airline?.id ?? null;
    form.defaults({
        name: airline?.name ?? '',
        code: airline?.code ?? '',
        is_active: airline?.is_active ?? true,
    });
    form.reset();
    form.clearErrors();
    open.value = true;
}

function submit() {
    form.submit(
        editingId.value
            ? admin.airlines.update(editingId.value)
            : admin.airlines.store(),
        { preserveScroll: true, onSuccess: () => (open.value = false) },
    );
}
</script>

<template>
    <Head title="Airlines" />

    <div class="flex max-w-3xl flex-col gap-4 p-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">Airlines</h1>
                <p class="text-sm text-muted-foreground">
                    Airlines you buy group seats on.
                </p>
            </div>
            <Button @click="openForm()"><Plus /> Add airline</Button>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead
                    class="bg-muted/50 text-left text-xs text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3 font-medium">Airline</th>
                        <th class="px-4 py-3 font-medium">Code</th>
                        <th class="px-4 py-3 font-medium">Groups</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="airline in airlines" :key="airline.id">
                        <td class="px-4 py-3 font-medium">
                            {{ airline.name }}
                        </td>
                        <td class="px-4 py-3 font-mono">{{ airline.code }}</td>
                        <td class="px-4 py-3 tabular-nums">
                            {{ airline.flight_groups_count }}
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge
                                :status="
                                    airline.is_active ? 'active' : 'closed'
                                "
                            />
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Button
                                size="icon-sm"
                                variant="ghost"
                                aria-label="Edit airline"
                                @click="openForm(airline)"
                                ><Pencil
                            /></Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <Dialog v-model:open="open">
        <DialogContent>
            <form class="grid gap-4" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>{{
                        editingId ? 'Edit airline' : 'Add airline'
                    }}</DialogTitle>
                </DialogHeader>
                <div class="grid gap-2">
                    <Label for="airline-name">Name</Label>
                    <Input id="airline-name" v-model="form.name" required />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="airline-code">Code (IATA)</Label>
                    <Input
                        id="airline-code"
                        v-model="form.code"
                        maxlength="3"
                        placeholder="SV"
                        class="uppercase"
                        required
                    />
                    <InputError :message="form.errors.code" />
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input
                        v-model="form.is_active"
                        type="checkbox"
                        class="size-4 rounded border-input"
                    />
                    Active (offered when creating groups)
                </label>
                <DialogFooter>
                    <Button type="submit" :disabled="form.processing"
                        >Save</Button
                    >
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
