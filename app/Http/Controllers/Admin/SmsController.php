<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
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
    public function history(MeliPayamakService $sms)
    {
        $messages = SmsMessage::latest()->paginate(25);
        $templates = SmsTemplate::orderByDesc('sent_count')->get();
        $providers = SmsProvider::where('is_active', true)->orderByDesc('is_default')->get();
        $provider = $providers->first();
        $balanceSupported = $provider && ! str_contains((string) $provider->base_url, 'console.melipayamak.com/api');
        if ($provider && $balanceSupported && (!$provider->last_checked_at || $provider->last_checked_at->lt(now()->subMinutes(10)))) {
            try {
                $balance = $sms->balance();
                $provider->update([
                    'last_error' => null,
                    'last_checked_at' => now(),
                    'settings' => array_merge($provider->settings ?? [], ['last_balance' => $balance]),
                ]);
                $providers = SmsProvider::where('is_active', true)->orderByDesc('is_default')->get();
            } catch (\Throwable $e) {
                $provider->update(['last_error'=>$e->getMessage(), 'last_checked_at'=>now()]);
            }
        }
        $historyStats = [
            'sent' => SmsMessage::where('status', 'sent')->count(),
            'today' => SmsMessage::where('status', 'sent')->whereDate('created_at', today())->count(),
            'failed' => SmsMessage::where('status', 'failed')->count(),
        ];
        return view('admin.sms.history', compact('messages', 'templates', 'providers', 'historyStats'));
    }
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
        try{$probe=$sms->probe();$provider->update(['last_error'=>null,'last_checked_at'=>now(),'settings'=>array_merge($provider->settings??[],['connection_status'=>'connected','connection_message'=>$probe['message']??null])]);return back()->with('success','اتصال ملی‌پیامک فعال است.');}
        catch(\Throwable $e){$provider->update(['last_error'=>$e->getMessage(),'last_checked_at'=>now()]);return back()->withErrors(['provider'=>$e->getMessage()]);}
    }

    public function templates(Request $request)
    {
        $filters = $request->validate([
            'event' => ['nullable', 'string', Rule::in(array_keys(config('sms_events.events', [])))],
            'state' => ['nullable', Rule::in(['active', 'inactive'])],
            'approval' => ['nullable', Rule::in(['approved', 'pending', 'rejected', 'not_applicable'])],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $event = $filters['event'] ?? '';
        $templates = SmsTemplate::query()
            ->when($event, fn($q) => $q->where('event_key', $event))
            ->when(($filters['state'] ?? null) === 'active', fn($q) => $q->where('is_active', true))
            ->when(($filters['state'] ?? null) === 'inactive', fn($q) => $q->where('is_active', false))
            ->when($filters['approval'] ?? null, fn($q, $approval) => $q->where('provider_approval_status', $approval))
            ->when($filters['q'] ?? null, fn($q, $search) => $q->where(fn($inner) => $inner->where('name', 'like', "%{$search}%")->orWhere('body', 'like', "%{$search}%")->orWhere('provider_template_id', 'like', "%{$search}%")))
            ->latest()->get();
        $templatePayload = $templates->map(fn (SmsTemplate $template) => [
            'id'=>$template->id, 'event_key'=>$template->event_key, 'name'=>$template->name,
            'body'=>$template->body, 'is_active'=>$template->is_active, 'is_default'=>$template->is_default,
            'provider_method'=>$template->provider_method, 'provider_template_id'=>$template->provider_template_id,
            'provider_variables'=>$template->provider_variables, 'provider_approval_status'=>$template->provider_approval_status,
        ])->values();
        $events = config('sms_events.events', []);
        $settings = SmsSetting::query()->pluck('value', 'key');
        $admins = Admin::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'phone']);
        $selectedTestAdminIds = collect(json_decode($settings['admin_test_admin_ids'] ?? '[]', true))
            ->map(fn ($id) => (int) $id)->filter()->values()->all();
        return view('admin.sms.templates', compact('templates', 'templatePayload', 'events', 'settings', 'event', 'filters', 'admins', 'selectedTestAdminIds'));
    }

    public function storeTemplate(Request $request)
    {
        $data = $this->validateTemplate($request);
        $data['provider_approval_status'] = $data['provider_method'] === 'shared' ? 'pending' : 'not_applicable';
        $data['provider_submitted_at'] = $data['provider_method'] === 'shared' ? now() : null;
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
        if ($data['provider_method'] === 'shared' && ($data['provider_template_id'] !== $template->provider_template_id || $template->provider_method !== 'shared')) {
            $data['provider_approval_status'] = 'pending';
            $data['provider_submitted_at'] = now();
        } elseif ($data['provider_method'] === 'shared') {
            $data['provider_approval_status'] = $template->provider_approval_status;
            $data['provider_submitted_at'] = $template->provider_submitted_at;
        }
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
        $data = $request->validate([
            'admin_ids' => ['nullable', 'array'],
            'admin_ids.*' => ['integer', Rule::exists('admins', 'id')->where('is_active', true)],
            'phone' => ['nullable', 'regex:/^09\d{9}$/'],
        ]);
        $adminIds = collect($data['admin_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $recipients = Admin::query()->whereIn('id', $adminIds)->where('is_active', true)->get()
            ->filter(fn (Admin $admin) => preg_match('/^09\d{9}$/', (string) $admin->phone))
            ->map(fn (Admin $admin) => ['name'=>$admin->name, 'phone'=>$admin->phone]);
        if (!empty($data['phone'])) {
            $recipients->push(['name'=>'شماره دلخواه', 'phone'=>$data['phone']]);
            SmsSetting::updateOrCreate(['key'=>'admin_test_phone'], ['value'=>$data['phone']]);
        }
        $recipients = $recipients->unique('phone')->values();
        if ($recipients->isEmpty()) {
            return back()->withErrors(['test'=>'حداقل یک مدیر دارای شماره موبایل یا یک شماره دلخواه برای تست انتخاب کنید.']);
        }

        SmsSetting::updateOrCreate(['key'=>'admin_test_admin_ids'], ['value'=>$adminIds->toJson()]);
        $failures = collect();
        $sent = 0;
        foreach ($recipients as $recipient) {
            $result = $events->testWithResult($template, $recipient['phone']);
            if ($result['success']) {
                $sent++;
            } else {
                $failures->push($recipient['name'] . ': ' . $result['error']);
            }
        }

        $template->forceFill([
            'last_test_status' => $sent > 0 && $failures->isEmpty() ? 'success' : ($sent > 0 ? 'partial' : 'failed'),
            'last_tested_at' => now(),
            'provider_approval_status' => $sent > 0 && $template->provider_method === 'shared'
                ? 'approved'
                : $template->provider_approval_status,
            'provider_note' => $sent > 0 && $template->provider_method === 'shared'
                ? 'تأیید با ارسال آزمایشی موفق'
                : $template->provider_note,
            'provider_checked_at' => $template->provider_method === 'shared' ? now() : $template->provider_checked_at,
        ])->save();

        if ($failures->isNotEmpty()) {
            $message = ($sent ? "{$sent} پیامک ارسال شد؛ " : 'ارسال انجام نشد؛ ') . $failures->implode(' | ');
            return back()->withErrors(['test'=>$message]);
        }
        return back()->with('success', "پیامک تست با داده نمونه برای {$sent} مدیر ارسال شد.");
    }

    public function syncTemplateStatuses(Request $request, SmsEventService $events)
    {
        $phone = Admin::query()->where('is_active', true)->whereNotNull('phone')->value('phone')
            ?: SmsSetting::valueOf('admin_test_phone');
        if (!$phone || !preg_match('/^09\d{9}$/', $phone)) {
            $message = 'ابتدا شماره تست مدیر را در پنجره تست یکی از الگوها ذخیره کنید.';
            return $request->expectsJson() ? response()->json(['message'=>$message], 422) : back()->withErrors(['sync'=>$message]);
        }

        $templates = SmsTemplate::where('provider_method', 'shared')
            ->whereNotNull('provider_template_id')
            ->whereIn('provider_approval_status', ['pending', 'not_configured'])
            ->get();
        $approved = 0;
        foreach ($templates->groupBy('provider_template_id') as $sameBodyIdTemplates) {
            $template = $sameBodyIdTemplates->first();
            $result = $events->testWithResult($template, $phone, 'approval-check');
            foreach ($sameBodyIdTemplates as $relatedTemplate) {
                $relatedTemplate->forceFill([
                    'provider_approval_status' => $result['success'] ? 'approved' : 'pending',
                    'provider_note' => $result['success'] ? 'تأیید با ارسال آزمایشی موفق' : 'آخرین بررسی: هنوز قابل ارسال نیست',
                    'provider_checked_at' => now(),
                ])->save();
                if ($result['success']) $approved++;
            }
        }

        $message = $approved
            ? "{$approved} الگو تأیید شد و پیامک آزمایشی آن به شماره مدیر رسید."
            : 'وضعیت بررسی شد؛ الگوی تازه‌ای هنوز قابل ارسال نیست.';
        return $request->expectsJson()
            ? response()->json(['message'=>$message, 'approved'=>$approved])
            : back()->with('success', $message);
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
            'body'=>['required','string','max:1000'],
            'provider_method'=>['required', Rule::in(['simple','shared'])],
            'provider_template_id'=>['nullable','required_if:provider_method,shared','string','max:100'],
            'provider_variables'=>['nullable','array'], 'provider_variables.*'=>['string','max:50'],
            'is_active'=>['nullable','boolean'], 'is_default'=>['nullable','boolean'],
        ]);
        preg_match_all('/\{([a-z_]+)\}/i', $data['body'], $matches);
        $allowed = config("sms_events.events.{$data['event_key']}.variables", []);
        $invalid = array_diff(array_unique($matches[1]), $allowed);
        if ($invalid) throw ValidationException::withMessages(['body'=>'متغیر نامعتبر در متن: {' . implode('}، {', $invalid) . '}']);
        $providerVariables = $data['provider_variables'] ?? [];
        if (array_diff($providerVariables, $allowed)) throw ValidationException::withMessages(['provider_variables'=>'ترتیب متغیرهای خدماتی معتبر نیست.']);
        $data['provider_variables'] = $data['provider_method'] === 'shared' ? array_values(array_unique($providerVariables)) : null;
        if ($data['provider_method'] === 'shared') {
            $approved = (array) config("sms_events.approved_shared_templates.{$data['event_key']}", []);
            if (($approved['provider_template_id'] ?? null) !== $data['provider_template_id']) {
                throw ValidationException::withMessages([
                    'provider_template_id' => 'برای این رویداد، فقط الگوی خدماتی تأییدشده قابل انتخاب است.',
                ]);
            }
            $approvedVariables = $approved['variables'] ?? [];
            if ($data['provider_variables'] !== [] && $data['provider_variables'] !== $approvedVariables) {
                throw ValidationException::withMessages([
                    'provider_variables' => 'ترتیب متغیرهای این الگوی خدماتی باید مطابق الگوی تأییدشده باشد.',
                ]);
            }
            $data['provider_variables'] = $approvedVariables;
        }
        if ($data['provider_method'] !== 'shared') {
            $data['provider_template_id'] = null;
            $data['provider_approval_status'] = 'not_applicable';
            $data['provider_submitted_at'] = null;
        }
        return $data;
    }
}
