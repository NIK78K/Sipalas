<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    ArrowLeft,
    FileSpreadsheet,
    CheckCircle2,
    AlertCircle,
    ExternalLink,
} from '@lucide/vue';

interface MissingSpm {
    id: number;
    nomor: string;
    satker_kode: string;
    satker_nama: string;
    tahun: number;
    bulan: number;
    jenis: string;
    nilai_transaksi: number;
}

interface DetailSpm extends MissingSpm {
    uraian_belanja: string | null;
    catatan_pengawas: string[];
    balasan_catatan: string[];
    link_bukti: string | null;
    is_checked: boolean;
    document_statuses: Record<number, 'sesuai' | 'tidak_sesuai' | '-' | null>;
}

interface DocumentDetail {
    id: number;
    urutan: number;
    nama: string;
    fulfilled_count: number;
    unfulfilled_count: number;
    percent: number;
    missing_spms: MissingSpm[];
    detail_spms: DetailSpm[];
}

interface SatkerItem {
    id: number;
    kode: string;
    nama: string;
}

interface AkunBelanja {
    id: number;
    kode: string;
    nama: string;
}

const props = defineProps<{
    account: AkunBelanja;
    total_spm: number;
    documents: DocumentDetail[];
    satkers: SatkerItem[];
    filters: {
        satker_id: string;
        tahun: string;
        bulan: string;
    };
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

const detailSpms = computed(() => props.documents[0]?.detail_spms ?? []);

function formatRupiah(val: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val);
}
</script>

<template>
    <AppLayout
        :breadcrumbs="[
            { title: 'List Kelengkapan', href: '/kelengkapan' },
            { title: account.nama, href: `/kelengkapan/${account.id}` },
        ]"
    >
        <Head :title="`Detail Rekap - ${account.nama}`" />

        <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 p-6">
            <!-- Header -->
            <div
                class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
            >
                <div class="flex items-center gap-3">
                    <Button as-child variant="outline" size="sm">
                        <Link
                            :href="`/kelengkapan?satker_id=${filters.satker_id}&tahun=${filters.tahun}&bulan=${filters.bulan}`"
                        >
                            <ArrowLeft class="mr-1 h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-2xl font-bold tracking-tight">
                                {{ account.nama }}
                            </h1>
                            <Badge variant="outline" class="font-mono">{{
                                account.kode
                            }}</Badge>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            Detail pemenuhan tiap dokumen wajib untuk akun
                            belanja ini (Total {{ total_spm }} SPM terdata).
                        </p>
                    </div>
                </div>
            </div>

            <!-- SPM Detail Table -->
            <div class="overflow-x-auto rounded-lg border bg-card shadow-sm">
                <table class="w-full min-w-max text-left text-xs">
                    <thead
                        class="border-b bg-muted/40 text-[10px] text-muted-foreground uppercase"
                    >
                        <tr>
                            <th
                                class="sticky left-0 z-10 w-48 border-r border-border bg-muted/40 px-3 py-3"
                            >
                                Uraian Belanja
                            </th>
                            <th
                                class="sticky left-48 z-10 w-24 border-r border-border bg-muted/40 px-3 py-3"
                            >
                                Kode Akun
                            </th>
                            <th
                                class="sticky left-[288px] z-10 w-28 border-r border-border bg-muted/40 px-3 py-3 text-right"
                            >
                                Nilai
                            </th>
                            <th
                                class="sticky left-[400px] z-10 w-24 border-r border-border bg-muted/40 px-3 py-3"
                            >
                                Jenis
                            </th>
                            <th
                                class="sticky left-[496px] z-10 w-28 border-r border-border bg-muted/40 px-3 py-3"
                            >
                                No SPM
                            </th>
                            <th
                                class="sticky left-[608px] z-10 w-20 border-r-2 border-border bg-muted/40 px-3 py-3"
                            >
                                Link
                            </th>
                            <th
                                v-for="document in documents"
                                :key="document.id"
                                class="min-w-28 px-3 py-3 text-center"
                            >
                                {{ document.nama }}
                            </th>
                            <th class="min-w-64 px-3 py-3">Catatan Pengawas</th>
                            <th class="min-w-64 px-3 py-3">Balasan Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="detailSpms.length === 0">
                            <td
                                :colspan="8 + documents.length"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                Belum ada SPM untuk filter ini.
                            </td>
                        </tr>
                        <tr
                            v-for="mspm in detailSpms"
                            :key="mspm.id"
                            class="hover:bg-muted/20"
                        >
                            <td
                                class="sticky left-0 z-10 w-48 border-r border-border bg-card px-3 py-3 align-top"
                            >
                                <p class="whitespace-pre-line">
                                    {{ mspm.uraian_belanja || '-' }}
                                </p>
                            </td>
                            <td
                                class="sticky left-48 z-10 w-24 border-r border-border bg-card px-3 py-3 align-top font-mono"
                            >
                                {{ account.kode }}
                            </td>
                            <td
                                class="sticky left-[288px] z-10 w-28 border-r border-border bg-card px-3 py-3 text-right align-top font-mono"
                            >
                                {{ formatRupiah(mspm.nilai_transaksi) }}
                            </td>
                            <td
                                class="sticky left-[400px] z-10 w-24 border-r border-border bg-card px-3 py-3 align-top"
                            >
                                {{ mspm.jenis }}
                            </td>
                            <td
                                class="sticky left-[496px] z-10 w-28 border-r border-border bg-card px-3 py-3 align-top"
                            >
                                <Link
                                    :href="`/spm/${mspm.id}`"
                                    class="font-mono font-bold text-primary hover:underline"
                                >
                                    {{ mspm.nomor }}
                                </Link>
                            </td>
                            <td
                                class="sticky left-[608px] z-10 w-20 border-r-2 border-border bg-card px-3 py-3 align-top"
                            >
                                <a
                                    v-if="mspm.link_bukti"
                                    :href="mspm.link_bukti"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1 text-primary hover:underline"
                                >
                                    Bukti
                                    <ExternalLink class="h-3 w-3" />
                                </a>
                                <span v-else class="text-muted-foreground"
                                    >-</span
                                >
                            </td>
                            <td
                                v-for="document in documents"
                                :key="document.id"
                                class="min-w-28 px-3 py-3 text-center align-top"
                            >
                                <Badge
                                    :variant="
                                        mspm.document_statuses[document.id] ===
                                        'sesuai'
                                            ? 'default'
                                            : mspm.document_statuses[
                                                    document.id
                                                ] === 'tidak_sesuai'
                                              ? 'destructive'
                                              : mspm.document_statuses[
                                                      document.id
                                                  ] === '-'
                                                ? 'outline'
                                                : 'secondary'
                                    "
                                    class="text-[10px]"
                                >
                                    {{
                                        mspm.document_statuses[document.id] ===
                                        'sesuai'
                                            ? 'Sesuai'
                                            : mspm.document_statuses[
                                                    document.id
                                                ] === 'tidak_sesuai'
                                              ? 'Tidak Sesuai'
                                              : mspm.document_statuses[
                                                      document.id
                                                  ] === '-'
                                                ? '-'
                                                : 'Belum Dinilai'
                                    }}
                                </Badge>
                            </td>
                            <td class="min-w-64 px-3 py-3 align-top">
                                <ul
                                    v-if="mspm.catatan_pengawas.length"
                                    class="space-y-1 whitespace-pre-line"
                                >
                                    <li
                                        v-for="(catatan, index) in mspm.catatan_pengawas"
                                        :key="index"
                                    >
                                        {{ catatan }}
                                    </li>
                                </ul>
                                <span v-else class="text-muted-foreground"
                                    >-</span
                                >
                            </td>
                            <td class="min-w-64 px-3 py-3 align-top">
                                <ul
                                    v-if="mspm.balasan_catatan.length"
                                    class="space-y-1 whitespace-pre-line"
                                >
                                    <li
                                        v-for="(balasan, index) in mspm.balasan_catatan"
                                        :key="index"
                                    >
                                        {{ balasan }}
                                    </li>
                                </ul>
                                <span v-else class="text-muted-foreground"
                                    >-</span
                                >
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
