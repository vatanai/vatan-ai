<?php

namespace App\Http\Controllers;

use App\Models\InstagramPostSetting;
use App\Models\InstagramPostKeyword;
use App\Models\InstagramPostResponse;
use App\Models\InstagramPostProduct;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstagramPostSettingController extends Controller
{
    public function index(): View
    {
        $posts = auth()->user()
            ->instagramPostSettings()
            ->latest()
            ->paginate(15);

        return view('instagram.posts.index', compact('posts'));
    }

    public function create(): View
    {
        return view('instagram.posts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'instagram_post_id' => 'required|string|unique:instagram_post_settings',
            'instagram_caption' => 'nullable|string',
            'status' => 'required|in:active,inactive,testing',
            'require_follow' => 'boolean',
            'min_followers' => 'integer|min:0',
            'comment_reply_delay' => 'integer|min:0|max:300',
            'dm_product_delay' => 'integer|min:0|max:300',
            'dm_form_delay' => 'integer|min:0|max:300',
            'repeat_policy' => 'required|in:once_per_user,every_time,once_per_day',
        ]);

        $validated['user_id'] = auth()->id();

        $post = InstagramPostSetting::create($validated);

        // If status is active, set started_at
        if ($post->status === 'active') {
            $post->update(['started_at' => now()]);
        }

        return redirect()
            ->route('instagram.posts.show', $post)
            ->with('success', 'تنظیمات پست با موفقیت ایجاد شد');
    }

    public function show(InstagramPostSetting $post): View
    {
        $this->authorize('view', $post);

        $keywords = $post->keywords()->get();
        $products = $post->products()->get();
        $responses = $post->responses()->get();
        $analytics = $post->getTodayAnalytics();

        return view('instagram.posts.show', compact('post', 'keywords', 'products', 'responses', 'analytics'));
    }

    public function edit(InstagramPostSetting $post): View
    {
        $this->authorize('update', $post);
        
        return view('instagram.posts.edit', compact('post'));
    }

    public function update(Request $request, InstagramPostSetting $post)
    {
        $this->authorize('update', $post);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'instagram_caption' => 'nullable|string',
            'status' => 'required|in:active,inactive,testing',
            'require_follow' => 'boolean',
            'min_followers' => 'integer|min:0',
            'comment_reply_delay' => 'integer|min:0|max:300',
            'dm_product_delay' => 'integer|min:0|max:300',
            'dm_form_delay' => 'integer|min:0|max:300',
            'repeat_policy' => 'required|in:once_per_user,every_time,once_per_day',
        ]);

        $post->update($validated);

        if ($post->status === 'active' && !$post->started_at) {
            $post->update(['started_at' => now()]);
        } elseif ($post->status === 'inactive') {
            $post->update(['ended_at' => now()]);
        }

        return redirect()
            ->route('instagram.posts.show', $post)
            ->with('success', 'تنظیمات پست به روزرسانی شد');
    }

    public function destroy(InstagramPostSetting $post)
    {
        $this->authorize('delete', $post);

        $post->delete();

        return redirect()
            ->route('instagram.posts.index')
            ->with('success', 'پست حذف شد');
    }

    // Keyword Management
    public function addKeyword(Request $request, InstagramPostSetting $post)
    {
        $this->authorize('update', $post);

        $validated = $request->validate([
            'keyword' => 'required|string|max:255',
            'match_type' => 'required|in:exact,contains,regex',
        ]);

        InstagramPostKeyword::create([
            'post_setting_id' => $post->id,
            ...$validated,
        ]);

        return response()->json(['success' => true]);
    }

    public function deleteKeyword(InstagramPostSetting $post, InstagramPostKeyword $keyword)
    {
        $this->authorize('update', $post);

        if ($keyword->post_setting_id !== $post->id) {
            abort(403);
        }

        $keyword->delete();

        return response()->json(['success' => true]);
    }

    // Product Management
    public function addProduct(Request $request, InstagramPostSetting $post)
    {
        $this->authorize('update', $post);

        $validated = $request->validate([
            'product_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|integer|min:0',
            'image_url' => 'nullable|url',
            'product_link' => 'nullable|url',
            'sku' => 'nullable|string|max:100',
            'inventory' => 'integer|min:0',
        ]);

        InstagramPostProduct::create([
            'post_setting_id' => $post->id,
            ...$validated,
        ]);

        return response()->json(['success' => true]);
    }

    public function updateProduct(Request $request, InstagramPostSetting $post, InstagramPostProduct $product)
    {
        $this->authorize('update', $post);

        if ($product->post_setting_id !== $post->id) {
            abort(403);
        }

        $validated = $request->validate([
            'product_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|integer|min:0',
            'image_url' => 'nullable|url',
            'product_link' => 'nullable|url',
            'inventory' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $product->update($validated);

        return response()->json(['success' => true]);
    }

    public function deleteProduct(InstagramPostSetting $post, InstagramPostProduct $product)
    {
        $this->authorize('update', $post);

        if ($product->post_setting_id !== $post->id) {
            abort(403);
        }

        $product->delete();

        return response()->json(['success' => true]);
    }

    // Response Configuration
    public function setResponses(Request $request, InstagramPostSetting $post)
    {
        $this->authorize('update', $post);

        $validated = $request->validate([
            'non_follower_comment' => 'required|string',
            'follower_comment' => 'required|string',
            'follower_dm_text' => 'required|string',
        ]);

        $post->update([
            'non_follower_response' => json_encode([
                'comment' => $validated['non_follower_comment'],
            ]),
            'follower_response' => json_encode([
                'comment' => $validated['follower_comment'],
                'dm_text' => $validated['follower_dm_text'],
            ]),
        ]);

        // Clear existing responses
        $post->responses()->delete();

        // Create new responses
        InstagramPostResponse::create([
            'post_setting_id' => $post->id,
            'scenario' => 'non_follower',
            'target' => 'comment',
            'message' => $validated['non_follower_comment'],
        ]);

        InstagramPostResponse::create([
            'post_setting_id' => $post->id,
            'scenario' => 'follower',
            'target' => 'comment',
            'message' => $validated['follower_comment'],
        ]);

        InstagramPostResponse::create([
            'post_setting_id' => $post->id,
            'scenario' => 'follower',
            'target' => 'dm_text',
            'message' => $validated['follower_dm_text'],
        ]);

        return response()->json(['success' => true]);
    }

    public function getAnalytics(InstagramPostSetting $post)
    {
        $this->authorize('view', $post);

        $today = $post->getTodayAnalytics();
        $week = $post->analytics()
            ->whereDate('date', '>=', now()->subDays(7))
            ->get();
        $month = $post->analytics()
            ->whereDate('date', '>=', now()->subDays(30))
            ->get();

        return response()->json([
            'today' => $today,
            'week' => $week,
            'month' => $month,
        ]);
    }

    public function activate(InstagramPostSetting $post)
    {
        $this->authorize('update', $post);

        $post->activate();

        return back()->with('success', 'پست فعال شد');
    }

    public function deactivate(InstagramPostSetting $post)
    {
        $this->authorize('update', $post);

        $post->deactivate();

        return back()->with('success', 'پست غیرفعال شد');
    }
}
