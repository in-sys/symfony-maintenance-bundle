OVERVIEW
========

This bundle allows you to place your website in maintenance mode by calling two commands in your console. A page with status code 503 appears to users,
it is possible to authorize certain ips addresses stored in your configuration

[![Latest Stable Version](http://poser.pugx.org/in-sys/symfony-maintenance-bundle/v)](https://packagist.org/packages/in-sys/symfony-maintenance-bundle)

Several choices of maintenance mode are possible: a simple test of an existing file, or memcache, or in a database.

---------------------

Documentation
=============

For installation and how to use the bundle refer to [Resources/doc/index.md](https://github.com/in-sys/symfony-maintenance-bundle/blob/master/Resources/doc/index.md)

Local testing
=============

This repository includes a Docker-based local environment for running Composer, PHPUnit, and PHPStan without installing PHP on your host.

Build the image:

    make docker-build

Install dependencies for the default Symfony constraint (`6.4.*`):

    make composer-install

Update dependencies explicitly when you want to refresh the resolved versions:

    make composer-update

Run the test suite:

    make test

Run PHPStan:

    make phpstan

Switch Symfony or PHP version when needed:

    SYMFONY_REQUIRE=7.4.* make test
    PHP_VERSION=8.5 make docker-build
