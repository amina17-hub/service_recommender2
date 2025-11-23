<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RecommendationController; // استيراد الكنترولر

// المسار الافتراضي (يمكنك تركه كما هو)
Route::get('/', function () {
    return view('welcome');
});

// مسار نظام التوصية (الذي يعمل الآن)
Route::get('/recommendations', [RecommendationController::class, 'getRecommendations']);