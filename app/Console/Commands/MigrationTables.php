<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Schema;
use Illuminate\Database\Schema\Blueprint;

class MigrationTables extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mig:tables {--drop} {--sheets} {--sessions} {--clean} {--all} {--both} {--teachers} {--students}';

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
        if ($this->option('drop')) {
            if ($this->option('both')) {
                if ($this->option('all')) {
                    $this->dropSSessions();
                    $this->dropSSheets();
                    $this->dropSCleanStudents();
                    $this->dropSCleanTeachers();
                    $this->dropTSessions();
                    $this->dropTSheets();
                    $this->dropTCleanStudents();
                    $this->dropTCleanTeachers();
                } elseif ($this->option('sheets')) {
                    $this->dropTSheets();
                    $this->dropSSheets();
                } elseif ($this->option('sessions')) {
                    $this->dropTSessions();
                    $this->dropSSessions();
                } elseif ($this->option('clean')) {
                    $this->dropSCleanStudents();
                    $this->dropSCleanTeachers();
                    $this->dropTCleanStudents();
                    $this->dropTCleanTeachers();
                }
            } elseif ($this->option('teachers')) {
                if ($this->option('all')) {
                    $this->dropTSessions();
                    $this->dropTSheets();
                    $this->dropTCleanStudents();
                    $this->dropTCleanTeachers();
                } elseif ($this->option('sheets')) {
                    $this->dropTSheets();
                } elseif ($this->option('sessions')) {
                    $this->dropTSessions();
                } elseif ($this->option('clean')) {
                    $this->dropTCleanStudents();
                    $this->dropTCleanTeachers();
                }
            } elseif ($this->option('students')) {
                if ($this->option('all')) {
                    $this->dropSSessions();
                    $this->dropSSheets();
                    $this->dropSCleanStudents();
                    $this->dropSCleanTeachers();
                } elseif ($this->option('sheets')) {
                    $this->dropSSheets();
                } elseif ($this->option('sessions')) {
                    $this->dropSSessions();
                } elseif ($this->option('clean')) {
                    $this->dropSCleanStudents();
                    $this->dropSCleanTeachers();
                }
            }
            echo "Tables droped successfuly\n";
            return;
        }
        if ($this->option('both')) {
            if ($this->option('all')) {
                $this->createTSessions();
                $this->createTSheets();
                $this->createTCleanStudents();
                $this->createTCleanTeachers();
                $this->createSSessions();
                $this->createSSheets();
                $this->createSCleanStudents();
                $this->createSCleanTeachers();
            } elseif ($this->option('sheets')) {
                $this->createTSheets();
                $this->createSSheets();
            } elseif ($this->option('sessions')) {
                $this->createTSessions();
                $this->createSSessions();
            } elseif ($this->option('clean')) {
                $this->createTCleanStudents();
                $this->createTCleanTeachers();
                $this->createSCleanStudents();
                $this->createSCleanTeachers();
            }
        } elseif ($this->option('teachers')) {
            if ($this->option('all')) {
                $this->createTSessions();
                $this->createTSheets();
                $this->createTCleanStudents();
                $this->createTCleanTeachers();
            } elseif ($this->option('sheets')) {
                $this->createTSheets();
            } elseif ($this->option('sessions')) {
                $this->createTSessions();
            } elseif ($this->option('clean')) {
                $this->createTCleanStudents();
                $this->createTCleanTeachers();
            }
        } elseif ($this->option('students')) {
            if ($this->option('all')) {
                $this->createSSessions();
                $this->createSSheets();
                $this->createSCleanStudents();
                $this->createSCleanTeachers();
            } elseif ($this->option('sheets')) {
                $this->createSSheets();
            } elseif ($this->option('sessions')) {
                $this->createSSessions();
            } elseif ($this->option('clean')) {
                $this->createSCleanStudents();
                $this->createSCleanTeachers();
            }
        }
        echo "Tables created successfuly\n";
    }

    private function createTSessions() {
        Schema::create('teacher_sessions', function(Blueprint $table) {
            $table->increments('id');
            $table->string('date')->nullable();
            $table->integer('course_id')->nullable();
            $table->string('teacher_name')->nullable();
            $table->string('student_name')->nullable();
            $table->text('teacher_sheet')->nullable();
            $table->integer('teacher_row')->nullable();
            $table->string('teacher_attendance')->nullable();
            $table->text('teacher_attendance_comments')->nullable();
            $table->integer('duration')->nullable();
            $table->text('homework')->nullable();
            $table->text('material')->nullable();
            $table->string('homework_proficiency')->nullable();
            $table->string('homework_completed')->nullable();
            $table->boolean('paired')->default(false);
        });
    }

    private function dropTSessions() {
        Schema::dropIfExists('teacher_sessions');
    }

    private function createSSessions() {
        Schema::create('student_sessions', function(Blueprint $table) {
            $table->increments('id');
            $table->string('date')->nullable();
            $table->integer('course_id')->nullable();
            $table->string('student_name')->nullable();
            $table->string('teacher_name')->nullable();
            $table->text('student_sheet')->nullable();
            $table->integer('student_row')->nullable();
            $table->string('student_attendance')->nullable();
            $table->text('student_attendance_comments')->nullable();
            $table->integer('duration')->nullable();
            $table->text('homework')->nullable();
            $table->text('material')->nullable();
            $table->string('homework_proficiency')->nullable();
            $table->string('homework_completed')->nullable();
            $table->boolean('paired')->default(false);
        });
    }

    private function dropSSessions() {
        Schema::dropIfExists('student_sessions');
    }

    private function createTSheets() {
        Schema::create('teacher_sheets', function(Blueprint $table) {
            $table->increments('id');
            $table->text('name');
            $table->boolean('refused');
            $table->boolean('has_errors');
        });
    }

    private function dropTSheets() {
        Schema::dropIfExists('teacher_sheets');
    }

    private function createSSheets() {
        Schema::create('student_sheets', function(Blueprint $table) {
            $table->increments('id');
            $table->text('name');
            $table->boolean('refused');
            $table->boolean('has_errors');
        });
    }

    private function dropSSheets() {
        Schema::dropIfExists('student_sheets');
    }

    private function createTCleanStudents() {
        Schema::create('t_clean_students', function(Blueprint $table) {
            $table->increments('id');
            $table->text('name');
        });
    }

    private function dropTCleanStudents() {
        Schema::dropIfExists('t_clean_students');
    }

    private function createTCleanTeachers() {
        Schema::create('t_clean_teachers', function(Blueprint $table) {
            $table->increments('id');
            $table->text('name');
        });
    }

    private function dropTCleanTeachers() {
        Schema::dropIfExists('t_clean_teachers');
    }

    private function createSCleanStudents() {
        Schema::create('s_clean_students', function(Blueprint $table) {
            $table->increments('id');
            $table->text('name');
        });
    }

    private function dropSCleanStudents() {
        Schema::dropIfExists('s_clean_students');
    }

    private function createSCleanTeachers() {
        Schema::create('s_clean_teachers', function(Blueprint $table) {
            $table->increments('id');
            $table->text('name');
        });
    }

    private function dropSCleanTeachers() {
        Schema::dropIfExists('s_clean_teachers');
    }

}
