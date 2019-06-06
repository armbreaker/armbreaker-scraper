# Armbreaker

Armbreaker is a tool to scrape and visualize like information from XenForo forums such as Spacebattles.com. This repository contains the backend scraping code used to extract data from the site.

## Requirements

* PHP 7.3
* Composer
* A SQL database that doctrine/dbal can use (tested with mysql8)

## Installation

### Manual/Unmanaged installation

1. `composer install`
2. Fill your database with `armbreaker.sql` (note: file outdated rn)
3. set `config.php` with the database credentials and a unique ID (0..255)
4. fire up one (1) instance of `runFaerieQueen.php` per cluster and however many `runShard.php` you want. Recommended to limit yourself to one Shard per IP addr / network interface.

### Automatic installation
(this will automatically spin up additional Shards based on demand. WIP.)

### Vagrant

(old info)

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
