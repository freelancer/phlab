<?php

return [
  'darkconsole.always-on'         => true,
  'darkconsole.enabled'           => true,
  'environment.append-paths'      => ['/usr/lib/git-core'],
  'files.enable-imagemagick'      => true,
  'load-libraries'                => ['phlab/src'],
  'metamta.default-address'       => 'noreply@'.getenv('PHABRICATOR_DOMAIN'),
  'metamta.reply-handler-domain'  => getenv('PHABRICATOR_DOMAIN'),
  'mysql.host'                    => getenv('PHABRICATOR_MYSQL_HOST'),
  'mysql.pass'                    => getenv('PHABRICATOR_MYSQL_PASSWORD') ?: null,
  'mysql.user'                    => getenv('PHABRICATOR_MYSQL_USER'),
  'phabricator.base-uri'          => sprintf('http://%s:%d', getenv('PHABRICATOR_DOMAIN'), getenv('PHABRICATOR_HTTP_PORT')),
  'phabricator.developer-mode'    => true,
  'phabricator.show-prototypes'   => true,
  'phabricator.timezone'          => 'Etc/UTC',
  'phd.log-directory'             => '/var/log',
  'pygments.enabled'              => true,
  'repository.default-local-path' => '/var/repo',
];
