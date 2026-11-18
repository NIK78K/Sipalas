<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
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
import { Plus, Search, KeyRound, ShieldAlert, CheckCircle2, XCircle, Trash2, Edit3 } from '@lucide/vue';

interface UserItem {
    id: number;
    name: string;
    username: string;
    email: string;
    role: 'operator' | 'admin' | 'user';
    is_active: boolean;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface UserPagination {
    data: UserItem[];
    current_page: number;
    last_page: number;
    total: number;
    links: PaginationLink[];
}

const props = defineProps<{
    users: UserPagination;
    filters: {
        search?: string;
        role?: string;
        status?: string;
    };
}>();

const page = usePage();
const currentUser = page.props.auth?.user;

const search = ref(props.filters.search || '');
const roleFilter = ref(props.filters.role || '');
const statusFilter = ref(props.filters.status ?? '');

function applyFilters() {
    router.get(
        '/pengguna',
        {
            search: search.value || undefined,
            role: roleFilter.value || undefined,
            status: statusFilter.value !== '' ? statusFilter.value : undefined,
        },
        { preserveState: true, replace: true }
    );
}

// Modal Create
const isCreateOpen = ref(false);
const createForm = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
    role: 'user',
    is_active: true,
});

function submitCreate() {
    createForm.post('/pengguna', {
        onSuccess: () => {
            isCreateOpen.value = false;
            createForm.reset();
        },
    });
}

// Modal Edit
const isEditOpen = ref(false);
const editingUserId = ref<number | null>(null);
const editForm = useForm({
    name: '',
    username: '',
    email: '',
    role: 'user',
    is_active: true,
});

function openEdit(u: UserItem) {
    editingUserId.value = u.id;
    editForm.name = u.name;
    editForm.username = u.username;
    editForm.email = u.email;
    editForm.role = u.role;
    editForm.is_active = u.is_active;
    isEditOpen.value = true;
}

function submitEdit() {
    if (!editingUserId.value) return;
    editForm.put(`/pengguna/${editingUserId.value}`, {
        onSuccess: () => {
            isEditOpen.value = false;
        },
    });
}

// Toggle Status
function toggleStatus(u: UserItem) {
    if (confirm(`Ubah status aktif ${u.username}?`)) {
        router.patch(`/pengguna/${u.id}/toggle`, {}, { preserveScroll: true });
    }
}

// Reset Password
const isResetOpen = ref(false);
const resetUserId = ref<number | null>(null);
const resetForm = useForm({
    password: '',
});

function openReset(u: UserItem) {
    resetUserId.value = u.id;
    resetForm.password = 'palas12345';
    isResetOpen.value = true;
}

function submitReset() {
    if (!resetUserId.value) return;
    resetForm.post(`/pengguna/${resetUserId.value}/reset-password`, {
        onSuccess: () => {
            isResetOpen.value = false;
            resetForm.reset();
        },
    });
}

// Delete User
function deleteUser(u: UserItem) {
    if (confirm(`Hapus pengguna ${u.username}? Tindakan ini tidak dapat dibatalkan.`)) {
        router.delete(`/pengguna/${u.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Pengguna', href: '/pengguna' }]">
        <Head title="Manajemen Pengguna" />

        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Manajemen Pengguna</h1>
                    <p class="text-sm text-muted-foreground">
                        Kelola akun, peran wewenang (Operator, Admin, User), dan status akses sistem.
                    </p>
                </div>
                <Button @click="isCreateOpen = true" class="w-fit">
                    <Plus class="mr-2 h-4 w-4" />
                    Tambah Pengguna
                </Button>
            </div>

            <!-- Filters -->
            <div class="grid grid-cols-1 gap-3 rounded-lg border bg-card p-4 sm:grid-cols-4">
                <div class="relative sm:col-span-2">
                    <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="search"
                        placeholder="Cari nama, username, atau email..."
                        class="pl-9"
                        @keydown.enter="applyFilters"
                    />
                </div>
                <div>
                    <select
                        v-model="roleFilter"
                        @change="applyFilters"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                    >
                        <option value="">Semua Peran</option>
                        <option value="operator">Operator</option>
                        <option value="admin">Admin</option>
                        <option value="user">User</option>
                    </select>
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
                            <th class="px-4 py-3">Nama Lengkap</th>
                            <th class="px-4 py-3">Username</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Peran</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="users.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
                                Tidak ada data pengguna yang cocok.
                            </td>
                        </tr>
                        <tr v-for="u in users.data" :key="u.id" class="hover:bg-muted/30">
                            <td class="px-4 py-3 font-medium">{{ u.name }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ u.username }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ u.email }}</td>
                            <td class="px-4 py-3">
                                <Badge
                                    :variant="
                                        u.role === 'operator'
                                            ? 'destructive'
                                            : u.role === 'admin'
                                              ? 'default'
                                              : 'secondary'
                                    "
                                    class="uppercase"
                                >
                                    {{ u.role }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <Badge :variant="u.is_active ? 'outline' : 'destructive'" class="text-xs">
                                    {{ u.is_active ? 'Aktif' : 'Nonaktif' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        title="Edit Pengguna"
                                        @click="openEdit(u)"
                                    >
                                        <Edit3 class="h-3.5 w-3.5" />
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        title="Reset Password"
                                        @click="openReset(u)"
                                    >
                                        <KeyRound class="h-3.5 w-3.5" />
                                    </Button>
                                    <Button
                                        :variant="u.is_active ? 'secondary' : 'default'"
                                        size="sm"
                                        :title="u.is_active ? 'Nonaktifkan' : 'Aktifkan'"
                                        @click="toggleStatus(u)"
                                    >
                                        <component :is="u.is_active ? XCircle : CheckCircle2" class="h-3.5 w-3.5" />
                                    </Button>
                                    <Button
                                        v-if="u.id !== currentUser?.id"
                                        variant="destructive"
                                        size="sm"
                                        title="Hapus Pengguna"
                                        @click="deleteUser(u)"
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
            <div v-if="users.links.length > 3" class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    Total: {{ users.total }} pengguna
                </p>
                <div class="flex gap-1">
                    <template v-for="(link, i) in users.links" :key="i">
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

        <!-- Modal Tambah Pengguna -->
        <Dialog :open="isCreateOpen" @update:open="isCreateOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Tambah Pengguna Baru</DialogTitle>
                    <DialogDescription>
                        Buat akun login baru dan tentukan wewenang perannya.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitCreate" class="space-y-4 py-2">
                    <div class="space-y-1">
                        <Label for="create-name">Nama Lengkap</Label>
                        <Input id="create-name" v-model="createForm.name" required />
                        <p v-if="createForm.errors.name" class="text-xs text-destructive">{{ createForm.errors.name }}</p>
                    </div>

                    <div class="space-y-1">
                        <Label for="create-username">Username</Label>
                        <Input id="create-username" v-model="createForm.username" required />
                        <p v-if="createForm.errors.username" class="text-xs text-destructive">{{ createForm.errors.username }}</p>
                    </div>

                    <div class="space-y-1">
                        <Label for="create-email">Email</Label>
                        <Input id="create-email" type="email" v-model="createForm.email" required />
                        <p v-if="createForm.errors.email" class="text-xs text-destructive">{{ createForm.errors.email }}</p>
                    </div>

                    <div class="space-y-1">
                        <Label for="create-password">Password Awal</Label>
                        <Input id="create-password" type="password" v-model="createForm.password" required />
                        <p v-if="createForm.errors.password" class="text-xs text-destructive">{{ createForm.errors.password }}</p>
                    </div>

                    <div class="space-y-1">
                        <Label for="create-role">Peran</Label>
                        <select
                            id="create-role"
                            v-model="createForm.role"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                        >
                            <option value="user">User (Lihat & Catatan Tambahan)</option>
                            <option value="admin">Admin (Kelola Master & SPM/Checklist)</option>
                            <option value="operator">Operator (Superadmin)</option>
                        </select>
                        <p v-if="createForm.errors.role" class="text-xs text-destructive">{{ createForm.errors.role }}</p>
                    </div>

                    <DialogFooter class="mt-4">
                        <Button type="button" variant="outline" @click="isCreateOpen = false">Batal</Button>
                        <Button type="submit" :disabled="createForm.processing">Simpan Akun</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Modal Edit Pengguna -->
        <Dialog :open="isEditOpen" @update:open="isEditOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Edit Data Pengguna</DialogTitle>
                    <DialogDescription>
                        Ubah identitas, peran, dan status akun pengguna.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitEdit" class="space-y-4 py-2">
                    <div class="space-y-1">
                        <Label for="edit-name">Nama Lengkap</Label>
                        <Input id="edit-name" v-model="editForm.name" required />
                        <p v-if="editForm.errors.name" class="text-xs text-destructive">{{ editForm.errors.name }}</p>
                    </div>

                    <div class="space-y-1">
                        <Label for="edit-username">Username</Label>
                        <Input id="edit-username" v-model="editForm.username" required />
                        <p v-if="editForm.errors.username" class="text-xs text-destructive">{{ editForm.errors.username }}</p>
                    </div>

                    <div class="space-y-1">
                        <Label for="edit-email">Email</Label>
                        <Input id="edit-email" type="email" v-model="editForm.email" required />
                        <p v-if="editForm.errors.email" class="text-xs text-destructive">{{ editForm.errors.email }}</p>
                    </div>

                    <div class="space-y-1">
                        <Label for="edit-role">Peran</Label>
                        <select
                            id="edit-role"
                            v-model="editForm.role"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                        >
                            <option value="user">User (Lihat & Catatan Tambahan)</option>
                            <option value="admin">Admin (Kelola Master & SPM/Checklist)</option>
                            <option value="operator">Operator (Superadmin)</option>
                        </select>
                        <p v-if="editForm.errors.role" class="text-xs text-destructive">{{ editForm.errors.role }}</p>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input
                            id="edit-active"
                            type="checkbox"
                            v-model="editForm.is_active"
                            class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                        />
                        <Label for="edit-active" class="cursor-pointer">Akun Aktif (Dapat Login)</Label>
                    </div>

                    <DialogFooter class="mt-4">
                        <Button type="button" variant="outline" @click="isEditOpen = false">Batal</Button>
                        <Button type="submit" :disabled="editForm.processing">Simpan Perubahan</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Modal Reset Password -->
        <Dialog :open="isResetOpen" @update:open="isResetOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Reset Password Pengguna</DialogTitle>
                    <DialogDescription>
                        Masukkan password baru untuk pengguna ini (default: palas12345).
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitReset" class="space-y-4 py-2">
                    <div class="space-y-1">
                        <Label for="reset-pass">Password Baru</Label>
                        <Input id="reset-pass" type="text" v-model="resetForm.password" required />
                        <p v-if="resetForm.errors.password" class="text-xs text-destructive">{{ resetForm.errors.password }}</p>
                    </div>

                    <DialogFooter class="mt-4">
                        <Button type="button" variant="outline" @click="isResetOpen = false">Batal</Button>
                        <Button type="submit" :disabled="resetForm.processing">Terapkan Password</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
