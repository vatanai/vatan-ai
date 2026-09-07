<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class FinanceTransaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference_code', 'direction', 'category', 'title', 'amount_original',
        'currency', 'exchange_rate_irr', 'exchange_rate_toman', 'amount_irr', 'amount_toman', 'status', 'occurred_at',
        'due_at', 'paid_at', 'cost_center_id', 'vendor_id', 'payment_method_id',
        'invoice_path', 'notes', 'tags', 'source_type', 'source_id', 'source_key',
        'user_id', 'plan_id', 'product_id', 'order_id', 'plan_purchase_id',
        'ai_model_id', 'provider', 'acquisition_channel', 'metadata', 'created_by',
        'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_original' => 'decimal:4',
            'exchange_rate_irr' => 'decimal:4',
            'exchange_rate_toman' => 'decimal:4',
            'amount_irr' => 'decimal:2',
            'amount_toman' => 'decimal:2',
            'occurred_at' => 'datetime',
            'due_at' => 'date',
            'paid_at' => 'datetime',
            'approved_at' => 'datetime',
            'tags' => 'array',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (FinanceTransaction $transaction): void {
            if (! $transaction->reference_code) {
                do {
                    $code = 'FIN-' . now()->format('ymd') . '-' . strtoupper(Str::random(6));
                } while (static::withTrashed()->where('reference_code', $code)->exists());

                $transaction->reference_code = $code;
            }
        });
    }

    public function costCenter() { return $this->belongsTo(FinanceCostCenter::class, 'cost_center_id'); }
    public function vendor() { return $this->belongsTo(FinanceVendor::class, 'vendor_id'); }
    public function paymentMethod() { return $this->belongsTo(FinancePaymentMethod::class, 'payment_method_id'); }
    public function creator() { return $this->belongsTo(Admin::class, 'created_by'); }
    public function approver() { return $this->belongsTo(Admin::class, 'approved_by'); }
}
