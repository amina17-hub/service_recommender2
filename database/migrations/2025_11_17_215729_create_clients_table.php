<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

// داخل دالة up() في ملف Migration الخاص بـ clients

Schema::create('clients', function (Blueprint $table) { // يجب أن يكون الاسم هنا clients
    $table->id(); 
    $table->string('name', 50); 
    $table->string('city', 50); 
    $table->string('commune', 50)->nullable(); 
    $table->double('latitude')->nullable(); 
    $table->double('longitude')->nullable();
    $table->timestamps(); 
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};