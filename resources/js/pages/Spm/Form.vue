<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ArrowLeft, Save, AlertTriangle } from '@lucide/vue';

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
}

const props = defineProps<{
    spm: SpmDetail | null;
    satkers: SatkerItem[];
    accounts: AkunItem[];
}>();

const isEdit = computed(() => !!props.spm);

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

const form = useForm({
    satker_id: props.spm?.satker_id || (props.satkers[0]?.id ?? ''),
    tahun: props.spm?.tahun || 2026,
    bulan: props.spm?.bulan || 1,
    nomor: props.spm?.nomor || '',
    tanggal_pemeriksaan: props.spm?.tanggal_pemeriksaan || new Date().toISOString().split('T')[0],
    jenis: props.spm?.jenis || 'LS',
    akun_belanja_id: props.spm?.akun_belanja_id || (props.accounts[0]?.id ?? ''),
    nilai_transaksi: props.spm?.nilai_transaksi || 0,
    catatan_pemeriksa: props.spm?.catatan_pemeriksa || '',
    link_bukti: props.spm?.link_bukti || '',
    version: props.spm?.version || 1,
});

const initialAkunId = ref(props.spm?.akun_belanja_id);
const showAccountChangeWarning = ref(false);

watch(
    () => form.akun_belanja_id,
    (newVal) => {
        if (isEdit.value && initialAkunId.value && Number(newVal) !== Number(initialAkunId.value)) {
            showAccountChangeWarning.value = true;
        } else {
            showAccountChangeWarning.value = false;
        }
    }
);

function submit() {
    if (isEdit.value && props.spm) {
        form.put(`/spm/${props.spm.id}`);
    } else {
        form.post('/spm');
    }
}
</script>

<template>
    <AppLayout
        :breadcrumbs="[
            { title: 'Daftar SPM', href: '/spm' },
            { title: isEdit ? `Edit SPM ${spm?.nomor}` : 'Tambah SPM', href: '#' },
        ]"
    >
        <Head :title="isEdit ? `Edit SPM ${spm?.nomor}` : 'Tambah SPM Baru'" />

        <div class="flex flex-col gap-6 p-6 max-w-4xl mx-auto w-full">
            <!-- Header -->
            <div class="flex items-center gap-3">
                <Button as-child variant="outline" size="sm">
                    <Link href="/spm">
                        <ArrowLeft class="h-4 w-4 mr-1" />
                        Kembali
                    </Link>
                </Button>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">
                        {{ isEdit ? `Edit Metadata SPM: ${spm?.nomor}` : 'Tambah Surat Perintah Membayar (SPM)' }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Pastikan data satker, nomor SPM, dan akun belanja sesuai dengan SPM fisik.
                    </p>
                </div>
            </div>

            <!-- Form Card -->
            <form @submit.prevent="submit" class="rounded-lg border bg-card p-6 shadow-sm space-y-6">
                <!-- Conflict Error Alert -->
                <div v-if="form.errors.version" class="rounded-md bg-destructive/15 p-4 text-sm text-destructive flex gap-2">
                    <AlertTriangle class="h-5 w-5 shrink-0" />
                    <div>
                        <p class="font-semibold">Konflik Penyimpanan Data</p>
                        <p>{{ form.errors.version }}</p>
                    </div>
                </div>

                <!-- Account Change Warning (FR-14) -->
                <div v-if="showAccountChangeWarning" class="rounded-md bg-amber-500/15 p-4 text-sm text-amber-700 dark:text-amber-400 flex gap-2 border border-amber-500/30">
                    <AlertTriangle class="h-5 w-5 shrink-0" />
                    <div>
                        <p class="font-semibold">Peringatan Penggantian Akun Belanja</p>
                        <p>Mengubah akun belanja akan memperbarui daftar dokumen wajib checklist SPM ini sesuai master akun yang baru.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-1">
                        <Label for="satker_id">Satuan Kerja (Satker) *</Label>
                        <select
                            id="satker_id"
                            v-model="form.satker_id"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                            required
                        >
                            <option v-for="s in satkers" :key="s.id" :value="s.id">
                                [{{ s.kode }}] {{ s.nama }}
                            </option>
                        </select>
                        <p v-if="form.errors.satker_id" class="text-xs text-destructive">{{ form.errors.satker_id }}</p>
                    </div>

                    <div class="space-y-1">
                        <Label for="nomor">Nomor SPM *</Label>
                        <Input
                            id="nomor"
                            v-model="form.nomor"
                            placeholder="Contoh: 00001A atau 00012A"
                            required
                        />
                        <p v-if="form.errors.nomor" class="text-xs text-destructive">{{ form.errors.nomor }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="space-y-1">
                        <Label for="tahun">Tahun Anggaran *</Label>
                        <Input
                            id="tahun"
                            type="number"
                            v-model="form.tahun"
                            min="2020"
                            max="2099"
                            required
                        />
                        <p v-if="form.errors.tahun" class="text-xs text-destructive">{{ form.errors.tahun }}</p>
                    </div>

                    <div class="space-y-1">
                        <Label for="bulan">Bulan *</Label>
                        <select
                            id="bulan"
                            v-model="form.bulan"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                            required
                        >
                            <option v-for="m in months" :key="m.value" :value="m.value">
                                {{ m.label }}
                            </option>
                        </select>
                        <p v-if="form.errors.bulan" class="text-xs text-destructive">{{ form.errors.bulan }}</p>
                    </div>

                    <div class="space-y-1">
                        <Label for="jenis">Jenis SPM *</Label>
                        <select
                            id="jenis"
                            v-model="form.jenis"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                            required
                        >
                            <option value="LS">LS</option>
                            <option value="GUP/PTUP">GUP/PTUP</option>
                            <option value="UP/PTUP">UP/TUP</option>
                        </select>
                        <p v-if="form.errors.jenis" class="text-xs text-destructive">{{ form.errors.jenis }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-1">
                        <Label for="akun_belanja_id">Akun Belanja *</Label>
                        <select
                            id="akun_belanja_id"
                            v-model="form.akun_belanja_id"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                            required
                        >
                            <option v-for="a in accounts" :key="a.id" :value="a.id">
                                {{ a.nama }}
                            </option>
                        </select>
                        <p v-if="form.errors.akun_belanja_id" class="text-xs text-destructive">{{ form.errors.akun_belanja_id }}</p>
                    </div>

                    <div class="space-y-1">
                        <Label for="nilai_transaksi">Nilai Transaksi (Rp) *</Label>
                        <Input
                            id="nilai_transaksi"
                            type="number"
                            v-model="form.nilai_transaksi"
                            min="0"
                            required
                        />
                        <p v-if="form.errors.nilai_transaksi" class="text-xs text-destructive">{{ form.errors.nilai_transaksi }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-1">
                        <Label for="tanggal_pemeriksaan">Tanggal Pemeriksaan Dokumen</Label>
                        <Input
                            id="tanggal_pemeriksaan"
                            type="date"
                            v-model="form.tanggal_pemeriksaan"
                        />
                        <p v-if="form.errors.tanggal_pemeriksaan" class="text-xs text-destructive">{{ form.errors.tanggal_pemeriksaan }}</p>
                    </div>

                    <div class="space-y-1">
                        <Label for="link_bukti">Tautan Bukti Dukung (Google Drive / Cloud)</Label>
                        <Input
                            id="link_bukti"
                            type="url"
                            v-model="form.link_bukti"
                            placeholder="https://drive.google.com/..."
                        />
                        <p v-if="form.errors.link_bukti" class="text-xs text-destructive">{{ form.errors.link_bukti }}</p>
                    </div>
                </div>

                <div class="space-y-1">
                    <Label for="catatan_pemeriksa">Uraian Belanja</Label>
                    <textarea
                        id="catatan_pemeriksa"
                        v-model="form.catatan_pemeriksa"
                        rows="3"
                        placeholder="Tulis uraian belanja untuk SPM ini..."
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-primary"
                    ></textarea>
                    <p v-if="form.errors.catatan_pemeriksa" class="text-xs text-destructive">{{ form.errors.catatan_pemeriksa }}</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t">
                    <Button as-child variant="outline">
                        <Link href="/spm">Batal</Link>
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Save class="mr-2 h-4 w-4" />
                        {{ isEdit ? 'Simpan Perubahan' : 'Buat SPM Baru' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
