<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Message Queue: Dead-Letter Queue demonstration.
 *
 * This job ALWAYS throws an exception to demonstrate what happens when
 * a job exhausts all retry attempts. After $tries failures, Laravel
 * moves the job to the failed_jobs table — acting as a dead-letter queue.
 *
 * Dispatch via: php artisan tinker → ForceFailJob::dispatch()
 * View failed: php artisan queue:failed
 */
class ForceFailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 2;

    public function __construct() {}

    /**
     * This job always fails — demonstrating the dead-letter queue pattern.
     *
     * After 3 failed attempts (with 2-second backoff), the job lands
     * in the failed_jobs table. Use `php artisan queue:failed` to inspect it.
     *
     * @throws \RuntimeException Always
     */
    public function handle(): void
    {
        Log::warning("ForceFailJob: Attempt #{$this->attempts()} — about to fail intentionally");

        throw new \RuntimeException(
            "ForceFailJob: Intentional failure on attempt #{$this->attempts()} to demonstrate dead-letter queue."
        );
    }
}
