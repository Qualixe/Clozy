<?php

namespace Webkul\Shop\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrackOrderRequest extends FormRequest
{
    /**
     * Determine if the request is authorized.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'order_id' => ['required', 'max:50'],
            'email'    => ['required', 'email', 'max:191'],
        ];
    }
}
