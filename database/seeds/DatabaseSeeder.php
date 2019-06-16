<?php

use Illuminate\Database\Seeder;
use App\Section;

class DatabaseSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        $this->call(BasicTablesSeeder::class);
        $this->call(ElearningSeeder::class);
    }

}
