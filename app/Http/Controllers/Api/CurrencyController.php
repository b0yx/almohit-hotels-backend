<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Services\AuditService;
use App\Services\CurrencyService;
use App\Support\CompatResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CurrencyController extends Controller
{
    public function __construct(private CurrencyService $currencies) {}

    public function index(Request $request): JsonResponse
    {
        $query = Currency::query();

        if (! $request->user()?->isAdmin()) {
            $query->where('is_active', true);
        }

        if ($request->query('is_active') !== null) {
            $query->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->query('is_default') !== null) {
            $query->where('is_default', filter_var($request->query('is_default'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($search = $request->query('search')) {
            $query->where(fn ($inner) => $inner
                ->where('code', 'like', '%'.strtoupper($search).'%')
                ->orWhere('name', 'like', '%'.$search.'%')
                ->orWhere('symbol', 'like', '%'.$search.'%'));
        }

        $sort = in_array($request->query('sort'), ['code', 'name', 'is_active', 'is_default', 'created_at'], true)
            ? $request->query('sort')
            : 'code';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        return response()->json(CompatResponse::page(
            $query->orderBy($sort, $direction)->paginate((int) $request->query('page_size', 20))
        ));
    }

    public function show(int $id): JsonResponse
    {
        $query = Currency::query();
        if (! request()->user()?->isAdmin()) {
            $query->where('is_active', true);
        }

        return response()->json(CompatResponse::item($query->findOrFail($id)));
    }

    public function store(Request $request): JsonResponse
    {
        if (! $request->user()?->isAdmin()) {
            return response()->json(['detail' => 'You do not have permission to perform this action.'], 403);
        }

        $data = $this->validatedData($request);

        if (! $this->currencies->validateCurrencyCode($data['code'])) {
            return response()->json(['detail' => 'Currency code must be ISO 4217 compliant.', 'code' => 'invalid_currency_code'], 422);
        }

        if (($data['is_default'] ?? false) && Currency::query()->where('is_default', true)->exists()) {
            return response()->json(['detail' => 'Only one default currency is allowed.', 'code' => 'default_currency_exists'], 422);
        }

        $currency = Currency::query()->create($data);
        AuditService::log('created', 'currency', $currency);

        return response()->json(CompatResponse::item($currency), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if (! $request->user()?->isAdmin()) {
            return response()->json(['detail' => 'You do not have permission to perform this action.'], 403);
        }

        $currency = Currency::query()->findOrFail($id);
        $data = $this->validatedData($request, $currency);

        if (isset($data['code']) && ! $this->currencies->validateCurrencyCode($data['code'])) {
            return response()->json(['detail' => 'Currency code must be ISO 4217 compliant.', 'code' => 'invalid_currency_code'], 422);
        }

        if (($data['is_default'] ?? false) && ! $currency->is_default) {
            Currency::query()->where('is_default', true)->whereKeyNot($currency->id)->update(['is_default' => false]);
        }

        if ($currency->is_default && array_key_exists('is_active', $data) && ! $data['is_active']) {
            return response()->json(['detail' => 'Default currency cannot be deactivated.', 'code' => 'cannot_deactivate_default_currency'], 422);
        }

        $changes = AuditService::changes($currency, $data);
        $currency->fill($data)->save();
        AuditService::log('updated', 'currency', $currency, $changes);

        return response()->json(CompatResponse::item($currency->fresh()));
    }

    public function destroy(int $id): JsonResponse
    {
        if (! request()->user()?->isAdmin()) {
            return response()->json(['detail' => 'You do not have permission to perform this action.'], 403);
        }

        $currency = Currency::query()->findOrFail($id);

        if ($currency->is_default) {
            return response()->json(['detail' => 'Default currency cannot be deleted.', 'code' => 'cannot_delete_default_currency'], 422);
        }

        $hasFinancialHistory = $currency->fromExchangeRates()->exists()
            || $currency->toExchangeRates()->exists()
            || $currency->bookingInquiries()->exists();

        if ($hasFinancialHistory) {
            $changes = AuditService::changes($currency, ['is_active' => false]);
            $currency->forceFill(['is_active' => false])->save();
            AuditService::log('deactivated', 'currency', $currency, $changes);

            return response()->json(CompatResponse::item($currency));
        }

        AuditService::log('deleted', 'currency', $currency);
        $currency->delete();

        return response()->json(null, 204);
    }

    private function validatedData(Request $request, ?Currency $currency = null): array
    {
        $data = $request->validate([
            'code' => [
                $currency ? 'sometimes' : 'required',
                'string',
                'size:3',
                'regex:/^[A-Z]{3}$/',
                Rule::unique('currencies', 'code')->ignore($currency?->id),
            ],
            'name' => [$currency ? 'sometimes' : 'required', 'string', 'max:100'],
            'symbol' => ['nullable', 'string', 'max:16'],
            'symbol_position' => ['nullable', Rule::in([Currency::SYMBOL_BEFORE, Currency::SYMBOL_AFTER])],
            'decimal_places' => ['nullable', 'integer', 'min:0', 'max:6'],
            'thousand_separator' => ['nullable', 'string', 'max:8'],
            'decimal_separator' => ['nullable', 'string', 'max:8'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }

        return $data;
    }
}
