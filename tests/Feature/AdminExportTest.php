<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\GeneratedImage;
use App\Models\Plan;
use App\Models\PlanPurchase;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminExportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();

        Schema::create('admins', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('admin');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('plan_code')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedBigInteger('price')->default(0);
            $table->unsignedInteger('tokens')->default(0);
            $table->string('status')->default('active');
            $table->string('model_tier_key')->default('professional');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('phone')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('avatar')->nullable();
            $table->string('password')->nullable();
            $table->string('status')->default('active');
            $table->string('customer_segment')->default('regular');
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->unsignedInteger('tokens')->default(0);
            $table->unsignedInteger('tokens_purchased')->default(0);
            $table->unsignedInteger('tokens_used')->default(0);
            $table->unsignedInteger('promotional_tokens')->default(0);
            $table->string('referral_code')->nullable();
            $table->unsignedBigInteger('referred_by')->nullable();
            $table->timestamp('referral_attributed_at')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedInteger('login_count')->default(0);
            $table->timestamps();
        });

        Schema::create('generated_images', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('image_path');
            $table->text('user_prompt')->nullable();
            $table->decimal('cost', 10, 6)->default(0);
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
        });

        Schema::create('plan_purchases', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number')->nullable()->unique();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->string('plan_code')->nullable();
            $table->string('plan_name');
            $table->string('customer_segment')->default('regular');
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->unsignedInteger('granted_tokens')->default(0);
            $table->json('plan_snapshot');
            $table->string('status')->default('pending');
            $table->string('gateway')->nullable();
            $table->string('gateway_track_id')->nullable()->unique();
            $table->string('gateway_reference')->nullable();
            $table->string('gateway_status')->nullable();
            $table->string('billing_name')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('billing_phone')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('callback_payload')->nullable();
            $table->string('payment_reference')->nullable()->unique();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('purchased_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_admin_can_export_only_selected_users_with_current_fields(): void
    {
        $admin = $this->admin();
        $plan = $this->plan();
        $selected = $this->user('کاربر منتخب', 'selected@example.test', $plan);
        $other = $this->user('کاربر دیگر', 'other@example.test', $plan);
        GeneratedImage::query()->create([
            'user_id' => $selected->id,
            'image_path' => 'generated/selected.jpg',
            'cost' => 1,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.users.export', ['user_ids' => [$selected->id]]))
            ->assertOk()
            ->assertDownload();

        $csv = $response->streamedContent();
        self::assertStringContainsString('شناسه کاربر', $csv);
        self::assertStringContainsString('selected@example.test', $csv);
        self::assertStringContainsString('کاربر منتخب', $csv);
        self::assertStringNotContainsString($other->email, $csv);
    }

    public function test_admin_can_export_only_selected_plan_purchases_with_user_data(): void
    {
        $admin = $this->admin();
        $plan = $this->plan();
        $selectedUser = $this->user('خریدار منتخب', 'buyer@example.test', $plan);
        $otherUser = $this->user('خریدار دیگر', 'other-buyer@example.test', $plan);
        $selectedPurchase = $this->purchase($selectedUser, $plan, 'PLN-SELECTED-001');
        $this->purchase($otherUser, $plan, 'PLN-OTHER-001');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.orders.plan-purchases'))
            ->assertOk()
            ->assertSee('buyer@example.test')
            ->assertSee('اطلاعات کامل کاربر');

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.orders.plan-purchases.export', ['ids' => [$selectedPurchase->id]]))
            ->assertOk()
            ->assertDownload();

        $csv = $response->streamedContent();
        self::assertStringContainsString('شماره سفارش', $csv);
        self::assertStringContainsString('PLN-SELECTED-001', $csv);
        self::assertStringContainsString('buyer@example.test', $csv);
        self::assertStringContainsString('خریدار منتخب', $csv);
        self::assertStringNotContainsString('PLN-OTHER-001', $csv);
    }

    private function admin(): Admin
    {
        return Admin::query()->create([
            'name' => 'مدیر تست',
            'email' => 'admin-export@example.test',
            'password' => 'password',
            'role' => 'leader',
            'is_active' => true,
        ]);
    }

    private function plan(): Plan
    {
        return Plan::query()->create([
            'plan_code' => 'PLN-PRO',
            'name' => 'حرفه‌ای',
            'slug' => 'professional',
            'price' => 485000,
            'tokens' => 515,
            'status' => 'active',
        ]);
    }

    private function user(string $name, string $email, Plan $plan): User
    {
        return User::query()->create([
            'name' => $name,
            'last_name' => 'تست',
            'email' => $email,
            'phone' => '09120000000',
            'birth_date' => '1995-03-21',
            'password' => 'password',
            'status' => 'active',
            'customer_segment' => 'loyal',
            'plan_id' => $plan->id,
            'tokens' => 120,
            'tokens_purchased' => 100,
            'tokens_used' => 30,
            'registered_at' => now()->subDay(),
            'last_login_at' => now(),
            'login_count' => 4,
        ]);
    }

    private function purchase(User $user, Plan $plan, string $orderNumber): PlanPurchase
    {
        return PlanPurchase::query()->create([
            'order_number' => $orderNumber,
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan_code' => $plan->plan_code,
            'plan_name' => $plan->name,
            'customer_segment' => $user->customer_segment,
            'paid_amount' => 485000,
            'granted_tokens' => 515,
            'plan_snapshot' => ['name' => $plan->name],
            'status' => PlanPurchase::COMPLETED,
            'gateway' => 'zibal',
            'gateway_track_id' => 'TRACK-' . $orderNumber,
            'gateway_reference' => 'REF-' . $orderNumber,
            'payment_reference' => 'PAY-' . $orderNumber,
            'initiated_at' => now()->subMinute(),
            'verified_at' => now(),
            'purchased_at' => now(),
        ]);
    }
}
