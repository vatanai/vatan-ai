<?php

namespace Tests\Unit;

use App\Models\AiModel;
use PHPUnit\Framework\TestCase;

class AiModelCreditGradeTest extends TestCase
{
    public function test_only_grade_three_and_four_models_allow_promotional_credits(): void
    {
        $gradeOne = new AiModel(['capability_config' => ['quality_score' => 9.5]]);
        $gradeTwo = new AiModel(['capability_config' => ['quality_score' => 9.0]]);
        $gradeThree = new AiModel(['capability_config' => ['quality_score' => 8.5]]);
        $gradeFour = new AiModel(['capability_config' => ['quality_score' => 8.4]]);

        self::assertSame(1, $gradeOne->pricingGrade());
        self::assertSame(2, $gradeTwo->pricingGrade());
        self::assertSame(3, $gradeThree->pricingGrade());
        self::assertSame(4, $gradeFour->pricingGrade());
        self::assertFalse($gradeOne->allowsPromotionalCredits());
        self::assertFalse($gradeTwo->allowsPromotionalCredits());
        self::assertTrue($gradeThree->allowsPromotionalCredits());
        self::assertTrue($gradeFour->allowsPromotionalCredits());
    }
}
