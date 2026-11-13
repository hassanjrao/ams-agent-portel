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

type Account = {
    id: number;
    bank_name: string;
    account_title: string;
    account_number: string;
    iban: string | null;
    branch: string | null;
    sort_order: number;
    is_active: boolean;
};

defineProps<{ accounts: Account[] }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Bank accounts', href: admin.bankAccounts.index() },
        ],
    },
});

const open = ref(false);
const editingId = ref<number | null>(null);
const form = useForm({
    bank_name: '',
    account_title: '',
    account_number: '',
    iban: '',
    branch: '',
    sort_order: 0,
    is_active: true,
});

function openForm(account?: Account) {
    editingId.value = account?.id ?? null;
    form.defaults({
        bank_name: account?.bank_name ?? '',
        account_title: account?.account_title ?? 'AMS Global Travel & Tours',
        account_number: account?.account_number ?? '',
        iban: account?.iban ?? '',
        branch: account?.branch ?? '',
        sort_order: account?.sort_order ?? 0,
        is_active: account?.is_active ?? true,
    });
    form.reset();
    form.clearErrors();
    open.value = true;
}

function submit() {
    form.submit(
        editingId.value
            ? admin.bankAccounts.update(editingId.value)
            : admin.bankAccounts.store(),
        { preserveScroll: true, onSuccess: () => (open.value = false) },
    );
}
</script>

<template>
    <Head title="Bank accounts" />

    <div class="flex max-w-5xl flex-col gap-4 p-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">
                    Bank accounts
                </h1>
                <p class="text-sm text-muted-foreground">
                    Shown to agencies as where to send payments.
                </p>
            </div>
            <Button @click="openForm()"><Plus /> Add account</Button>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead
                    class="bg-muted/50 text-left text-xs text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3 font-medium">Bank</th>
                        <th class="px-4 py-3 font-medium">Account</th>
                        <th class="px-4 py-3 font-medium">IBAN</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="account in accounts" :key="account.id">
                        <td class="px-4 py-3">
                            <div class="font-medium">
                                {{ account.bank_name }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{ account.branch }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ account.account_title }}</div>
                            <div class="font-mono text-xs">
                                {{ account.account_number }}
                            </div>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs">
                            {{ account.iban }}
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge
                                :status="
                                    account.is_active ? 'active' : 'closed'
                                "
                            />
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Button
                                size="icon-sm"
                                variant="ghost"
                                aria-label="Edit account"
                                @click="openForm(account)"
                                ><Pencil
                            /></Button>
                        </td>
                    </tr>
                    <tr v-if="accounts.length === 0">
                        <td
                            colspan="5"
                            class="px-4 py-10 text-center text-muted-foreground"
                        >
                            No bank accounts yet.
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
                        editingId ? 'Edit bank account' : 'Add bank account'
                    }}</DialogTitle>
                </DialogHeader>
                <div class="grid gap-2">
                    <Label for="bank-name">Bank</Label>
                    <Input
                        id="bank-name"
                        v-model="form.bank_name"
                        placeholder="Meezan Bank"
                        required
                    />
                    <InputError :message="form.errors.bank_name" />
                </div>
                <div class="grid gap-2">
                    <Label for="account-title">Account title</Label>
                    <Input
                        id="account-title"
                        v-model="form.account_title"
                        required
                    />
                    <InputError :message="form.errors.account_title" />
                </div>
                <div class="grid gap-2 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="account-number">Account number</Label>
                        <Input
                            id="account-number"
                            v-model="form.account_number"
                            required
                        />
                        <InputError :message="form.errors.account_number" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="branch">Branch</Label>
                        <Input id="branch" v-model="form.branch" />
                        <InputError :message="form.errors.branch" />
                    </div>
                </div>
                <div class="grid gap-2">
                    <Label for="iban">IBAN</Label>
                    <Input
                        id="iban"
                        v-model="form.iban"
                        placeholder="PK36SCBL0000001123456702"
                        class="uppercase"
                    />
                    <InputError :message="form.errors.iban" />
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
