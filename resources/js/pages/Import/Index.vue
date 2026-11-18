<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    UploadCloud,
    FileSpreadsheet,
    Clock,
    CheckCircle2,
    XCircle,
    AlertTriangle,
    Eye,
    RefreshCw,
} from '@lucide/vue';

interface ReportDetail {
    berhasil?: number;
    dilewati?: number;
    warnings?: string[];
    unmatched_accounts?: string[];
    error?: string;
}

interface ImportItem {
    id: number;
    nama_file: string;
    tipe: string;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    berhasil: number;
    dilewati: number;
    laporan: ReportDetail | null;
    created_at: string;
    user?: {
        name: string;
    };
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface ImportPagination {
    data: ImportItem[];
    current_page: number;
    last_page: number;
    total: number;
    links: PaginationLink[];
}

const props = defineProps<{
    logs: ImportPagination;
}>();

const uploadForm = useForm<{ file: File | null }>({
    file: null,
});

function onFileSelect(e: Event) {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        uploadForm.file = target.files[0];
    }
}

function submitUpload() {
    if (!uploadForm.file) return;
    uploadForm.post('/impor/pemeriksa', {
        preserveScroll: true,
        onSuccess: () => {
            uploadForm.reset();
        },
    });
}

// Polling for processing imports
let pollInterval: any = null;

onMounted(() => {
    pollInterval = setInterval(() => {
        const hasActive = props.logs.data.some(
            (l) => l.status === 'pending' || l.status === 'processing'
        );
        if (hasActive) {
            router.reload({ only: ['logs'] });
        }
    }, 4000);
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});

// Modal Laporan
const isDetailOpen = ref(false);
const activeLog = ref<ImportItem | null>(null);

function openDetail(log: ImportItem) {
    activeLog.value = log;
    isDetailOpen.value = true;
}
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Impor Excel Catatan Pemeriksa', href: '/impor' }]">
        <Head title="Impor Catatan Pemeriksa Excel" />

        <div class="flex flex-col gap-6 p-6 max-w-5xl mx-auto w-full">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Impor Data Catatan Pemeriksa</h1>
                <p class="text-sm text-muted-foreground">
                    Migrasi data pemeriksaan SPM tahun 2026 dari berkas <code>Catatan_Pemeriksa_New.xlsx</code> (12 Satker).
                </p>
            </div>

            <!-- Upload Card -->
            <div class="rounded-xl border bg-card p-6 shadow-sm space-y-4">
                <div class="flex items-center gap-2 border-b pb-3">
                    <UploadCloud class="h-5 w-5 text-primary" />
                    <div>
                        <h2 class="text-base font-bold">Unggah Berkas Excel</h2>
                        <p class="text-xs text-muted-foreground">
                            Format yang didukung: <code>Catatan_Pemeriksa_New.xlsx</code> (maks. 20 MB). Proses berjalan di background queue.
                        </p>
                    </div>
                </div>

                <form @submit.prevent="submitUpload" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="pemeriksa-file">Pilih Berkas (.xlsx)</Label>
                        <Input
                            id="pemeriksa-file"
                            type="file"
                            accept=".xlsx"
                            @change="onFileSelect"
                            required
                        />
                        <p v-if="uploadForm.errors.file" class="text-xs text-destructive">{{ uploadForm.errors.file }}</p>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <span class="text-xs text-muted-foreground">
                            Sistem secara otomatis membersihkan awalan <code>: </code>, mengabaikan blok SPM kosong, dan melakukan <strong>upsert</strong> (tidak dobel).
                        </span>
                        <Button type="submit" :disabled="uploadForm.processing || !uploadForm.file">
                            <UploadCloud class="mr-2 h-4 w-4" />
                            Mulai Proses Impor
                        </Button>
                    </div>
                </form>
            </div>

            <!-- Import History & Logs -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-bold tracking-tight">Riwayat & Status Impor</h2>
                    <Button variant="ghost" size="sm" @click="router.reload({ only: ['logs'] })">
                        <RefreshCw class="h-3.5 w-3.5 mr-1" />
                        Segarkan
                    </Button>
                </div>

                <div class="overflow-x-auto rounded-lg border bg-card shadow-sm">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b bg-muted/40 text-xs font-semibold uppercase text-muted-foreground">
                            <tr>
                                <th class="px-4 py-3">Nama Berkas</th>
                                <th class="w-32 px-4 py-3">Status</th>
                                <th class="w-28 px-4 py-3 text-center">Berhasil</th>
                                <th class="w-28 px-4 py-3 text-center">Dilewati</th>
                                <th class="w-40 px-4 py-3">Waktu</th>
                                <th class="w-24 px-4 py-3 text-right">Laporan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-if="logs.data.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
                                    Belum ada riwayat impor berkas.
                                </td>
                            </tr>
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-muted/30">
                                <td class="px-4 py-3 font-medium">
                                    <div class="flex items-center gap-2">
                                        <FileSpreadsheet class="h-4 w-4 text-muted-foreground" />
                                        <span>{{ log.nama_file }}</span>
                                    </div>
                                    <span v-if="log.user" class="text-xs text-muted-foreground block">
                                        Oleh: {{ log.user.name }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <Badge
                                        :variant="
                                            log.status === 'completed'
                                                ? 'default'
                                                : log.status === 'processing'
                                                  ? 'secondary'
                                                  : log.status === 'failed'
                                                    ? 'destructive'
                                                    : 'outline'
                                        "
                                        class="capitalize"
                                    >
                                        {{ log.status }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3 text-center font-mono font-bold text-emerald-600">
                                    {{ log.berhasil }} SPM
                                </td>
                                <td class="px-4 py-3 text-center font-mono text-muted-foreground">
                                    {{ log.dilewati }}
                                </td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">
                                    {{ new Date(log.created_at).toLocaleString('id-ID') }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        title="Lihat Rincian Laporan"
                                        @click="openDetail(log)"
                                    >
                                        <Eye class="h-3.5 w-3.5 mr-1" />
                                        Detail
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Dialog Laporan Detail -->
        <Dialog :open="isDetailOpen" @update:open="isDetailOpen = $event">
            <DialogContent class="sm:max-w-2xl max-h-[85vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Laporan Hasil Impor</DialogTitle>
                    <DialogDescription>
                        Berkas: {{ activeLog?.nama_file }} &bull; Status: {{ activeLog?.status }}
                    </DialogDescription>
                </DialogHeader>

                <div v-if="activeLog" class="space-y-4 py-2 text-sm">
                    <!-- Stat Box -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg border bg-muted/20 p-3">
                            <span class="text-xs text-muted-foreground block">Jumlah SPM Berhasil Diimpor / Diperbarui</span>
                            <span class="text-xl font-bold font-mono text-emerald-600">{{ activeLog.berhasil }}</span>
                        </div>
                        <div class="rounded-lg border bg-muted/20 p-3">
                            <span class="text-xs text-muted-foreground block">Jumlah Blok Kosong Dilewati</span>
                            <span class="text-xl font-bold font-mono text-muted-foreground">{{ activeLog.dilewati }}</span>
                        </div>
                    </div>

                    <!-- Error if Failed -->
                    <div v-if="activeLog.status === 'failed'" class="rounded-lg bg-destructive/15 p-4 text-xs text-destructive space-y-1">
                        <p class="font-bold">Kegagalan Proses Impor:</p>
                        <p class="font-mono">{{ activeLog.laporan?.error }}</p>
                    </div>

                    <!-- Unmatched Accounts Warning -->
                    <div v-if="activeLog.laporan?.unmatched_accounts && activeLog.laporan.unmatched_accounts.length > 0" class="space-y-2">
                        <h4 class="font-bold text-amber-700 dark:text-amber-400 flex items-center gap-1.5 text-xs uppercase tracking-wider">
                            <AlertTriangle class="h-4 w-4" />
                            Akun Belanja Tidak Ditemukan di Master ({{ activeLog.laporan.unmatched_accounts.length }})
                        </h4>
                        <div class="max-h-36 overflow-y-auto rounded-md border bg-muted/10 p-2 text-xs font-mono space-y-1">
                            <div v-for="(ua, i) in activeLog.laporan.unmatched_accounts" :key="i">
                                {{ ua }}
                            </div>
                        </div>
                    </div>

                    <!-- Warnings (Numeric SPM Numbers, Mismatched Dates) -->
                    <div v-if="activeLog.laporan?.warnings && activeLog.laporan.warnings.length > 0" class="space-y-2">
                        <h4 class="font-bold text-muted-foreground flex items-center gap-1.5 text-xs uppercase tracking-wider">
                            <AlertTriangle class="h-4 w-4 text-muted-foreground" />
                            Catatan & Peringatan Baris ({{ activeLog.laporan.warnings.length }})
                        </h4>
                        <div class="max-h-48 overflow-y-auto rounded-md border bg-muted/10 p-2 text-xs font-mono space-y-1">
                            <div v-for="(w, i) in activeLog.laporan.warnings" :key="i">
                                {{ w }}
                            </div>
                        </div>
                    </div>

                    <div v-if="(!activeLog.laporan?.warnings || activeLog.laporan.warnings.length === 0) && (!activeLog.laporan?.unmatched_accounts || activeLog.laporan.unmatched_accounts.length === 0) && activeLog.status === 'completed'" class="text-center py-4 text-muted-foreground text-xs">
                        Seluruh data berhasil diproses tanpa anomali.
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
