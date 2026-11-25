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
    status: 'sesuai' | 'tidak_sesuai' | '-' | null;
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
    parent_id: number | null;
    isi: string;
    can_delete: boolean;
    created_at: string;
    replies?: CatatanItem[];
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
    '',
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
];

function formatRupiah(val: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val);
}

// Local state for interactive checklist
const checklistStatuses = ref<Record<number, string>>(
    Object.fromEntries(
        props.checklists.map((c) => [c.dokumen_wajib_id, c.status ?? '']),
    ),
);
const catatanPemeriksa = ref(props.spm.catatan_pemeriksa || '');
const hasUnsavedChanges = ref(false);

watch(
    [checklistStatuses, catatanPemeriksa],
    () => {
        hasUnsavedChanges.value = true;
    },
    { deep: true },
);

function setAllStatuses(status: string) {
    if (!props.canExamine) return;
    props.checklists
        .filter((c) => c.dokumen_wajib?.is_active)
        .forEach((c) => {
            checklistStatuses.value[c.dokumen_wajib_id] = status;
        });
}

const isSavingChecklist = ref(false);

function saveChecklist() {
    isSavingChecklist.value = true;
    router.post(
        `/spm/${props.spm.id}/checklist`,
        {
            version: props.spm.version,
            statuses: checklistStatuses.value,
            catatan_pemeriksa: catatanPemeriksa.value,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isSavingChecklist.value = false;
                hasUnsavedChanges.value = false;
            },
        },
    );
}

// Progress calculation
const activeCheckedCount = computed(
    () =>
        props.checklists.filter(
            (c) =>
                c.dokumen_wajib?.is_active &&
                checklistStatuses.value[c.dokumen_wajib_id] === 'sesuai',
        ).length,
);
const totalDocs = computed(
    () =>
        props.checklists.filter(
            (c) =>
                c.dokumen_wajib?.is_active &&
                checklistStatuses.value[c.dokumen_wajib_id] !== '-',
        ).length,
);
const progressPercent = computed(() => {
    if (totalDocs.value === 0) return 0;
    return Math.round((activeCheckedCount.value / totalDocs.value) * 100);
});

// Form Catatan Tambahan
const catatanForm = useForm({
    isi: '',
    parent_id: null as number | null,
});

const replyingTo = ref<CatatanItem | null>(null);

function startReply(c: CatatanItem) {
    replyingTo.value = c;
    catatanForm.parent_id = c.id;
}

function cancelReply() {
    replyingTo.value = null;
    catatanForm.parent_id = null;
    catatanForm.isi = '';
}

function submitCatatan() {
    catatanForm.post(`/spm/${props.spm.id}/catatan`, {
        preserveScroll: true,
        onSuccess: () => {
            catatanForm.reset();
            replyingTo.value = null;
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

        <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 p-6">
            <!-- Header Bar -->
            <div
                class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
            >
                <div class="flex items-center gap-3">
                    <Button as-child variant="outline" size="sm">
                        <Link href="/spm">
                            <ArrowLeft class="mr-1 h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-2xl font-bold tracking-tight">
                                SPM No. {{ spm.nomor }}
                            </h1>
                            <Badge
                                variant="outline"
                                class="font-mono text-xs"
                                >{{ spm.jenis }}</Badge
                            >
                            <Badge
                                :variant="
                                    progressPercent === 100
                                        ? 'default'
                                        : 'secondary'
                                "
                            >
                                {{
                                    progressPercent === 100
                                        ? 'Lengkap'
                                        : `Kurang ${totalDocs - activeCheckedCount}`
                                }}
                            </Badge>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            [{{ spm.satker?.kode }}]
                            {{ spm.satker?.nama }} &bull; Periode
                            {{ months[spm.bulan] }} {{ spm.tahun }}
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
                        :class="
                            hasUnsavedChanges
                                ? 'bg-amber-600 text-white hover:bg-amber-700'
                                : ''
                        "
                    >
                        <Save class="mr-1.5 h-3.5 w-3.5" />
                        {{
                            hasUnsavedChanges
                                ? 'Simpan Perubahan'
                                : 'Checklist Tersimpan'
                        }}
                    </Button>
                </div>
            </div>

            <!-- Warning if unsaved changes -->
            <div
                v-if="hasUnsavedChanges && canExamine"
                class="flex items-center justify-between rounded-lg border border-amber-500/30 bg-amber-500/15 p-3 text-sm text-amber-800 dark:text-amber-400"
            >
                <div class="flex items-center gap-2">
                    <AlertCircle class="h-4 w-4 shrink-0" />
                    <span
                        >Anda memiliki perubahan checklist atau catatan
                        pemeriksa yang belum tersimpan ke server.</span
                    >
                </div>
                <Button size="sm" variant="outline" @click="saveChecklist">
                    Simpan Sekarang
                </Button>
            </div>

            <!-- SPM Information Overview Card -->
            <div
                class="grid grid-cols-1 gap-4 rounded-lg border bg-card p-5 shadow-sm md:grid-cols-4"
            >
                <div>
                    <span class="block text-xs text-muted-foreground"
                        >Akun Belanja</span
                    >
                    <span class="text-sm font-semibold">{{
                        spm.akun_belanja?.nama
                    }}</span>
                </div>
                <div>
                    <span class="block text-xs text-muted-foreground"
                        >Nilai Transaksi</span
                    >
                    <span class="font-mono text-sm font-bold text-primary">{{
                        formatRupiah(spm.nilai_transaksi)
                    }}</span>
                </div>
                <div>
                    <span class="block text-xs text-muted-foreground"
                        >Tgl Pemeriksaan</span
                    >
                    <span class="text-sm font-medium">{{
                        spm.tanggal_pemeriksaan || '-'
                    }}</span>
                </div>
                <div>
                    <span class="block text-xs text-muted-foreground"
                        >Link Bukti Dukung</span
                    >
                    <a
                        v-if="spm.link_bukti"
                        :href="spm.link_bukti"
                        target="_blank"
                        rel="noopener"
                        class="flex items-center gap-1 truncate text-sm font-medium text-primary hover:underline"
                    >
                        <span>Buka Berkas Bukti</span>
                        <ExternalLink class="h-3 w-3 shrink-0" />
                    </a>
                    <span v-else class="text-sm text-muted-foreground italic"
                        >Tidak ada tautan</span
                    >
                </div>
            </div>

            <!-- Progress Meter Bar -->
            <div class="space-y-2 rounded-lg border bg-card p-4 shadow-sm">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium">Progres Kelengkapan Dokumen</span>
                    <span class="font-mono font-bold"
                        >{{ activeCheckedCount }} / {{ totalDocs }} ({{
                            progressPercent
                        }}%)</span
                    >
                </div>
                <div class="h-3 w-full overflow-hidden rounded-full bg-muted">
                    <div
                        class="h-full rounded-full transition-all duration-300"
                        :class="
                            progressPercent === 100
                                ? 'bg-emerald-600'
                                : 'bg-primary'
                        "
                        :style="{ width: `${progressPercent}%` }"
                    ></div>
                </div>
            </div>

            <!-- Interactive Checklist Table -->
            <div
                class="space-y-4 overflow-hidden rounded-lg border bg-card p-5 shadow-sm"
            >
                <div
                    class="flex flex-col justify-between gap-3 border-b pb-4 sm:flex-row sm:items-center"
                >
                    <div>
                        <h2 class="flex items-center gap-2 text-lg font-bold">
                            <FileCheck class="h-5 w-5 text-primary" />
                            Lembar Checklist Kelengkapan Dokumen
                        </h2>
                        <p class="text-xs text-muted-foreground">
                            Pilih status pemeriksaan untuk setiap dokumen.
                        </p>
                    </div>
                    <div v-if="canExamine" class="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            @click="setAllStatuses('sesuai')"
                            >Tandai Semua Sesuai</Button
                        >
                        <Button
                            variant="outline"
                            size="sm"
                            @click="setAllStatuses('')"
                            >Kosongkan Status</Button
                        >
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead
                            class="border-b bg-muted/40 text-xs font-semibold text-muted-foreground uppercase"
                        >
                            <tr>
                                <th class="w-12 px-3 py-2 text-center">No</th>
                                <th class="w-40 px-3 py-2 text-center">
                                    Status
                                </th>
                                <th class="px-4 py-2">Nama Dokumen Wajib</th>
                                <th class="w-48 px-4 py-2 text-right">
                                    Diperiksa Oleh
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-if="checklists.length === 0">
                                <td
                                    colspan="4"
                                    class="px-4 py-6 text-center text-muted-foreground"
                                >
                                    Belum ada dokumen wajib yang terdaftar untuk
                                    akun belanja ini.
                                </td>
                            </tr>
                            <tr
                                v-for="(chk, idx) in checklists"
                                :key="chk.id"
                                class="transition-colors hover:bg-muted/30"
                                :class="
                                    checklistStatuses[chk.dokumen_wajib_id] ===
                                    'sesuai'
                                        ? 'bg-emerald-500/5'
                                        : ''
                                "
                            >
                                <td
                                    class="px-3 py-3 text-center font-mono text-xs text-muted-foreground"
                                >
                                    {{ idx + 1 }}
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <select
                                        v-model="
                                            checklistStatuses[
                                                chk.dokumen_wajib_id
                                            ]
                                        "
                                        :disabled="!canExamine"
                                        :aria-label="`Status ${chk.dokumen_wajib?.nama ?? 'dokumen'}`"
                                        class="h-9 w-full rounded-md border border-input bg-background px-2 text-xs focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        <option value="">Pilih status</option>
                                        <option value="sesuai">Sesuai</option>
                                        <option value="tidak_sesuai">
                                            Tidak Sesuai
                                        </option>
                                        <option value="-">-</option>
                                    </select>
                                </td>
                                <td class="px-4 py-3 font-medium">
                                    <span
                                        :class="
                                            checklistStatuses[
                                                chk.dokumen_wajib_id
                                            ] === 'sesuai'
                                                ? 'text-foreground'
                                                : 'text-muted-foreground'
                                        "
                                    >
                                        {{ chk.dokumen_wajib?.nama }}
                                    </span>
                                </td>
                                <td
                                    class="px-4 py-3 text-right text-xs text-muted-foreground"
                                >
                                    <span
                                        v-if="
                                            checklistStatuses[
                                                chk.dokumen_wajib_id
                                            ] !== (chk.status ?? '')
                                        "
                                    >
                                        (Perubahan baru)
                                    </span>
                                    <span
                                        v-else-if="
                                            checklistStatuses[
                                                chk.dokumen_wajib_id
                                            ] && chk.checked_by
                                        "
                                    >
                                        {{ chk.checked_by.name }}
                                    </span>
                                    <span v-else>-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Uraian Belanja -->
                <div class="space-y-2 border-t pt-4">
                    <Label
                        for="catatan-pemeriksa"
                        class="text-sm font-semibold"
                    >
                        Uraian Belanja (Operator / Admin)
                    </Label>
                    <textarea
                        id="catatan-pemeriksa"
                        v-model="catatanPemeriksa"
                        :disabled="!canExamine"
                        rows="3"
                        placeholder="Tulis ringkasan temuan atau kelengkapan resmi untuk SPM ini..."
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:ring-1 focus:ring-primary focus:outline-none disabled:cursor-not-allowed disabled:bg-muted"
                    ></textarea>
                </div>

                <div v-if="canExamine" class="flex justify-end pt-2">
                    <Button
                        size="sm"
                        @click="saveChecklist"
                        :disabled="isSavingChecklist"
                    >
                        <Save class="mr-2 h-4 w-4" />
                        Simpan Lembar Pemeriksaan
                    </Button>
                </div>
            </div>

            <!-- Catatan Tambahan SPM (FR-22) -->
            <div class="space-y-4 rounded-lg border bg-card p-5 shadow-sm">
                <div class="flex items-center gap-2 border-b pb-3">
                    <MessageSquare class="h-5 w-5 text-primary" />
                    <div>
                        <h2 class="text-base font-bold">
                            Catatan Tambahan / Klarifikasi Dokumen
                        </h2>
                        <p class="text-xs text-muted-foreground">
                            Bisa ditambahkan oleh semua pengguna untuk
                            komunikasi terkait dokumen SPM ini.
                        </p>
                    </div>
                </div>

                <!-- Notes List -->
                <div class="space-y-3">
                    <div
                        v-if="catatans.length === 0"
                        class="py-4 text-center text-xs text-muted-foreground"
                    >
                        Belum ada catatan tambahan pada SPM ini.
                    </div>
                    <div
                        v-for="c in catatans"
                        :key="c.id"
                        class="space-y-1.5 rounded-lg border bg-muted/20 p-3.5"
                    >
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-1.5">
                                <UserCircle
                                    class="h-4 w-4 text-muted-foreground"
                                />
                                <span class="font-semibold">{{
                                    c.user?.name
                                }}</span>
                                <Badge
                                    variant="outline"
                                    class="font-mono text-[10px] uppercase"
                                >
                                    {{ c.user?.role }}
                                </Badge>
                                <Badge
                                    :variant="
                                        c.parent_id ? 'secondary' : 'default'
                                    "
                                    class="text-[10px]"
                                >
                                    {{
                                        c.parent_id
                                            ? `Balasan ${c.user?.role === 'admin' ? 'admin' : c.user?.role === 'operator' ? 'operator' : 'pengguna'}`
                                            : 'Catatan baru'
                                    }}
                                </Badge>
                            </div>
                            <div
                                class="flex items-center gap-2 text-muted-foreground"
                            >
                                <Clock class="h-3 w-3" />
                                <span>{{
                                    new Date(c.created_at).toLocaleString(
                                        'id-ID',
                                    )
                                }}</span>
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
                        <p class="text-sm whitespace-pre-line text-foreground">
                            {{ c.isi }}
                        </p>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="h-7 px-2 text-xs"
                            @click="startReply(c)"
                        >
                            <MessageSquare class="mr-1 h-3.5 w-3.5" />
                            Balas
                        </Button>

                        <div
                            v-if="c.replies?.length"
                            class="mt-3 space-y-2 border-l-2 border-primary/40 pl-4"
                        >
                            <div
                                v-for="reply in c.replies"
                                :key="reply.id"
                                class="space-y-1.5 rounded-md border bg-background p-3"
                            >
                                <div
                                    class="flex items-center justify-between text-xs"
                                >
                                    <div class="flex items-center gap-1.5">
                                        <UserCircle
                                            class="h-4 w-4 text-muted-foreground"
                                        />
                                        <span class="font-semibold">{{
                                            reply.user?.name
                                        }}</span>
                                        <Badge
                                            variant="outline"
                                            class="font-mono text-[10px] uppercase"
                                        >
                                            {{ reply.user?.role }}
                                        </Badge>
                                        <Badge
                                            variant="secondary"
                                            class="text-[10px]"
                                        >
                                            Balasan
                                            {{
                                                reply.user?.role === 'admin'
                                                    ? 'admin'
                                                    : reply.user?.role ===
                                                        'operator'
                                                      ? 'operator'
                                                      : 'pengguna'
                                            }}
                                        </Badge>
                                    </div>
                                    <div
                                        class="flex items-center gap-2 text-muted-foreground"
                                    >
                                        <Clock class="h-3 w-3" />
                                        <span>{{
                                            new Date(
                                                reply.created_at,
                                            ).toLocaleString('id-ID')
                                        }}</span>
                                        <Button
                                            v-if="reply.can_delete"
                                            variant="ghost"
                                            size="sm"
                                            class="h-6 w-6 p-0 text-destructive hover:bg-destructive/10"
                                            title="Hapus balasan"
                                            @click="deleteCatatan(reply)"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </Button>
                                    </div>
                                </div>
                                <p
                                    class="text-sm whitespace-pre-line text-foreground"
                                >
                                    {{ reply.isi }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Note Form -->
                <form
                    @submit.prevent="submitCatatan"
                    class="space-y-2 border-t pt-3"
                >
                    <div
                        v-if="replyingTo"
                        class="flex items-center justify-between rounded-md bg-primary/10 px-3 py-2 text-xs"
                    >
                        <span>
                            Membalas catatan
                            <strong>{{ replyingTo.user?.name }}</strong>
                        </span>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="h-6"
                            @click="cancelReply"
                        >
                            Batal balas
                        </Button>
                    </div>
                    <Label for="catatan-baru" class="text-xs font-semibold">
                        {{ replyingTo ? 'Tulis Balasan' : 'Tambah Catatan' }}
                    </Label>
                    <div class="flex gap-2">
                        <textarea
                            id="catatan-baru"
                            v-model="catatanForm.isi"
                            rows="2"
                            placeholder="Tulis catatan atau klarifikasi kekurangan berkas..."
                            class="flex-1 rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:ring-1 focus:ring-primary focus:outline-none"
                            required
                        ></textarea>
                        <Button
                            type="submit"
                            class="self-end"
                            :disabled="
                                catatanForm.processing || !catatanForm.isi
                            "
                        >
                            <Send class="mr-1 h-4 w-4" />
                            {{ replyingTo ? 'Kirim Balasan' : 'Kirim' }}
                        </Button>
                    </div>
                    <p
                        v-if="catatanForm.errors.isi"
                        class="text-xs text-destructive"
                    >
                        {{ catatanForm.errors.isi }}
                    </p>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
