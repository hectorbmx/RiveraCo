<?php

namespace App\Jobs\Sat;

use App\Models\SatDocumentRequest;
use App\Services\Sat\CsfRequestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSatCsfRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $documentRequestId;

    public function __construct(int $documentRequestId)
    {
        $this->documentRequestId = $documentRequestId;
    }

   public function handle(CsfRequestService $csfRequestService): void
{
    $documentRequest = SatDocumentRequest::find($this->documentRequestId);

    if (!$documentRequest) {
        return;
    }

    try {
        $csfRequestService->handle($documentRequest);
    } catch (\Throwable $e) {

        $documentRequest->update([
            'status' => SatDocumentRequest::STATUS_ERROR,
            'error_message' => $e->getMessage(),
        ]);

        throw $e; // importante: para que Laravel lo registre en failed_jobs
    }
}
}