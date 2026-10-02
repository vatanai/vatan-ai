{{-- یک ردیف اقدام در فرم اتومیشن: $i, $action --}}
@php($type = $action['type'] ?? 'send_dm')
<div class="si-action">
  <select name="actions[{{ $i }}][type]" class="input-pro" data-field="type" aria-label="نوع اقدام">
    @foreach(\App\Services\SmartInstagram\Automation\AutomationEngine::ACTIONS as $k => $l)<option value="{{ $k }}" @selected($type === $k)>{{ $l }}</option>@endforeach
  </select>
  <div class="si-action-extra">
    <div data-show-for="public_reply,private_reply,send_dm,create_task,create_deal"><textarea name="actions[{{ $i }}][text]" class="input-pro" rows="2" style="min-height:60px" maxlength="1000" data-field="text">{{ $action['text'] ?? '' }}</textarea></div>
    <div data-show-for="product_card" class="si-card-fields">
      <select name="actions[{{ $i }}][product_id]" class="input-pro" data-field="product_id"><option value="">انتخاب محصول از کاتالوگ</option>@foreach(($products ?? collect()) as $product)<option value="{{ $product->id }}" @selected((int) ($action['product_id'] ?? 0) === $product->id)>{{ $product->name_fa }}</option>@endforeach</select>
      <input name="actions[{{ $i }}][card_title]" class="input-pro" maxlength="100" placeholder="عنوان کارت (اختیاری؛ پیش‌فرض نام محصول)" value="{{ $action['card_title'] ?? '' }}">
      <textarea name="actions[{{ $i }}][card_subtitle]" class="input-pro" rows="2" maxlength="160" placeholder="توضیح کوتاه کارت (اختیاری)">{{ $action['card_subtitle'] ?? '' }}</textarea>
      <textarea name="actions[{{ $i }}][card_message]" class="input-pro" rows="2" maxlength="500" placeholder="متن دایرکت کارت (اختیاری)">{{ $action['card_message'] ?? '' }}</textarea>
      <input name="actions[{{ $i }}][card_image_url]" class="input-pro si-ltr" maxlength="1000" placeholder="لینک تصویر عمومی (اختیاری با انتخاب محصول)" value="{{ $action['card_image_url'] ?? '' }}">
      <input name="actions[{{ $i }}][card_button_text]" class="input-pro" maxlength="30" placeholder="متن دکمه؛ مثلاً مشاهده صفحه" value="{{ $action['card_button_text'] ?? '' }}">
      <input name="actions[{{ $i }}][card_button_url]" class="input-pro si-ltr" maxlength="1000" placeholder="لینک صفحه هدف (اختیاری با انتخاب محصول)" value="{{ $action['card_button_url'] ?? '' }}">
    </div>
    <div data-show-for="add_tag"><input name="actions[{{ $i }}][tag]" class="input-pro" maxlength="60" placeholder="نام برچسب" value="{{ $action['tag'] ?? '' }}"></div>
    <div data-show-for="assign"><select name="actions[{{ $i }}][admin_id]" class="input-pro"><option value="">بدون مسئول</option>@foreach(($admins ?? collect()) as $admin)<option value="{{ $admin->id }}" @selected((int) ($action['admin_id'] ?? 0) === $admin->id)>{{ $admin->name }}</option>@endforeach</select></div>
    <div data-show-for="set_lead_status"><select name="actions[{{ $i }}][stage]" class="input-pro">@foreach(config('smart_instagram.lead_statuses') as $k => $l)<option value="{{ $k }}" @selected(($action['stage'] ?? '') === $k)>{{ $l }}</option>@endforeach</select></div>
    <div data-show-for="create_task"><input type="number" min="1" max="720" name="actions[{{ $i }}][due_hours]" class="input-pro" placeholder="موعد (ساعت بعد)" value="{{ $action['due_hours'] ?? 24 }}"></div>
    <div data-show-for="ai_suggest,request_human,stop"><span class="si-help">این اقدام تنظیم اضافه ندارد.</span></div>
  </div>
  <button type="button" class="icon-action-btn danger" data-remove-action aria-label="حذف اقدام"><i class="fa-solid fa-trash"></i></button>
</div>
