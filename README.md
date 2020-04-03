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
