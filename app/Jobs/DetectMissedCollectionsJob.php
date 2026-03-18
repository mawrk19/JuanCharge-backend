<?php

namespace App\Jobs;

use App\Models\FieldReport;
use App\Models\Kiosk;
use App\Models\MissedCollectionAlert;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DetectMissedCollectionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $timezone = config('app.timezone', 'UTC');
        $now = Carbon::now($timezone);

        // Check yesterday's scheduled collections. Deadline is today 08:00 local time.
        $scheduledDate = $now->copy()->subDay()->toDateString();
        $scheduledWeekday = strtolower(Carbon::parse($scheduledDate, $timezone)->format('l'));
        $cutoffAt = Carbon::parse($scheduledDate, $timezone)->addDay()->setTime(8, 0, 0);

        if ($now->lt($cutoffAt)) {
            return;
        }

        $kiosks = Kiosk::with('collectionSchedule')
            ->whereNotNull('collection_schedule_id')
            ->whereHas('collectionSchedule', function ($q) use ($scheduledWeekday) {
                $q->where('is_active', true)
                  ->whereJsonContains('collection_days', $scheduledWeekday);
            })
            ->get();

        foreach ($kiosks as $kiosk) {
            $hasCollectionReport = FieldReport::where('kiosk_id', $kiosk->id)
                ->where('activity_type', 'collection_completed')
                ->whereDate('submitted_at', $scheduledDate)
                ->exists();

            if ($hasCollectionReport) {
                MissedCollectionAlert::where('kiosk_id', $kiosk->id)
                    ->whereDate('scheduled_date', $scheduledDate)
                    ->whereNull('resolved_at')
                    ->update(['resolved_at' => $now]);
                continue;
            }

            MissedCollectionAlert::updateOrCreate(
                [
                    'kiosk_id' => $kiosk->id,
                    'scheduled_date' => $scheduledDate,
                ],
                [
                    'lgu_id' => $kiosk->lgu_id,
                    'cutoff_at' => $cutoffAt,
                    'detected_at' => $now,
                    'resolved_at' => null,
                ]
            );
        }
    }
}
