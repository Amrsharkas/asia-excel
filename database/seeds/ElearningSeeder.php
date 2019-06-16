<?php

use Illuminate\Database\Seeder;

class ElearningSeeder extends Seeder implements SeederInterface {

    private $faker;
    private $free_samples_count;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        $this->faker = Faker\Factory::create();
        $this->seedCourses();
        $this->seedModules();
        $this->seedLessons();
        $this->seedTopics();
        $this->seedCoursesContent();
        $this->seedUnlocks();
        $this->updateFreeSamplesCount();
    }

    public function seedCourses() {
        DB::table('elearning_courses')->delete();
        $this->free_samples_count = array_fill(1, self::ELEARNING_COURSES_COUNT, 0);
        $courses = [];
        $modules = [];
        $lessons = [];
        $topics = [];
        $quizes = [];
        $module_id = $lesson_id = $topic_id = 1;
        for ($i = 0; $i < self::ELEARNING_COURSES_COUNT; $i++) {
            $courses[$i] = [
                'id' => $i + 1,
                'section_id' => rand(1, self::SECTIONS_COUNT),
                'title' => $this->faker->sentence,
                'description_overall' => $this->faker->text,
                'description_section' => $this->faker->text,
                'description_course' => $this->faker->text,
                'estimated_time' => $this->faker->randomFloat(2, 0.25, 1.5),
                'prerequisite_id' => (rand(0, 4) > 3 && $i > 0) ? rand(1, $i) : NULL,
                'unlock_periods' => json_encode($this->unlock_periods()),
                'unlock_extensions' => json_encode($this->unlock_extensions()),
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ];
            $modules_count = rand(4, 10);
            $module_order = 1;
            $next_quiz = rand(1, 2);
            $quiz_id = 1;
            for ($j = $module_id; $j <= $module_id + $modules_count - 1; $j++) {
                $modules[$j] = [
                    'id' => $j,
                    'course_id' => $i + 1,
                    'title' => $this->faker->sentence,
                    'free_sample' => rand(1, 5) > 4 ? true : false,
                    'created_at' => \Carbon\Carbon::now(),
                    'updated_at' => \Carbon\Carbon::now(),
                ];
                $next_quiz--;
                if ($next_quiz == 0) {
                    $next_quiz = rand(1, 2);
                }
            }
            $module_id = $j;
        }
        DB::table('elearning_courses')->insert($courses);
    }

    public function seedModules() {
        DB::table('elearning_modules')->delete();
        $modules = [];
        for ($i = 1; $i <= self::ELEARNING_MODULES_COUNT; $i++) {
            $course_id = rand(1, self::ELEARNING_COURSES_COUNT);
            $free_sample = rand(1, 5) > 4 ? true : false;
            if ($free_sample) {
                $this->free_samples_count[$course_id] ++;
            }
            $modules[$i] = [
                'id' => $i,
                'course_id' => $course_id,
                'title' => $this->faker->sentence,
                'free_sample' => $free_sample,
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ];
        }
        DB::table('elearning_modules')->insert($modules);
    }

    public function seedLessons() {
        DB::table('elearning_lessons')->delete();
        $lessons = [];
        for ($i = 1; $i <= self::ELEARNING_LESSONS_COUNT; $i++) {
            $lessons[$i] = [
                'id' => $i,
                'module_id' => rand(1, self::ELEARNING_MODULES_COUNT),
                'title' => $this->faker->sentence,
                'free_sample' => false,
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ];
        }
        DB::table('elearning_lessons')->insert($lessons);
    }

    public function seedTopics() {
        DB::table('elearning_topics')->delete();
        $topics = [];
        for ($i = 1; $i <= self::ELEARNING_TOPICS_COUNT; $i++) {
            $topics[$i] = [
                'id' => $i,
                'lesson_id' => rand(1, self::ELEARNING_LESSONS_COUNT),
                'title' => $this->faker->sentence,
                'free_sample' => false,
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ];
        }
        DB::table('elearning_topics')->insert($topics);
    }

    public function seedCoursesContent() {
        DB::table('elearning_contents')->delete();
        $contents = [];
        for ($i = 1; $i <= self::ELEARNING_MODULES_COUNT; $i++) {
            $contents[$i] = [
                'id' => $i,
                'type' => 'module',
                'course_id' => rand(1, self::ELEARNING_COURSES_COUNT),
                'title' => $this->faker->sentence,
                'parent_id' => NULL,
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ];
        }
        DB::table('elearning_contents')->insert($contents);
        $contents = [];
        for ($i = self::ELEARNING_MODULES_COUNT + 1; $i <= self::ELEARNING_MODULES_COUNT + self::ELEARNING_LESSONS_COUNT; $i++) {
            $parent_id = rand(1, self::ELEARNING_MODULES_COUNT);
            $course_id = App\ElearningContent::find($parent_id)->course_id;
            $contents[$i] = [
                'id' => $i,
                'type' => 'lesson',
                'course_id' => $course_id,
                'parent_id' => $parent_id,
                'title' => $this->faker->sentence,
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ];
        }
        DB::table('elearning_contents')->insert($contents);
        $contents = [];

        for ($i = 1 + self::ELEARNING_MODULES_COUNT + self::ELEARNING_LESSONS_COUNT; $i <= self::ELEARNING_MODULES_COUNT + self::ELEARNING_LESSONS_COUNT + self::ELEARNING_TOPICS_COUNT; $i++) {
            $parent_id = rand(self::ELEARNING_MODULES_COUNT + 1, self::ELEARNING_MODULES_COUNT + self::ELEARNING_LESSONS_COUNT);
            $course_id = App\ElearningContent::find($parent_id)->course_id;

            $contents[$i] = [
                'id' => $i,
                'type' => 'topic',
                'course_id' => $course_id,
                'parent_id' => $parent_id,
                'title' => $this->faker->sentence,
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ];
        }
        DB::table('elearning_contents')->insert($contents);
    }

    public function seedUnlocks() {
        DB::table('elearning_unlocks')->delete();
        $periods = [4, 8, 12, 16];
        $unlocks = [];
        for ($i = 1; $i <= self::ELEARNING_COURSES_COUNT; $i++) {
            for ($j = 0; $j < 4; $j++) {
                $unlocks[] = [
                    'course_id' => $i,
                    'extend' => false,
                    'period' => $periods[$j],
                    'price' => 10 * ($periods[$j] + 1),
                    'created_at' => \Carbon\Carbon::now(),
                    'updated_at' => \Carbon\Carbon::now(),
                ];
            }
            for ($j = 0; $j < 4; $j++) {
                $unlocks[] = [
                    'course_id' => $i,
                    'extend' => true,
                    'period' => $periods[$j],
                    'price' => 2 * ($periods[$j] + 1),
                    'created_at' => \Carbon\Carbon::now(),
                    'updated_at' => \Carbon\Carbon::now(),
                ];
            }
        }
        DB::table('elearning_unlocks')->insert($unlocks);
    }

    public function updateFreeSamplesCount() {
        foreach ($this->free_samples_count as $id => $count) {
            $course = App\ElearningCourse::find($id);
            $course->free_samples_count = $count;
            $course->save();
//            App\ElearningCourse::find($id)->update(['free_samples_count', $count]);
        }
    }

    public function unlock_periods() {
        $periods = [4, 8, 12, 16];
        for ($j = 0; $j < 4; $j++) {
            $unlocks[] = [
                'period' => $periods[$j],
                'price' => rand(10, 20) * ($periods[$j] + 1),
            ];
        }
        return $unlocks;
    }

    public function unlock_extensions() {
        $periods = [4, 8, 12, 16];
        for ($j = 0; $j < 4; $j++) {
            $unlocks[] = [
                'period' => $periods[$j],
                'price' => rand(2, 5) * ($periods[$j] + 1),
            ];
        }
        return $unlocks;
    }

}
