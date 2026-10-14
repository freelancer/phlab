Custom extensions for Phabricator.

= Development =
The best way to develop Phlab is to use [Docker Compose](https://docs.docker.com/compose/) locally by running `docker-compose up` from the root of the repository. Before doing so, you should have the following repositories cloned locally, in the same parent directory as Phlab itself:

  - [Arcanist](https://github.com/phacility/arcanist)
  - [libphutil](https://github.com/phacility/libphutil)
  - [Phabricator](https://github.com/phacility/phabricator)

Phabricator requires that it is accessed with a `Host` header containing a period (see [T2433: Move the "no dots in domain" setup check to pre-install](https://secure.phabricator.com/T2433)) and so you will not be able to access Phabricator via http://localhost. Instead, you should add `127.0.0.1 phabricator.local` to `/etc/hosts` and access Phabricator via http://phabricator.local.

Nginx has been configured to bind to port `80` by default. You can bind to an alternative port by setting the `HTTP_PORT` environment variable.

== Unit tests ==
`arc unit`, and so `arc diff`, runs the unit tests through `bin/unit`, which runs them in Docker with PHP 7.2, MySQL 5.7 and the Arcanist and Phabricator versions that production runs. It needs Docker with Compose v2. The first run fetches those versions into `~/.cache/phlab` (or `$XDG_CACHE_HOME/phlab`) and builds an image, which takes a couple of minutes.

```
bin/unit                       # tests for files changed on this branch
bin/unit --everything          # every test
bin/unit src/infrastructure/__tests__/RocketChatClientTestCase.php
bin/unit --down                # stop the test database
```

Every checkout shares one test database. It is held in memory and keeps running between runs, so that later runs are quicker.

  - In a new checkout, run `bin/unit` (or `composer install`) once before using `arc`, which loads Phlab and so needs its Composer dependencies.
  - Without Docker, `arc diff --nounit` skips the tests.
  - In the containers that `docker-compose up` starts, `arc unit` runs the tests there, against the development database.

== Troubleshooting ==
During local development, the db can get corrupted. When this happens, you can run `docker-compose down --volumes` to stop and remove containers, networks and volumes created by `docker-compose up`.
