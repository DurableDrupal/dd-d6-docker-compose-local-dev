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