<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ArtisanSeeder extends Seeder
{
    public function run(): void
    {
        // إذا كان الجدول موجوداً، قم بمسح البيانات القديمة لضمان نظافة العملية
        Schema::disableForeignKeyConstraints();
        DB::table('artisans')->truncate();
        Schema::enableForeignKeyConstraints();

        $csvFile = base_path('artisans_dataset.csv'); 
        if (!file_exists($csvFile)) {
            $this->command->error("CSV file not found at: " . $csvFile);
            return;
        }

        $file = fopen($csvFile, 'r');
        $header = fgetcsv($file); // تخطي سطر العناوين (Headers)

        $records = [];
        while (($row = fgetcsv($file)) !== FALSE) {
            // يجب أن يكون لديك 9 أعمدة في ملف CSV:
            // [ID, Name, Service, City, Price, Rating, Lat, Lon, Description]
            if (count($row) != 9) continue; 

            $records[] = [
                'name' => $row[1],
                'service_type' => $row[2],
                'city' => $row[3],
                'price' => (float)$row[4],
                'rating' => (float)$row[5],
                'latitude' => (double)$row[6],
                'longitude' => (double)$row[7],
                'description' => $row[8],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        DB::table('artisans')->insert($records);
        fclose($file);
        $this->command->info('Artisans data seeded successfully!');
    }
}