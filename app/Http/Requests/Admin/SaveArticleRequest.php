<?php

namespace App\Http\Requests\Admin;

use App\Models\Article;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'seo_auto_fill' => $this->boolean('seo_auto_fill'),
            'is_indexable' => $this->boolean('is_indexable'),
            'is_featured' => $this->boolean('is_featured'),
            'allow_comments' => $this->boolean('allow_comments'),
        ]);
    }

    public function rules(): array
    {
        $article = $this->route('article');

        return [
            'title' => ['required', 'string', 'max:220'],
            'slug' => ['nullable', 'string', 'max:190', Rule::unique('articles', 'slug')->ignore($article?->id)],
            'excerpt' => ['required', 'string', 'max:1000'],
            'article_category_id' => ['required', 'exists:article_categories,id'],
            'article_author_id' => ['required', 'exists:article_authors,id'],
            'reviewer_id' => ['nullable', 'exists:admins,id'],
            'content_type' => ['required', Rule::in(Article::CONTENT_TYPES)],
            'status' => ['required', Rule::in(Article::STATUSES)],
            'content_blocks' => ['required', 'array', 'min:1', 'max:100'],
            'content_blocks.*.type' => ['required', 'string', 'max:30'],
            'content_blocks.*.title' => ['nullable', 'string', 'max:500'],
            'content_blocks.*.content' => ['nullable', 'string', 'max:30000'],
            'content_blocks.*.url' => ['nullable', 'string', 'max:2048'],
            'content_blocks.*.alt' => ['nullable', 'string', 'max:500'],
            'content_blocks.*.caption' => ['nullable', 'string', 'max:1000'],
            'content_blocks.*.button_label' => ['nullable', 'string', 'max:120'],
            'content_blocks.*.button_url' => ['nullable', 'string', 'max:2048'],
            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:8192'],
            'featured_image_path' => ['nullable', 'string', 'max:2048'],
            'featured_image_alt' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:8192'],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'seo_keywords_text' => ['nullable', 'string', 'max:1500'],
            'hashtags_text' => ['nullable', 'string', 'max:1000'],
            'tags' => ['nullable', 'string', 'max:1500'],
            'canonical_url' => ['nullable', 'url', 'max:2048'],
            'seo_auto_fill' => ['boolean'],
            'is_indexable' => ['boolean'],
            'is_featured' => ['boolean'],
            'allow_comments' => ['boolean'],
            'scheduled_at' => ['nullable', 'date', 'required_if:status,scheduled'],
            'published_at' => ['nullable', 'date'],
            'product_ids' => ['nullable', 'array', 'max:30'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'galleries' => ['nullable', 'array', 'max:12'],
            'galleries.*.id' => ['nullable', 'integer'],
            'galleries.*.title' => ['required_with:galleries', 'string', 'max:220'],
            'galleries.*.description' => ['nullable', 'string', 'max:1000'],
            'galleries.*.source_type' => ['required_with:galleries', Rule::in(['manual', 'products', 'category', 'generated'])],
            'galleries.*.display_style' => ['required_with:galleries', Rule::in(['grid', 'slider', 'masonry', 'showcase'])],
            'galleries.*.product_category_id' => ['nullable', 'exists:categories,id'],
            'galleries.*.product_ids' => ['nullable', 'array'],
            'galleries.*.product_ids.*' => ['integer', 'exists:products,id'],
            'galleries.*.generated_image_ids' => ['nullable', 'array'],
            'galleries.*.generated_image_ids.*' => ['integer', 'exists:generated_images,id'],
            'galleries.*.uploads' => ['nullable', 'array', 'max:20'],
            'galleries.*.uploads.*' => ['file', 'mimes:jpg,jpeg,png,webp,avif,mp4,webm', 'max:20480'],
            'galleries.*.items' => ['nullable', 'array', 'max:30'],
            'galleries.*.items.*.id' => ['nullable', 'integer'],
            'galleries.*.items.*.media_type' => ['nullable', Rule::in(['image', 'video'])],
            'galleries.*.items.*.media_path' => ['nullable', 'string', 'max:2048'],
            'galleries.*.items.*.title' => ['nullable', 'string', 'max:220'],
            'galleries.*.items.*.description' => ['nullable', 'string', 'max:1000'],
            'galleries.*.items.*.alt_text' => ['nullable', 'string', 'max:500'],
            'galleries.*.items.*.link_url' => ['nullable', 'string', 'max:2048'],
            'galleries.*.items.*.remove' => ['nullable', 'boolean'],
            'remove_gallery_ids' => ['nullable', 'array'],
            'remove_gallery_ids.*' => ['integer'],
            'change_note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
