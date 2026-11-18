<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { login } from '@/routes';

defineOptions({
    layout: {
        title: 'Registrasi Ditutup',
        description: 'Registrasi mandiri tidak tersedia pada sistem ini.',
    },
});
</script>

<template>
    <Head title="Registrasi Ditutup" />
    <div class="space-y-4 text-center">
        <p class="text-sm text-muted-foreground">
            Pendaftaran akun dilakukan secara terpusat oleh Operator Sistem.
        </p>
        <Button as-child class="w-full">
            <Link :href="login()">Kembali ke Halaman Login</Link>
        </Button>
    </div>
</template>
