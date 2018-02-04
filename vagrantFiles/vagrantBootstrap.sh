#!/usr/bin/env bash
#variables
DBPASSWRD="armbreaker"
DBNAME="armbreaker"

#adding PPA
sudo add-apt-repository -y ppa:ondrej/php
sudo add-apt-repository -y ppa:ondrej/apache2
curl -sL https://deb.nodesource.com/setup_8.x | sudo -E bash -
sudo apt-get update

#debconf selection (the blue interactive stuffs)
debconf-set-selections <<< "mysql-server-5.7 mysql-server/root_password password ${DBPASSWRD}"
debconf-set-selections <<< "mysql-server-5.7 mysql-server/root_password_again password ${DBPASSWRD}"
debconf-set-selections <<< "phpmyadmin phpmyadmin/dbconfig-install boolean true"
debconf-set-selections <<< "phpmyadmin phpmyadmin/reconfigure-webserver multiselect apache2"
debconf-set-selections <<< "phpmyadmin phpmyadmin/app-password-confirm password ${DBPASSWRD}"
debconf-set-selections <<< "phpmyadmin phpmyadmin/mysql/admin-pass password ${DBPASSWRD}"
debconf-set-selections <<< "phpmyadmin phpmyadmin/mysql/app-pass password ${DBPASSWRD}"

#installing debs
sudo apt-get install -y apache2 \
php7.1 php-xdebug php7.1-zip \
php7.1-xml \
php7.1-mbstring php7.1-mysql \
git nodejs build-essential \
mysql-server-5.7 phpmyadmin

#setup apache2 server
sudo a2enmod rewrite
#need to do it like this becouse default cp is /bin/cp -i which forces interactivity
/bin/cp -rf /vagrant/vagrantFiles/000-default.conf /etc/apache2/sites-available/000-default.conf
/bin/cp -rf /vagrant/vagrantFiles/xdebug.ini /etc/php/7.1/mods-available/xdebug.ini
sudo service apache2 restart

#setup MySQL
mysql -u root -p${DBPASSWRD} -e "CREATE DATABASE IF NOT EXISTS ${DBNAME} DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p${DBPASSWRD} -D ${DBNAME} < /vagrant/armbreaker.sql
mysql -u root -p${DBPASSWRD} -D ${DBNAME} < /vagrant/vagrantFiles/sample_data.sql

#setup webserver files
if ! [ -L /var/www/html ]; then
    sudo rm -rf /var/www/html
    sudo ln -fs /vagrant/ /var/www/html
fi

#setup armbreaker
sudo /vagrant/vagrantFiles/getComposer.sh
cd /vagrant/
./update
/bin/cp -rf /vagrant/config.sample.php /vagrant/config.php
sed -i "s\mysqli://x:x@y/z\mysql://root:${DBPASSWRD}@localhost/${DBNAME}\g" /vagrant/config.php
touch /vagrant/log.txt
chmod o+w /vagrant/log.txt
