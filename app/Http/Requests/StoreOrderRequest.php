<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * API Design: FormRequest for input validation.
 *
 * Centralizes validation rules outside the controller, keeping
 * the controller thin. Returns 422 with structured error messages
 * when validation fails.
 */
class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'customer_name'  => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'item'           => ['required', 'string', 'max:255'],
            'quantity'        => ['required', 'integer', 'min:1', 'max:1000'],
            'total_price'    => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_name.required'  => 'Customer name is required.',
            'customer_email.required' => 'Customer email is required.',
            'customer_email.email'    => 'Please provide a valid email address.',
            'item.required'           => 'Item name is required.',
            'quantity.required'        => 'Quantity is required.',
            'quantity.min'            => 'Quantity must be at least 1.',
            'total_price.required'    => 'Total price is required.',
            'total_price.min'         => 'Total price must be greater than 0.',
        ];
    }
}
