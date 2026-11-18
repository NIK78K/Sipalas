<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
    Building2,
    CheckCircle2,
    AlertCircle,
    FileText,
    ArrowUpRight,
    TrendingUp,
    Coins,
} from '@lucide/vue';

interface SatkerSummary {
    satker_id: number;
    kode: string;
    nama: string;
    total_spm: number;
    spm_lengkap: number;
    spm_kurang: number;
    persen_lengkap: number;
    nilai_transaksi: number;
}

interface TotalsSummary {
    total_spm: number;
    total_lengkap: number;
    total_kurang: number;
    total_nilai: number;
    overall_percent: number;
}

const props = defineProps<{
    satkers: SatkerSummary[];
    totals: TotalsSummary;
    filters: {
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

const tahun = ref(props.filters.tahun || '2026');
const bulan = ref(props.filters.bulan || '');

function applyFilters() {
    router.get(
        '/dashboard',
        {
            tahun: tahun.value || undefined,
            bulan: bulan.value || undefined,
        },
        { preserveState: true, replace: true }
    );
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
    <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: '/dashboard' }]">
        <Head title="Dashboard Pengawasan SPM" />

        <div class="flex flex-col gap-6 p-6">
            <!-- Header & Filter Bar -->
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Dashboard Rekapitulasi SPM</h1>
                    <p class="text-sm text-muted-foreground">
                        Ringkasan progres kelengkapan dokumen SPM pada 12 Satuan Kerja BPS se-Provinsi Riau.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Input
                        v-model="tahun"
                        type="number"
                        placeholder="Tahun"
                        class="w-24 text-center font-semibold"
                        @keydown.enter="applyFilters"
                    />
                    <select
                        v-model="bulan"
                        @change="applyFilters"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                    >
                        <option v-for="m in months" :key="m.value" :value="m.value">
                            {{ m.label }}
                        </option>
                    </select>
                    <Button size="sm" @click="applyFilters">Filter</Button>
                </div>
            </div>

            <!-- Top Metric Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border bg-card p-5 shadow-sm space-y-2">
                    <div class="flex items-center justify-between text-muted-foreground">
                        <span class="text-xs font-semibold uppercase tracking-wider">Total SPM</span>
                        <FileText class="h-4 w-4 text-primary" />
                    </div>
                    <div class="text-3xl font-extrabold font-mono">{{ totals.total_spm }}</div>
                    <p class="text-xs text-muted-foreground">Surat Perintah Membayar terdata</p>
                </div>

                <div class="rounded-xl border bg-card p-5 shadow-sm space-y-2">
                    <div class="flex items-center justify-between text-muted-foreground">
                        <span class="text-xs font-semibold uppercase tracking-wider">Lengkap</span>
                        <CheckCircle2 class="h-4 w-4 text-emerald-600" />
                    </div>
                    <div class="text-3xl font-extrabold font-mono text-emerald-600">{{ totals.total_lengkap }}</div>
                    <p class="text-xs text-muted-foreground">{{ totals.overall_percent }}% dari total SPM</p>
                </div>

                <div class="rounded-xl border bg-card p-5 shadow-sm space-y-2">
                    <div class="flex items-center justify-between text-muted-foreground">
                        <span class="text-xs font-semibold uppercase tracking-wider">Kurang Dokumen</span>
                        <AlertCircle class="h-4 w-4 text-amber-600" />
                    </div>
                    <div class="text-3xl font-extrabold font-mono text-amber-600">{{ totals.total_kurang }}</div>
                    <p class="text-xs text-muted-foreground">Perlu verifikasi atau tindak lanjut</p>
                </div>

                <div class="rounded-xl border bg-card p-5 shadow-sm space-y-2">
                    <div class="flex items-center justify-between text-muted-foreground">
                        <span class="text-xs font-semibold uppercase tracking-wider">Total Nilai Anggaran</span>
                        <Coins class="h-4 w-4 text-primary" />
                    </div>
                    <div class="text-2xl font-extrabold font-mono text-primary truncate" :title="formatRupiah(totals.total_nilai)">
                        {{ formatRupiah(totals.total_nilai) }}
                    </div>
                    <p class="text-xs text-muted-foreground">Realisasi SPM terperiksa</p>
                </div>
            </div>

            <!-- Satker Grid Overview (FR-20) -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold tracking-tight">Ringkasan Kelengkapan 12 Satker</h2>
                    <Button as-child variant="outline" size="sm">
                        <Link href="/kelengkapan">
                            Lihat Rekap List Kelengkapan
                            <ArrowUpRight class="ml-1 h-3.5 w-3.5" />
                        </Link>
                    </Button>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="s in satkers"
                        :key="s.satker_id"
                        class="rounded-xl border bg-card p-5 shadow-sm hover:border-primary/50 transition-colors flex flex-col justify-between gap-4"
                    >
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <Building2 class="h-4 w-4 text-muted-foreground" />
                                    <span class="font-bold text-sm">[{{ s.kode }}]</span>
                                </div>
                                <Badge
                                    :variant="s.persen_lengkap === 100 && s.total_spm > 0 ? 'default' : 'secondary'"
                                    class="text-xs font-mono"
                                >
                                    {{ s.persen_lengkap }}%
                                </Badge>
                            </div>
                            <h3 class="font-semibold text-base line-clamp-1" :title="s.nama">{{ s.nama }}</h3>

                            <!-- Progress Bar -->
                            <div class="w-full bg-muted rounded-full h-2 overflow-hidden mt-2">
                                <div
                                    class="h-full rounded-full transition-all duration-300"
                                    :class="s.persen_lengkap === 100 && s.total_spm > 0 ? 'bg-emerald-600' : 'bg-primary'"
                                    :style="{ width: `${s.persen_lengkap}%` }"
                                ></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2 text-center border-t pt-3 text-xs">
                            <div>
                                <span class="text-muted-foreground block text-[10px] uppercase">Total</span>
                                <span class="font-bold font-mono text-sm">{{ s.total_spm }}</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground block text-[10px] uppercase">Lengkap</span>
                                <span class="font-bold font-mono text-sm text-emerald-600">{{ s.spm_lengkap }}</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground block text-[10px] uppercase">Kurang</span>
                                <span class="font-bold font-mono text-sm text-amber-600">{{ s.spm_kurang }}</span>
                            </div>
                        </div>

                        <div class="border-t pt-3 flex items-center justify-between text-xs">
                            <span class="font-mono text-muted-foreground">{{ formatRupiah(s.nilai_transaksi) }}</span>
                            <Link
                                :href="`/spm?satker_id=${s.satker_id}&tahun=${filters.tahun}${filters.bulan ? `&bulan=${filters.bulan}` : ''}`"
                                class="text-primary hover:underline font-semibold flex items-center gap-1"
                            >
                                Daftar SPM
                                <ArrowUpRight class="h-3 w-3" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
