<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$request = Illuminate\Http\Request::create('/api/lgu-users', 'POST', [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john.doe@example.com',
    // intentionally omitting lgu_id to test if it passes validation
]);

try {
    $rules = [
        'first_name' => 'required|string|max:64',
        'last_name' => 'required|string|max:64',
        'email' => 'required|email|max:128|unique:lgu_users,email',
        'lgu_id' => 'nullable|exists:lgus,id',
    ];
    $validator = Illuminate\Support\Facades\Validator::make($request->all(), $rules);
    
    if ($validator->fails()) {
        echo "Validation Failed:\n";
        print_r($validator->errors()->toArray());
    } else {
        echo "Validation Passed!\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
