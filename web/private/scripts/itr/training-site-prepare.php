<?php

#!/usr/bin/env drush

$sitename = \Drupal::config('system.site')->get('name');

echo "This is the site $sitename\n";
echo "TERMINUS_ENV:".$TERMINUS_ENV."\n";