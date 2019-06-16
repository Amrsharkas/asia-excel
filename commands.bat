php artisan migrate:migrate
php artisan mig:tables --all --drop
php artisan mig:tables --all
php artisan mig:teachers
php artisan mig:students
php artisan mig:ssessions
php artisan mig:tsessions
php artisan mig:count --teachers
php artisan mig:count --students
git add *
git commit -m "remote trials"
git push origin master
shutdown -s -t 1