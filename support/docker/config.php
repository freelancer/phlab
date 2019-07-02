<?php

return [
  'darkconsole.enabled'          => true,
  'load-libraries'               => ['phlab/src'],
  'metamta.default-address'      => 'noreply@'.getenv('PHABRICATOR_DOMAIN'),
  'metamta.reply-handler-domain' => getenv('PHABRICATOR_DOMAIN'),
  'mysql.host'                   => getenv('PHABRICATOR_MYSQL_HOST'),
  'mysql.pass'                   => getenv('PHABRICATOR_MYSQL_PASSWORD') ?: null,
  'mysql.user'                   => getenv('PHABRICATOR_MYSQL_USER'),
  'phabricator.base-uri'         => 'http://'.getenv('PHABRICATOR_DOMAIN'),
  'phabricator.developer-mode'   => true,
  'phabricator.show-prototypes'  => true,
  'phabricator.timezone'         => 'Etc/UTC',
  'pygments.enabled'             => true,
];
