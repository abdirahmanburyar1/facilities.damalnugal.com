<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Jobs\GenerateMonthlyInventoryReportJob;

class GenerateMonthlyInventoryReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:generate-monthly-report 
                            {--facility= : Generate report for specific facility ID}
                            {--year= : Year for the report (default: current year)}
                            {--month= : Month for the report (default: current month)}
                            {--force : Force regeneration of existing reports}
                            {--sync : Run synchronously instead of queuing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly inventory reports for all facilities';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $facilityId = $this->option('facility');
        $year = (int) ($this->option('year') ?? now()->year);
        $month = (int) ($this->option('month') ?? now()->month);
        $force = $this->option('force');
        $sync = $this->option('sync');

        try {
            // Create report period in YYYY-MM format
            $reportPeriod = sprintf('%04d-%02d', $year, $month);
            
            if ($sync) {
                // Run synchronously for debugging
                $job = new GenerateMonthlyInventoryReportJob($facilityId, $reportPeriod, $force);
                $job->handle();
                $this->info("✅ Job executed synchronously for period: {$reportPeriod}");
            } else {
                // Dispatch job
                GenerateMonthlyInventoryReportJob::dispatch($facilityId, $reportPeriod, $force);
                logger()->info("✅ Job dispatched successfully for period: {$reportPeriod}");
                $this->info("✅ Job dispatched successfully for period: {$reportPeriod}");
            }
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            $this->error('📝 File: ' . $e->getFile());
            $this->error('📝 Line: ' . $e->getLine());
            return 1;
        }

        return 0;
    }
}
