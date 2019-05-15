[![CircleCI](https://circleci.com/gh/TheRestartProject/restarters.net/tree/dockerize.svg?style=svg)](https://circleci.com/gh/TheRestartProject/restarters.net/tree/dockerize)

# restarters.net

restarters.net is a suite of software for the repair community.

Restarters brings together community repair enthusiasts and activists from
around the world, to engage in discussion and to organise local community repair
events, to bring down the barriers to repair.

It combines together three core modules:

* The Fixometer - our engine for capturing the impact of our community repair
  activities
* Restarters Talk - a space for local and global discussion of community repair
* Restarters Wiki - a collectively produced knowledge base of advice and support
  for community repair

## Roadmap

Our roadmap is on restarters.net itself - you can register at
https://restarters.net/about and get involved in the future direction of the
platform in the Restarters.net Development category.

## Creating issues

If you encounter any bugs, or have any feature ideas, the best place to discuss
them is on the platform itself. You can register at
https://restarters.net/about, and then start a topic in the Restarters.net
Development category.

(However, if you would prefer to create an issue in Github, please go ahead -
we'll make sure it ends up in the right place.)

## Tech

The core of the application is built using the Laravel framework. It integrates
with Discourse for community discussion and with Mediawiki for the community
knowledgebase.

## Installation

See Installation Guidelines in the wiki.

### Basic setup

This is currently assuming Debian / Ubuntu.  Get in touch if you're trying on a different OS!

#### Prerequisites

- php
  - php-curl
  - php-mysql
  - php-xml
- mysql/mariadb
  - and create a database
- npm

#### Install

- clone this repository
- copy .env.example -> .env
- edit .env
  - update DB settings to match your local DB
- edit /etc/hosts -> 127.0.0.1 restarters.test

- Generate an app key: `php artisan key:generate`

- initialise the DB:

```
$ php artisan migrate
```

- create a first admin user

```
$ php artisan tinker
> User::create(['name'=>'Jane Bloggs','email'=>'jane@bloggs.net','password'=>Hash::make('passw0rd'),'role'=>2]);
```

- run the app:

```
$ php artisan serve --host=restarters.test
```

* login!

## Develop using Docker and Docker Compose

**edit /etc/hosts -> 127.0.0.1 restarters.test**

Make sure you have Docker and Docker Compose installed. See [here](https://linuxize.com/post/how-to-install-and-use-docker-on-ubuntu-18-04/) to install Docker and [here](https://www.digitalocean.com/community/tutorials/how-to-install-docker-compose-on-ubuntu-18-04) for Docker Compose installation on Ubuntu 18.04.

Once these prerequsites are installed you can build the applicaition by doing:

```
docker-compose -f local.yml build
```
This will build the stack for local development using an `env` file at `./compose/local/env`.

The `local.yml` file defines the *restarters.net* laravel application and pulls in a mysql docker image. The mysql data directory is mounted using a docker volume. An instance of [MailHog](https://github.com/mailhog/MailHog) is used for SMTP testing.

Once the application is built you can add a user by doing:

```
docker-compose -f local.yml run --rm app php artisan tinker
> User::create(['name'=>'Jane Bloggs' 'email'=>'jane@bloggs.net','password'=>Hash::make('passw0rd'),'role'=>2]);
```
To run the application stack do:

```
docker-compose -f local.yml up --build
```

Login to restarters.net at http://restarters.test:8000

## Testing using Docker / Docker Compose

To run the unit / feature tests run:

```
docker-compose -f local.yml run --rm app vendor/bin/phpunit --code-coverage (Unit | Feature)
```

The tests will run and a coverage report will be generated in the root folder after the named tests.



## Methodology

We've documented our method for estimating CO2 emissions prevented through
electronic repairs. Please read more at http://rstrt.org/FAQ

## Specifications

Compiled version of the specs is available at: https://therestartproject.github.io/restarters.net/Index.html

## Funding and future development

The first version of the tool (2015) was the core Fixometer engine. This was
made possible with modest funding from the Innovation in Waste Prevention Fund,
Defra-funded and administered by WRAP. Subsequent development has been financed
by the Shuttleworth Foundation, and by Nesta and the Department for Digital,
Culture, Media & Sport.
