<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Faq;
use App\Models\Product;
use App\Models\TaxRate;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InlineEditController extends Controller
{
    protected const ALLOWED = [
        'User' => [
            'role_type' => ['type' => 'string', 'values' => ['admin', 'seller', 'supplier', 'customer']],
            'status' => ['type' => 'string', 'values' => ['active', 'inactive', 'suspended']],
        ],
        'TaxRate' => [
            'rate' => ['type' => 'numeric', 'min' => 0, 'max' => 100],
            'name' => ['type' => 'string', 'max' => 255],
            'is_active' => ['type' => 'boolean'],
        ],
        'Coupon' => [
            'value' => ['type' => 'numeric', 'min' => 0],
            'min_order_amount' => ['type' => 'numeric', 'min' => 0],
            'is_active' => ['type' => 'boolean'],
        ],
        'Category' => [
            'name' => ['type' => 'string', 'max' => 255],
            'sort_order' => ['type' => 'integer'],
            'status' => ['type' => 'boolean'],
        ],
        'Brand' => [
            'name' => ['type' => 'string', 'max' => 255],
            'sort_order' => ['type' => 'integer'],
            'is_active' => ['type' => 'boolean'],
        ],
        'Faq' => [
            'sort_order' => ['type' => 'integer'],
            'is_active' => ['type' => 'boolean'],
        ],
        'Product' => [
            'status' => ['type' => 'string', 'values' => ['published', 'draft', 'archived']],
        ],
    ];

    protected const MODELS = [
        'User' => User::class,
        'TaxRate' => TaxRate::class,
        'Coupon' => Coupon::class,
        'Category' => Category::class,
        'Brand' => Brand::class,
        'Faq' => Faq::class,
        'Product' => Product::class,
    ];

    public function save(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'model' => 'required|string',
            'id'    => 'required|integer',
            'field' => 'required|string',
            'value' => 'nullable',
        ]);

        $model = $validated['model'];

        if (!isset(static::MODELS[$model]) || !isset(static::ALLOWED[$model][$validated['field']])) {
            return response()->json(['error' => 'This field cannot be edited.'], 422);
        }

        $rules = static::ALLOWED[$model][$validated['field']];
        $value = $validated['value'];

        switch ($rules['type']) {
            case 'boolean':
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                break;
            case 'integer':
                if (!is_numeric($value)) {
                    return response()->json(['error' => 'Invalid value.'], 422);
                }
                $value = (int) $value;
                break;
            case 'numeric':
                if (!is_numeric($value)) {
                    return response()->json(['error' => 'Invalid value.'], 422);
                }
                if (isset($rules['min']) && $value < $rules['min']) {
                    return response()->json(['error' => 'Value is too low.'], 422);
                }
                if (isset($rules['max']) && $value > $rules['max']) {
                    return response()->json(['error' => 'Value is too high.'], 422);
                }
                $value = (float) $value;
                break;
            case 'string':
                $value = (string) $value;
                if (isset($rules['max']) && mb_strlen($value) > $rules['max']) {
                    return response()->json(['error' => 'Value is too long.'], 422);
                }
                if (isset($rules['values']) && !in_array($value, $rules['values'], true)) {
                    return response()->json(['error' => 'Invalid value.'], 422);
                }
                break;
        }

        $modelClass = static::MODELS[$model];
        $record = $modelClass::find($validated['id']);

        if (!$record) {
            return response()->json(['error' => 'Record not found.'], 404);
        }

        $record->update([$validated['field'] => $value]);

        return response()->json([
            'success' => true,
            'value'   => $record->fresh()->{$validated['field']},
            'message' => 'Updated successfully.',
        ]);
    }
}
