<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Plus, Search, Edit3, Trash2, FileSpreadsheet, ListChecks, CheckCircle2, XCircle, UploadCloud } from '@lucide/vue';

interface AkunItem {
    id: number;
    kode: string;
    nama: string;
    is_active: boolean;
    dokumen_count: number;
    active_dokumen_count: number;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface AkunPagination {
    data: AkunItem[];
    current_page: number;
    last_page: number;
    total: number;
    links: PaginationLink[];
}

const props = defineProps<{
    accounts: AkunPagination;
    filters: {
        search?: string;
        status?: string;
    };
}>();

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status ?? '');

function applyFilters() {
    router.get(
        '/master/akun',
        {
            search: search.value || undefined,
            status: statusFilter.value !== '' ? statusFilter.value : undefined,
        },
        { preserveState: true, replace: true }
    );
}

// Modal Create
const isCreateOpen = ref(false);
const createForm = useForm({
    kode: '',
    nama: '',
    is_active: true,
});

function submitCreate() {
    createForm.post('/master/akun', {
        onSuccess: () => {
            isCreateOpen.value = false;
            createForm.reset();
        },
    });
}

// Modal Edit
const isEditOpen = ref(false);
const editingAkunId = ref<number | null>(null);
const editForm = useForm({
    kode: '',
    nama: '',
    is_active: true,
});

function openEdit(a: AkunItem) {
    editingAkunId.value = a.id;
    editForm.kode = a.kode;
    editForm.nama = a.nama;
    editForm.is_active = a.is_active;
    isEditOpen.value = true;
}

function submitEdit() {
    if (!editingAkunId.value) return;
    editForm.put(`/master/akun/${editingAkunId.value}`, {
        onSuccess: () => {
            isEditOpen.value = false;
        },
    });
}

// Toggle status
function toggleStatus(a: AkunItem) {
    if (confirm(`Ubah status aktif akun ${a.nama}?`)) {
        router.patch(`/master/akun/${a.id}/toggle`, {}, { preserveScroll: true });
    }
}

// Delete
function deleteAkun(a: AkunItem) {
    if (confirm(`Hapus akun belanja ${a.nama}? Semua dokumen terkait akan ikut terhapus.`)) {
        router.delete(`/master/akun/${a.id}`, { preserveScroll: true });
    }
}

// Modal Impor List Kelengkapan
const isImportOpen = ref(false);
const importForm = useForm<{ file: File | null }>({
    file: null,
});

function onFileChange(e: Event) {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        importForm.file = target.files[0];
    }
}

function submitImport() {
    if (!importForm.file) return;
    importForm.post('/master/akun/impor-kelengkapan', {
        onSuccess: () => {
            isImportOpen.value = false;
            importForm.reset();
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Master Akun Belanja', href: '/master/akun' }]">
        <Head title="Master Akun Belanja" />

        <div class="flex flex-col gap-6 p-6">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Master Akun Belanja & Dokumen Wajib</h1>
                    <p class="text-sm text-muted-foreground">
                        Kelola 35 akun belanja dan daftar dokumen kelengkapan per akun.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" @click="isImportOpen = true">
                        <UploadCloud class="mr-2 h-4 w-4" />
                        Impor List Kelengkapan.xlsx
                    </Button>
                    <Button @click="isCreateOpen = true">
                        <Plus class="mr-2 h-4 w-4" />
                        Tambah Akun
                    </Button>
                </div>
            </div>

            <!-- Filters -->
            <div class="grid grid-cols-1 gap-3 rounded-lg border bg-card p-4 sm:grid-cols-4">
                <div class="relative sm:col-span-3">
                    <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="search"
                        placeholder="Cari kode akun atau nama akun belanja..."
                        class="pl-9"
                        @keydown.enter="applyFilters"
                    />
                </div>
                <div>
                    <select
                        v-model="statusFilter"
                        @change="applyFilters"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                    >
                        <option value="">Semua Status</option>
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded-lg border bg-card shadow-sm">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-muted/40 text-xs font-semibold uppercase text-muted-foreground">
                        <tr>
                            <th class="w-24 px-4 py-3">Kode</th>
                            <th class="px-4 py-3">Nama Lengkap Akun Belanja</th>
                            <th class="w-32 px-4 py-3 text-center">Dokumen</th>
                            <th class="w-24 px-4 py-3 text-center">Status</th>
                            <th class="w-40 px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="accounts.data.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                                Tidak ada data akun belanja.
                            </td>
                        </tr>
                        <tr v-for="a in accounts.data" :key="a.id" class="hover:bg-muted/30">
                            <td class="px-4 py-3 font-mono font-semibold">{{ a.kode }}</td>
                            <td class="px-4 py-3 font-medium">
                                <Link
                                    :href="`/master/akun/${a.id}`"
                                    class="text-primary hover:underline flex items-center gap-1.5"
                                >
                                    <FileSpreadsheet class="h-4 w-4 text-muted-foreground" />
                                    {{ a.nama }}
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <Badge variant="secondary" class="font-mono text-xs">
                                    {{ a.active_dokumen_count }} dokumen
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <Badge :variant="a.is_active ? 'outline' : 'destructive'" class="text-xs">
                                    {{ a.is_active ? 'Aktif' : 'Nonaktif' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Button
                                        as-child
                                        variant="outline"
                                        size="sm"
                                        title="Kelola Dokumen Wajib"
                                    >
                                        <Link :href="`/master/akun/${a.id}`">
                                            <ListChecks class="h-3.5 w-3.5" />
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        title="Edit Akun"
                                        @click="openEdit(a)"
                                    >
                                        <Edit3 class="h-3.5 w-3.5" />
                                    </Button>
                                    <Button
                                        :variant="a.is_active ? 'secondary' : 'default'"
                                        size="sm"
                                        :title="a.is_active ? 'Nonaktifkan' : 'Aktifkan'"
                                        @click="toggleStatus(a)"
                                    >
                                        <component :is="a.is_active ? XCircle : CheckCircle2" class="h-3.5 w-3.5" />
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        title="Hapus Akun"
                                        @click="deleteAkun(a)"
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
            <div v-if="accounts.links.length > 3" class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    Total: {{ accounts.total }} akun belanja
                </p>
                <div class="flex gap-1">
                    <template v-for="(link, i) in accounts.links" :key="i">
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

        <!-- Modal Tambah Akun -->
        <Dialog :open="isCreateOpen" @update:open="isCreateOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Tambah Akun Belanja</DialogTitle>
                    <DialogDescription>
                        Tambahkan akun belanja baru ke sistem.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitCreate" class="space-y-4 py-2">
                    <div class="space-y-1">
                        <Label for="create-kode">Kode Akun</Label>
                        <Input id="create-kode" v-model="createForm.kode" placeholder="Misal: 521211" required />
                        <p v-if="createForm.errors.kode" class="text-xs text-destructive">{{ createForm.errors.kode }}</p>
                    </div>

                    <div class="space-y-1">
                        <Label for="create-nama">Nama Lengkap Akun</Label>
                        <Input id="create-nama" v-model="createForm.nama" placeholder="Misal: 521211 Konsumsi Rapat" required />
                        <p v-if="createForm.errors.nama" class="text-xs text-destructive">{{ createForm.errors.nama }}</p>
                    </div>

                    <DialogFooter class="mt-4">
                        <Button type="button" variant="outline" @click="isCreateOpen = false">Batal</Button>
                        <Button type="submit" :disabled="createForm.processing">Simpan Akun</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Modal Edit Akun -->
        <Dialog :open="isEditOpen" @update:open="isEditOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Edit Akun Belanja</DialogTitle>
                    <DialogDescription>
                        Perbarui kode atau nama lengkap akun belanja.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitEdit" class="space-y-4 py-2">
                    <div class="space-y-1">
                        <Label for="edit-kode">Kode Akun</Label>
                        <Input id="edit-kode" v-model="editForm.kode" required />
                        <p v-if="editForm.errors.kode" class="text-xs text-destructive">{{ editForm.errors.kode }}</p>
                    </div>

                    <div class="space-y-1">
                        <Label for="edit-nama">Nama Lengkap Akun</Label>
                        <Input id="edit-nama" v-model="editForm.nama" required />
                        <p v-if="editForm.errors.nama" class="text-xs text-destructive">{{ editForm.errors.nama }}</p>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input
                            id="edit-active"
                            type="checkbox"
                            v-model="editForm.is_active"
                            class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                        />
                        <Label for="edit-active" class="cursor-pointer">Akun Aktif</Label>
                    </div>

                    <DialogFooter class="mt-4">
                        <Button type="button" variant="outline" @click="isEditOpen = false">Batal</Button>
                        <Button type="submit" :disabled="editForm.processing">Simpan Perubahan</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Modal Impor List Kelengkapan -->
        <Dialog :open="isImportOpen" @update:open="isImportOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Impor List Kelengkapan Excel</DialogTitle>
                    <DialogDescription>
                        Unggah file <code>List Kelengkapan.xlsx</code> untuk memperbarui akun dan susunan dokumen wajib secara massal.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitImport" class="space-y-4 py-2">
                    <div class="space-y-2">
                        <Label for="import-file">Pilih Berkas Excel (.xlsx, .xls)</Label>
                        <Input id="import-file" type="file" accept=".xlsx,.xls" @change="onFileChange" required />
                        <p v-if="importForm.errors.file" class="text-xs text-destructive">{{ importForm.errors.file }}</p>
                    </div>

                    <DialogFooter class="mt-4">
                        <Button type="button" variant="outline" @click="isImportOpen = false">Batal</Button>
                        <Button type="submit" :disabled="importForm.processing || !importForm.file">Mulai Impor</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
