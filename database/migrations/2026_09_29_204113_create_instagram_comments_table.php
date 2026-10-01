<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('instagram_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('instagram_comment_id')->unique(); // Instagram media comment ID
            $table->string('instagram_user_id')->nullable(); // Instagram user who commented
            $table->string('instagram_username')->nullable(); // Username of commenter
            $table->string('instagram_media_id')->nullable(); // Media (post) ID being commented on
            $table->longText('comment_text'); // Original comment text
            $table->boolean('contains_keyword')->default(false); // Whether comment matches trigger keyword
            $table->boolean('user_is_following')->default(false); // Cached follow status at time of check
            $table->longText('ai_response')->nullable(); // Generated AI response
            $table->string('status')->default('pending'); // pending, sent, failed, skipped
            $table->string('failure_reason')->nullable(); // Reason if failed
            $table->integer('retry_count')->default(0); // Number of retry attempts
            $table->text('metadata')->nullable(); // JSON - any additional data (webhook payload, etc.)
            $table->timestamp('checked_at')->nullable(); // When we checked if user follows
            $table->timestamp('sent_at')->nullable(); // When DM was sent
            $table->timestamps();
            
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['instagram_media_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instagram_comments');
    }
};
