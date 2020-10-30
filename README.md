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

If you change files locally, they will be picked up automatically.  There may be a delay of a second or so for changes to the client code (e.g. a `.vue` or `.js` file), while the rebuild happens.

## Tech

More details on the tech side of things in the [dev wiki](https://github.com/therestartproject/restarters.net/wiki).

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