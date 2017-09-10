# Installation and configuration


## Initial setup
* Install the plugin like any other. 
* Go to menu Configuration/Plugin. Click on **install** then **activate**. This will create additional tables within your GLPI database.
* Mount your deployement share locally on the GLPI server (read-only is OK, only the "Control" directory is necessary. Pay attention to SE-Linux restirctions. PHP may not be able to read the files without proper configuration)
* Once the plugin is activated you can click on its name in the "Plugins" page to get to the configuraiton page

* Configure the plugin: credentials to the MDT database, path to the "control" directory, local admin password to be used for computers..... Save! The "port" is ignored as of version 0.0.1
* Click on "Test". If all goes well you should get confirmation that the database is accessible.
* Click on "Initialize data". Configuration data from MDT's database and XML files on the share will be uploaded. When task sequences or applications are modified on MDT, the data needs to be refresehd again. A cron task will fix that later.

That's all for now!

## Daily usage

* Go to one of your computers. 
* It should have at least one unique identifier availalble: Serial (might not be unique), Mac address, GUID.
* Go to sub-item "auto-install" in the computer page.
* Set your desired values, mainly "Automatic Install" to YES. Choose the proper task sequence and application (yes just one for now)
* Save

The computer is now created into MDT with those settings. Pressing F12 for PXE boot should fire the install.


Hope it works for you too!

