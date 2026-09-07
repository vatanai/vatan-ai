<section class="article-stats" aria-label="خلاصه وضعیت مقالات">
  @foreach([
    ['all','fa-regular fa-newspaper','کل مقالات'],
    ['published','fa-solid fa-circle-check','منتشرشده'],
    ['draft','fa-regular fa-pen-to-square','پیش‌نویس و اصلاح'],
    ['scheduled','fa-regular fa-clock','زمان‌بندی‌شده'],
    ['views','fa-regular fa-eye','کل بازدید'],
    ['pending_comments','fa-regular fa-comment-dots','دیدگاه منتظر'],
  ] as [$key,$icon,$label])
    <article class="article-stat"><i class="{{ $icon }}"></i><div><span>{{ $label }}</span><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div></article>
  @endforeach
</section>
