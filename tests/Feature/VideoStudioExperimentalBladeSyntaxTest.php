<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class VideoStudioExperimentalBladeSyntaxTest extends TestCase
{
    public function test_step_one_partial_compiles_to_valid_php(): void
    {
        $blade = file_get_contents(resource_path('views/admin/video-studio/experimental/step-one.blade.php'));

        $this->assertNotFalse($blade);

        $compiled = Blade::compileString($blade);
        $compiledPath = tempnam(sys_get_temp_dir(), 'video-studio-v2-blade-');

        $this->assertNotFalse($compiledPath);
        file_put_contents($compiledPath, $compiled);

        try {
            exec(sprintf('%s -l %s 2>&1', escapeshellarg(PHP_BINARY), escapeshellarg($compiledPath)), $output, $status);

            $this->assertSame(0, $status, implode(PHP_EOL, $output));
        } finally {
            @unlink($compiledPath);
        }
    }
}
