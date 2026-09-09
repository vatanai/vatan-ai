@extends('layouts.app')
@section('page_title', 'پلن‌ها و تعرفه‌ها — وطن استودیو')
@push('styles')
<link rel="stylesheet" href="{{ asset('assets/site/css/home-preview.css') }}?v={{ filemtime(public_path('assets/site/css/home-preview.css')) }}">
<style>
.plans-page{min-height:100vh;padding:48px 20px 90px;background:var(--vp-bg);color:var(--vp-text)}
.plans-head{max-width:720px;margin:0 auto 38px;text-align:center}.plans-head h1{margin:0;font-size:clamp(34px,4vw,58px);font-weight:800;line-height:1.2;letter-spacing:-.04em}.plans-head p{margin-top:18px;color:var(--vp-text-soft);font-size:18px;line-height:1.9}.plans-segment{display:inline-flex;gap:7px;margin-top:16px;border:1px solid var(--vp-border);border-radius:99px;padding:6px 10px;background:var(--vp-card);color:var(--vp-text-soft);font-size:12px}
.plans-grid{display:grid;max-width:1280px;margin:auto;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;align-items:stretch}.plans-grid>div{display:flex;min-width:0}.plans-grid .vp-proposal-plan{width:100%}
.plans-alert{max-width:720px;margin:0 auto 20px;padding:13px 16px;border-radius:12px;font-size:12px}.plans-alert.success{background:rgba(22,163,74,.12);color:#16a34a}.plans-alert.error{background:rgba(239,68,68,.12);color:#ef4444}
.comparison-table{max-width:1280px;margin:32px auto 0;overflow:auto;border:1px solid var(--vp-border);border-radius:18px;background:var(--vp-card)}.comparison-table table{width:100%;border-collapse:collapse;min-width:760px}.comparison-table th,.comparison-table td{border-bottom:1px solid var(--vp-border);padding:13px;text-align:center;color:var(--vp-text-soft);font-size:11px}.comparison-table th{color:var(--vp-text);font-weight:800}.comparison-table th:first-child,.comparison-table td:first-child{position:sticky;right:0;background:var(--vp-surface-2);text-align:right}
@media(max-width:1100px){.plans-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:680px){.plans-page{padding:30px 14px 70px}.plans-grid{grid-template-columns:1fr}.plans-head h1{font-size:32px}.plans-head p{font-size:15px}}
</style>
@endpush

@section('content')
<div class="plans-page" dir="rtl">
  @php $pricingSettings = is_array($homePricing ?? null) ? $homePricing : []; @endphp
  @if(session('success'))<div class="plans-alert success">{{ session('success') }}</div>@endif
  @if(session('error'))<div class="plans-alert error">{{ session('error') }}</div>@endif
  @if($errors->any())<div class="plans-alert error">{{ $errors->first() }}</div>@endif
  <header class="plans-head">
    <h1>{{ $pricingSettings['title'] ?? 'پلن‌ها، بر پایه اعتبار دائمی' }}</h1>
    @if(collect($pricingSettings['notes'] ?? [])->filter()->isNotEmpty())
      <p>{{ collect($pricingSettings['notes'])->filter()->first() }}</p>
    @endif
    <span class="plans-segment"><i class="fa-solid {{ $customerSegment==='loyal'?'fa-crown':'fa-user' }}"></i> قیمت‌های مخصوص {{ $customerSegment==='loyal'?'مشتری ثابت':'کاربر عادی' }}</span>
  </header>

  <main class="plans-grid vp-plans vp-proposal-plans">
    @forelse($plans as $plan)
      @include('site.partials.pricing-proposal-card', ['plan' => $plan, 'pricingPage' => true])
    @empty
      <div class="plans-alert error">در حال حاضر پلن فعالی برای نمایش وجود ندارد.</div>
    @endforelse
  </main>

  @if(($planDisplay['show_comparison']??true) && $plans->isNotEmpty())
    @php $featureNames=$plans->flatMap(fn($p)=>collect($p->features??[])->map(fn($feature)=>is_array($feature)?($feature['title']??''):$feature))->filter()->unique()->values(); @endphp
    <div class="comparison-table"><table><thead><tr><th>مقایسه قابلیت‌ها</th>@foreach($plans as $plan)<th>{{ $plan->name }}</th>@endforeach</tr></thead><tbody>
      @foreach($featureNames as $featureName)<tr><td>{{ $featureName }}</td>@foreach($plans as $plan)@php $f=collect($plan->features??[])->firstWhere('title',$featureName); @endphp<td>@if(!$f)—@elseif(($f['included']??'yes')==='no')<i class="fa-solid fa-xmark text-red-500"></i>@else<i class="fa-solid fa-check text-green-500"></i> {{ $f['value']??'' }}@endif</td>@endforeach</tr>@endforeach
    </tbody></table></div>
  @endif
</div>
@endsection
