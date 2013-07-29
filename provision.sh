printf "Running apt-get update... \n\n"
apt-get update --force-yes -y

# MySQL
echo mysql-server mysql-server/root_password password wp | sudo debconf-set-selections
echo mysql-server mysql-server/root_password_again password wp | sudo debconf-set-selections

apt_package_list=(
	# Imagemagick
	imagemagick

	# PHP5
	php5-fpm
	php5-cli

	# Commmon and dev packages for PHP
	php5-common
	php5-dev

	php5-imagick
	php5-xdebug
	php5-mcrypt
	php5-mysql
	php5-curl
	php-pear
	php5-gd

	# Nginx
	nginx

	# MySQL
	mysql-server

	# Others
	curl
	sendmail
)

printf "Install all apt-get packages...\n"
apt-get install --force-yes -y ${apt_package_list[@]}

# Clean up apt caches
apt-get clean

# Symlink directories
printf "\nLink Directories...\n"

# Configuration for Nginx
sudo ln -sf /vagrant/_configs/nginx.conf /etc/nginx/nginx.conf | echo "Linked nginx.conf to /etc/nginx/nginx.conf"
sudo ln -sf /vagrant/_configs/hosts /etc/hosts | echo "Linked hosts to /etc/hosts"

# Check if mysql service gives an error or not
exists_mysql=`service mysql status`
if [ "mysql stop/waiting" == "$exists_mysql" ]
then
	printf "\nStart MySQL...\n"
	sudo service mysql start
else
	printf "\nRestart MySQL...\n"
	sudo service mysql restart
fi

# WP-CLI Install
if [ ! -f /usr/bin/wp ]
then
	printf "\nDownloading wp-cli.phar...\n"
	curl --silent http://wp-cli.org/packages/phar/wp-cli.phar > /usr/bin/wp
	chmod +x /usr/bin/wp
else
	printf "\nwp-cli already installed.\n"
fi

# Install wp
if [ ! -d /home/vagrant/wp ]
then
	printf "\nDownloading WordPress...\n"
	mkdir -p /home/vagrant/wp
	cd /home/vagrant/wp
	wp core download --quiet
	ln -sf /vagrant/_configs/wp-config.php /home/vagrant/wp | echo "Link wp-config.php to /home/vagrant/wp/wp-config.php"
	mysql -e "DROP DATABASE IF EXISTS wp; CREATE DATABASE wp;" -uroot -pwp
	mysql wp < /vagrant/_configs/db.sql -uroot -pwp
else
	printf "\nUpdating WordPress...\n"
	cd /home/vagrant/wp
	wp core update --quiet
fi

# Link plugins
for plugin in $(find /vagrant -type d | grep "^\/[a-z]*/[a-zA-Z\-]*/[a-zA-Z\-]*$")
do
	plugin_name=${plugin##*/}
	ln -sf $plugin /home/vagrant/wp/wp-content/plugins/$plugin_name | echo "Link $plugin_name to /vagrant/_wp/wp-content/plugins/$plugin_name"
done

# Restart services
printf "Restart nginx...\n"
sudo service nginx restart

printf "Restart php5-fpm...\n"
sudo service php5-fpm restart

printf "Everything is setup.\n"
