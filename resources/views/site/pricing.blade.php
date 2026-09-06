@extends('layouts.app')
@section('page_title', 'پلن‌ها و تعرفه‌ها — وطن استودیو')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/plan-cards.css') }}?v={{ filemtime(public_path('css/plan-cards.css')) }}">
<style>
.plans-page{min-height:100vh;padding:48px 20px 90px;background:var(--vatan-bg-page);color:#fff}.light .plans-page{background:var(--vatan-bg-page);color:#111}
.plans-head{text-align:center;max-width:720px;margin:0 auto 38px}.plans-head h1{font-size:32px;font-weight:900}.plans-head p{margin-top:10px;color:#a8a8c0}.light .plans-head p{color:#686e6b}
.plans-segment{display:inline-flex;gap:7px;margin-top:16px;padding:6px 10px;border-radius:99px;background:#16161c;border:1px solid #222230;font-size:12px}.light .plans-segment{background:#fff;border-color:#e5e6e6}
.plans-grid{max-width:1380px;margin:auto;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;align-items:stretch}.plans-grid.comparison{grid-template-columns:repeat(4,minmax(0,1fr))}
.plan-card{background:#111116;border:1px solid #222230;border-radius:22px;padding:26px;display:flex;flex-direction:column;position:relative;transition:.25s}.light .plan-card{background:#fff;border-color:#e5e6e6;box-shadow:0 10px 30px rgba(0,0,0,.05)}.plan-card:hover{transform:translateY(-4px);border-color:#16594f}.plan-card.featured{border-color:#c2fd75}.light .plan-card.featured{border-color:#16594f}
.plan-badge{position:absolute;top:14px;left:14px;border-radius:99px;padding:5px 10px;background:#16594f;color:#c2fd75;font-size:10px;font-weight:800}.plan-icon{width:48px;height:48px;border-radius:14px;background:rgba(22,89,79,.18);color:#c2fd75;display:flex;align-items:center;justify-content:center;font-size:19px;margin-bottom:16px}.light .plan-icon{color:#16594f}
.plan-card h2{font-size:19px;font-weight:900}.plan-fit{font-size:12px;color:#a8a8c0;min-height:38px;margin:5px 0 14px}.light .plan-fit{color:#686e6b}.plan-price{font-size:27px;font-weight:900}.plan-price small{font-size:11px;color:#a8a8c0}.plan-tokens{margin-top:6px;font-size:13px;color:#c2fd75;font-weight:800}.light .plan-tokens{color:#16594f}.plan-per-token{font-size:10px;color:#a8a8c0;margin-top:3px}
.plan-features{display:grid;gap:9px;margin:20px 0;flex:1;padding-top:18px;border-top:1px solid #222230}.light .plan-features{border-color:#e5e6e6}.plan-feature{display:flex;gap:9px;font-size:12px;color:#d5d5df}.light .plan-feature{color:#333}.plan-feature i{margin-top:3px}.plan-feature.yes i{color:#16a34a}.plan-feature.no{opacity:.55}.plan-feature.no i{color:#ef4444}.plan-feature.limited i{color:#f5923a}
.plan-cta{width:100%;height:43px;border-radius:12px;background:#16594f;color:#c2fd75;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:12px;border:1px solid #16594f}.plan-cta.secondary{background:transparent;border-color:#333345;color:#fff}.light .plan-cta.secondary{border-color:#e5e6e6;color:#111}
.plans-alert{max-width:720px;margin:0 auto 20px;padding:13px 16px;border-radius:12px;font-size:12px}.plans-alert.success{background:rgba(22,163,74,.12);color:#16a34a}.plans-alert.error{background:rgba(239,68,68,.12);color:#ef4444}
.comparison-table{max-width:1280px;margin:32px auto 0;overflow:auto;border:1px solid #222230;border-radius:18px}.light .comparison-table{border-color:#e5e6e6;background:#fff}.comparison-table table{width:100%;border-collapse:collapse;min-width:760px}.comparison-table th,.comparison-table td{padding:13px;border-bottom:1px solid #222230;text-align:center;font-size:11px}.light .comparison-table th,.light .comparison-table td{border-color:#e5e6e6}.comparison-table th:first-child,.comparison-table td:first-child{text-align:right;position:sticky;right:0;background:#111116}.light .comparison-table th:first-child,.light .comparison-table td:first-child{background:#fff}
@media(max-width:1100px){.plans-grid,.plans-grid.comparison{grid-template-columns:repeat(2,1fr)}}@media(max-width:680px){.plans-page{padding:30px 14px 70px}.plans-grid,.plans-grid.comparison{grid-template-columns:1fr}.plans-head h1{font-size:25px}}
</style>
@endpush

@section('content')
<div class="plans-page" dir="rtl">
  @if(session('success'))<div class="plans-alert success">{{ session('success') }}</div>@endif
  @if(session('error'))<div class="plans-alert error">{{ session('error') }}</div>@endif
  <header class="plans-head">
    <h1>{{ $planDisplay['title'] ?? 'پلن مناسب خودت را انتخاب کن' }}</h1>
    <p>{{ $planDisplay['subtitle'] ?? '' }}</p>
    <span class="plans-segment"><i class="fa-solid {{ $customerSegment==='loyal'?'fa-crown':'fa-user' }}"></i> قیمت‌های مخصوص {{ $customerSegment==='loyal'?'مشتری ثابت':'کاربر عادی' }}</span>
  </header>

  <main class="plans-grid {{ ($planDisplay['mode']??'cards')==='comparison'?'comparison':'' }}">
    @forelse($plans as $plan)
      @include('site.partials.plan-card', ['offer' => $plan->offer])
    @empty
      <div class="plans-alert error">در حال حاضر پلن فعالی برای نمایش وجود ندارد.</div>
    @endforelse
  </main>

  @if(($planDisplay['show_comparison']??true) && $plans->isNotEmpty())
    @php $featureNames=$plans->flatMap(fn($p)=>collect($p->features??[])->pluck('title'))->unique()->values(); @endphp
    <div class="comparison-table"><table><thead><tr><th>مقایسه قابلیت‌ها</th>@foreach($plans as $plan)<th>{{ $plan->name }}</th>@endforeach</tr></thead><tbody>
      @foreach($featureNames as $featureName)<tr><td>{{ $featureName }}</td>@foreach($plans as $plan)@php $f=collect($plan->features??[])->firstWhere('title',$featureName); @endphp<td>@if(!$f)—@elseif(($f['included']??'yes')==='no')<i class="fa-solid fa-xmark text-red-500"></i>@else<i class="fa-solid fa-check text-green-500"></i> {{ $f['value']??'' }}@endif</td>@endforeach</tr>@endforeach
    </tbody></table></div>
  @endif
</div>
@endsection
