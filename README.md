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

## Develop using Docker and Docker Compose

Currently only includes the Fixometer.

1. First edit `/etc/hosts -> 127.0.0.1 restarters.test talk.restarters.test`
2. Make sure you have Docker and Docker Compose installed.
3. Copy `./compose/local/env` to `.env`.  
4. Edit .env and provide a valid value of `GOOGLE_API_CONSOLE_KEY`. 
5. `docker-compose up --build`

You can then log in:
- To the Fixometer at `http://restarters.test:8000` using an email of `jane@bloggs.net` and a password `passw0rd`.
- To Mailhog (to see emails sent) at `http://restarters.test:8025/`

If you change files locally, they will be picked up automatically.  There may be a delay of a second or so for changes to the client code (e.g. a `.vue` or `.js` file).

## Installation

See Installation Guidelines in the wiki.

### Basic setup

This is currently assuming Debian / Ubuntu. Get in touch if you're trying on a different OS!

#### Prerequisites

- php
  - php-curl
  - php-mysql
  - php-xml
  - php-xmlrpc
  - php-intl
- mysql/mariadb
  - and create a database:
    - CREATE DATABASE restarters_db_test
  - add users:
    - CREATE USER 'restarters'@'localhost' IDENTIFIED BY 's3cr3t'; 
    - CREATE USER 'tester'@'localhost' IDENTIFIED BY 'tester';
  - give users permissions:
    - GRANT ALL PRIVILEGES ON restarters_db_test.* TO 'restarters'@'localhost';
    - GRANT ALL PRIVILEGES ON restarters_db_test.* TO 'tester'@'localhost';
- npm

#### Install

- Clone this repository
`php composer.phar install`
`npm install`
- Copy .env.example -> .env
- Edit .env
  - update DB settings to match your local DB
- Edit /etc/hosts -> 127.0.0.1 restarters.test talk.restarters.test

- Generate an app key: `php artisan key:generate`

- Initialise the DB:

```
$ php artisan migrate
```

- Create a first admin user

```
$ php artisan tinker
> User::create(['name'=>'Jane Bloggs','email'=>'jane@bloggs.net','password'=>Hash::make('passw0rd'),'role'=>2]);
```

- Create a folder for image uploads:
`mkdir public/uploads`
- Run the app: 

```
$ php artisan serve --host=restarters.test
```

* Login!

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

```

```