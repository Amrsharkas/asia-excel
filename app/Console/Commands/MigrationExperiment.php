<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use DateTime;
use App\Account;
use PHPExcel_IOFactory;
use PHPExcel_Reader_IReadFilter;
use PHPExcel_CachedObjectStorageFactory;
use PHPExcel_Settings;

class MigrationExperiment extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mig:experiment';

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
        $records = DB::select('SELECT `date`, `course_id`,`student_name`,`teacher_name`, COUNT(*) as cnt FROM student_sessions_exp GROUP BY `date`, `course_id`,`student_name`,`teacher_name` HAVING COUNT(*) > 1');
        $total = 0;
        foreach ($records as $record) {
            $total += $record->cnt;
        }
        var_dump($total);
    }

}
