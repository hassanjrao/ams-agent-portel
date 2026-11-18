<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Mail, Pencil, Phone, Plus } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import admin from '@/routes/admin';

type Supplier = {
    id: number;
    name: string;
    contact_person: string | null;
    phone: string | null;
    email: string | null;
    city: string | null;
    notes: string | null;
    is_active: boolean;
    flight_groups_count: number;
};

defineProps<{ suppliers: Supplier[] }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Suppliers', href: admin.suppliers.index() }],
    },
});

const open = ref(false);
const editingId = ref<number | null>(null);
const form = useForm({
    name: '',
    contact_person: '',
    phone: '',
    email: '',
    city: '',
    notes: '',
    is_active: true,
});

function openForm(supplier?: Supplier) {
    editingId.value = supplier?.id ?? null;
    form.defaults({
        name: supplier?.name ?? '',
        contact_person: supplier?.contact_person ?? '',
        phone: supplier?.phone ?? '',
        email: supplier?.email ?? '',
        city: supplier?.city ?? '',
        notes: supplier?.notes ?? '',
        is_active: supplier?.is_active ?? true,
    });
    form.reset();
    form.clearErrors();
    open.value = true;
}

function submit() {
    form.submit(
        editingId.value
            ? admin.suppliers.update(editingId.value)
            : admin.suppliers.store(),
        { preserveScroll: true, onSuccess: () => (open.value = false) },
    );
}
</script>

<template>
    <Head title="Suppliers" />

    <div class="flex w-full max-w-5xl flex-col gap-5 p-4 md:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1
                    class="font-display text-2xl font-semibold sm:text-[1.7rem]"
                >
                    Suppliers
                </h1>
                <p class="text-sm text-muted-foreground">
                    Who AMS buys outsourced group seats from. Only AMS staff see
                    this.
                </p>
            </div>
            <Button @click="openForm()"><Plus /> Add supplier</Button>
        </div>

        <div class="panel overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="px-4 py-3 font-medium">Supplier</th>
                        <th class="px-4 py-3 font-medium">Contact</th>
                        <th class="px-4 py-3 font-medium">Groups</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="supplier in suppliers" :key="supplier.id">
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ supplier.name }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{ supplier.city }}
                            </div>
                            <div
                                v-if="supplier.notes"
                                class="mt-1 max-w-xs truncate text-xs text-muted-foreground"
                                :title="supplier.notes"
                            >
                                {{ supplier.notes }}
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <div v-if="supplier.contact_person">
                                {{ supplier.contact_person }}
                            </div>
                            <a
                                v-if="supplier.phone"
                                :href="`tel:${supplier.phone.replace(/[^0-9+]/g, '')}`"
                                class="flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground"
                                ><Phone class="size-3" />{{ supplier.phone }}</a
                            >
                            <a
                                v-if="supplier.email"
                                :href="`mailto:${supplier.email}`"
                                class="flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground"
                                ><Mail class="size-3" />{{ supplier.email }}</a
                            >
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <Link
                                v-if="supplier.flight_groups_count > 0"
                                :href="
                                    admin.groups.index({
                                        query: {
                                            sourcing: 'outsourced',
                                            supplier: supplier.id,
                                            past: 1,
                                        },
                                    })
                                "
                                class="font-medium text-highlight hover:underline"
                                >{{ supplier.flight_groups_count }}
                                {{
                                    supplier.flight_groups_count === 1
                                        ? 'group'
                                        : 'groups'
                                }}</Link
                            >
                            <span v-else class="text-muted-foreground"
                                >None yet</span
                            >
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge
                                :status="
                                    supplier.is_active ? 'active' : 'closed'
                                "
                            />
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Button
                                size="sm"
                                variant="ghost"
                                @click="openForm(supplier)"
                                ><Pencil /> Edit</Button
                            >
                        </td>
                    </tr>
                    <tr v-if="suppliers.length === 0">
                        <td
                            colspan="5"
                            class="px-4 py-10 text-center text-muted-foreground"
                        >
                            No suppliers yet. Add the ones AMS buys seats from,
                            then pick them on outsourced groups.
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
                        editingId ? 'Edit supplier' : 'Add supplier'
                    }}</DialogTitle>
                    <DialogDescription
                        >Kept for AMS's own records; agencies never see
                        it.</DialogDescription
                    >
                </DialogHeader>
                <div class="grid gap-2">
                    <Label for="supplier-name">Name</Label>
                    <Input
                        id="supplier-name"
                        v-model="form.name"
                        placeholder="e.g. Al Haram Travels"
                        required
                    />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="grid gap-2 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="contact-person">Contact person</Label>
                        <Input
                            id="contact-person"
                            v-model="form.contact_person"
                        />
                        <InputError :message="form.errors.contact_person" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="supplier-phone">Phone</Label>
                        <Input
                            id="supplier-phone"
                            v-model="form.phone"
                            type="tel"
                            placeholder="0300 1234567"
                        />
                        <InputError :message="form.errors.phone" />
                    </div>
                </div>
                <div class="grid gap-2 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="supplier-email">Email</Label>
                        <Input
                            id="supplier-email"
                            v-model="form.email"
                            type="email"
                        />
                        <InputError :message="form.errors.email" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="supplier-city">City</Label>
                        <Input id="supplier-city" v-model="form.city" />
                        <InputError :message="form.errors.city" />
                    </div>
                </div>
                <div class="grid gap-2">
                    <Label for="supplier-notes">Notes</Label>
                    <textarea
                        id="supplier-notes"
                        v-model="form.notes"
                        rows="2"
                        class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                        placeholder="e.g. Payment terms, usual routes"
                    ></textarea>
                    <InputError :message="form.errors.notes" />
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input
                        v-model="form.is_active"
                        type="checkbox"
                        class="size-4 rounded border-input"
                    />
                    Active (can be picked on new groups)
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
