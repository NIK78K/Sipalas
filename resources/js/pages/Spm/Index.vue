<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Plus, Search, Filter, Eye, Edit3, Trash2, FileCheck2, ExternalLink, Download } from '@lucide/vue';

interface SatkerItem {
    id: number;
    kode: string;
    nama: string;
}

interface AkunItem {
    id: number;
    kode: string;
    nama: string;
}

interface SpmItem {
    id: number;
    satker_id: number;
    tahun: number;
    bulan: number;
    nomor: string;
    tanggal_pemeriksaan: string | null;
    jenis: string;
    nilai_transaksi: number;
    checked_count: number;
    total_docs_count: number;
    is_lengkap: boolean;
    status_label: string;
    satker?: SatkerItem;
    akun_belanja?: AkunItem;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface SpmPagination {
    data: SpmItem[];
    current_page: number;
    last_page: number;
    total: number;
    links: PaginationLink[];
}

const props = defineProps<{
    spms: SpmPagination;
    satkers: SatkerItem[];
    accounts: AkunItem[];
    filters: {
        search?: string;
        satker_id?: string;
        tahun?: string;
        bulan?: string;
        jenis?: string;
        akun_belanja_id?: string;
        status?: string;
    };
}>();

const page = usePage();
const currentUser = page.props.auth?.user;
const canExamine = currentUser?.role === 'operator' || currentUser?.role === 'admin';

const months = [
    { value: 1, label: 'Januari' },
    { value: 2, label: 'Februari' },
    { value: 3, label: 'Maret' },
    { value: 4, label: 'April' },
    { value: 5, label: 'Mei' },
    { value: 6, label: 'Juni' },
    { value: 7, label: 'Juli' },
    { value: 8, label: 'Agustus' },
    { value: 9, label: 'September' },
    { value: 10, label: 'Oktober' },
    { value: 11, label: 'November' },
    { value: 12, label: 'Desember' },
];

const search = ref(props.filters.search || '');
const satkerId = ref(props.filters.satker_id || '');
const tahun = ref(props.filters.tahun || '2026');
const bulan = ref(props.filters.bulan || '');
const jenis = ref(props.filters.jenis || '');
const akunId = ref(props.filters.akun_belanja_id || '');
const status = ref(props.filters.status || '');

function applyFilters() {
    router.get(
        '/spm',
        {
            search: search.value || undefined,
            satker_id: satkerId.value || undefined,
            tahun: tahun.value || undefined,
            bulan: bulan.value || undefined,
            jenis: jenis.value || undefined,
            akun_belanja_id: akunId.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true }
    );
}

function resetFilters() {
    search.value = '';
    satkerId.value = '';
    tahun.value = '2026';
    bulan.value = '';
    jenis.value = '';
    akunId.value = '';
    status.value = '';
    applyFilters();
}

function formatRupiah(val: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val);
}

function deleteSpm(item: SpmItem) {
    if (confirm(`Hapus SPM No ${item.nomor}? Tindakan ini menggunakan soft-delete.`)) {
        router.delete(`/spm/${item.id}`, { preserveScroll: true });
    }
}

function exportExcel() {
    const params = new URLSearchParams();
    if (search.value) params.append('search', search.value);
    if (satkerId.value) params.append('satker_id', satkerId.value);
    if (tahun.value) params.append('tahun', tahun.value);
    if (bulan.value) params.append('bulan', bulan.value);
    if (jenis.value) params.append('jenis', jenis.value);
    if (akunId.value) params.append('akun_belanja_id', akunId.value);
    if (status.value) params.append('status', status.value);

    window.open(`/ekspor/spm?${params.toString()}`, '_blank');
}
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Daftar SPM', href: '/spm' }]">
        <Head title="Daftar SPM" />

        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Daftar Surat Perintah Membayar (SPM)</h1>
                    <p class="text-sm text-muted-foreground">
                        Pemeriksaan kelengkapan dokumen pengeluaran anggaran per satker dan periode.
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" size="sm" @click="exportExcel">
                        <Download class="mr-1.5 h-4 w-4" />
                        Ekspor Excel
                    </Button>
                    <Button v-if="canExamine" as-child size="sm">
                        <Link href="/spm/create">
                            <Plus class="mr-1.5 h-4 w-4" />
                            Tambah SPM
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Filters Bar -->
            <div class="rounded-lg border bg-card p-4 shadow-sm space-y-3">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                    <div class="relative sm:col-span-2">
                        <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            v-model="search"
                            placeholder="Cari nomor SPM (misal: 00001A)..."
                            class="pl-9"
                            @keydown.enter="applyFilters"
                        />
                    </div>
                    <div>
                        <select
                            v-model="satkerId"
                            @change="applyFilters"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                        >
                            <option value="">Semua Satker</option>
                            <option v-for="s in satkers" :key="s.id" :value="s.id">
                                [{{ s.kode }}] {{ s.nama }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <select
                            v-model="bulan"
                            @change="applyFilters"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                        >
                            <option value="">Semua Bulan</option>
                            <option v-for="m in months" :key="m.value" :value="m.value">
                                {{ m.label }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                    <div>
                        <Input
                            v-model="tahun"
                            type="number"
                            placeholder="Tahun"
                            @keydown.enter="applyFilters"
                        />
                    </div>
                    <div>
                        <select
                            v-model="jenis"
                            @change="applyFilters"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                        >
                            <option value="">Semua Jenis SPM</option>
                            <option value="LS">LS</option>
                            <option value="GUP/PTUP">GUP/PTUP</option>
                            <option value="UP/PTUP">UP/PTUP</option>
                        </select>
                    </div>
                    <div>
                        <select
                            v-model="akunId"
                            @change="applyFilters"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                        >
                            <option value="">Semua Akun Belanja</option>
                            <option v-for="a in accounts" :key="a.id" :value="a.id">
                                {{ a.nama }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <select
                            v-model="status"
                            @change="applyFilters"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                        >
                            <option value="">Semua Status Kelengkapan</option>
                            <option value="lengkap">Lengkap</option>
                            <option value="kurang">Kurang Dokumen</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-1">
                    <Button variant="outline" size="sm" @click="resetFilters">Reset Filter</Button>
                    <Button size="sm" @click="applyFilters">Terapkan Filter</Button>
                </div>
            </div>

            <!-- SPM Table -->
            <div class="overflow-x-auto rounded-lg border bg-card shadow-sm">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-muted/40 text-xs font-semibold uppercase text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">No SPM</th>
                            <th class="px-4 py-3">Satker</th>
                            <th class="px-4 py-3">Bulan / Thn</th>
                            <th class="px-4 py-3">Jenis</th>
                            <th class="px-4 py-3">Akun Belanja</th>
                            <th class="px-4 py-3 text-right">Nilai Transaksi</th>
                            <th class="px-4 py-3 text-center">Progres Dokumen</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="spms.data.length === 0">
                            <td colspan="9" class="px-4 py-8 text-center text-muted-foreground">
                                Tidak ada data SPM yang sesuai filter.
                            </td>
                        </tr>
                        <tr v-for="spm in spms.data" :key="spm.id" class="hover:bg-muted/30">
                            <td class="px-4 py-3 font-mono font-bold">
                                <Link :href="`/spm/${spm.id}`" class="text-primary hover:underline flex items-center gap-1">
                                    {{ spm.nomor }}
                                    <ExternalLink class="h-3 w-3 opacity-60" />
                                </Link>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-medium">[{{ spm.satker?.kode }}]</span>
                                <span class="text-xs text-muted-foreground block">{{ spm.satker?.nama }}</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ months.find((m) => m.value === spm.bulan)?.label }} {{ spm.tahun }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge variant="outline" class="font-mono">{{ spm.jenis }}</Badge>
                            </td>
                            <td class="px-4 py-3 max-w-[220px] truncate" :title="spm.akun_belanja?.nama">
                                {{ spm.akun_belanja?.nama }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-medium">
                                {{ formatRupiah(spm.nilai_transaksi) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-mono text-xs font-semibold">
                                    {{ spm.checked_count }} / {{ spm.total_docs_count }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <Badge
                                    :variant="spm.is_lengkap ? 'default' : 'secondary'"
                                    class="text-xs"
                                >
                                    {{ spm.status_label }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <Button as-child variant="outline" size="sm" title="Periksa / Detail">
                                        <Link :href="`/spm/${spm.id}`">
                                            <FileCheck2 class="h-3.5 w-3.5" />
                                        </Link>
                                    </Button>
                                    <Button
                                        v-if="canExamine"
                                        as-child
                                        variant="outline"
                                        size="sm"
                                        title="Edit Metadata SPM"
                                    >
                                        <Link :href="`/spm/${spm.id}/edit`">
                                            <Edit3 class="h-3.5 w-3.5" />
                                        </Link>
                                    </Button>
                                    <Button
                                        v-if="canExamine"
                                        variant="destructive"
                                        size="sm"
                                        title="Hapus SPM"
                                        @click="deleteSpm(spm)"
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
            <div v-if="spms.links.length > 3" class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    Total: {{ spms.total }} SPM
                </p>
                <div class="flex gap-1">
                    <template v-for="(link, i) in spms.links" :key="i">
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
