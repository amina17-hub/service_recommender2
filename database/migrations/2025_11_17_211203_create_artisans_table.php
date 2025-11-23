<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تشغيل عملية الـ Migrations (إنشاء الجدول).
     */
    public function up(): void
    {
        // 1. يجب أن يكون اسم الجدول 'artisans'
        Schema::create('artisans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            
            // العوامل الأساسية للتوصية (Content-Based & Hybrid)
            $table->string('service_type', 50); // نوع الخدمة
            $table->text('description')->nullable();
            $table->string('city', 50);
            
            // العوامل العددية
            $table->decimal('price', 8, 2); // السعر: عدد عشري (مثلاً 1000.50)
            $table->float('rating'); // التقييم: عدد عشري
            $table->double('latitude'); // إحداثيات الموقع (ضرورية لـ Haversine)
            $table->double('longitude'); // إحداثيات الموقع
            
            $table->timestamps(); // أعمدة التاريخ (created_at و updated_at)
        });
    }

    /**
     * التراجع عن عملية الـ Migrations (حذف الجدول).
     */
    public function down(): void
    {
        Schema::dropIfExists('artisans');
    }
};