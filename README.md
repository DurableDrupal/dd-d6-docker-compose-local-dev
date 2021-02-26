## DurableDrupal 6 Docker Compose Local Dev

Whip up your legacy Drupal site in your own local dev environment

- [DurableDrupal 6 Docker Compose Local Dev](#durabledrupal-6-docker-compose-local-dev)
  - [Fork or clone the repo](#fork-or-clone-the-repo)
  - [Edit docker-compose to create app-based service, network and volume names](#edit-docker-compose-to-create-app-based-service-network-and-volume-names)
  - [Replace your D6 app's doc root file system](#replace-your-d6-apps-doc-root-file-system)
  - [Set up database credentials and `settings.php`](#set-up-database-credentials-and-settingsphp)
  - [Double check mysql setup](#double-check-mysql-setup)
  - [Build and run with docker-compose](#build-and-run-with-docker-compose)
    - [Build](#build)
    - [Run in detached mode](#run-in-detached-mode)
  - [Restore legacy database](#restore-legacy-database)
    - [Copy backup file to mysql service instance](#copy-backup-file-to-mysql-service-instance)
    - [Restore to database](#restore-to-database)
    - [Note on common error with large files](#note-on-common-error-with-large-files)
  - [Access app in browser](#access-app-in-browser)
    - [Note on common error with visualization of strict php errors](#note-on-common-error-with-visualization-of-strict-php-errors)
  - [Drush included!](#drush-included)
    - [Login to the web service and run drush](#login-to-the-web-service-and-run-drush)
    - [Run drush commands from the host computer command line without logging into the web server](#run-drush-commands-from-the-host-computer-command-line-without-logging-into-the-web-server)
  - [Control Controls app](#control-controls-app)

### Fork or clone the repo

```bash
git clone git@github.com:awebfactory/dd-d6-docker-compose-local-dev.git
cd dd-d6-docker-compose-local-dev
```

### Edit docker-compose to create app-based service, network and volume names

If you wish, everything will run as is, but for multiple use on a single system, you will want to create a unique "namespace" for each app, so that, for example, destruction of a volume will not destroy the data of another app instance.

Before:

```yaml
version: "3"

services:
  d6web:
    build:
      context: .
      dockerfile: .docker/d6web/Dockerfile
    image: d6web
    container_name: d6web
    restart: unless-stopped
    ports:
      - "10080:80"
    volumes:
      - ./drupal-6.38:/var/www/html
    networks:
      - d601-network
    depends_on:
      - d6mysql
  d6mysql:
    image: mysql:5.6
    # image: docker-drush6-php53
    container_name: d6mysql
    restart: unless-stopped
    volumes:
      - db_d601_data:/var/lib/mysql
    env_file: .env
    environment:
      - MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
      - MYSQL_DATABASE=${MYSQL_DATABASE}
      - MYSQL_USER=${MYSQL_USER}
      - MYSQL_PASSWORD=${MYSQL_PASSWORD}
    networks:
      - d601-network

networks:
  d601-network:
    driver: bridge

volumes:
  db_d601_data:
```

For my literary workshop app `lit`:

```yaml
version: "3"

services:
  d6litweb:
    build:
      context: .
      dockerfile: .docker/d6litweb/Dockerfile
    image: d6litweb
    container_name: d6litweb
    restart: unless-stopped
    ports:
      - "10080:80"
    volumes:
      - ./drupal-6.38:/var/www/html
    networks:
      - d6lit-network
    depends_on:
      - d6mysql
  d6mysql:
    image: mysql:5.6
    # image: docker-drush6-php53
    container_name: d6mysql
    restart: unless-stopped
    volumes:
      - d6lit_data:/var/lib/mysql
    env_file: .env
    environment:
      - MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
      - MYSQL_DATABASE=${MYSQL_DATABASE}
      - MYSQL_USER=${MYSQL_USER}
      - MYSQL_PASSWORD=${MYSQL_PASSWORD}
    networks:
      - d6lit-network

networks:
  d6lit-network:
    driver: bridge

volumes:
  d6lit_data:
```

with the Dockerfile folder adjusted accordingly to `.docker/d6litweb/`.

### Replace your D6 app's doc root file system

The repo by default has a vanilla Drupal 6.38 file system in the `drupal-6.38` folder. It is referenced by the app Dockerfile on line 6:

```bash
COPY ./drupal-6.38/ /var/www/html/
```

To drop in your own legacy app filesystem, you should do the following:

- Replace the `drupal-6.38` folder with your legacy app filesystem doc root, exactly as it is to run in docker.
- Have a legacy database backup on-hand (either in the `db-backups` folder or on hand so that it can be manually restored once the system is up)
- Replace the name of the folder in the `Dockerfile`
  - **Important** also replace the name of the `web.conf` path on line 14:

```bash
COPY ./.docker/d6litweb/web.conf /etc/apache2/sites-available/web.conf
```

- **Important** Replace the name of the folder in the `docker-compose` file.
  - was: `- ./drupal-6.38:/var/www/html`
  - **now**: `- ./lit:/var/www/html`

So, for our `lit` app, we have a database backup in the document root, so we have removed the `db-backups` folder and our file structure looks like this now:

```bash
$ ls -la
total 100
drwxrwxr-x  5 victor victor  4096 Feb 25 08:24 .
drwxrwxr-x  4 victor victor  4096 Feb 25 08:17 ..
-rw-rw-r--  1 victor victor   369 Feb 24 14:27 dd-d6-docker-compose.code-workspace
-rw-rw-r--  1 victor victor 18772 Feb 24 14:27 DEV.md
drwxrwxr-x  3 victor victor  4096 Feb 25 07:42 .docker
-rw-rw-r--  1 victor victor   811 Feb 25 06:55 docker-compose.yml
-rw-rw-r--  1 victor victor    55 Feb 24 14:27 .dockerignore
-rw-rw-r--  1 victor victor    89 Feb 24 14:27 .env.example
drwxrwxr-x  8 victor victor  4096 Feb 25 07:36 .git
-rw-rw-r--  1 victor victor   113 Feb 24 14:27 .gitignore
-rw-rw-r--  1 victor victor 35122 Feb 24 14:27 LICENSE
drwxrwxrwx 11 victor victor  4096 Feb 23 11:19 lit
-rw-rw-r--  1 victor victor   104 Feb 24 14:27 README.md
```

Our `.docker` folder situation is as follows:

```bash
$ tree .docker
.docker
└── d6litweb
    ├── Dockerfile
    └── web.conf

$ grep lit .docker/d6litweb/Dockerfile
COPY ./lit/ /var/www/html/
```

### Set up database credentials and `settings.php`

- copy mysql credentials from `settings.php` to dotenv file
- in `settings.php` replace `localhost` or mysql server uri with mysql service docker network name

In the case of the example app `lit`, the uri in `lit/sites/default/settings.php` read as follows:

    $db_url = 'mysqli://dr_workshop:workshoppw22@localhost/dr_workshop';

So we create the `.env` file accordingly (based on provided example):

```bash
cp .env.example .env
vi .env
cat .env
# $db_url = 'mysqli://dr_workshop:workshoppw22@localhost/dr_workshop';
MYSQL_ROOT_PASSWORD=workshoppw22
MYSQL_DATABASE=dr_workshop
MYSQL_USER=dr_workshop
MYSQL_PASSWORD=workshoppw22
```

We then copy the name of the mysql service and edit `/lit/sites/default/settings.php` accordingly.

Legacy (with localhost):

    $db_url = 'mysqli://dr_workshop:workshoppw22@localhost/dr_workshop';

Edited: (with docker-compose mysql service name):

    $db_url = 'mysqli://dr_workshop:workshoppw22@d6mysql/dr_workshop';

### Double check mysql setup

Service name and credentials:

```bash
grep mysql docker-compose.yml
      - d6mysql
  d6mysql:
    image: mysql:5.6
    container_name: d6mysql
      - d6lit_data:/var/lib/mysql

cat .env
# $db_url = 'mysqli://dr_workshop:workshoppw22@localhost/dr_workshop';
MYSQL_ROOT_PASSWORD=workshoppw22
MYSQL_DATABASE=dr_workshop
MYSQL_USER=dr_workshop
MYSQL_PASSWORD=workshoppw22

grep mysql lit/sites/default/settings.php
 *   $db_url = 'mysql://username:password@localhost/databasename';
 *   $db_url = 'mysqli://username:password@localhost/databasename';
#$db_url = 'mysql://username:password@localhost/databasename';
#$db_url = 'mysqli://dr_workshop:workshoppw22@localhost/dr_workshop';
$db_url = 'mysqli://dr_workshop:workshoppw22@d6mysql/dr_workshop';
```

### Build and run with docker-compose

If you are using docker machine, make sure it's running and you have a usable current IP

```bash
docker-machine ls
docker-machine restart default
eval "$(docker-machine env $machine)"
docker-machine ip default
```

#### Build

```bash
docker-compose build
d6mysql uses an image, skipping
Building d6litweb
Step 1/13 : FROM nimmis/apache-php5
 ---> 2f18ea462e8a
Step 2/13 : MAINTAINER victorkane@awebfactory.com
 ---> Using cache
 ---> bf731d7cbc13
Step 3/13 : WORKDIR /var/www/html
 ---> Using cache
 ---> 4e4ae2ec4a93
Step 4/13 : COPY ./lit/ /var/www/html/
 ---> Using cache
 ---> 37e952273a52
Step 5/13 : EXPOSE 80
 ---> Using cache
 ---> 38432b1f3c0b
Step 6/13 : COPY ./.docker/d6litweb/web.conf /etc/apache2/sites-available/web.conf
 ---> 8ffeb7fd04cf
Step 7/13 : RUN a2dissite 000-default && a2ensite web
 ---> Running in 09611154c46f
Site 000-default disabled.
To activate the new configuration, you need to run:
  service apache2 reload
Enabling site web.
To activate the new configuration, you need to run:
  service apache2 reload
Removing intermediate container 09611154c46f
 ---> ff3a34ea2a7d
Step 8/13 : RUN a2enmod rewrite
 ---> Running in 344a5641023d
Enabling module rewrite.

...

Setting up mysql-client-5.5 (5.5.62-0ubuntu0.14.04.1) ...
Setting up mysql-client (5.5.62-0ubuntu0.14.04.1) ...
Processing triggers for libc-bin (2.19-0ubuntu6.15) ...
Removing intermediate container ad5d17d375cb
 ---> a0cb1d98e048
Successfully built a0cb1d98e048
Successfully tagged d6litweb:latest
```

#### Run in detached mode

```bash
docker-compose up -d
Creating network "dd-d6-docker-compose-local-dev-lit_d6lit-network" with driver "bridge"
Creating volume "dd-d6-docker-compose-local-dev-lit_d6lit_data" with default driver
Creating d6mysql ... done
Creating d6litweb ... done

docker-compose ps
  Name               Command             State           Ports
----------------------------------------------------------------------
d6litweb   /my_init                      Up      0.0.0.0:10080->80/tcp
d6mysql    docker-entrypoint.sh mysqld   Up      3306/tcp
```

### Restore legacy database

#### Copy backup file to mysql service instance

I copied the legacy database backup sql file (made in 2008 via `mysqldump`) from the laptop host to the docker database container:

```bash
docker cp lit/db.sql d6mysql:/
```

#### Restore to database

```bash
docker exec -it d6mysql /bin/bash
root@578a834cd55a:/# ls -l db.sql
-rwxrwxrwx 1 1000 1000 28845997 Nov 1 2010 db.sql
root@578a834cd55a:/# mysql -u dr_workshop -p dr_workshop < /db.sql
Enter password:
```

#### Note on common error with large files

If, up running `mysql -u dr_workshop -p dr_workshop < /db.sql` you get the error `ERROR 2006 (HY000) at line 356: MySQL server has gone away`, a special configuration is necessary due to large values present in column values. To solve [see [ERROR 2006 (HY000): MySQL server has gone away](https://stackoverflow.com/questions/10474922/error-2006-hy000-mysql-server-has-gone-away)]

```bash
root@578a834cd55a:/etc/mysql# mysql -u root -p
Enter password:

mysql> set global max_allowed_packet=64*1024*1024;
Query OK, 0 rows affected (0.00 sec)

mysql>
```

### Access app in browser

Point browser at `http://<docker-machine ip>:11180` or if not using docker-machine at `http://localhost:11180`.

#### Note on common error with visualization of strict php errors

This is especially common with very early versions of Drupal 6 and contribution modules (lit is Drupal 6.4)

See discussion here: [Prevent the display of PHP's strict warnings with the Disable Messages module]https://www.drupal.org/node/1913314)

I solved the problem by using a contribution module mentioned in the discussion (important: not covered by official drupal security advisory policy; also I removed leading underscores in directory and file names): [\_\_\_drupal_php_strict_suppress 6.x-1.0](https://www.drupal.org/project/___drupal_php_strict_suppress/releases/6.x-1.0)

### Drush included!

[Drush](https://www.drush.org/latest) comes installed, and you can use it in one of two ways:

#### Login to the web service and run drush

When you login via docker command, you are automatically logged into document root of the web app, the working directory. You can directly execute any drush command there.

```bash
docker exec -it d6litweb /bin/bash
root@3f9b09c3f55a:/var/www/html#
drush status
 Drupal version                  :  6.4
 Site URI                        :  http://default
 Database driver                 :  mysql
 Database hostname               :  d6mysql
 Database port                   :
 Database username               :  dr_workshop
 Database name                   :  dr_workshop
 Database                        :  Connected
 Drupal bootstrap                :  Successful
 Drupal user                     :
 Default theme                   :  zenlitworkshop
 Administration theme            :  bluemarine
 PHP executable                  :  /usr/bin/php
 PHP configuration               :  /etc/php5/cli/php.ini
 PHP OS                          :  Linux
 Drush script                    :  /.composer/vendor/drush/drush/drush.php
 Drush version                   :  8.4.6
 Drush temp directory            :  /tmp
 Drush configuration             :
 Drush alias files               :
 Install profile                 :  default
 Drupal root                     :  /var/www/html
 Drupal Settings File            :  sites/default/settings.php
 Site path                       :  sites/default
 File directory path             :  files
 Temporary file directory path   :  /tmp
```

Run `drush help` to find out all the incredible things you can do with drush, if you are not (or even if you are) familiar with it.

#### Run drush commands from the host computer command line without logging into the web server

Obtain info for user `joyce`

```bash
docker exec d6litweb drush user-information joyce
 User ID       :  5
 User name     :  joyce
 User mail     :  joyce@joyce.net
 User roles    :  authenticated user
                  Workshop member
 User status   :  active
```

### Control Controls app

To stop without destroying volumes, containers:

    docker-compose stop

To restart if just stopped:

    docker-compose start

To only destroy only containers:

    docker-compose down

To destroy containers, volumes, images:

    docker-compose down --rmi all -v
