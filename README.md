## Composer.json Settings
{
    "name": "nikita/blog",
    "description": "Forum on PHP and MySQL",
    "type": "project",
    "autoload": {
        "psr-4": {
            "Nikita\\Blog\\": "src/"
        }
    },
    "authors": [
        {
            "name": "kisapablo",
            "email": "kisapablos@gmail.com"
        }
    ],
    "minimum-stability": "stable",
    "require": {
        "slim/slim": "4.*",
        "slim/psr7": "^1.7",
        "twig/twig": "^3.1",
        "ext-pdo": "7.4"
    },
    "autoload": {
        "psr-4": {
            "Blog\\": "src/"
        }
    }
}


## Create to MySQL base and settings
## ATTENTION! Some of the text is written specifically in caps because it is probably necessary in syntax MySQL, Please enter the text in the same way as it is specified in the README File.
 ### CREATE DATABASE blog_php
 #### Create TABLE post {
 #### post_id int(10) UNSIGNED NOT NULL AUTO_INCREMENT
 #### title VARCHAR(255) NOT NULL,
 #### url_key VARCHAR(255) NOT NULL,
 #### image_path varchar(255) NULL,
 #### content TEXT DEFAULT NULL, 
 #### description VARCHAR(255) DEFAULT NULL,
 #### published_date DATETIME NOT NULL,
 #### PRIMARY KEY {post_id},
 #### UNIQUE KEY url_key (url_key)
 #### } ENGINE=InnoDB;
 
 ##### INSERT INTO post (title, url_key, content, description, published_date) 
##### VALUES ('Hello World', 'hello-world', 'This is the content of the post.', 'A sample post to say hello to the world.', CURRENT_DATE);
