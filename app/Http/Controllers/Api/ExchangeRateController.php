<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Services\AuditService;
use App\Services\ExchangeRateService;
use App\Support\CompatResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExchangeRateController extends Controller
{
    public function __construct(private ExchangeRateService $exchangeRates)
    {
    }

    public function index(Request $request): JsonResponse
    {
        if (! $request->user()?->isAdmin()) {
            return response()->json(['detail' => 'You do not have permission to perform this action.'], 403);
        }

        $query = ExchangeRate::query()->with(['fromCurrency', 'toCurrency', 'creator']);

        foreach (['from_currency_id', 'to_currency_id', 'source'] as $field) {
            if ($request->query($field) !== null && $request->query($field) !== '') {
                $query->where($field, $request->query($field));
            }
        }

        if ($request->query('is_manual') !== null) {
            $query->where('is_manual', filter_var($request->query('is_manual'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->query('effective_at')) {
            $at = now()->parse($request->query('effective_at'));
            $query->where('effective_from', '<=', $at)
                ->where(fn ($inner) => $inner->whereNull('effective_to')->orWhere('effective_to', '>', $at));
        }

        if ($search = $request->query('search')) {
            $query->where(fn ($inner) => $inner
                ->where('source', 'like', '%'.$search.'%')
                ->orWhere('notes', 'like', '%'.$search.'%')
                ->orWhereHas('fromCurrency', fn ($q) => $q->where('code', 'like', '%'.strtoupper($search).'%'))
                ->orWhereHas('toCurrency', fn ($q) => $q->where('code', 'like', '%'.strtoupper($search).'%')));
        }

        $sort = in_array($request->query('sort'), ['effective_from', 'created_at', 'exchange_rate', 'source'], true)
            ? $request->query('sort')
            : 'effective_from';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        return response()->json(CompatResponse::page(
            $query->orderBy($sort, $direction)->paginate((int) $request->query('page_size', 20))
        ));
    }

    public function show(int $id): JsonResponse
    {
        if (! request()->user()?->isAdmin()) {
            return response()->json(['detail' => 'You do not have permission to perform this action.'], 403);
        }

        return response()->json(CompatResponse::item(
            ExchangeRate::query()->with(['fromCurrency', 'toCurrency', 'creator'])->findOrFail($id)
        ));
    }

    public function store(Request $request): JsonResponse
    {
        if (! $request->user()?->isAdmin()) {
            return response()->json(['detail' => 'You do not have permission to perform this action.'], 403);
        }

        $data = $request->validate([
            'from_currency_id' => ['required', 'exists:currencies,id'],
            'to_currency_id' => ['required', 'exists:currencies,id', 'different:from_currency_id'],
            'exchange_rate' => ['required', 'numeric', 'gt:0'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after:effective_from'],
            'source' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string'],
            'is_manual' => ['nullable', 'boolean'],
        ]);

        $fromCurrency = Currency::query()->findOrFail($data['from_currency_id']);
        $toCurrency = Currency::query()->findOrFail($data['to_currency_id']);

        try {
            $this->exchangeRates->validatePair($fromCurrency, $toCurrency);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['currency_pair' => $e->getMessage()]);
        }

        $exists = ExchangeRate::query()
            ->where('from_currency_id', $data['from_currency_id'])
            ->where('to_currency_id', $data['to_currency_id'])
            ->where('effective_from', now()->parse($data['effective_from']))
            ->exists();

        if ($exists) {
            return response()->json(['detail' => 'An exchange rate already exists for this currency pair and effective date.', 'code' => 'duplicate_exchange_rate'], 422);
        }

        $rate = ExchangeRate::query()->create(array_merge($data, [
            'source' => $data['source'] ?? 'manual',
            'is_manual' => $data['is_manual'] ?? true,
            'created_by' => $request->user()?->id,
        ]));

        AuditService::log('created', 'exchange_rate', $rate);

        return response()->json(CompatResponse::item($rate->fresh(['fromCurrency', 'toCurrency', 'creator'])), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if (! $request->user()?->isAdmin()) {
            return response()->json(['detail' => 'You do not have permission to perform this action.'], 403);
        }

        $immutable = ['from_currency_id', 'to_currency_id', 'exchange_rate', 'effective_from'];
        if (array_intersect($immutable, array_keys($request->all()))) {
            return response()->json([
                'detail' => 'Exchange rate value, pair, and effective date are immutable. Create a new rate to preserve financial history.',
                'code' => 'exchange_rate_immutable',
            ], 422);
        }

        $rate = ExchangeRate::query()->findOrFail($id);
        $data = $request->validate([
            'effective_to' => ['nullable', 'date', 'after:'.$rate->effective_from],
            'source' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string'],
            'is_manual' => ['nullable', 'boolean'],
        ]);

        $changes = AuditService::changes($rate, $data);
        $rate->fill($data)->save();
        AuditService::log('updated_metadata', 'exchange_rate', $rate, $changes);

        return response()->json(CompatResponse::item($rate->fresh(['fromCurrency', 'toCurrency', 'creator'])));
    }

    public function destroy(int $id): JsonResponse
    {
        if (! request()->user()?->isAdmin()) {
            return response()->json(['detail' => 'You do not have permission to perform this action.'], 403);
        }

        return response()->json([
            'detail' => 'Exchange rates are append-only financial records and cannot be deleted.',
            'code' => 'exchange_rate_delete_forbidden',
        ], 422);
    }
}
