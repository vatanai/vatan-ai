<?php

namespace App\Services\Finance;

class FinanceProfitCalculator
{
    public const FORMULA_VERSION = 'v1';

    public function plan(
        float $sales,
        int $credits,
        float $modelCostPerCredit,
        float $gatewayPercent,
        float $infrastructurePercent,
        float $workforcePercent,
    ): array {
        $gateway = $this->percent($sales, $gatewayPercent);
        $modelCost = max(0, $credits) * max(0, $modelCostPerCredit);
        $infrastructure = $this->percent($sales, $infrastructurePercent);
        $workforce = $this->percent($sales, $workforcePercent);
        $grossProfit = $sales - $gateway - $modelCost;
        $netProfit = $grossProfit - $infrastructure - $workforce;

        return $this->rounded([
            'gross_sales_irr' => $sales,
            'gateway_fee_irr' => $gateway,
            'estimated_model_cost_irr' => $modelCost,
            'allocated_infrastructure_irr' => $infrastructure,
            'allocated_workforce_irr' => $workforce,
            'gross_profit_irr' => $grossProfit,
            'net_profit_irr' => $netProfit,
            'margin_percent' => $sales > 0 ? ($netProfit / $sales) * 100 : 0,
        ]);
    }

    public function order(
        float $allocatedRevenue,
        float $directCost,
        float $infrastructurePercent,
        float $workforcePercent,
    ): array {
        $infrastructure = $this->percent($allocatedRevenue, $infrastructurePercent);
        $workforce = $this->percent($allocatedRevenue, $workforcePercent);
        $grossProfit = $allocatedRevenue - $directCost;
        $netProfit = $grossProfit - $infrastructure - $workforce;

        return $this->rounded([
            'allocated_revenue_irr' => $allocatedRevenue,
            'direct_cost_irr' => $directCost,
            'allocated_infrastructure_irr' => $infrastructure,
            'allocated_workforce_irr' => $workforce,
            'gross_profit_irr' => $grossProfit,
            'net_profit_irr' => $netProfit,
            'margin_percent' => $allocatedRevenue > 0 ? ($netProfit / $allocatedRevenue) * 100 : 0,
        ]);
    }

    public function planToman(
        float $sales,
        int $credits,
        float $modelCostPerCredit,
        float $gatewayPercent,
        float $infrastructurePercent,
        float $workforcePercent,
    ): array {
        $values = $this->plan($sales, $credits, $modelCostPerCredit, $gatewayPercent, $infrastructurePercent, $workforcePercent);

        return [
            'gross_sales_toman' => $values['gross_sales_irr'],
            'gateway_fee_toman' => $values['gateway_fee_irr'],
            'estimated_model_cost_toman' => $values['estimated_model_cost_irr'],
            'allocated_infrastructure_toman' => $values['allocated_infrastructure_irr'],
            'allocated_workforce_toman' => $values['allocated_workforce_irr'],
            'gross_profit_toman' => $values['gross_profit_irr'],
            'net_profit_toman' => $values['net_profit_irr'],
            'margin_percent' => $values['margin_percent'],
        ];
    }

    public function orderToman(
        float $allocatedRevenue,
        float $directCost,
        float $infrastructurePercent,
        float $workforcePercent,
    ): array {
        $values = $this->order($allocatedRevenue, $directCost, $infrastructurePercent, $workforcePercent);

        return [
            'allocated_revenue_toman' => $values['allocated_revenue_irr'],
            'direct_cost_toman' => $values['direct_cost_irr'],
            'allocated_infrastructure_toman' => $values['allocated_infrastructure_irr'],
            'allocated_workforce_toman' => $values['allocated_workforce_irr'],
            'gross_profit_toman' => $values['gross_profit_irr'],
            'net_profit_toman' => $values['net_profit_irr'],
            'margin_percent' => $values['margin_percent'],
        ];
    }

    private function percent(float $amount, float $percent): float
    {
        return max(0, $amount) * max(0, $percent) / 100;
    }

    private function rounded(array $values): array
    {
        foreach ($values as $key => $value) {
            $values[$key] = round($value, $key === 'margin_percent' ? 2 : 2);
        }

        return $values;
    }
}
