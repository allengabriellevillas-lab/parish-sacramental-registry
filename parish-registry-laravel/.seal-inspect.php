<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$parish = App\Models\ParishSetting::first();
$disk = Illuminate\Support\Facades\Storage::disk('public');
$seal = $parish ? $parish->seal_image_path : null;
$logo = $parish ? $parish->logo_image_path : null;
echo json_encode([
    'seal_configured' => (bool) $seal,
    'seal_on_public_disk' => $seal ? $disk->exists($seal) : false,
    'seal_in_legacy_uploads' => $seal ? is_file(base_path('../' . $seal)) : false,
    'logo_configured' => (bool) $logo,
]);
