<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateCampaignRequest extends BaseRequest
{

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['sometimes', 'required', 'string', Rule::in(['active', 'archived'])],
        ];
    }
}

