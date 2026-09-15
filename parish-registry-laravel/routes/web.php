<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AuthController,RecordController,IssuanceController,ImportController,SettingsController,CertificateTemplateController,ProfileController,CertificateRequestController};

Route::get('/', fn()=>response()->file(public_path('parish-registry.html')));
Route::get('/certificate-request', fn()=>response()->file(public_path('certificate-request.html')));
Route::get('/request-status', fn()=>response()->file(public_path('certificate-request.html')));
Route::prefix('api/v1')->group(function(){
 Route::post('auth/login',[AuthController::class,'login']);
 Route::post('certificate-requests',[CertificateRequestController::class,'store']);
 Route::get('certificate-requests/track',[CertificateRequestController::class,'track']);
 Route::middleware('session-auth')->group(function(){
  Route::post('auth/logout',[AuthController::class,'logout']); Route::get('auth/me',[AuthController::class,'me']);
  Route::middleware('role:admin,staff,viewer')->group(function(){Route::get('records',[RecordController::class,'index']);Route::get('records/{record}',[RecordController::class,'show']);Route::get('issuance-logs',[IssuanceController::class,'index']);});
  Route::middleware('role:admin,staff')->group(function(){Route::get('certificate-requests',[CertificateRequestController::class,'index']);Route::get('certificate-requests/{certificateRequest}',[CertificateRequestController::class,'show']);Route::put('certificate-requests/{certificateRequest}',[CertificateRequestController::class,'update']);Route::get('certificate-requests/{certificateRequest}/attachment',[CertificateRequestController::class,'attachment']);Route::post('certificate-requests/{certificateRequest}/issue',[CertificateRequestController::class,'issue']);Route::post('records',[RecordController::class,'store']);Route::post('records/{record}/issue',[IssuanceController::class,'store']);Route::get('records/{record}/certificate.pdf',[IssuanceController::class,'certificate']);Route::post('imports',[ImportController::class,'store']);Route::post('imports/{batch}/commit',[ImportController::class,'commit']);Route::delete('imports/{batch}',[ImportController::class,'destroy']);Route::get('imports/template',[ImportController::class,'template']);});
  Route::middleware('role:admin')->group(function(){Route::get('settings',[SettingsController::class,'show']);Route::put('settings',[SettingsController::class,'update']);Route::post('settings/seal',[SettingsController::class,'uploadSeal']);Route::post('settings/priest-signature',[SettingsController::class,'uploadSignature']);Route::put('certificate-templates/{sacrament}',[CertificateTemplateController::class,'update']);});
  Route::get('settings/image/{type}',[SettingsController::class,'image'])->middleware('role:admin');
  Route::get('certificate-preview-settings',[SettingsController::class,'preview'])->middleware('role:admin,staff'); Route::get('certificate-preview-image/{type}',[SettingsController::class,'image'])->middleware('role:admin,staff');
  Route::put('me',[ProfileController::class,'update']);Route::put('me/password',[ProfileController::class,'updatePassword']);Route::post('me/avatar',[ProfileController::class,'uploadAvatar']);Route::get('me/avatar',[ProfileController::class,'avatar']);
 });
});
