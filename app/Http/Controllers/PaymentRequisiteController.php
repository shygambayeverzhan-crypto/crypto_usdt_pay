<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequisiteRequest;
use App\Http\Resources\PaymentRequisiteResource;
use App\Models\PaymentRequisite;
use App\Support\PaymentRequisiteOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;

class PaymentRequisiteController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate(['method' => ['nullable', 'string', Rule::in(['card', 'usdt'])]]);
        $method = $validated['method'] ?? null;

        $requisites = PaymentRequisite::query()
            ->where('user_id', $request->user()->id)
            ->when($method, fn ($query, string $method) => $query->where('method', $method))
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (PaymentRequisite $requisite): array => (new PaymentRequisiteResource($requisite))->resolve());

        return $this->inertia('requisites/Index', [
            'requisites' => $requisites,
            'banks' => PaymentRequisiteOptions::banks(),
            'networks' => PaymentRequisiteOptions::networks(),
            'currencies' => PaymentRequisiteOptions::currencies(),
            'filters' => ['method' => $method],
        ]);
    }

    public function store(PaymentRequisiteRequest $request): RedirectResponse
    {
        $requisite = new PaymentRequisite;
        $requisite->user_id = $request->user()->id;
        $requisite->fill($request->persistenceData());
        $requisite->save();

        return redirect()->route('requisites.index')->with('success', $this->text('Реквизиты добавлены.', 'Payment details added.'));
    }

    public function update(PaymentRequisiteRequest $request, PaymentRequisite $requisite): RedirectResponse
    {
        $requisite->update($request->persistenceData());

        return redirect()->route('requisites.index')->with('success', $this->text('Реквизиты сохранены.', 'Payment details saved.'));
    }

    public function destroy(Request $request, PaymentRequisite $requisite): RedirectResponse
    {
        abort_unless((int) $requisite->user_id === (int) $request->user()->id, 404);
        $requisite->delete();

        return redirect()->route('requisites.index')->with('success', $this->text('Реквизиты удалены.', 'Payment details deleted.'));
    }

    private function text(string $russian, string $english): string
    {
        return app()->getLocale() === 'ru' ? $russian : $english;
    }
}
