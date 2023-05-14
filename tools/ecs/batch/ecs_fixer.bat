:: Run easy-coding-standard (ecs) via this batch file inside your IDE e.g. PhpStorm (Windows only)
:: Install inside PhpStorm the  "Batch Script Support" plugin
cd..
cd..
cd..
cd..
cd..
cd..
php vendor\bin\ecs check vendor/markocupic/sac-event-registration-reminder/src --fix --config vendor/markocupic/sac-event-registration-reminder/tools/ecs/config.php
php vendor\bin\ecs check vendor/markocupic/sac-event-registration-reminder/contao --fix --config vendor/markocupic/sac-event-registration-reminder/tools/ecs/config.php
php vendor\bin\ecs check vendor/markocupic/sac-event-registration-reminder/config --fix --config vendor/markocupic/sac-event-registration-reminder/tools/ecs/config.php
php vendor\bin\ecs check vendor/markocupic/sac-event-registration-reminder/templates --fix --config vendor/markocupic/sac-event-registration-reminder/tools/ecs/config.php
php vendor\bin\ecs check vendor/markocupic/sac-event-registration-reminder/tests --fix --config vendor/markocupic/sac-event-registration-reminder/tools/ecs/config.php
