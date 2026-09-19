<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'string|min:4|max:255',
            'description' => 'nullable|string|min:6|max:512',
            'task_group_id' => 'integer',
            'position' => 'integer|min:0',
            'assignees' => 'nullable|array|max:5',
            'tags' => 'nullable|array|max:5',
        ];
    }
}
