<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
    ShieldCheck,
    Search,
    UserCircle,
    Clock,
    FileSpreadsheet,
    LogIn,
    CheckSquare,
    Trash2,
    KeyRound,
    UserCog,
    Activity,
} from '@lucide/vue';

interface LogItem {
    id: number;
    user_id: number | null;
    aksi: string;
    subjek_tipe: string | null;
    subjek_id: number | null;
    perubahan: Record<string, any> | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
    user?: {
        name: string;
        username: string;
        role: string;
    };
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface LogPagination {
    data: LogItem[];
    current_page: number;
    last_page: number;
    total: number;
    links: PaginationLink[];
}

const props = defineProps<{
    logs: LogPagination;
    filters: {
        search?: string;
        aksi?: string;
    };
}>();

const search = ref(props.filters.search || '');
const aksi = ref(props.filters.aksi || '');

function applyFilters() {
    router.get(
        '/log',
        {
            search: search.value || undefined,
            aksi: aksi.value || undefined,
        },
        { preserveState: true, replace: true }
    );
}

function getActionBadgeVariant(action: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (action) {
        case 'login':
            return 'outline';
        case 'perubahan_checklist':
            return 'default';
        case 'penghapusan_spm':
            return 'destructive';
        case 'perubahan_peran':
        case 'status_pengguna':
        case 'reset_password':
            return 'secondary';
        default:
            return 'outline';
    }
}

function getActionLabel(action: string): string {
    switch (action) {
        case 'login':
            return 'Login Akun';
        case 'perubahan_checklist':
            return 'Update Checklist';
        case 'penghapusan_spm':
            return 'Hapus SPM';
        case 'perubahan_peran':
            return 'Ubah Peran User';
        case 'status_pengguna':
            return 'Toggle Status User';
        case 'reset_password':
            return 'Reset Password';
        case 'impor_excel':
            return 'Impor Excel';
        default:
            return action;
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Log Aktivitas', href: '/log' }]">
        <Head title="Audit Trail Log Aktivitas" />

        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Audit Trail Log Aktivitas</h1>
                    <p class="text-sm text-muted-foreground">
                        Rekam jejak tindakan penting pengguna dalam sistem (Login, Checklist, Perubahan Peran, Penghapusan SPM).
                    </p>
                </div>
            </div>

            <!-- Filters -->
            <div class="grid grid-cols-1 gap-3 rounded-lg border bg-card p-4 sm:grid-cols-3">
                <div class="relative sm:col-span-2">
                    <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="search"
                        placeholder="Cari nama atau username pengguna..."
                        class="pl-9"
                        @keydown.enter="applyFilters"
                    />
                </div>
                <div>
                    <select
                        v-model="aksi"
                        @change="applyFilters"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                    >
                        <option value="">Semua Jenis Aksi</option>
                        <option value="login">Login Akun</option>
                        <option value="perubahan_checklist">Update Checklist</option>
                        <option value="penghapusan_spm">Hapus SPM</option>
                        <option value="perubahan_peran">Ubah Peran User</option>
                        <option value="status_pengguna">Toggle Status User</option>
                        <option value="reset_password">Reset Password</option>
                        <option value="impor_excel">Impor Excel</option>
                    </select>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="overflow-x-auto rounded-lg border bg-card shadow-sm">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-muted/40 text-xs font-semibold uppercase text-muted-foreground">
                        <tr>
                            <th class="w-44 px-4 py-3">Waktu</th>
                            <th class="w-48 px-4 py-3">Pengguna</th>
                            <th class="w-40 px-4 py-3">Aksi</th>
                            <th class="px-4 py-3">Detail Perubahan</th>
                            <th class="w-36 px-4 py-3 text-right">IP Address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="logs.data.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                                Tidak ada log aktivitas yang ditemukan.
                            </td>
                        </tr>
                        <tr v-for="log in logs.data" :key="log.id" class="hover:bg-muted/30">
                            <td class="px-4 py-3 text-xs text-muted-foreground font-mono">
                                {{ new Date(log.created_at).toLocaleString('id-ID') }}
                            </td>
                            <td class="px-4 py-3">
                                <div v-if="log.user" class="flex items-center gap-1.5">
                                    <UserCircle class="h-4 w-4 text-muted-foreground shrink-0" />
                                    <div>
                                        <div class="font-medium text-xs">{{ log.user.name }}</div>
                                        <div class="text-[10px] text-muted-foreground font-mono">{{ log.user.username }}</div>
                                    </div>
                                </div>
                                <span v-else class="text-xs text-muted-foreground italic">Sistem / Anonim</span>
                            </td>
                            <td class="px-4 py-3">
                                <Badge :variant="getActionBadgeVariant(log.aksi)" class="text-[11px] whitespace-nowrap">
                                    {{ getActionLabel(log.aksi) }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div v-if="log.perubahan" class="rounded bg-muted/30 p-2 font-mono text-[11px] max-w-lg overflow-x-auto text-foreground/90">
                                    <div v-for="(val, key) in log.perubahan" :key="key" class="truncate">
                                        <span class="text-muted-foreground font-semibold">{{ key }}:</span>
                                        <span class="ml-1">{{ typeof val === 'object' ? JSON.stringify(val) : val }}</span>
                                    </div>
                                </div>
                                <span v-else class="text-xs text-muted-foreground italic">-</span>
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-xs text-muted-foreground">
                                {{ log.ip_address || '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="logs.links.length > 3" class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    Total: {{ logs.total }} catatan aktivitas
                </p>
                <div class="flex gap-1">
                    <template v-for="(link, i) in logs.links" :key="i">
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
    </AppLayout>
</template>
