# Armbreaker

Armbreaker is a tool to scrape and visualize like information from XenForo forums such as Spacebattles.com.

## Requirements

### Server

* PHP 7.1
* Composer
* A SQL database that doctrine/dbal can use

### Client

#### Required

* Python 3
* Flask
* npm 5.6+

#### Automatically fetched or included

* d3.js (Cloudflare)
* babel
* webpack
* js-levenshtein
* popper.js
* d3.slider
* moment (Cloudflare)
* moment-timezone

## Installation

### Manual

#### Server

1. `composer install`
2. Fill your database with `armbreaker.sql`
3. Profit?

#### Client

1. Navigate to `client/`
2. Init submodules, if you haven't already:
    ```bash
    git submodule init
    git submodule update
    ```
3. `npm install` to download requirements
4. `npm start` for dev, `npm build` for prod. `npm run-script watch` for an automatically updating dev build.
5. Run `run_webserver.bat/run_webserver.sh` to start the Flask webserver.
6. Navigate to the proper page for testing.

Page|Content
----|-------
<http://localhost:5000/fault> | Fault
<http://localhost:5000/ringmaker> | Ringmaker
<http://localhost:5000/api/fic/[FICID]> | API endpoint mockup
<http://localhost:5000/static/dist/demo_dropdown.html> | Dropdown testing.

### Vagrant

1. Install [Vagrant](https://www.vagrantup.com/downloads.html)
2. Install [Virtualbox](https://www.virtualbox.org/wiki/Downloads)
3. `vagrant up` in the armbreaker directory
4. visit [localhost:8080](localhost:8080)
5. optimal: `vagrant ssh` in the armbreaker directory to get access to the virtual server
6. for phpmyadmin: <http://localhost:8080/phpmyadmin>

#### Vagrant Box Specs

* Ubuntu 16.04.3
* git
* apache2 from [ondrej/apache2](https://launchpad.net/~ondrej/+archive/ubuntu/apache2)
* php7.1 from [ondrej/php](https://launchpad.net/~ondrej/+archive/ubuntu/php)
* php-xdebug [ondrej/php](https://launchpad.net/~ondrej/+archive/ubuntu/php)  
    it is setup so you can debug you just need to set your host Xdebug follow [this guide](https://gist.github.com/NaGeL182/9aa38362d4f3bb2b343d41363f0eb311#file-host_php-ini)
* php7.1-zip [ondrej/php](https://launchpad.net/~ondrej/+archive/ubuntu/php)
* php7.1-xml [ondrej/php](https://launchpad.net/~ondrej/+archive/ubuntu/php)
* php7.1-mbstring [ondrej/php](https://launchpad.net/~ondrej/+archive/ubuntu/php)
* php7.1-mysql [ondrej/php](https://launchpad.net/~ondrej/+archive/ubuntu/php)
* mysql-server-5.7
* phpmyadmin
* nodejs with [latest 8.x installer](https://nodejs.org/en/download/package-manager/#debian-and-ubuntu-based-linux-distributions)
* build-essential

Vagrant creates a private network with you, so only your computer can reach it.  
Database passsword for root is: `armbreaker`
