<?php

namespace Tests\Unit;

use App\Models\AiModel;
use PHPUnit\Framework\TestCase;

class AiModelCreditGradeTest extends TestCase
{
    public function test_quality_scores_map_to_pricing_grades(): void
    {
        $gradeOne = new AiModel(['capability_config' => ['quality_score' => 9.5]]);
        $gradeTwo = new AiModel(['capability_config' => ['quality_score' => 9.0]]);
        $gradeThree = new AiModel(['capability_config' => ['quality_score' => 8.5]]);
        $gradeFour = new AiModel(['capability_config' => ['quality_score' => 8.4]]);

        self::assertSame(1, $gradeOne->pricingGrade());
        self::assertSame(2, $gradeTwo->pricingGrade());
        self::assertSame(3, $gradeThree->pricingGrade());
        self::assertSame(4, $gradeFour->pricingGrade());
    }
}
