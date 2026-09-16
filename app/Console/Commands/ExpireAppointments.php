<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ExpireAppointments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark pending appointments whose date has passed as expired while preserving all appointment history for reports';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today()->format('Y-m-d');
        $count = Appointment::where('status', 'pending')
            ->where('appointment_date', '<', $today)
            ->update([
                'status' => 'expired',
                'admin_comment' => 'Expired automatically (appointment date passed)',
            ]);

        $this->info("Marked {$count} past pending appointments as expired (records kept for reports).");
        Log::info("Marked {$count} past pending appointments as expired via appointments:expire command.");
    }
}
?>
