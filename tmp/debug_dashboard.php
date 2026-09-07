<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/admin/products/dashboard', 'GET', ['product_id' => 155]);
$kernel->bootstrap();
$app->instance('request', $request);
auth('admin')->loginUsingId(1);
try {
    $response = app('App\Http\Controllers\Admin\VideoStudioController')->index($request);
    echo get_class($response) . PHP_EOL;
    echo $response->getStatusCode() . PHP_EOL;
} catch (Throwable $e) {
    echo get_class($e) . '|' . $e->getMessage() . '|' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
