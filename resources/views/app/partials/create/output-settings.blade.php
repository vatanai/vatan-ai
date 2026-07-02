{{-- پارشیال: تنظیمات خروجی (نسبت تصویر و کیفیت) --}}
<div class="cp-output">
  <div class="cp-output__inner" style="padding:14px 16px;">

    <div style="font-size:13px;font-weight:700;color:#fff;margin-bottom:14px;">
      <i class="fa-solid fa-crop" style="color:#a07af5;margin-left:6px;font-size:12px;"></i>
      نسبت تصویر خروجی
    </div>

    <div class="cp-radio-group" style="display:flex;flex-wrap:wrap;gap:8px;">
      @foreach([
        ['1:1',  'مربع'],
        ['4:5',  'پرتره'],
        ['16:9', 'افقی'],
        ['9:16', 'عمودی'],
        ['3:4',  '۳:۴'],
      ] as [$val, $lbl])
      <label style="cursor:pointer;">
        <input type="radio" name="output[aspect_ratio]" value="{{ $val }}"
          {{ $val === '1:1' ? 'checked' : '' }} style="display:none;" class="cp-ratio-radio">
        <span class="cp-ratio-btn" data-val="{{ $val }}"
          style="display:inline-flex;align-items:center;justify-content:center;padding:7px 14px;border-radius:9px;border:1px solid rgba(255,255,255,0.12);background:rgba(255,255,255,0.05);font-size:12px;font-weight:600;color:rgba(255,255,255,0.6);transition:all 0.18s;white-space:nowrap;">
          {{ $lbl }}
          <span style="font-size:10px;color:rgba(255,255,255,0.35);margin-right:4px;">{{ $val }}</span>
        </span>
      </label>
      @endforeach
    </div>

    <div style="font-size:13px;font-weight:700;color:#fff;margin:16px 0 12px;">
      <i class="fa-solid fa-star" style="color:#a07af5;margin-left:6px;font-size:12px;"></i>
      کیفیت
    </div>

    <div class="cp-radio-group" style="display:flex;flex-wrap:wrap;gap:8px;">
      @foreach(['512' => 'معمولی', '1K' => 'خوب', '2K' => 'عالی'] as $val => $lbl)
      <label style="cursor:pointer;">
        <input type="radio" name="output[quality]" value="{{ $val }}"
          {{ $val === '1K' ? 'checked' : '' }} style="display:none;" class="cp-quality-radio">
        <span class="cp-quality-btn" data-val="{{ $val }}"
          style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:9px;border:1px solid rgba(255,255,255,0.12);background:rgba(255,255,255,0.05);font-size:12px;font-weight:600;color:rgba(255,255,255,0.6);transition:all 0.18s;">
          {{ $lbl }}
          <span style="font-size:10px;color:rgba(255,255,255,0.35);">{{ $val }}</span>
        </span>
      </label>
      @endforeach
    </div>

  </div>
</div>

<style>
input[type=radio].cp-ratio-radio:checked + .cp-ratio-btn,
input[type=radio].cp-quality-radio:checked + .cp-quality-btn {
  border-color: #a07af5;
  background: rgba(160,122,245,0.12);
  color: #a07af5;
}
</style>