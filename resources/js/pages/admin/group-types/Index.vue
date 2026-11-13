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

type GroupType = {
    id: number;
    name: string;
    sort_order: number;
    is_active: boolean;
    flight_groups_count: number;
};

defineProps<{ groupTypes: GroupType[] }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Categories', href: admin.groupTypes.index() }],
    },
});

const open = ref(false);
const editingId = ref<number | null>(null);
const form = useForm({ name: '', sort_order: 0, is_active: true });

function openForm(type?: GroupType) {
    editingId.value = type?.id ?? null;
    form.defaults({
        name: type?.name ?? '',
        sort_order: type?.sort_order ?? 0,
        is_active: type?.is_active ?? true,
    });
    form.reset();
    form.clearErrors();
    open.value = true;
}

function submit() {
    form.submit(
        editingId.value
            ? admin.groupTypes.update(editingId.value)
            : admin.groupTypes.store(),
        { preserveScroll: true, onSuccess: () => (open.value = false) },
    );
}
</script>

<template>
    <Head title="Categories" />

    <div class="flex max-w-3xl flex-col gap-4 p-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">Categories</h1>
                <p class="text-sm text-muted-foreground">
                    The tabs agencies browse groups by, in this order.
                </p>
            </div>
            <Button @click="openForm()"><Plus /> Add category</Button>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead
                    class="bg-muted/50 text-left text-xs text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3 font-medium">Order</th>
                        <th class="px-4 py-3 font-medium">Category</th>
                        <th class="px-4 py-3 font-medium">Groups</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="type in groupTypes" :key="type.id">
                        <td class="px-4 py-3 tabular-nums">
                            {{ type.sort_order }}
                        </td>
                        <td class="px-4 py-3 font-medium">{{ type.name }}</td>
                        <td class="px-4 py-3 tabular-nums">
                            {{ type.flight_groups_count }}
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge
                                :status="type.is_active ? 'active' : 'closed'"
                            />
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Button
                                size="icon-sm"
                                variant="ghost"
                                aria-label="Edit category"
                                @click="openForm(type)"
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
                        editingId ? 'Edit category' : 'Add category'
                    }}</DialogTitle>
                </DialogHeader>
                <div class="grid gap-2">
                    <Label for="type-name">Name</Label>
                    <Input
                        id="type-name"
                        v-model="form.name"
                        placeholder="Umrah Groups"
                        required
                    />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="type-order">Order</Label>
                    <Input
                        id="type-order"
                        v-model="form.sort_order"
                        type="number"
                        min="0"
                    />
                    <InputError :message="form.errors.sort_order" />
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input
                        v-model="form.is_active"
                        type="checkbox"
                        class="size-4 rounded border-input"
                    />
                    Active (shown to agencies)
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
