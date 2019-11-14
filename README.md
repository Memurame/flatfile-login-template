# Flatlogin - Template

![flatlogin_screen](https://user-images.githubusercontent.com/15847494/68676863-39511780-055b-11ea-9376-d4a1e8d1704a.PNG)

This is a template with a login area without a database. Built on the [Slim Framework v3](https://www.slimframework.com/).
I have developed this template as a template for a webapp, 
who uses this template should therefore be familiar with the web development.

The template may be used freely without stating a copyright.\
Feedbacks and improvements are welcome.

## Requirements
* Composer
* PHP 5.5.0 or newer
* Node (for the default theme)
* APCu Extension (for bruteforce protection)

## Installation
* Download `Flatlogin` and unzip it to any directory on your webserver.
* Open a terminal and execute `composer install`.
* Open in the browser e.g. `http://localhost/your/project`
* Register your first acc

## Configuration
In the `settings.yaml` in the root directory you can change the settings from the webapp.

## Design / Theme
You can create your own theme in the folder `themes/`. 
The `default` theme can be copied as a template and built on it.

Node is required to handle the design `default`. \
In the folder `themes/default` execute the command` npm install`. 

**Order of gulp tasks:** \
gulp dep-update = Load the required resources to the right place \
gulp build = Generates the css and js files \

With `gulp watch`, the files can then be monitored for changes

## Cronjob
To use cronjobs first the `crunz.yml` has to be generated. \
```
/project/vendor/bin/crunz publish:config
The configuration file was generated successfully
```
As a result, a copy of the configuration file will be created within our project's root directory.

To run the tasks, you only need to install an ordinary cron job (a crontab entry) which runs **every minute**, 
and delegates the responsibility to Crunz' event runner:
```
* * * * * cd /project && vendor/bin/crunz schedule:run
```

For more info read the [documentation](https://github.com/lavary/crunz)


## Debug Mode
By default, the debug mode is disabled and no errors are displayed. 
In order to activate the debug mode, 
a file with the name `.DEBUG` must be created in the root directory.

## In progress or planned
-[ ] Multilanguage
