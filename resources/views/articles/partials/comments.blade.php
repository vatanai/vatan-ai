<section class="article-comments" id="comments">
  <div class="article-section-heading"><div><span>گفت‌وگو</span><h2>دیدگاه کاربران</h2></div><p>{{ number_format($article->comments_count) }} دیدگاه تأییدشده</p></div>

  @if(session('comment_success'))<div class="article-message article-message--success">{{ session('comment_success') }}</div>@endif
  @if($errors->has('body'))<div class="article-message article-message--error">{{ $errors->first('body') }}</div>@endif

  @if($article->allow_comments)
    @auth
      <form class="article-comment-form" method="POST" action="{{ route('articles.comments.store', $article) }}">@csrf
        <label for="article-comment-body">نظر یا تجربه شما</label>
        <textarea id="article-comment-body" name="body" rows="4" maxlength="2000" required placeholder="دیدگاه مرتبط و محترمانه خود را بنویسید...">{{ old('body') }}</textarea>
        <button type="submit">ثبت دیدگاه</button>
      </form>
    @else
      <div class="article-login-note"><p>برای ثبت دیدگاه وارد حساب وطن شوید.</p><a href="{{ route('login', ['redirect' => $article->publicUrl() . '#comments']) }}">ورود و ثبت‌نام</a></div>
    @endauth
  @endif

  <div class="article-comment-list">
    @forelse($article->approvedComments as $comment)
      <article class="article-comment">
        <div class="article-comment__avatar">{{ mb_substr($comment->user?->name ?: 'ک', 0, 1) }}</div>
        <div><header><strong>{{ trim(($comment->user?->name ?? 'کاربر') . ' ' . ($comment->user?->last_name ?? '')) }}</strong><time>{{ $comment->created_at->diffForHumans() }}</time></header><p>{{ $comment->body }}</p>
          @foreach($comment->replies as $reply)<div class="article-comment__reply"><strong>{{ trim(($reply->user?->name ?? 'کاربر') . ' ' . ($reply->user?->last_name ?? '')) }}</strong><p>{{ $reply->body }}</p></div>@endforeach
        </div>
      </article>
    @empty
      <p class="article-comments__empty">هنوز دیدگاهی ثبت نشده است؛ اولین نفر باشید.</p>
    @endforelse
  </div>
</section>
