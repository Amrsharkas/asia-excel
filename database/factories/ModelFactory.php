<?php

/*
  |--------------------------------------------------------------------------
  | Model Factories
  |--------------------------------------------------------------------------
  |
  | Here you may define all of your model factories. Model factories give
  | you a convenient way to create models for testing and seeding your
  | database. Just tell the factory how a default model should look.
  |
 */

$factory->define(App\User::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->safeEmail,
        'password' => bcrypt(str_random(10)),
        'remember_token' => str_random(10),
    ];
});

$factory->define(App\ElearningCourse::class, function (Faker\Generator $faker) {
    $sections_count = App\Section::count();
    $offset = rand(0, $sections_count - 1);
    $section_id = App\Section::skip($offset)->first()->id;
    $has_prerequisite = rand(0, 4) > 3 ? true : false;
    $data = [
        'section_id' => $section_id,
        'title' => $faker->sentence,
        'section_image' => $faker->url,
        'description_overall' => $faker->text,
        'estimated_time' => $faker->randomFloat(2, 0.25, 1.5)
    ];
    if ($has_prerequisite) {
        $courses_count = App\ElearningCourse::count();
        if ($courses_count > 0) {
            $offset = rand(0, $courses_count - 1);
            $prerequisite_id = App\ElearningCourse::skip($offset)->first()->id;
            $data['prerequisite_id'] = $prerequisite_id;
        }
    }
    return $data;
});

$factory->define(App\ElearningContent::class, function (Faker\Generator $faker) {
    $data = [
        'title' => $faker->sentence,
        'content' => $faker->text
    ];
    return $data;
});
