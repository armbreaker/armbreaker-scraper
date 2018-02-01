#!/usr/bin/env bash

sudo add-apt-repository -y ppa:ondrej/php
sudo add-apt-repository -y ppa:ondrej/apache2
curl -sL https://deb.nodesource.com/setup_8.x | sudo -E bash -
sudo apt-get update
sudo apt-get install -y apache2 php7.1 php-xdebug php7.1-zip php7.1-xml php7.1-mbstring git nodejs build-essential
sudo a2enmod rewrite
#need to do it like this becouse default cp is /bin/cp -i which forces interactivity
/bin/cp -rf /vagrant/vagrantFiles/000-default.conf /etc/apache2/sites-available/000-default.conf
/bin/cp -rf /vagrant/vagrantFiles/xdebug.ini /etc/php/7.1/mods-available/xdebug.ini
sudo service apache2 restart
if ! [ -L /var/www/html ]; then
    sudo rm -rf /var/www/html
    sudo ln -fs /vagrant/ /var/www/html
fi
sudo /vagrant/vagrantFiles/getComposer.sh
cd /vagrant/
./update
/bin/cp -rf /vagrant/config.sample.php /vagrant/config.php
touch /vagrant/log.txt
chmod o+w /vagrant/log.txt
