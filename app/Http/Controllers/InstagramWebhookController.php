<?php

namespace App\Http\Controllers;

use App\Models\InstagramComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class InstagramWebhookController extends Controller
{
    /**
     * Receive webhook from n8n when Instagram comment is detected
     * 
     * POST /api/instagram/webhook
     */
    public function receiveComment(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'instagram_comment_id' => 'required|string',
                'instagram_user_id' => 'required|string',
                'instagram_username' => 'nullable|string',
                'instagram_media_id' => 'required|string',
                'comment_text' => 'required|string',
                'contains_keyword' => 'required|boolean',
                'user_is_following' => 'required|boolean',
                'ai_response' => 'nullable|string',
                'status' => 'nullable|string|in:pending,sent,failed,skipped',
                'metadata' => 'nullable|array',
            ]);

            // Check if comment already exists (idempotency)
            $existing = InstagramComment::where('instagram_comment_id', $validated['instagram_comment_id'])->first();
            if ($existing) {
                Log::info('Instagram comment already processed', ['comment_id' => $validated['instagram_comment_id']]);
                return response()->json([
                    'success' => true,
                    'message' => 'Comment already processed',
                    'id' => $existing->id,
                    'status' => $existing->status,
                ], 200);
            }

            $userId = null;

            // Create the comment record
            $comment = InstagramComment::create([
                'user_id' => $userId,
                'instagram_comment_id' => $validated['instagram_comment_id'],
                'instagram_user_id' => $validated['instagram_user_id'],
                'instagram_username' => $validated['instagram_username'],
                'instagram_media_id' => $validated['instagram_media_id'],
                'comment_text' => $validated['comment_text'],
                'contains_keyword' => $validated['contains_keyword'],
                'user_is_following' => $validated['user_is_following'],
                'ai_response' => $validated['ai_response'] ?? null,
                'status' => $validated['status'] ?? 'pending',
                'metadata' => $validated['metadata'] ?? null,
                'checked_at' => now(),
            ]);

            Log::info('Instagram comment recorded', [
                'id' => $comment->id,
                'comment_id' => $comment->instagram_comment_id,
                'username' => $comment->instagram_username,
                'keyword_match' => $comment->contains_keyword,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Comment processed successfully',
                'id' => $comment->id,
                'status' => $comment->status,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Instagram webhook validation failed', [
                'errors' => $e->errors(),
                'payload' => $request->all(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Instagram webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Processing failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update comment status
     */
    public function updateStatus(Request $request, int $commentId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'required|string|in:pending,sent,failed,skipped',
                'sent_at' => 'nullable|date',
                'failure_reason' => 'nullable|string',
            ]);

            $comment = InstagramComment::findOrFail($commentId);
            
            $comment->update([
                'status' => $validated['status'],
                'sent_at' => $validated['sent_at'] ?? ($validated['status'] === 'sent' ? now() : null),
                'failure_reason' => $validated['failure_reason'] ?? null,
            ]);

            Log::info('Instagram comment status updated', [
                'id' => $comment->id,
                'status' => $comment->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status updated',
                'id' => $comment->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Status update failed', [
                'comment_id' => $commentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Update failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get recent comments for dashboard
     */
    public function getComments(Request $request): JsonResponse
    {
        try {
            $limit = min($request->query('limit', 50), 500);
            $status = $request->query('status', 'all');
            
            $query = InstagramComment::query()->orderBy('created_at', 'desc');
            
            if ($status !== 'all' && in_array($status, ['pending', 'sent', 'failed', 'skipped'])) {
                $query->where('status', $status);
            }

            $comments = $query->limit($limit)->get()->map(fn($c) => [
                'id' => $c->id,
                'instagram_username' => $c->instagram_username,
                'comment_text' => $c->comment_text,
                'ai_response' => $c->ai_response,
                'status' => $c->status,
                'user_is_following' => $c->user_is_following,
                'sent_at' => $c->sent_at,
                'created_at' => $c->created_at,
            ]);

            return response()->json([
                'success' => true,
                'total' => count($comments),
                'comments' => $comments,
            ]);

        } catch (\Exception $e) {
            Log::error('Get comments failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch comments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get statistics for dashboard
     */
    public function getStats(): JsonResponse
    {
        try {
            $now = now();
            $sevenDaysAgo = $now->copy()->subDays(7);
            $thirtyDaysAgo = $now->copy()->subDays(30);

            return response()->json([
                'success' => true,
                'stats' => [
                    'total_comments' => InstagramComment::count(),
                    'total_sent' => InstagramComment::where('status', 'sent')->count(),
                    'total_failed' => InstagramComment::where('status', 'failed')->count(),
                    'total_pending' => InstagramComment::where('status', 'pending')->count(),
                    'followers_engaged' => InstagramComment::where('user_is_following', true)->count(),
                    'last_7_days' => InstagramComment::where('created_at', '>=', $sevenDaysAgo)->count(),
                    'last_30_days' => InstagramComment::where('created_at', '>=', $thirtyDaysAgo)->count(),
                    'keyword_matches' => InstagramComment::where('contains_keyword', true)->count(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Get stats failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch stats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
