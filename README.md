Custom extensions for Phabricator.

= Development =
The best way to develop Phlab is to use [Docker Compose](https://docs.docker.com/compose/) locally by running `docker-compose up` from the root of the repository. Before doing so, you should have the following repositories cloned locally, in the same parent directory as Phlab itself:

  - [Arcanist](https://github.com/phacility/arcanist)
  - [libphutil](https://github.com/phacility/libphutil)
  - [Phabricator](https://github.com/phacility/phabricator)

Phabricator requires that it is accessed with a `Host` header containing a period (see [T2433: Move the "no dots in domain" setup check to pre-install](https://secure.phabricator.com/T2433)) and so you will not be able to access Phabricator via http://localhost. Instead, you should add `127.0.0.1 phabricator.local` to `/etc/hosts` and access Phabricator via http://phabricator.local.

Nginx has been configured to bind to port `80` by default. You can bind to an alternative port by setting the `HTTP_PORT` environment variable.
