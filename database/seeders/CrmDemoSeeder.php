<?php

namespace Database\Seeders;

use App\Models\Crm\CrmPersonnel;
use App\Models\Crm\CrmProject;
use App\Models\Crm\CrmTask;
use App\Models\Crm\CrmMicrotask;
use App\Models\Crm\CrmAttendance;
use App\Models\Crm\CrmActivityLog;
use Illuminate\Database\Seeder;

/**
 * دیتای نمونه‌ی CRM برای تست لوکال.
 * اجرا: php artisan db:seed --class=Database\\Seeders\\CrmDemoSeeder
 * توجه: برای جلوگیری از داده‌ی تکراری، اگر پرسنلی موجود باشد کاری نمی‌کند.
 */
class CrmDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (CrmPersonnel::count() > 0) {
            $this->command?->warn('CRM از قبل داده دارد — CrmDemoSeeder رد شد.');
            return;
        }

        // ─── پرسنل ───────────────────────────────────────────
        $ali = CrmPersonnel::create([
            'name' => 'علی محمدی', 'mobile' => '09121234567', 'role' => 'مدیر کل',
            'email' => 'ali@vatan.test', 'join_date' => '1403/01/15', 'active' => true,
        ]);
        $sara = CrmPersonnel::create([
            'name' => 'سارا احمدی', 'mobile' => '09351234567', 'role' => 'برنامه‌نویس',
            'email' => 'sara@vatan.test', 'join_date' => '1403/03/10', 'active' => true,
        ]);
        $reza = CrmPersonnel::create([
            'name' => 'رضا کریمی', 'mobile' => '09181234567', 'role' => 'گرافیست',
            'email' => 'reza@vatan.test', 'join_date' => '1403/05/20', 'active' => true,
        ]);
        $nazi = CrmPersonnel::create([
            'name' => 'نازنین رستمی', 'mobile' => '09901234567', 'role' => 'تولیدکننده محتوا',
            'email' => 'nazanin@vatan.test', 'join_date' => '1403/07/01', 'active' => true,
        ]);

        // ─── پروژه‌ها ─────────────────────────────────────────
        $appProject = CrmProject::create([
            'name' => 'اپلیکیشن موبایل وطن', 'emoji' => '📱', 'status' => 'inprogress',
            'description' => 'ساخت اپلیکیشن iOS و اندروید', 'deadline' => '1404/09/01',
            'start_date' => '1404/03/01', 'end_date' => '1404/09/01', 'manager_id' => $ali->id,
        ]);
        $appProject->members()->sync([$ali->id, $sara->id, $reza->id]);

        $siteProject = CrmProject::create([
            'name' => 'وب‌سایت شرکتی', 'emoji' => '🌐', 'status' => 'planning',
            'description' => 'طراحی و توسعه‌ی وب‌سایت رسمی', 'deadline' => '1404/07/15',
            'start_date' => '1404/04/01', 'end_date' => '1404/07/15', 'manager_id' => $ali->id,
        ]);
        $siteProject->members()->sync([$reza->id, $nazi->id]);

        $campaignProject = CrmProject::create([
            'name' => 'کمپین تبلیغاتی پاییز', 'emoji' => '📣', 'status' => 'waiting',
            'description' => 'برنامه‌ریزی محتوای شبکه‌های اجتماعی', 'deadline' => '1404/08/10',
            'start_date' => '1404/06/15', 'end_date' => '1404/08/10', 'manager_id' => $sara->id,
        ]);
        $campaignProject->members()->sync([$nazi->id, $sara->id]);

        // ─── تسک‌ها + میکروتسک‌ها ─────────────────────────────
        $t1 = CrmTask::create([
            'project_id' => $appProject->id, 'title' => 'طراحی رابط کاربری',
            'description' => 'ساخت وایرفریم و طراحی صفحات اصلی', 'priority' => 'high',
            'status' => 'inprogress', 'done' => false, 'deadline' => '1404/06/30',
            'start_date' => '1404/03/05', 'assignee_id' => $reza->id,
        ]);
        $this->micros($t1->id, [['صفحه لاگین', true], ['صفحه اصلی', true], ['پروفایل کاربر', false]]);

        $t2 = CrmTask::create([
            'project_id' => $appProject->id, 'title' => 'راه‌اندازی API',
            'description' => 'اتصال اپ به بک‌اند', 'priority' => 'urgent',
            'status' => 'todo', 'done' => false, 'deadline' => '1404/07/10',
            'start_date' => '1404/06/10', 'assignee_id' => $sara->id,
        ]);
        $this->micros($t2->id, [['احراز هویت', false], ['مدل داده', false], ['تست endpointها', false]]);

        $t3 = CrmTask::create([
            'project_id' => $siteProject->id, 'title' => 'تولید محتوای صفحات',
            'description' => 'نوشتن متن معرفی و خدمات', 'priority' => 'medium',
            'status' => 'todo', 'done' => false, 'deadline' => '1404/07/05',
            'start_date' => '1404/06/12', 'assignee_id' => $nazi->id,
        ]);
        $this->micros($t3->id, [['صفحه درباره ما', true], ['صفحه خدمات', false]]);

        $t4 = CrmTask::create([
            'project_id' => $siteProject->id, 'title' => 'تنظیم دامنه و هاست',
            'description' => '', 'priority' => 'low', 'status' => 'done', 'done' => true,
            'deadline' => '1404/06/01', 'start_date' => '1404/05/25', 'assignee_id' => $reza->id,
            'completed_at' => now(),
        ]);

        $t5 = CrmTask::create([
            'project_id' => $campaignProject->id, 'title' => 'تقویم محتوایی',
            'description' => 'برنامه‌ریزی پست‌های ماهانه', 'priority' => 'high',
            'status' => 'backlog', 'done' => false, 'deadline' => '1404/07/20',
            'start_date' => '1404/06/20', 'assignee_id' => $nazi->id,
        ]);
        $this->micros($t5->id, [['اینستاگرام', false], ['لینکدین', false], ['تلگرام', false]]);

        // ─── حضور و غیاب (چند روز نمونه برای ماه جاری) ────────
        $month = '1404/04';
        $people = [$ali, $sara, $reza];
        foreach ($people as $pi => $person) {
            for ($d = 1; $d <= 6; $d++) {
                $day = str_pad((string) ($d + $pi), 2, '0', STR_PAD_LEFT);
                $checkIn = sprintf('%02d:%02d', rand(8, 9), rand(0, 59));
                $checkOut = ($d % 4 === 0) ? null : sprintf('%02d:%02d', rand(17, 20), rand(0, 59));
                CrmAttendance::create([
                    'personnel_id' => $person->id,
                    'date' => $month . '/' . $day,
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'total_hours' => CrmAttendance::calcHours($checkIn, $checkOut),
                    'note' => '',
                ]);
            }
        }

        // ─── لاگ فعالیت نمونه ─────────────────────────────────
        CrmActivityLog::log('project', 'ایجاد پروژه: ' . $appProject->name, $ali->id, $ali->name, $appProject->id);
        CrmActivityLog::log('project', 'ایجاد پروژه: ' . $siteProject->name, $ali->id, $ali->name, $siteProject->id);
        CrmActivityLog::log('task', 'ایجاد تسک: ' . $t1->title, $reza->id, $reza->name, $appProject->id, $t1->id);
        CrmActivityLog::log('task', 'تکمیل تسک: ' . $t4->title, $reza->id, $reza->name, $siteProject->id, $t4->id);

        $this->command?->info('✓ دیتای نمونه‌ی CRM ساخته شد: ۴ پرسنل، ۳ پروژه، ۵ تسک، حضوروغیاب و لاگ فعالیت.');
    }

    private function micros(string $taskId, array $items): void
    {
        foreach ($items as $i => [$text, $done]) {
            CrmMicrotask::create([
                'task_id' => $taskId, 'text' => $text, 'done' => $done, 'sort_order' => $i,
            ]);
        }
    }
}
