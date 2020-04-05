FROM nimmis/apache-php5
MAINTAINER victorkane@awebfactory.com

WORKDIR /var/www/html

COPY ./drupal-6.38/ /var/www/html/

EXPOSE 80

# Add custom PHP configuration changes from php.ini
# COPY php.ini /usr/local/etc/php/

# Copy and enable our own site configuration -- needed for .htaccess enable.
COPY ./.docker/d6web/web.conf /etc/apache2/sites-available/web.conf
RUN a2dissite 000-default && a2ensite web

# Enable mod_rewrite, otherwise Drupal form submission doesn't work.
RUN a2enmod rewrite

RUN service apache2 restart

# Install Composer & Drush
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN HOME=/ /usr/local/bin/composer global require drush/drush:~8
RUN ln -s /.composer/vendor/drush/drush/drush /usr/local/bin/drush

# Install mysql client
RUN apt-get update && apt-get install -y mysql-client