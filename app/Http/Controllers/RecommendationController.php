<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Artisan;
use App\Traits\GeoCalculations; 
use Illuminate\Support\Facades\DB;

class RecommendationController extends Controller
{
    // تأكد من أن لديك ملف Traits/GeoCalculations.php يحتوي على دالة calculateDistance
    use GeoCalculations; 

    public function getRecommendations(Request $request)
    {
        // 1. استقبال المدخلات والتحقق منها 
        $clientWilaya = $request->query('client_wilaya');
        $serviceType = $request->query('service_type');
        $clientLat = $request->query('client_lat'); 
        $clientLon = $request->query('client_lon'); 

        if (!$clientWilaya || !$serviceType || !$clientLat || !$clientLon) {
            return response()->json(['error' => 'Missing required parameters: client_wilaya, service_type, client_lat, or client_lon.'], 400);
        }

        // 2. التصفية أولاً: الخدمة والولاية (Filtering)
        $initialArtisans = Artisan::whereRaw('LOWER(service_type) = ?', [strtolower($serviceType)])
                    ->where('city', $clientWilaya) // التصفية حسب الولاية المدخلة
                    ->get();
        
        if ($initialArtisans->isEmpty()) {
            return response()->json(['recommendations' => []]);
        }
        
        // 3. حساب المسافة بالكيلومتر 
        $shortlist = [];
        foreach ($initialArtisans as $artisan) {
            $distance = $this->calculateDistance(
                $clientLat, 
                $clientLon, 
                $artisan->latitude, 
                $artisan->longitude
            );
            $artisan->distance_km = round($distance, 1);
            $shortlist[] = $artisan;
        }

        // 4. حساب النقاط الموحدة والوزن (Normalization and Scoring)
        
        // منع القسمة على صفر في حال كانت البيانات فارغة أو مكررة
        $minPrice = collect($shortlist)->min('price') ?? 1;
        $maxPrice = collect($shortlist)->max('price') ?? 1;
        
        $maxDistance = collect($shortlist)->max('distance_km'); 
        $maxRating = 5.0; 
        $minRating = 1.0; 

        // تحديد الأوزان: 50% للمسافة، 30% للتقييم، 20% للسعر
        $W_Distance = 0.5;
        $W_Rating = 0.3;
        $W_Price = 0.2;

        foreach ($shortlist as $artisan) {
            
            // (أ) نقاط المسافة (الأقرب = نقاط أعلى)
            $S_Distance = 1 - ($artisan->distance_km / ($maxDistance > 0 ? $maxDistance : 1));
            
            // (ب) نقاط التقييم
            $S_Rating = ($artisan->rating - $minRating) / ($maxRating - $minRating);
            
            // (ج) نقاط السعر (الأرخص = نقاط أعلى)
            $priceRange = $maxPrice - $minPrice;
            $S_Price_Normalized = ($priceRange > 0) ? 1 - (($artisan->price - $minPrice) / $priceRange) : 1;
            
            // 5. حساب النقاط الكلية (Total Score)
            $artisan->Total_Score = 
                ($W_Distance * $S_Distance) + 
                ($W_Rating * $S_Rating) + 
                ($W_Price * $S_Price_Normalized);
        }

        // 6. الترتيب النهائي حسب النقاط الكلية
        $shortlist = collect($shortlist)->sortByDesc('Total_Score')->values();

        // 7. تنسيق الإخراج النهائي (البيانات التي تظهر على الخريطة)
        $formatted_recommendations = $shortlist->map(function ($artisan) {
            
            // توجيه افتراضي لصورة الملف الشخصي
            $defaultImage = asset('img/default_artisan.jpg'); 
            $imageUrl = $defaultImage; // استخدم $artisan->profile_image_url إذا كانت موجودة

            return [
                'id' => $artisan->id,
                'name' => $artisan->name,
                'service_type' => $artisan->service_type,
                'latitude' => (float) $artisan->latitude, // التأكد من نوع البيانات
                'longitude' => (float) $artisan->longitude,
                'profile_image_url' => $imageUrl,
                'distance_km' => $artisan->distance_km,
                'rating' => $artisan->rating,
                'price' => $artisan->price,
                'total_score' => round($artisan->Total_Score, 4),
            ];
        })->all();

        return response()->json(['recommendations' => $formatted_recommendations]);
    }
}