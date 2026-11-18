<?php

namespace App\Jobs;

use App\Models\ImportLog;
use App\Services\Excel\CatatanPemeriksaParser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Throwable;

class ProcessCatatanPemeriksaImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Timeout in seconds (give ample time for 4,800+ rows).
     */
    public int $timeout = 600;

    public function __construct(
        public int $importLogId,
        public string $filePath
    ) {}

    public function handle(CatatanPemeriksaParser $parser): void
    {
        $log = ImportLog::find($this->importLogId);
        if (! $log) {
            return;
        }

        $log->update(['status' => 'processing']);

        try {
            $parser->import($this->filePath, $log, function (int $berhasil, int $dilewati) use ($log) {
                $log->update([
                    'berhasil' => $berhasil,
                    'dilewati' => $dilewati,
                ]);
            });
        } catch (Throwable $e) {
            $log->update([
                'status' => 'failed',
                'laporan' => [
                    'error' => $e->getMessage(),
                    'trace' => substr($e->getTraceAsString(), 0, 1000),
                ],
            ]);
        } finally {
            // Remove uploaded temp file if stored in temporary folder
            if (File::exists($this->filePath) && str_contains($this->filePath, 'app/private/imports')) {
                File::delete($this->filePath);
            }
        }
    }
}
