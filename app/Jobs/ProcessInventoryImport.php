<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\FacilityUploadInventory;

class ProcessInventoryImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $importId;
    public $filePath;
    public $timeout = 300; // 5 minutes timeout

    /**
     * Create a new job instance.
     */
    public function __construct(string $importId, string $filePath)
    {
        $this->importId = $importId;
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting queued inventory import', [
                'import_id' => $this->importId,
                'file_path' => $this->filePath
            ]);

            // Check if file exists
            if (!Storage::disk('local')->exists($this->filePath)) {
                throw new \Exception('Import file not found: ' . $this->filePath);
            }

            $fullPath = storage_path('app/' . $this->filePath);

            // Process the import
            Excel::import(new FacilityUploadInventory($this->importId), $fullPath);

            // Clean up the file after successful import
            Storage::disk('local')->delete($this->filePath);

            Log::info('Inventory import completed successfully', [
                'import_id' => $this->importId,
                'file_path' => $this->filePath
            ]);

        } catch (\Exception $e) {
            Log::error('Inventory import job failed', [
                'import_id' => $this->importId,
                'file_path' => $this->filePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Clean up file on failure
            if (Storage::disk('local')->exists($this->filePath)) {
                Storage::disk('local')->delete($this->filePath);
            }

            // Update cache with error
            Cache::put($this->importId, -1); // -1 indicates error

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Inventory import job failed', [
            'import_id' => $this->importId,
            'file_path' => $this->filePath,
            'error' => $exception->getMessage()
        ]);

        // Clean up file on failure
        if (Storage::disk('local')->exists($this->filePath)) {
            Storage::disk('local')->delete($this->filePath);
        }

        // Update cache with error
        Cache::put($this->importId, -1);
    }
} 