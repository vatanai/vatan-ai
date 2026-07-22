<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsMessage;
use App\Models\SmsSetting;
use App\Models\SmsTemplate;
use App\Models\SmsProvider;
use App\Models\SmsCampaign;
use App\Services\MeliPayamakService;
use App\Services\SmsEventService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SmsController extends Controller
{
    public function index()
    {
        $stats = [
            'sent' => SmsMessage::whereIn('status', ['sent','scheduled'])->count(),
            'failed' => SmsMessage::where('status', 'failed')->count(),
            'today' => SmsMessage::whereDate('created_at', today())->count(),
            'week' => SmsMessage::where('created_at', '>=', now()->startOfWeek())->count(),
            'templates' => SmsTemplate::where('is_active', true)->count(),
            'campaigns' => SmsCampaign::count(),
        ];
        $providers = SmsProvider::get();
        return view('admin.sms.index', compact('stats','providers'));
    }

    public function compose() { $settings=SmsSetting::query()->pluck('value','key'); return view('admin.sms.compose', compact('settings')); }
    public function history() { $messages=SmsMessage::latest()->paginate(25); return view('admin.sms.history', compact('messages')); }
    public function campaigns() { $campaigns=SmsCampaign::with('template')->latest()->paginate(20); $templates=SmsTemplate::where('is_active',true)->get(); return view('admin.sms.campaigns',compact('campaigns','templates')); }
    public function providers() { abort_unless(auth('admin')->user()?->isLeader(),403); $providers=SmsProvider::latest()->get(); return view('admin.sms.providers',compact('providers')); }

    public function storeProvider(Request $request)
    {
        abort_unless(auth('admin')->user()?->isLeader(),403);
        $data=$request->validate(['name'=>'required|string|max:100','driver'=>'required|in:melipayamak,custom','api_key'=>'nullable|string','sender'=>'nullable|string|max:30','base_url'=>'nullable|url','is_active'=>'nullable|boolean','is_default'=>'nullable|boolean']);
        $data['is_active']=$request->boolean('is_active');$data['is_default']=$request->boolean('is_default');
        if($data['is_default']) SmsProvider::query()->update(['is_default'=>false]); SmsProvider::create($data);
        return back()->with('success','ارائه‌دهنده پیامک اضافه شد.');
    }

    public function updateProvider(Request $request, SmsProvider $provider)
    {
        abort_unless(auth('admin')->user()?->isLeader(),403);
        $data=$request->validate(['name'=>'required|string|max:100','api_key'=>'nullable|string','sender'=>'nullable|string|max:30','base_url'=>'nullable|url','is_active'=>'nullable|boolean','is_default'=>'nullable|boolean']);
        if(empty($data['api_key'])) unset($data['api_key']);$data['is_active']=$request->boolean('is_active');$data['is_default']=$request->boolean('is_default');
        if($data['is_default']) SmsProvider::where('id','!=',$provider->id)->update(['is_default'=>false]);$provider->update($data);
        return back()->with('success','تنظیمات ارائه‌دهنده ذخیره شد.');
    }

    public function testProvider(SmsProvider $provider, MeliPayamakService $sms)
    {
        abort_unless(auth('admin')->user()?->isLeader(),403);
        try{$balance=$sms->balance();$provider->update(['last_error'=>null,'last_checked_at'=>now(),'settings'=>array_merge($provider->settings??[],['last_balance'=>$balance])]);return back()->with('success','اتصال بررسی شد.');}
        catch(\Throwable $e){$provider->update(['last_error'=>$e->getMessage(),'last_checked_at'=>now()]);return back()->withErrors(['provider'=>$e->getMessage()]);}
    }

    public function templates(Request $request)
    {
        $event = $request->string('event')->toString();
        $templates = SmsTemplate::query()->when($event, fn($q) => $q->where('event_key', $event))->latest()->get();
        $templatePayload = $templates->map(fn (SmsTemplate $template) => [
            'id'=>$template->id, 'event_key'=>$template->event_key, 'name'=>$template->name,
            'body'=>$template->body, 'is_active'=>$template->is_active, 'is_default'=>$template->is_default,
        ])->values();
        $events = config('sms_events.events', []);
        $settings = SmsSetting::query()->pluck('value', 'key');
        return view('admin.sms.templates', compact('templates', 'templatePayload', 'events', 'settings', 'event'));
    }

    public function storeTemplate(Request $request)
    {
        $data = $this->validateTemplate($request);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');
        DB::transaction(function () use ($data) {
            if ($data['is_default']) SmsTemplate::where('event_key', $data['event_key'])->update(['is_default'=>false]);
            SmsTemplate::create($data);
        });
        return back()->with('success', 'الگوی جدید پیامک اضافه شد.');
    }

    public function updateTemplate(Request $request, SmsTemplate $template)
    {
        $data = $this->validateTemplate($request);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');
        DB::transaction(function () use ($template, $data) {
            if ($data['is_default']) SmsTemplate::where('event_key', $data['event_key'])->where('id', '!=', $template->id)->update(['is_default'=>false]);
            $template->update($data);
        });
        return back()->with('success', 'الگوی پیامک به‌روزرسانی شد.');
    }

    public function destroyTemplate(SmsTemplate $template)
    {
        if ($template->is_default && SmsTemplate::where('event_key', $template->event_key)->where('id', '!=', $template->id)->exists()) {
            return back()->withErrors(['template'=>'ابتدا یک الگوی دیگر را به‌عنوان پیش‌فرض انتخاب کنید.']);
        }
        $template->delete();
        return back()->with('success', 'الگوی پیامک حذف شد.');
    }

    public function toggleTemplate(SmsTemplate $template)
    {
        $template->update(['is_active'=>!$template->is_active]);
        return back()->with('success', $template->is_active ? 'الگو فعال شد.' : 'الگو غیرفعال شد.');
    }

    public function defaultTemplate(SmsTemplate $template)
    {
        DB::transaction(function () use ($template) {
            SmsTemplate::where('event_key', $template->event_key)->update(['is_default'=>false]);
            $template->update(['is_default'=>true, 'is_active'=>true]);
        });
        return back()->with('success', 'الگوی پیش‌فرض تغییر کرد.');
    }

    public function testTemplate(Request $request, SmsTemplate $template, SmsEventService $events)
    {
        $phone = $request->validate(['phone'=>['required','regex:/^09\d{9}$/']])['phone'];
        SmsSetting::updateOrCreate(['key'=>'admin_test_phone'], ['value'=>$phone]);
        return $events->test($template, $phone)
            ? back()->with('success', 'پیامک تست با داده نمونه ارسال شد.')
            : back()->withErrors(['test'=>'ارسال تست انجام نشد؛ خط فرستنده و اتصال ملی‌پیامک را بررسی کنید.']);
    }

    public function send(Request $request, MeliPayamakService $sms)
    {
        $data = $request->validate([
            'mode' => ['required', 'in:simple,advanced,scheduled'],
            'recipients' => ['required', 'string'],
            'message' => ['required', 'string', 'max:1000'],
            'scheduled_at' => ['nullable', 'required_if:mode,scheduled', 'date', 'after:now'],
            'period' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);
        $recipients = collect(preg_split('/[\s,،;]+/', $data['recipients']))->filter()->unique()->values();
        if ($recipients->isEmpty() || $recipients->contains(fn ($phone) => !preg_match('/^09\d{9}$/', $phone))) {
            return back()->withErrors(['recipients' => 'شماره‌ها باید با فرمت 09123456789 و با ویرگول یا خط جدید جدا شوند.'])->withInput();
        }

        try {
            if ($data['mode'] === 'scheduled') {
                if ($recipients->count() !== 1) return back()->withErrors(['recipients' => 'ارسال زمان‌دار فعلاً برای یک گیرنده انجام می‌شود.'])->withInput();
                $sms->schedule($recipients->first(), $data['message'], date('n/j/Y H:i', strtotime($data['scheduled_at'])), $data['period'] ?? null);
            } elseif ($data['mode'] === 'advanced' || $recipients->count() > 1) {
                $sms->sendAdvanced($recipients->all(), $data['message']);
            } else {
                $sms->sendSimple($recipients->first(), $data['message']);
            }
            return back()->with('success', 'پیامک با موفقیت برای ارسال ثبت شد.');
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['sms' => $e->getMessage()])->withInput();
        }
    }

    public function settings(Request $request)
    {
        $data = $request->validate([
            'admin_test_phone' => ['nullable', 'regex:/^09\d{9}$/'],
            'credit_low_threshold' => ['required', 'integer', 'min:0', 'max:10000000'],
        ]);
        foreach ($data as $key=>$value) SmsSetting::updateOrCreate(['key'=>$key], ['value'=>(string)($value ?? '')]);
        return back()->with('success', 'تنظیمات عمومی پیامک ذخیره شد.');
    }

    private function validateTemplate(Request $request): array
    {
        $events = array_keys(config('sms_events.events', []));
        $data = $request->validate([
            'event_key'=>['required', Rule::in($events)], 'name'=>['required','string','max:100'],
            'body'=>['required','string','max:1000'], 'is_active'=>['nullable','boolean'], 'is_default'=>['nullable','boolean'],
        ]);
        preg_match_all('/\{([a-z_]+)\}/i', $data['body'], $matches);
        $allowed = config("sms_events.events.{$data['event_key']}.variables", []);
        $invalid = array_diff(array_unique($matches[1]), $allowed);
        if ($invalid) throw ValidationException::withMessages(['body'=>'متغیر نامعتبر در متن: {' . implode('}، {', $invalid) . '}']);
        return $data;
    }
}
