<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Plus, Search, Edit3, Trash2, Building2 } from '@lucide/vue';

interface SatkerItem {
    id: number;
    kode: string;
    nama: string;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface SatkerPagination {
    data: SatkerItem[];
    current_page: number;
    last_page: number;
    total: number;
    links: PaginationLink[];
}

const props = defineProps<{
    satkers: SatkerPagination;
    filters: {
        search?: string;
    };
}>();

const search = ref(props.filters.search || '');

function applyFilters() {
    router.get(
        '/master/satker',
        { search: search.value || undefined },
        { preserveState: true, replace: true }
    );
}

// Modal Create
const isCreateOpen = ref(false);
const createForm = useForm({
    kode: '',
    nama: '',
});

function submitCreate() {
    createForm.post('/master/satker', {
        onSuccess: () => {
            isCreateOpen.value = false;
            createForm.reset();
        },
    });
}

// Modal Edit
const isEditOpen = ref(false);
const editingSatkerId = ref<number | null>(null);
const editForm = useForm({
    kode: '',
    nama: '',
});

function openEdit(s: SatkerItem) {
    editingSatkerId.value = s.id;
    editForm.kode = s.kode;
    editForm.nama = s.nama;
    isEditOpen.value = true;
}

function submitEdit() {
    if (!editingSatkerId.value) return;
    editForm.put(`/master/satker/${editingSatkerId.value}`, {
        onSuccess: () => {
            isEditOpen.value = false;
        },
    });
}

// Delete
function deleteSatker(s: SatkerItem) {
    if (confirm(`Hapus satker [${s.kode}] ${s.nama}?`)) {
        router.delete(`/master/satker/${s.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Master Satker', href: '/master/satker' }]">
        <Head title="Master Satker" />

        <div class="flex flex-col gap-6 p-6">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Master Satuan Kerja (Satker)</h1>
                    <p class="text-sm text-muted-foreground">
                        Daftar 12 Satuan Kerja BPS di lingkungan Provinsi Riau.
                    </p>
                </div>
                <Button @click="isCreateOpen = true" class="w-fit">
                    <Plus class="mr-2 h-4 w-4" />
                    Tambah Satker
                </Button>
            </div>

            <div class="grid grid-cols-1 gap-3 rounded-lg border bg-card p-4 sm:grid-cols-3">
                <div class="relative sm:col-span-2">
                    <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="search"
                        placeholder="Cari kode atau nama satker..."
                        class="pl-9"
                        @keydown.enter="applyFilters"
                    />
                </div>
                <div class="flex justify-end">
                    <Button variant="outline" @click="applyFilters">Cari</Button>
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg border bg-card shadow-sm">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-muted/40 text-xs font-semibold uppercase text-muted-foreground">
                        <tr>
                            <th class="w-24 px-4 py-3">Kode</th>
                            <th class="px-4 py-3">Nama Satker</th>
                            <th class="w-32 px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="satkers.data.length === 0">
                            <td colspan="3" class="px-4 py-8 text-center text-muted-foreground">
                                Tidak ada data satker.
                            </td>
                        </tr>
                        <tr v-for="s in satkers.data" :key="s.id" class="hover:bg-muted/30">
                            <td class="px-4 py-3 font-mono font-semibold">{{ s.kode }}</td>
                            <td class="px-4 py-3 font-medium flex items-center gap-2">
                                <Building2 class="h-4 w-4 text-muted-foreground" />
                                {{ s.nama }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        title="Edit Satker"
                                        @click="openEdit(s)"
                                    >
                                        <Edit3 class="h-3.5 w-3.5" />
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        title="Hapus Satker"
                                        @click="deleteSatker(s)"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="satkers.links.length > 3" class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    Total: {{ satkers.total }} satker
                </p>
                <div class="flex gap-1">
                    <template v-for="(link, i) in satkers.links" :key="i">
                        <Button
                            v-if="link.url"
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            @click="router.get(link.url)"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="px-3 py-1 text-xs text-muted-foreground"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>

        <!-- Modal Create Satker -->
        <Dialog :open="isCreateOpen" @update:open="isCreateOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Tambah Satuan Kerja</DialogTitle>
                    <DialogDescription>
                        Masukkan kode 4 digit dan nama satker.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitCreate" class="space-y-4 py-2">
                    <div class="space-y-1">
                        <Label for="create-kode">Kode Satker</Label>
                        <Input id="create-kode" v-model="createForm.kode" placeholder="Misal: 1401" required />
                        <p v-if="createForm.errors.kode" class="text-xs text-destructive">{{ createForm.errors.kode }}</p>
                    </div>

                    <div class="space-y-1">
                        <Label for="create-nama">Nama Satker</Label>
                        <Input id="create-nama" v-model="createForm.nama" placeholder="Misal: Kab. Kuantan Singingi" required />
                        <p v-if="createForm.errors.nama" class="text-xs text-destructive">{{ createForm.errors.nama }}</p>
                    </div>

                    <DialogFooter class="mt-4">
                        <Button type="button" variant="outline" @click="isCreateOpen = false">Batal</Button>
                        <Button type="submit" :disabled="createForm.processing">Simpan Satker</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Modal Edit Satker -->
        <Dialog :open="isEditOpen" @update:open="isEditOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Edit Satuan Kerja</DialogTitle>
                    <DialogDescription>
                        Perbarui kode atau nama satker.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitEdit" class="space-y-4 py-2">
                    <div class="space-y-1">
                        <Label for="edit-kode">Kode Satker</Label>
                        <Input id="edit-kode" v-model="editForm.kode" required />
                        <p v-if="editForm.errors.kode" class="text-xs text-destructive">{{ editForm.errors.kode }}</p>
                    </div>

                    <div class="space-y-1">
                        <Label for="edit-nama">Nama Satker</Label>
                        <Input id="edit-nama" v-model="editForm.nama" required />
                        <p v-if="editForm.errors.nama" class="text-xs text-destructive">{{ editForm.errors.nama }}</p>
                    </div>

                    <DialogFooter class="mt-4">
                        <Button type="button" variant="outline" @click="isEditOpen = false">Batal</Button>
                        <Button type="submit" :disabled="editForm.processing">Simpan Perubahan</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
