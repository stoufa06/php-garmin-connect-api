# Requirements
- php 7.1 or more
- php package [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv) for .env variables
# Usage
- Create .env file in examples folder
- Set env variable `GARMIN_KEY` and `GARMIN_SECRET` and `GARMIN_CALLBACK_URI=http://localhost:8000/example.php`
- Open terminal and go to example folder
- run `php -S localhost:8000`
- open http://localhost:8000/example.php