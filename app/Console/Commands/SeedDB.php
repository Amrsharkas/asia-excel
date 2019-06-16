<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;

class SeedDB extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:db';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display an inspiring quote';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        // seeding one on one course types
        DB::table('one_courses')->delete();
        DB::table('one_courses')->insert([
            ['id' => 1, 'name' => 'Memorization/Recitation'],
            ['id' => 2, 'name' => 'Tajweed Theoretical'],
            ['id' => 3, 'name' => 'Annourania'],
        ]);
        // seeding world data
        $filenames = ['city.sql', 'country.sql', 'countrylanguage.sql'];
        foreach ($filenames as $filename) {
            $sql_file = realpath(database_path() . '/sql/' . $filename);
            exec("mysql -u " . env('DB_USERNAME') . " -p" . env('DB_PASSWORD') . " " . env('DB_DATABASE') . " < " . $sql_file);
        }
    }

}
