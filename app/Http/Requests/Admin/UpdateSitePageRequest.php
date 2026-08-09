<?php

namespace App\Http\Requests\Admin;

use App\Models\SitePage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSitePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_indexable' => $this->boolean('is_indexable'),
            'requires_auth' => $this->boolean('requires_auth'),
            'maintenance_mode' => $this->boolean('maintenance_mode'),
            'show_footer' => $this->boolean('show_footer'),
            'show_page_title' => $this->boolean('show_page_title'),
            'show_search' => $this->boolean('show_search'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name_fa' => ['required', 'string', 'max:150'],
            'name_en' => ['nullable', 'string', 'max:150'],
            'status' => ['required', Rule::in(SitePage::STATUSES)],
            'title' => ['required', 'string', 'max:180'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:1000'],
            'canonical_url' => ['nullable', 'url', 'max:2048'],
            'og_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'is_indexable' => ['boolean'],
            'requires_auth' => ['boolean'],
            'maintenance_mode' => ['boolean'],
            'maintenance_message' => ['nullable', 'string', 'max:500'],
            'scheduled_at' => ['nullable', 'date', 'required_if:status,scheduled'],
            'theme' => ['required', Rule::in(['system', 'light', 'dark'])],
            'show_footer' => ['boolean'],
            'show_page_title' => ['boolean'],
            'show_search' => ['boolean'],
            'items_per_page' => ['required', 'integer', 'min:6', 'max:100'],
            'change_note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
