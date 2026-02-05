<?php

return [

    'chrome_path' => env('BROWSERSHOT_CHROME_PATH'),

    'remote_instance_name' => env('BROWSERSHOT_REMOTE_INSTANCE_NAME'),

    'remote_instance_port' => (int) env('BROWSERSHOT_REMOTE_INSTANCE_PORT', 9222),

];
