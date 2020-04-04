# Attempt 01

## Plan

### Steps taken with docker containers

| Step                                                            | docker only                                                                                                                                                                       | docker compose |
| --------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------- |
| Pull Mysql image                                                | `sudo docker pull mysql:5.6`                                                                                                                                                      | ?              |
| Start Mysql container                                           | `$ sudo docker run -d \ --name="drupal-mysql" \ -e MYSQL_ROOT_PASSWORD=drupalroot \ -e MYSQL_DATABASE=drupal6 \ -e MYSQL_USER=drupal \ -e MYSQL_PASSWORD=drupal6pass \ mysql:5.6` | ?              |
| Obtain Drupal Code                                              | `wget https://ftp.drupal.org/files/projects/drupal-6.38.tar.gz` \ `tar -xzf drupal-6.38.tar.gz`                                                                                   |
| Pull Apache image` | `$ sudo docker pull nimmis/apache-php5`    | ?                                                                                                                                                                                 |
| Build document root container with Apache image and Drupal code | `$ sudo docker run -d  \ -p 10080:80 \ -v ./drupal-6.38:/var/www/html \ --name="drupal-app" \ --link="drupal-mysql" \ nimmis/apache-php5`                                         | ?              |
| Install Drupal interactively in the browser                     | `http://localhost:10080`                                                                                                                                                          | ?              |

So in [Docker for Legacy Drupal Development](:/0267f841e7c7464b9be35acb4d1b696a), using docker only, what happens is this:

`drupal-mysql`

```bash
docker pull mysql:5.6
docker run -d \
--name="drupal-mysql" \
-e MYSQL_ROOT_PASSWORD=drupalroot \
-e MYSQL_DATABASE=drupal6 \
-e MYSQL_USER=drupal \
-e MYSQL_PASSWORD=drupal6pass \
mysql:5.6
```

`drupal-app`

```bash
wget https://ftp.drupal.org/files/projects/drupal-6.38.tar.gz
tar xvzf drupal-6.38.tar.gz
docker pull nimmis/apache-php5
sudo docker run -d \
-p 10080:80 \
-v ~/drupal-6.38:/var/www/html \
--name="drupal-app" \
--link="drupal-mysql" \
nimmis/apache-php5
```

Point browser at `http://localhost:10080`

Install Drupal interactively.

### Steps taken using our `Dockerfile` and `docker-compose.yml`

The container `drupal-app` is now called `d6web` and is built in the context of the project root based on `Dockerfile`, which specifies the `nimmis/apache-php5`, copies the local project files to the working directory `/var/www/html`, and exposes port 80. `docker-compose.yml` 

```bash
victorkane@Victors-MacBook-Air attempt01 % docker-compose build
d6mysql uses an image, skipping
Building d6web
Step 1/4 : FROM nimmis/apache-php5
 ---> 2f18ea462e8a
Step 2/4 : WORKDIR /var/www/html
 ---> Using cache
 ---> 666366153094
Step 3/4 : COPY ./drupal-6.38/ /var/www/html/
 ---> 2ca1dab23a03
Step 4/4 : EXPOSE 80
 ---> Running in 703bb63befc5
Removing intermediate container 703bb63befc5
 ---> 41be09ed6b04
Successfully built 41be09ed6b04
Successfully tagged d6web:latest
victorkane@Victors-MacBook-Air attempt01 % docker-compose up -d
Creating network "attempt01_d601-network" with driver "bridge"
Creating d6mysql ... done
Creating d6web   ... done
victorkane@Victors-MacBook-Air attempt01 % docker exec -it d6web /bin/bash
root@e47b35ee17b4:/var/www/html# ls
CHANGELOG.txt      INSTALL.txt      UPGRADE.txt  install.php  robots.txt  update.php
COPYRIGHT.txt      LICENSE.txt      cron.php     misc         scripts     xmlrpc.php
INSTALL.mysql.txt  MAINTAINERS.txt  includes     modules      sites
INSTALL.pgsql.txt  README.md        index.php    profiles     themes
root@e47b35ee17b4:/var/www/html# exit
exit
victorkane@Victors-MacBook-Air attempt01 % docker-compose down
Stopping d6web   ... done
Stopping d6mysql ... done
Removing d6web   ... done
Removing d6mysql ... done
Removing network attempt01_d601-network
```

```bash
victorkane@Victors-MacBook-Air default % ls -l
total 24
-rw-r--r--  1 victorkane  staff  10310 Feb 24  2016 default.settings.php

victorkane@Victors-MacBook-Air default % mkdir files
victorkane@Victors-MacBook-Air default % chmod 777 files
victorkane@Victors-MacBook-Air default % cp default.settings.php settings.php
victorkane@Victors-MacBook-Air default % chmod 666 settings.php
victorkane@Victors-MacBook-Air default % ls -l
total 48
-rw-r--r--  1 victorkane  staff  10310 Feb 24  2016 default.settings.php
drwxrwxrwx  3 victorkane  staff     96 Apr  3 11:48 files
-rw-rw-rw-  1 victorkane  staff  10310 Apr  3 12:18 settings.php

docker build

docker up -d

docker-machine ip default
192.168.99.101
```

browse to: http://192.168.99.101:10080

in interactive install, cite mysql host and port in advanced settings as follows: d6mysql and 3306 (shouldn't require port but seems to need it)

### Reset to uninstalled status

```
Creating network "attempt01_d601-network" with driver "bridge"
Creating d6mysql ... done
Creating d6web   ... done
victorkane@Victors-MacBook-Air attempt01 % docker-compose down -v --rmi all
Stopping d6web   ... done
Stopping d6mysql ... done
Removing d6web   ... done
Removing d6mysql ... done
Removing network attempt01_d601-network
Removing image mysql:5.6
Removing image d6web
```

Adjust the directory `drupal-6.38/sites/default/` as follows:

```bash
% docker-compose down
Stopping d6web   ... done
Stopping d6mysql ... done
Removing d6web   ... done
Removing d6mysql ... done
Removing network attempt01_d601-network
% ls -l drupal-6.38/sites/default
total 48
-rw-r--r--  1 victorkane  staff  10310 Feb 24  2016 default.settings.php
drwxrwxrwx  3 victorkane  staff     96 Apr  3 11:48 files
-rw-rw-rw-  1 victorkane  staff  10310 Apr  3 14:36 settings.php
% diff drupal-6.38/sites/default/default.settings.php drupal-6.38/sites/default/settings.php
%
```

### Persist db with volume (bridge)

Just a few lines in the `docker-compose.yml` file:

```yaml
  ...
  d6mysql:
    ...
    volumes:
      - db_d601_data:/var/lib/mysql
    ...
volumes:
  db_d601_data:
```

### Back up the db just to be sure

```bash
root@137949ae85b4:/# mysqldump -u drupal601 -p drupal601 > db.sq
root@137949ae85b4:/# head -20 db.sql
-- MySQL dump 10.13  Distrib 5.6.47, for Linux (x86_64)
--
-- Host: localhost    Database: drupal601
-- ------------------------------------------------------
-- Server version	5.6.47

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `access`
--
root@137949ae85b4:/# exit
docker cp d6mysql:/db.sql backup
% ls -l backup
total 1496
-rw-r--r--  1 victorkane  staff  762908 Apr  3 16:27 db.sql
```

### Test db persistence in volume

First down the app, removing images but not volumes. Then build, come back up, check to make sure site still up with content and admin login.

```bash
% docker-compose down --rmi all
Stopping d6web   ... done
Stopping d6mysql ... done
Removing d6web   ... done
Removing d6mysql ... done
Removing network attempt01_d601-network
Removing image mysql:5.6
Removing image d6web
victorkane@Victors-MacBook-Air attempt01 % grep mysql drupal-6.38/sites/default/settings.php
 *   $db_url = 'mysql://username:password@localhost/databasename';
 *   $db_url = 'mysqli://username:password@localhost/databasename';
$db_url = 'mysqli://drupal601:drupal601p@d6mysql:3306/drupal601';
% docker-compose build
d6mysql uses an image, skipping
Building d6web
Step 1/4 : FROM nimmis/apache-php5
 ---> 2f18ea462e8a
Step 2/4 : WORKDIR /var/www/html
 ---> Using cache
 ---> 666366153094
Step 3/4 : COPY ./drupal-6.38/ /var/www/html/
 ---> de1a2b3fae81
Step 4/4 : EXPOSE 80
 ---> Running in 9a4123d55eb1
Removing intermediate container 9a4123d55eb1
 ---> f50053ad862d
Successfully built f50053ad862d
Successfully tagged d6web:latest
% docker-compose up -d
Creating network "attempt01_d601-network" with driver "bridge"
Pulling d6mysql (mysql:5.6)...
5.6: Pulling from library/mysql
48839397421a: Pull complete
725652de4539: Pull complete
e4e83fcf33af: Pull complete
d22eed95a35d: Pull complete
7c6413e9e73a: Pull complete
db37ee61b2cc: Pull complete
5f352f2d0e1b: Pull complete
6f664886aa54: Pull complete
7f4961f446cc: Pull complete
7e610963b475: Pull complete
2b007da11435: Pull complete
Digest: sha256:f0165a6f800d183ca92ab822e2fe6157acebc5752bab3ca2b9e805b2fa894bc8
Status: Downloaded newer image for mysql:5.6
Creating d6mysql ... done
Creating d6web   ... done
```

Indeed, everything comes up, reload page, logout, login, check content, story and page still there.

### TODO clean url's

```bash
a2enmod rewrite
Enabling module rewrite.
To activate the new configuration, you need to run:
  service apache2 restart
root@602acc2b32d7:/var/www/html# service apache2 restart
 * Restarting web server apache2

cat /etc/apache2/sites-available/ 000-default.conf

<VirtualHost *:80>

        ServerAdmin webmaster@localhost
        DocumentRoot /var/www/html
          <Directory "/var/www/html">
                Options Indexes FollowSymLinks MultiViews
                AllowOverride All
                Order allow,deny
                allow from all
          </Directory>

        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined

</VirtualHost *:80>
```

Then enable at http://192.168.99.101:10080/?q=admin/settings/clean-urls (assuming clean url test passes and the enable option is not disabled)

This hard-coded config plus of course db config (persisted in volume) survives stop/start but not down (container destroyed without committing changes to new image)

```
% docker-compose stop

% docker-compose start
```

TODO create image that manages this with command in original image creation (build/up)

### Backup a container set's volatile data before downing

- Create top-level db-backups directory
  - .keep file
  - gitignore common backup files ()
- Small & medium
  - backup database

#### Using container's mysql client and local host file system without volume

```bash
% docker exec  d6mysql which mysqldump
/usr/bin/mysqldump
% docker exec -it d6mysql /usr/bin/mysqldump -u drupal601 -p drupal601 > ./db-backups/202004041426-db.sql
victorkane@Victors-MacBook-Air attempt01 % ls -l db-backups
total 9704
-rw-r--r--  1 victorkane  staff  1187055 Apr  4 14:26 202004041426-db.sql
```

### Add more features to the `d6web` container

So we'll stop our container set and delete the containers and images (all, but without killing the database volume. Then we'll make these changes, build and bring up again.

```bash
docker-compose down --rmi all
docker-compose build
docker-compose up -d
```

But we if we're only making dev changes to d6web, we could just do:

```bash
docker-compose down
docker rmi d6web
docker-compose build
docker-compose up -d
```

- Move from project root to `.docker` subdir with its own name
- Add clean urls to the image

Done! With help from [socketwench / drupal-base](https://github.com/socketwench/drupal-base). Our commit: [feat(docker image d6web): Add clean urls to the image](http://noraperusin:3000/DurableDrupal/dd-d6-docker-compose-local-dev/commit/b2b1956ff4a4f198c2c77a5ada9da161c59e87ad)

### Add a drush container to the set

### Use the drush container for various tasks

### Refs

- [Docker for Legacy Drupal Development](:/0267f841e7c7464b9be35acb4d1b696a)
- [Dockerize an Existing Project | Drupalize.Me](:/6de5d97befea4f4ba63951e64a40973b)
- [Quickstart: Compose and WordPress](https://docs.docker.com/compose/wordpress/)
- [docker hub for image nimmis/apache-php5](https://hub.docker.com/r/nimmis/apache-php5/tags)
- [docker hub for official image mysql](https://hub.docker.com/_/mysql/)
  - [docker-library/mysql 5.6 Dockerfile](https://github.com/docker-library/mysql/blob/d284e15821ac64b6eda1b146775bf4b6f4844077/5.6/Dockerfile)
- [Mysql Installation Guide. Basic Steps for MySQL Server Deployment with Docker](https://dev.mysql.com/doc/mysql-installation-excerpt/8.0/en/docker-mysql-getting-started.html)
- docker4drupal
- drush
- etc
