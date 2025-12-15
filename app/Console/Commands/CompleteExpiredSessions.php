<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ChargingSession;
use Illuminate\Support\Facades\Log;

class CompleteExpiredSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'charging:complete-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-complete expired charging sessions';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $now = now();

        // Find all active sessions that have passed their end_time
        $expiredSessions = ChargingSession::where('status', 'active')
            ->where('end_time', '<=', $now)
            ->get();

        $count = $expiredSessions->count();

        if ($count === 0) {
            $this->info('No expired sessions found.');
            return 0;
        }

        foreach ($expiredSessions as $session) {
            $session->status = 'completed';
            $session->completed_at = $now;
            $session->save();

            Log::info('Charging session auto-completed', [
                'session_id' => $session->session_id,
                'user_id' => $session->user_id,
                'points_redeemed' => $session->points_redeemed,
            ]);

            $this->info("Completed session: {$session->session_id}");
        }

        $this->info("Successfully completed {$count} expired session(s).");
        return 0;
    }
}
