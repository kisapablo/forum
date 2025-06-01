# Forum on PHP and MySQL


## Composer.json Settings



### composer init

### composer require slim/slim: "4.*'

### composer require ext-pdo

### composer require dump-autoload

### composer require php-di/php-di:6




## Create to MySQL base and settings

ATTENTION! Some of the text is written specifically in caps because it is probably necessary in syntax MySQL, Please enter the text in the same way as it is specified in the README File.

 ### CREATE DATABASE blog_php

 #### Create TABLE post {


 #### post_id int(10) UNSIGNED NOT NULL AUTO_INCREMENT


 #### title VARCHAR(255) NOT NULL,


 #### url_key VARCHAR(255) NOT NULL,


 #### image_path varchar(255) NULL,


 #### content TEXT DEFAULT NULL, 


 #### description VARCHAR(255) DEFAULT NULL,


 #### published_date DATETIME NOT NULL,


 post_id int(10) UNSIGNED NOT NULL AUTO_INCREMENT


 title VARCHAR(255) NOT NULL,


 url_key VARCHAR(255) NOT NULL,


 image_path varchar(255) NULL,


 content TEXT DEFAULT NULL, 


 description VARCHAR(255) DEFAULT NULL,


 published_date DATETIME NOT NULL,

 #### PRIMARY KEY {post_id},

 #### UNIQUE KEY url_key (url_key)

} ENGINE=InnoDB;
