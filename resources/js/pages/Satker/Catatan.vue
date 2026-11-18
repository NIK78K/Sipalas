<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Building2, Calendar, Plus, Trash2, UserCircle, Send, StickyNote } from '@lucide/vue';

interface SatkerItem {
    id: number;
    kode: string;
    nama: string;
}

interface CatatanItem {
    id: number;
    satker_id: number;
    user_id: number;
    tanggal: string;
    isi: string;
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

interface CatatanPagination {
    data: CatatanItem[];
    current_page: number;
    last_page: number;
    total: number;
    links: PaginationLink[];
}

const props = defineProps<{
    satkers: SatkerItem[];
    selectedSatkerId: number;
    catatans: CatatanPagination;
}>();

const selectedSatker = ref(props.selectedSatkerId);

function onSatkerChange() {
    router.get(
        '/catatan-satker',
        { satker_id: selectedSatker.value },
        { preserveState: true }
    );
}

// Add note form
const form = useForm({
    tanggal: new Date().toISOString().split('T')[0],
    isi: '',
});

function submit() {
    form.post(`/satker/${selectedSatker.value}/catatan`, {
        preserveScroll: true,
        onSuccess: () => {
            form.isi = '';
        },
    });
}

function deleteCatatan(c: CatatanItem) {
    if (confirm('Hapus catatan pengawasan ini?')) {
        router.delete(`/catatan-satker/${c.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Catatan Pengawasan Satker', href: '/catatan-satker' }]">
        <Head title="Catatan Pengawasan Satker (MonPPSPM)" />

        <div class="flex flex-col gap-6 p-6 max-w-5xl mx-auto w-full">
            <!-- Header -->
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Catatan Pengawasan Satuan Kerja</h1>
                    <p class="text-sm text-muted-foreground">
                        Catatan monitoring, evaluasi, dan rekap temuan per satker (pengganti lembar Catatan MonPPSPM).
                    </p>
                </div>
            </div>

            <!-- Satker Selector -->
            <div class="rounded-lg border bg-card p-4 shadow-sm flex flex-col sm:flex-row items-center gap-4">
                <div class="flex items-center gap-2 text-sm font-semibold whitespace-nowrap">
                    <Building2 class="h-5 w-5 text-primary" />
                    <span>Pilih Satker:</span>
                </div>
                <select
                    v-model="selectedSatker"
                    @change="onSatkerChange"
                    class="w-full sm:max-w-md rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                >
                    <option v-for="s in satkers" :key="s.id" :value="s.id">
                        [{{ s.kode }}] {{ s.nama }}
                    </option>
                </select>
            </div>

            <!-- Form Tambah Catatan Baru -->
            <form @submit.prevent="submit" class="rounded-lg border bg-card p-5 shadow-sm space-y-4">
                <div class="flex items-center gap-2 border-b pb-3">
                    <StickyNote class="h-5 w-5 text-primary" />
                    <div>
                        <h2 class="text-base font-bold">Tambah Catatan Evaluasi Pengawasan</h2>
                        <p class="text-xs text-muted-foreground">
                            Simpan temuan atau tindak lanjut pemeriksaan untuk satker terpilih.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                    <div class="space-y-1">
                        <Label for="tanggal">Tanggal Catatan</Label>
                        <Input id="tanggal" type="date" v-model="form.tanggal" required />
                        <p v-if="form.errors.tanggal" class="text-xs text-destructive">{{ form.errors.tanggal }}</p>
                    </div>

                    <div class="space-y-1 sm:col-span-3">
                        <Label for="isi">Isi Evaluasi / Catatan Pengawasan</Label>
                        <textarea
                            id="isi"
                            v-model="form.isi"
                            rows="4"
                            placeholder="Tulis poin-poin evaluasi pemeriksaan satker ini..."
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-primary"
                            required
                        ></textarea>
                        <p v-if="form.errors.isi" class="text-xs text-destructive">{{ form.errors.isi }}</p>
                    </div>
                </div>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="form.processing || !form.isi">
                        <Send class="h-4 w-4 mr-1.5" />
                        Simpan Catatan Satker
                    </Button>
                </div>
            </form>

            <!-- List of Notes -->
            <div class="space-y-4">
                <h3 class="text-sm font-semibold uppercase text-muted-foreground tracking-wider">
                    Riwayat Catatan Evaluasi Satker
                </h3>

                <div v-if="catatans.data.length === 0" class="rounded-lg border bg-card p-8 text-center text-sm text-muted-foreground">
                    Belum ada riwayat catatan pengawasan untuk satker ini.
                </div>

                <div
                    v-for="c in catatans.data"
                    :key="c.id"
                    class="rounded-lg border bg-card p-5 shadow-sm space-y-3"
                >
                    <div class="flex items-center justify-between border-b pb-2">
                        <div class="flex items-center gap-2 text-sm font-semibold">
                            <Calendar class="h-4 w-4 text-primary" />
                            <span>{{ new Date(c.tanggal).toLocaleDateString('id-ID', { dateStyle: 'full' }) }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                <UserCircle class="h-3.5 w-3.5" />
                                <span>{{ c.user?.name }}</span>
                                <Badge variant="outline" class="text-[10px] uppercase">{{ c.user?.role }}</Badge>
                            </div>
                            <Button
                                variant="ghost"
                                size="sm"
                                class="h-7 w-7 p-0 text-destructive hover:bg-destructive/10"
                                title="Hapus catatan"
                                @click="deleteCatatan(c)"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                            </Button>
                        </div>
                    </div>
                    <div class="text-sm whitespace-pre-line text-foreground leading-relaxed">
                        {{ c.isi }}
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="catatans.links && catatans.links.length > 3" class="flex items-center justify-between pt-2">
                    <p class="text-sm text-muted-foreground">
                        Total: {{ catatans.total }} catatan
                    </p>
                    <div class="flex gap-1">
                        <template v-for="(link, i) in catatans.links" :key="i">
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
        </div>
    </AppLayout>
</template>
