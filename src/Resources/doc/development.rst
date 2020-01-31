Development tips
================
This section lists tips and tricks to collaborate on the bundle.

Clone the repository
--------------------
TODO

Docker setup
------------
A docker-compose setup is available in Resources/docker.
Along the usual symfony requirements, the docker image must have git installed
to load the bundle as a path package.

Dumping the assets
------------------
To dump the assets you must first run Encore on the bundle. This updates the Resources/public folder
Then install the assets in the symfony project
```
# Direct
cd /path/to/bundle; yarn encore prod; cd /path/to/symfony; bin/console assets:install --symlink

# Docker
docker-compose run -w /path/to/bundle encore yarn encore prod;
docker-compose run php bin/console assets:install --symlink;
```

