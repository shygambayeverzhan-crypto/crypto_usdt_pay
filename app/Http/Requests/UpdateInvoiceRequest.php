<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\InvoiceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * @property-read string $status Новый статус инвойса
 * @property-read string|null $txid Хэш транзакции (обязателен, если статус paid)
 */
class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(InvoiceStatus::class)],
            'txid' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function prepareForValidation(): void
    {
        $status = (string) $this->input('status', '');
        $status = trim(strtolower($status));
        if ($status !== '') {
            $this->merge(['status' => $status]);
        }
        $txid = (string) $this->input('txid', '');
        $txid = trim($txid);
        if ($txid === '') {
            $this->merge(['txid' => null]);
        } else {
            $this->merge(['txid' => $txid]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $status = (string) $this->input('status');

            // Если ставим paid — txid обязателен
            if ($status === InvoiceStatus::PAID->value) {
                if (!is_string($this->input('txid')) || trim((string) $this->input('txid')) === '') {
                    $v->errors()->add('txid', __('validation.app.invoice.txid_required_for_paid'));
                }
            }

            // Если статус cancel/expired/pending/processing — txid очищаем
            if (in_array($status, [
                InvoiceStatus::CANCELLED->value,
                InvoiceStatus::EXPIRED->value,
                InvoiceStatus::PENDING->value,
                InvoiceStatus::PROCESSING->value,
            ], true)) {
                $this->merge(['txid' => null]);
            }
        });
    }
}


