<?php

namespace App\Console\Commands;

use App\Http\Controllers\EmailController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProcessEmailInbox extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'email:process-inbox';

    /**
     * The console command description.
     */
    protected $description = 'Pull unread emails from MS365 inbox and create/update tickets accordingly';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Processing email inbox...');

        try {
            $controller = new EmailController();
            $request    = new Request();
            $response   = $controller->processInbox($request);

            $data = $response->getData(true);

            if (($data['status'] ?? '') === 'done') {
                $this->info("Done. Processed: {$data['processed']}, Skipped: {$data['skipped']}");

                if (!empty($data['errors'])) {
                    foreach ($data['errors'] as $err) {
                        $this->warn("  Error: {$err}");
                    }
                }

                return self::SUCCESS;
            }

            $this->error('Email processing failed: ' . ($data['message'] ?? 'unknown error'));
            return self::FAILURE;

        } catch (\Throwable $e) {
            $this->error('Exception: ' . $e->getMessage());
            Log::error('ProcessEmailInbox command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::FAILURE;
        }
    }
}
