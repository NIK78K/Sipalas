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
import {
    ArrowLeft,
    Plus,
    ArrowUp,
    ArrowDown,
    Edit3,
    Trash2,
    CheckCircle2,
    XCircle,
    FileText,
} from '@lucide/vue';

interface DokumenItem {
    id: number;
    akun_belanja_id: number;
    nama: string;
    urutan: number;
    is_active: boolean;
}

interface AkunBelanjaDetail {
    id: number;
    kode: string;
    nama: string;
    is_active: boolean;
    dokumen_wajibs: DokumenItem[];
}

const props = defineProps<{
    account: AkunBelanjaDetail;
}>();

// Modal Add Document
const isCreateOpen = ref(false);
const createForm = useForm({
    nama: '',
});

function submitCreate() {
    createForm.post(`/master/akun/${props.account.id}/dokumen`, {
        onSuccess: () => {
            isCreateOpen.value = false;
            createForm.reset();
        },
    });
}

// Modal Edit Document
const isEditOpen = ref(false);
const editingDocId = ref<number | null>(null);
const editForm = useForm({
    nama: '',
    is_active: true,
});

function openEdit(doc: DokumenItem) {
    editingDocId.value = doc.id;
    editForm.nama = doc.nama;
    editForm.is_active = doc.is_active;
    isEditOpen.value = true;
}

function submitEdit() {
    if (!editingDocId.value) return;
    editForm.put(`/master/dokumen/${editingDocId.value}`, {
        onSuccess: () => {
            isEditOpen.value = false;
        },
    });
}

// Toggle status document
function toggleDocStatus(doc: DokumenItem) {
    router.patch(`/master/dokumen/${doc.id}/toggle`, {}, { preserveScroll: true });
}

// Delete document (soft delete)
function deleteDoc(doc: DokumenItem) {
    if (confirm(`Nonaktifkan dokumen wajib "${doc.nama}"? Dokumen ini akan disembunyikan dari checklist baru.`)) {
        router.delete(`/master/dokumen/${doc.id}`, { preserveScroll: true });
    }
}

// Move Up / Move Down
function moveItem(index: number, direction: 'up' | 'down') {
    const list = [...props.account.dokumen_wajibs];
    const targetIndex = direction === 'up' ? index - 1 : index + 1;

    if (targetIndex < 0 || targetIndex >= list.length) return;

    // Swap
    const temp = list[index];
    list[index] = list[targetIndex];
    list[targetIndex] = temp;

    const ids = list.map((item) => item.id);

    router.post(
        '/master/dokumen/reorder',
        { ids },
        { preserveScroll: true }
    );
}
</script>

<template>
    <AppLayout
        :breadcrumbs="[
            { title: 'Master Akun Belanja', href: '/master/akun' },
            { title: account.nama, href: `/master/akun/${account.id}` },
        ]"
    >
        <Head :title="`Dokumen Wajib - ${account.nama}`" />

        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div class="flex items-center gap-3">
                    <Button as-child variant="outline" size="sm">
                        <Link href="/master/akun">
                            <ArrowLeft class="h-4 w-4 mr-1" />
                            Kembali
                        </Link>
                    </Button>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-2xl font-bold tracking-tight">{{ account.nama }}</h1>
                            <Badge variant="outline" class="font-mono text-xs">{{ account.kode }}</Badge>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            Kelola susunan dokumen wajib untuk pemeriksaan SPM pada akun belanja ini.
                        </p>
                    </div>
                </div>
                <Button @click="isCreateOpen = true" class="w-fit">
                    <Plus class="mr-2 h-4 w-4" />
                    Tambah Dokumen Wajib
                </Button>
            </div>

            <!-- Documents Table -->
            <div class="overflow-x-auto rounded-lg border bg-card shadow-sm">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-muted/40 text-xs font-semibold uppercase text-muted-foreground">
                        <tr>
                            <th class="w-16 px-4 py-3 text-center">Urutan</th>
                            <th class="px-4 py-3">Nama Dokumen Wajib</th>
                            <th class="w-28 px-4 py-3 text-center">Status</th>
                            <th class="w-48 px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="account.dokumen_wajibs.length === 0">
                            <td colspan="4" class="px-4 py-8 text-center text-muted-foreground">
                                Belum ada dokumen wajib untuk akun belanja ini.
                            </td>
                        </tr>
                        <tr
                            v-for="(doc, idx) in account.dokumen_wajibs"
                            :key="doc.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 text-center font-mono font-medium text-muted-foreground">
                                {{ doc.urutan }}
                            </td>
                            <td class="px-4 py-3 font-medium">
                                <div class="flex items-center gap-2">
                                    <FileText class="h-4 w-4 text-muted-foreground" />
                                    <span>{{ doc.nama }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <Badge :variant="doc.is_active ? 'outline' : 'destructive'" class="text-xs">
                                    {{ doc.is_active ? 'Aktif' : 'Nonaktif' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        title="Pindah ke Atas"
                                        :disabled="idx === 0"
                                        @click="moveItem(idx, 'up')"
                                    >
                                        <ArrowUp class="h-3.5 w-3.5" />
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        title="Pindah ke Bawah"
                                        :disabled="idx === account.dokumen_wajibs.length - 1"
                                        @click="moveItem(idx, 'down')"
                                    >
                                        <ArrowDown class="h-3.5 w-3.5" />
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        title="Edit Dokumen"
                                        @click="openEdit(doc)"
                                    >
                                        <Edit3 class="h-3.5 w-3.5" />
                                    </Button>
                                    <Button
                                        :variant="doc.is_active ? 'secondary' : 'default'"
                                        size="sm"
                                        :title="doc.is_active ? 'Nonaktifkan' : 'Aktifkan'"
                                        @click="toggleDocStatus(doc)"
                                    >
                                        <component :is="doc.is_active ? XCircle : CheckCircle2" class="h-3.5 w-3.5" />
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        title="Hapus Dokumen"
                                        @click="deleteDoc(doc)"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Tambah Dokumen -->
        <Dialog :open="isCreateOpen" @update:open="isCreateOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Tambah Dokumen Wajib</DialogTitle>
                    <DialogDescription>
                        Tambahkan dokumen kelengkapan baru untuk {{ account.nama }}.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitCreate" class="space-y-4 py-2">
                    <div class="space-y-1">
                        <Label for="create-nama">Nama Dokumen Wajib</Label>
                        <Input
                            id="create-nama"
                            v-model="createForm.nama"
                            placeholder="Misal: SP2D, SPM, KAK, BAST..."
                            required
                        />
                        <p v-if="createForm.errors.nama" class="text-xs text-destructive">{{ createForm.errors.nama }}</p>
                    </div>

                    <DialogFooter class="mt-4">
                        <Button type="button" variant="outline" @click="isCreateOpen = false">Batal</Button>
                        <Button type="submit" :disabled="createForm.processing">Simpan Dokumen</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Modal Edit Dokumen -->
        <Dialog :open="isEditOpen" @update:open="isEditOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Edit Dokumen Wajib</DialogTitle>
                    <DialogDescription>
                        Perbarui nama atau status dokumen kelengkapan.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitEdit" class="space-y-4 py-2">
                    <div class="space-y-1">
                        <Label for="edit-nama">Nama Dokumen</Label>
                        <Input id="edit-nama" v-model="editForm.nama" required />
                        <p v-if="editForm.errors.nama" class="text-xs text-destructive">{{ editForm.errors.nama }}</p>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input
                            id="edit-doc-active"
                            type="checkbox"
                            v-model="editForm.is_active"
                            class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                        />
                        <Label for="edit-doc-active" class="cursor-pointer">Dokumen Aktif (Muncul di Checklist)</Label>
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
