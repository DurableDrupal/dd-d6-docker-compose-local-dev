FROM nimmis/apache-php5

WORKDIR /var/www/html

COPY ./drupal-6.38/ /var/www/html/

EXPOSE 80