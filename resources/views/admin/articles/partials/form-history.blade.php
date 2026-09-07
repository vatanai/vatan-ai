@if($article->exists)
<section class="article-card-admin">
  <div class="article-card-admin__head"><div><h2>تاریخچه نسخه‌ها</h2><p>آخرین نسخه‌ها برای بازیابی امن نگه‌داری می‌شوند.</p></div></div>
  <div class="article-card-admin__body"><div class="article-revisions">@forelse($article->revisions as $revision)<article class="article-revision"><div><strong>نسخه {{ $revision->version }} · {{ $revision->action }}</strong><small>{{ $revision->created_at->diffForHumans() }} @if($revision->change_note)· {{ $revision->change_note }}@endif</small></div><button class="article-btn article-btn--small" type="submit" form="revision-restore-{{ $revision->id }}" onclick="return confirm('این نسخه بازیابی شود؟')">بازیابی</button></article>@empty<p style="color:var(--text-soft);font-size:10px;">هنوز نسخه‌ای ثبت نشده است.</p>@endforelse</div></div>
</section>
@endif
