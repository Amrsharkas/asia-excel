<?php

use Illuminate\Database\Seeder;

class BasicTablesSeeder extends Seeder implements SeederInterface{

    /**
     * Run the database seeds.
     *
     * @return void
     */

    private $faker;

    public function run() {
        $this->faker = Faker\Factory::create();
        $this->seedSections();
    }

    public function seedSections() {
        DB::table('sections')->delete();
        $orders = [1, 2, 3];
        $titles = ['Arabic', 'Quran', 'Practices'];
        shuffle($orders);
        $sections = [];
        for ($i = 0; $i < self::SECTIONS_COUNT; $i++) {
            $sections[$i] = [
                'id' => $i + 1,
                'title' => $titles[$i],
                'order' => $orders[$i],
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ];
        }
        DB::table('sections')->insert($sections);
    }

}
