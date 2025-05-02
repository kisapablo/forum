Composer.json Settings
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


Create to MySQL base and settings

