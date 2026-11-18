<script setup lang="ts">
import { ref } from 'vue';
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
    ChevronDown,
    ChevronUp,
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

interface DocumentDetail {
    id: number;
    urutan: number;
    nama: string;
    fulfilled_count: number;
    unfulfilled_count: number;
    percent: number;
    missing_spms: MissingSpm[];
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
    '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];

const expandedDocIds = ref<number[]>([]);

function toggleExpand(docId: number) {
    const idx = expandedDocIds.value.indexOf(docId);
    if (idx === -1) {
        expandedDocIds.value.push(docId);
    } else {
        expandedDocIds.value.splice(idx, 1);
    }
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

        <div class="flex flex-col gap-6 p-6 max-w-6xl mx-auto w-full">
            <!-- Header -->
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div class="flex items-center gap-3">
                    <Button as-child variant="outline" size="sm">
                        <Link :href="`/kelengkapan?satker_id=${filters.satker_id}&tahun=${filters.tahun}&bulan=${filters.bulan}`">
                            <ArrowLeft class="h-4 w-4 mr-1" />
                            Kembali
                        </Link>
                    </Button>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-2xl font-bold tracking-tight">{{ account.nama }}</h1>
                            <Badge variant="outline" class="font-mono">{{ account.kode }}</Badge>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            Detail pemenuhan tiap dokumen wajib untuk akun belanja ini (Total {{ total_spm }} SPM terdata).
                        </p>
                    </div>
                </div>
            </div>

            <!-- Documents Table -->
            <div class="overflow-x-auto rounded-lg border bg-card shadow-sm">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-muted/40 text-xs font-semibold uppercase text-muted-foreground">
                        <tr>
                            <th class="w-16 px-4 py-3 text-center">Urutan</th>
                            <th class="px-4 py-3">Nama Dokumen Wajib</th>
                            <th class="w-32 px-4 py-3 text-center">Pemenuhan</th>
                            <th class="w-40 px-4 py-3 text-center">Persentase</th>
                            <th class="w-36 px-4 py-3 text-right">SPM Belum Lengkap</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="documents.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                                Belum ada dokumen wajib untuk akun belanja ini.
                            </td>
                        </tr>
                        <template v-for="doc in documents" :key="doc.id">
                            <tr class="hover:bg-muted/30">
                                <td class="px-4 py-3 text-center font-mono font-medium text-muted-foreground">
                                    {{ doc.urutan }}
                                </td>
                                <td class="px-4 py-3 font-medium">
                                    {{ doc.nama }}
                                </td>
                                <td class="px-4 py-3 text-center font-mono text-sm">
                                    <span class="font-bold text-emerald-600">{{ doc.fulfilled_count }}</span>
                                    <span class="text-muted-foreground"> / {{ total_spm }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center gap-2">
                                        <div class="w-full bg-muted rounded-full h-2 overflow-hidden">
                                            <div
                                                class="h-full rounded-full transition-all"
                                                :class="doc.percent === 100 && total_spm > 0 ? 'bg-emerald-600' : 'bg-primary'"
                                                :style="{ width: `${doc.percent}%` }"
                                            ></div>
                                        </div>
                                        <span class="font-mono text-xs w-12 text-right font-medium">
                                            {{ doc.percent }}%
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Button
                                        v-if="doc.missing_spms.length > 0"
                                        variant="ghost"
                                        size="sm"
                                        class="text-amber-700 dark:text-amber-400 font-semibold text-xs"
                                        @click="toggleExpand(doc.id)"
                                    >
                                        {{ doc.missing_spms.length }} SPM
                                        <component
                                            :is="expandedDocIds.includes(doc.id) ? ChevronUp : ChevronDown"
                                            class="ml-1 h-3.5 w-3.5"
                                        />
                                    </Button>
                                    <span v-else class="text-xs text-emerald-600 font-medium">
                                        Lengkap 100%
                                    </span>
                                </td>
                            </tr>

                            <!-- Expandable Missing SPMs Row (FR-18) -->
                            <tr v-if="expandedDocIds.includes(doc.id) && doc.missing_spms.length > 0">
                                <td colspan="5" class="bg-muted/20 px-6 py-4 border-b">
                                    <div class="space-y-2">
                                        <div class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                                            Daftar SPM yang belum memenuhi dokumen: {{ doc.nama }}
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <Link
                                                v-for="mspm in doc.missing_spms"
                                                :key="mspm.id"
                                                :href="`/spm/${mspm.id}`"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md border bg-card text-xs hover:border-primary transition-colors font-mono"
                                            >
                                                <span class="font-bold text-primary">{{ mspm.nomor }}</span>
                                                <span class="text-muted-foreground text-[10px]">
                                                    [{{ mspm.satker_kode }} &bull; {{ months[mspm.bulan] }}]
                                                </span>
                                                <ExternalLink class="h-3 w-3 opacity-60" />
                                            </Link>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
