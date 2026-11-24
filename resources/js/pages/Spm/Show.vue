<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import {
    ArrowLeft,
    CheckCircle2,
    Clock,
    Edit3,
    ExternalLink,
    FileCheck,
    MessageSquare,
    Save,
    Trash2,
    AlertCircle,
    UserCircle,
    Send,
} from '@lucide/vue';

interface DokumenWajibItem {
    id: number;
    nama: string;
    urutan: number;
    is_active: boolean;
}

interface ChecklistItem {
    id: number;
    spm_id: number;
    dokumen_wajib_id: number;
    is_checked: boolean;
    checked_at: string | null;
    checked_by?: {
        name: string;
        username: string;
    };
    dokumen_wajib?: DokumenWajibItem;
}

interface CatatanItem {
    id: number;
    user_id: number;
    isi: string;
    can_delete: boolean;
    created_at: string;
    user?: {
        name: string;
        username: string;
        role: string;
    };
}

interface SpmDetail {
    id: number;
    satker_id: number;
    akun_belanja_id: number;
    tahun: number;
    bulan: number;
    nomor: string;
    tanggal_pemeriksaan: string | null;
    jenis: string;
    nilai_transaksi: number;
    catatan_pemeriksa: string | null;
    link_bukti: string | null;
    version: number;
    checked_count: number;
    total_docs_count: number;
    is_lengkap: boolean;
    status_label: string;
    satker?: {
        kode: string;
        nama: string;
    };
    akun_belanja?: {
        kode: string;
        nama: string;
    };
    creator?: {
        name: string;
    };
    updater?: {
        name: string;
    };
}

const props = defineProps<{
    spm: SpmDetail;
    checklists: ChecklistItem[];
    catatans: CatatanItem[];
    canExamine: boolean;
}>();

const months = [
    '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];

function formatRupiah(val: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val);
}

// Local state for interactive checklist
const checkedDocIds = ref<number[]>(
    props.checklists.filter((c) => c.is_checked).map((c) => c.dokumen_wajib_id)
);
const catatanPemeriksa = ref(props.spm.catatan_pemeriksa || '');
const hasUnsavedChanges = ref(false);

watch(
    [checkedDocIds, catatanPemeriksa],
    () => {
        hasUnsavedChanges.value = true;
    },
    { deep: true }
);

function toggleCheck(docId: number) {
    if (!props.canExamine) return;
    const index = checkedDocIds.value.indexOf(docId);
    if (index === -1) {
        checkedDocIds.value.push(docId);
    } else {
        checkedDocIds.value.splice(index, 1);
    }
}

function checkAll() {
    if (!props.canExamine) return;
    checkedDocIds.value = props.checklists.map((c) => c.dokumen_wajib_id);
}

function uncheckAll() {
    if (!props.canExamine) return;
    checkedDocIds.value = [];
}

const isSavingChecklist = ref(false);

function saveChecklist() {
    isSavingChecklist.value = true;
    router.post(
        `/spm/${props.spm.id}/checklist`,
        {
            version: props.spm.version,
            checked_ids: checkedDocIds.value,
            catatan_pemeriksa: catatanPemeriksa.value,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isSavingChecklist.value = false;
                hasUnsavedChanges.value = false;
            },
        }
    );
}

// Progress calculation
const activeCheckedCount = computed(() => checkedDocIds.value.length);
const totalDocs = computed(() => props.checklists.length);
const progressPercent = computed(() => {
    if (totalDocs.value === 0) return 0;
    return Math.round((activeCheckedCount.value / totalDocs.value) * 100);
});

// Form Catatan Tambahan
const catatanForm = useForm({
    isi: '',
});

function submitCatatan() {
    catatanForm.post(`/spm/${props.spm.id}/catatan`, {
        preserveScroll: true,
        onSuccess: () => {
            catatanForm.reset();
        },
    });
}

function deleteCatatan(c: CatatanItem) {
    if (confirm('Hapus catatan tambahan ini?')) {
        router.delete(`/spm-catatan/${c.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <AppLayout
        :breadcrumbs="[
            { title: 'Daftar SPM', href: '/spm' },
            { title: `SPM No ${spm.nomor}`, href: `/spm/${spm.id}` },
        ]"
    >
        <Head :title="`Pemeriksaan SPM No ${spm.nomor}`" />

        <div class="flex flex-col gap-6 p-6 max-w-6xl mx-auto w-full">
            <!-- Header Bar -->
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div class="flex items-center gap-3">
                    <Button as-child variant="outline" size="sm">
                        <Link href="/spm">
                            <ArrowLeft class="h-4 w-4 mr-1" />
                            Kembali
                        </Link>
                    </Button>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-2xl font-bold tracking-tight">SPM No. {{ spm.nomor }}</h1>
                            <Badge variant="outline" class="font-mono text-xs">{{ spm.jenis }}</Badge>
                            <Badge :variant="progressPercent === 100 ? 'default' : 'secondary'">
                                {{ progressPercent === 100 ? 'Lengkap' : `Kurang ${totalDocs - activeCheckedCount}` }}
                            </Badge>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            [{{ spm.satker?.kode }}] {{ spm.satker?.nama }} &bull; Periode {{ months[spm.bulan] }} {{ spm.tahun }}
                        </p>
                    </div>
                </div>

                <div v-if="canExamine" class="flex items-center gap-2">
                    <Button as-child variant="outline" size="sm">
                        <Link :href="`/spm/${spm.id}/edit`">
                            <Edit3 class="mr-1.5 h-3.5 w-3.5" />
                            Edit Metadata
                        </Link>
                    </Button>
                    <Button
                        size="sm"
                        :disabled="isSavingChecklist"
                        @click="saveChecklist"
                        :class="hasUnsavedChanges ? 'bg-amber-600 hover:bg-amber-700 text-white' : ''"
                    >
                        <Save class="mr-1.5 h-3.5 w-3.5" />
                        {{ hasUnsavedChanges ? 'Simpan Perubahan' : 'Checklist Tersimpan' }}
                    </Button>
                </div>
            </div>

            <!-- Warning if unsaved changes -->
            <div
                v-if="hasUnsavedChanges && canExamine"
                class="flex items-center justify-between rounded-lg bg-amber-500/15 p-3 text-sm text-amber-800 dark:text-amber-400 border border-amber-500/30"
            >
                <div class="flex items-center gap-2">
                    <AlertCircle class="h-4 w-4 shrink-0" />
                    <span>Anda memiliki perubahan checklist atau catatan pemeriksa yang belum tersimpan ke server.</span>
                </div>
                <Button size="sm" variant="outline" @click="saveChecklist">
                    Simpan Sekarang
                </Button>
            </div>

            <!-- SPM Information Overview Card -->
            <div class="grid grid-cols-1 gap-4 rounded-lg border bg-card p-5 shadow-sm md:grid-cols-4">
                <div>
                    <span class="text-xs text-muted-foreground block">Akun Belanja</span>
                    <span class="text-sm font-semibold">{{ spm.akun_belanja?.nama }}</span>
                </div>
                <div>
                    <span class="text-xs text-muted-foreground block">Nilai Transaksi</span>
                    <span class="text-sm font-mono font-bold text-primary">{{ formatRupiah(spm.nilai_transaksi) }}</span>
                </div>
                <div>
                    <span class="text-xs text-muted-foreground block">Tgl Pemeriksaan</span>
                    <span class="text-sm font-medium">{{ spm.tanggal_pemeriksaan || '-' }}</span>
                </div>
                <div>
                    <span class="text-xs text-muted-foreground block">Link Bukti Dukung</span>
                    <a
                        v-if="spm.link_bukti"
                        :href="spm.link_bukti"
                        target="_blank"
                        rel="noopener"
                        class="text-sm text-primary hover:underline flex items-center gap-1 font-medium truncate"
                    >
                        <span>Buka Berkas Bukti</span>
                        <ExternalLink class="h-3 w-3 shrink-0" />
                    </a>
                    <span v-else class="text-sm text-muted-foreground italic">Tidak ada tautan</span>
                </div>
            </div>

            <!-- Progress Meter Bar -->
            <div class="rounded-lg border bg-card p-4 shadow-sm space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium">Progres Kelengkapan Dokumen</span>
                    <span class="font-mono font-bold">{{ activeCheckedCount }} / {{ totalDocs }} ({{ progressPercent }}%)</span>
                </div>
                <div class="w-full bg-muted rounded-full h-3 overflow-hidden">
                    <div
                        class="h-full transition-all duration-300 rounded-full"
                        :class="progressPercent === 100 ? 'bg-emerald-600' : 'bg-primary'"
                        :style="{ width: `${progressPercent}%` }"
                    ></div>
                </div>
            </div>

            <!-- Interactive Checklist Table -->
            <div class="rounded-lg border bg-card shadow-sm overflow-hidden space-y-4 p-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b pb-4">
                    <div>
                        <h2 class="text-lg font-bold flex items-center gap-2">
                            <FileCheck class="h-5 w-5 text-primary" />
                            Lembar Checklist Kelengkapan Dokumen
                        </h2>
                        <p class="text-xs text-muted-foreground">
                            Centang berkas fisik yang telah memenuhi syarat pemeriksaan.
                        </p>
                    </div>
                    <div v-if="canExamine" class="flex gap-2">
                        <Button variant="outline" size="sm" @click="checkAll">Centang Semua</Button>
                        <Button variant="outline" size="sm" @click="uncheckAll">Kosongkan Semua</Button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b bg-muted/40 text-xs font-semibold uppercase text-muted-foreground">
                            <tr>
                                <th class="w-12 px-3 py-2 text-center">No</th>
                                <th class="w-16 px-3 py-2 text-center">Status</th>
                                <th class="px-4 py-2">Nama Dokumen Wajib</th>
                                <th class="w-48 px-4 py-2 text-right">Dicentang Oleh</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-if="checklists.length === 0">
                                <td colspan="4" class="px-4 py-6 text-center text-muted-foreground">
                                    Belum ada dokumen wajib yang terdaftar untuk akun belanja ini.
                                </td>
                            </tr>
                            <tr
                                v-for="(chk, idx) in checklists"
                                :key="chk.id"
                                class="hover:bg-muted/30 transition-colors"
                                :class="checkedDocIds.includes(chk.dokumen_wajib_id) ? 'bg-emerald-500/5' : ''"
                            >
                                <td class="px-3 py-3 text-center font-mono text-xs text-muted-foreground">
                                    {{ idx + 1 }}
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <input
                                        type="checkbox"
                                        :checked="checkedDocIds.includes(chk.dokumen_wajib_id)"
                                        :disabled="!canExamine"
                                        @change="toggleCheck(chk.dokumen_wajib_id)"
                                        class="h-5 w-5 rounded border-gray-300 text-primary focus:ring-primary cursor-pointer disabled:cursor-not-allowed"
                                    />
                                </td>
                                <td
                                    class="px-4 py-3 font-medium cursor-pointer"
                                    @click="toggleCheck(chk.dokumen_wajib_id)"
                                >
                                    <span :class="checkedDocIds.includes(chk.dokumen_wajib_id) ? 'text-foreground' : 'text-muted-foreground'">
                                        {{ chk.dokumen_wajib?.nama }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right text-xs text-muted-foreground">
                                    <span v-if="chk.is_checked && chk.checked_by">
                                        {{ chk.checked_by.name }}
                                    </span>
                                    <span v-else-if="checkedDocIds.includes(chk.dokumen_wajib_id)">
                                        (Perubahan baru)
                                    </span>
                                    <span v-else>-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Uraian Belanja -->
                <div class="pt-4 border-t space-y-2">
                    <Label for="catatan-pemeriksa" class="font-semibold text-sm">
                        Uraian Belanja (Operator / Admin)
                    </Label>
                    <textarea
                        id="catatan-pemeriksa"
                        v-model="catatanPemeriksa"
                        :disabled="!canExamine"
                        rows="3"
                        placeholder="Tulis ringkasan temuan atau kelengkapan resmi untuk SPM ini..."
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-primary disabled:bg-muted disabled:cursor-not-allowed"
                    ></textarea>
                </div>

                <div v-if="canExamine" class="flex justify-end pt-2">
                    <Button size="sm" @click="saveChecklist" :disabled="isSavingChecklist">
                        <Save class="mr-2 h-4 w-4" />
                        Simpan Lembar Pemeriksaan
                    </Button>
                </div>
            </div>

            <!-- Catatan Tambahan SPM (FR-22) -->
            <div class="rounded-lg border bg-card p-5 shadow-sm space-y-4">
                <div class="flex items-center gap-2 border-b pb-3">
                    <MessageSquare class="h-5 w-5 text-primary" />
                    <div>
                        <h2 class="text-base font-bold">Catatan Tambahan / Klarifikasi Dokumen</h2>
                        <p class="text-xs text-muted-foreground">
                            Bisa ditambahkan oleh semua pengguna untuk komunikasi terkait dokumen SPM ini.
                        </p>
                    </div>
                </div>

                <!-- Notes List -->
                <div class="space-y-3">
                    <div v-if="catatans.length === 0" class="text-center py-4 text-xs text-muted-foreground">
                        Belum ada catatan tambahan pada SPM ini.
                    </div>
                    <div
                        v-for="c in catatans"
                        :key="c.id"
                        class="rounded-lg border bg-muted/20 p-3.5 space-y-1.5"
                    >
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-1.5">
                                <UserCircle class="h-4 w-4 text-muted-foreground" />
                                <span class="font-semibold">{{ c.user?.name }}</span>
                                <Badge variant="outline" class="text-[10px] uppercase font-mono">
                                    {{ c.user?.role }}
                                </Badge>
                            </div>
                            <div class="flex items-center gap-2 text-muted-foreground">
                                <Clock class="h-3 w-3" />
                                <span>{{ new Date(c.created_at).toLocaleString('id-ID') }}</span>
                                <Button
                                    v-if="c.can_delete"
                                    variant="ghost"
                                    size="sm"
                                    class="h-6 w-6 p-0 text-destructive hover:bg-destructive/10"
                                    title="Hapus catatan (hanya dapat dihapus dalam 15 menit)"
                                    @click="deleteCatatan(c)"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </Button>
                            </div>
                        </div>
                        <p class="text-sm whitespace-pre-line text-foreground">{{ c.isi }}</p>
                    </div>
                </div>

                <!-- Add Note Form -->
                <form @submit.prevent="submitCatatan" class="pt-3 border-t space-y-2">
                    <Label for="catatan-baru" class="text-xs font-semibold">Tambah Catatan</Label>
                    <div class="flex gap-2">
                        <textarea
                            id="catatan-baru"
                            v-model="catatanForm.isi"
                            rows="2"
                            placeholder="Tulis catatan atau klarifikasi kekurangan berkas..."
                            class="flex-1 rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-primary"
                            required
                        ></textarea>
                        <Button type="submit" class="self-end" :disabled="catatanForm.processing || !catatanForm.isi">
                            <Send class="h-4 w-4 mr-1" />
                            Kirim
                        </Button>
                    </div>
                    <p v-if="catatanForm.errors.isi" class="text-xs text-destructive">{{ catatanForm.errors.isi }}</p>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
