<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\InvoiceCallbackLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceCallbackLogResource extends JsonResource
{
    /** @var InvoiceCallbackLog */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'event' => $this->event,
            'url' => $this->url,
            'request_payload' => $this->request_payload,
            'response_status' => $this->response_status,
            'response_body' => $this->response_body,
            'error_message' => $this->error_message,
            'duration_ms' => $this->duration_ms,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}


