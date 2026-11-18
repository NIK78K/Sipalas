<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
    FileSpreadsheet,
    Download,
    Eye,
    CheckCircle2,
    Building2,
    ArrowUpRight,
} from '@lucide/vue';

interface SatkerItem {
    id: number;
    kode: string;
    nama: string;
}

interface AkunSummary {
    id: number;
    kode: string;
    nama: string;
    total_docs: number;
    total_spm: number;
    spm_lengkap: number;
    spm_kurang: number;
    persen_lengkap: number;
    nilai_transaksi: number;
}

const props = defineProps<{
    accounts: AkunSummary[];
    satkers: SatkerItem[];
    filters: {
        satker_id: string;
        tahun: string;
        bulan: string;
    };
}>();

const months = [
    { value: '', label: 'Semua Bulan (Jan - Des)' },
    { value: '1', label: 'Januari' },
    { value: '2', label: 'Februari' },
    { value: '3', label: 'Maret' },
    { value: '4', label: 'April' },
    { value: '5', label: 'Mei' },
    { value: '6', label: 'Juni' },
    { value: '7', label: 'Juli' },
    { value: '8', label: 'Agustus' },
    { value: '9', label: 'September' },
    { value: '10', label: 'Oktober' },
    { value: '11', label: 'November' },
    { value: '12', label: 'Desember' },
];

const satkerId = ref(props.filters.satker_id || '');
const tahun = ref(props.filters.tahun || '2026');
const bulan = ref(props.filters.bulan || '');

function applyFilters() {
    router.get(
        '/kelengkapan',
        {
            satker_id: satkerId.value || undefined,
            tahun: tahun.value || undefined,
            bulan: bulan.value || undefined,
        },
        { preserveState: true, replace: true }
    );
}

function resetFilters() {
    satkerId.value = '';
    tahun.value = '2026';
    bulan.value = '';
    applyFilters();
}

function exportExcel() {
    const params = new URLSearchParams();
    if (satkerId.value) params.append('satker_id', satkerId.value);
    if (tahun.value) params.append('tahun', tahun.value);
    if (bulan.value) params.append('bulan', bulan.value);

    window.open(`/ekspor/kelengkapan?${params.toString()}`, '_blank');
}

function formatRupiah(val: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val);
}
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'List Kelengkapan', href: '/kelengkapan' }]">
        <Head title="Rekapitulasi List Kelengkapan" />

        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Rekapitulasi List Kelengkapan</h1>
                    <p class="text-sm text-muted-foreground">
                        Ringkasan otomatis persentase kelengkapan dokumen berdasarkan 35 akun belanja.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" @click="exportExcel">
                        <Download class="mr-1.5 h-4 w-4" />
                        Ekspor Rekap Excel
                    </Button>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="rounded-lg border bg-card p-4 shadow-sm flex flex-col sm:flex-row gap-3 items-center">
                <div class="w-full sm:w-64">
                    <select
                        v-model="satkerId"
                        @change="applyFilters"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                    >
                        <option value="">Semua Satker (Provinsi)</option>
                        <option v-for="s in satkers" :key="s.id" :value="s.id">
                            [{{ s.kode }}] {{ s.nama }}
                        </option>
                    </select>
                </div>

                <div class="w-full sm:w-28">
                    <Input
                        v-model="tahun"
                        type="number"
                        placeholder="Tahun"
                        @keydown.enter="applyFilters"
                    />
                </div>

                <div class="w-full sm:w-56">
                    <select
                        v-model="bulan"
                        @change="applyFilters"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                    >
                        <option v-for="m in months" :key="m.value" :value="m.value">
                            {{ m.label }}
                        </option>
                    </select>
                </div>

                <div class="flex gap-2 w-full sm:w-auto ml-auto">
                    <Button variant="outline" size="sm" @click="resetFilters">Reset</Button>
                    <Button size="sm" @click="applyFilters">Terapkan</Button>
                </div>
            </div>

            <!-- Summary Table -->
            <div class="overflow-x-auto rounded-lg border bg-card shadow-sm">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-muted/40 text-xs font-semibold uppercase text-muted-foreground">
                        <tr>
                            <th class="w-20 px-4 py-3">Kode</th>
                            <th class="px-4 py-3">Nama Akun Belanja</th>
                            <th class="w-24 px-4 py-3 text-center">Dokumen</th>
                            <th class="w-24 px-4 py-3 text-center">Total SPM</th>
                            <th class="w-24 px-4 py-3 text-center">Lengkap</th>
                            <th class="w-24 px-4 py-3 text-center">Kurang</th>
                            <th class="w-36 px-4 py-3 text-center">Persentase</th>
                            <th class="px-4 py-3 text-right">Nilai Transaksi</th>
                            <th class="w-28 px-4 py-3 text-right">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="accounts.length === 0">
                            <td colspan="9" class="px-4 py-8 text-center text-muted-foreground">
                                Tidak ada akun belanja.
                            </td>
                        </tr>
                        <tr v-for="acc in accounts" :key="acc.id" class="hover:bg-muted/30">
                            <td class="px-4 py-3 font-mono font-semibold">{{ acc.kode }}</td>
                            <td class="px-4 py-3 font-medium">
                                <Link
                                    :href="`/kelengkapan/${acc.id}?satker_id=${filters.satker_id}&tahun=${filters.tahun}&bulan=${filters.bulan}`"
                                    class="text-primary hover:underline"
                                >
                                    {{ acc.nama }}
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-center font-mono text-xs">
                                {{ acc.total_docs }}
                            </td>
                            <td class="px-4 py-3 text-center font-mono font-semibold">
                                {{ acc.total_spm }}
                            </td>
                            <td class="px-4 py-3 text-center font-mono text-emerald-600 font-semibold">
                                {{ acc.spm_lengkap }}
                            </td>
                            <td class="px-4 py-3 text-center font-mono text-amber-600 font-semibold">
                                {{ acc.spm_kurang }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center gap-2">
                                    <div class="w-full bg-muted rounded-full h-2 overflow-hidden">
                                        <div
                                            class="h-full rounded-full transition-all"
                                            :class="acc.persen_lengkap === 100 && acc.total_spm > 0 ? 'bg-emerald-600' : 'bg-primary'"
                                            :style="{ width: `${acc.persen_lengkap}%` }"
                                        ></div>
                                    </div>
                                    <span class="font-mono text-xs w-12 text-right font-medium">
                                        {{ acc.persen_lengkap }}%
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-xs">
                                {{ formatRupiah(acc.nilai_transaksi) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Button as-child variant="outline" size="sm">
                                    <Link
                                        :href="`/kelengkapan/${acc.id}?satker_id=${filters.satker_id}&tahun=${filters.tahun}&bulan=${filters.bulan}`"
                                    >
                                        <Eye class="h-3.5 w-3.5 mr-1" />
                                        Per Dokumen
                                    </Link>
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
