<?php

namespace Modules\Blog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterTagsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'filter' => 'nullable|array',
            'search' => 'nullable|string|max:255',
            'sort' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateFilterOperators($validator, $this->input('filter', []), 'filter');
        });
    }

    private function validateFilterOperators($validator, array $data, string $prefix): void
    {
        $allowedOperators = array_keys(\Modules\Shared\Infrastructure\QueryBuilders\FilterableBuilder::ALLOWED_OPERATORS);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->validateFilterOperators($validator, $value, "{$prefix}.{$key}");
            } elseif (in_array($key, $allowedOperators, true)) {
                continue;
            } elseif (!is_string($value)) {
                $validator->errors()->add(
                    "{$prefix}.{$key}",
                    "Invalid filter structure at '{$prefix}.{$key}'."
                );
            }
        }
    }
}
