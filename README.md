# glpi2mdt


## Introduction
This goal of this plugin is to interface GLPI with MDT, the Microsoft Deployment Toolkit in a way quite similar to what SSCM can do.

My goal when creating this tool was to enable our support team to reinstall computers using PXE boot with minimal interaction with any other tool than GLPI. Ideally I would even like end-users to be able to reinstall their computer "in place" just pressing F12 after contacting the support.

In order to work well the plugin needs to have as much informations as possible on the computers themselves so it can push the proper settings to MDT, namely:
- Make and model (for drivers)
- Hardware configuration (mac addresses)
- Type (I want to enable BitLocker for laptops for example)
- GUID, serial number, name.
- OS to deploy (windows or even servers)
- Applications
- Roles, packages (as in MDT)


The plugin will therefore get its full potential when used in conjunction with "Fusion Inventory".


## Prerequisites

* MDT must be installed in a MS-SQL database accessible from the GLPI server
* MDT must be fully operationnal by itself. The plugin will not fix a faulty MDT installation, it is only remote-controlling it.
* MSSQL and SimpleXML PHP modules must be installed
* The "Control" directory in your MDT deployment share contains part of the MDT configuration (the other part is in the MS-SQL database). It needs to be mounted (read-only is OK) somewhere on your GLPI server and accessible to PHP scripts. 
* Fusion inventory, though not mandatory is really very very nice to have.

As of version 0.0.1 this plugin is just a little more than a proof of concept. I hope to improve it with time but some feedback on this
version is welcome in order to direct my efforts in the right direction.
Still, it works for us, in our specific environment!

The plugin is available in French and English, other translators welcome.


## TODOs
* Add rights management. Currently anyone can do anything
* Make it possible to choose more than one application. Manage groups (this is mainly UI interface stuff, the code can already handle it. My main question is how many applications are avalaible on average MDT servers? Our has only 5, but in a previous company we had hundreds. The gui would not be the same....)
* Handle roles, models, packages in the same way applications are handled (but are you using those features in MDT)?
* Automate some actions based on information available in GLPI and not managed by MDT (location, entity....)
* Be multi-MDT-server, multi-deployment-share, multi-domain aware. Currently the plugin is not domain aware and is connected to only one MDT database. This raises quite a few questions as to how it should work then.
* Several coupling modes are proposed in the config page.... but only one is really implemented. There too I wonder what user want. For me the "Strict Master-Slave is fine" as I want everything to be done in GLPI.
* Add "database port" support. This is easy but as I did not need it it was low on the list
* Add a few crontasks to automate data import from MDT and data cleanup, especially in Master-Master mode if I ever implement it.
* Fix Bugs!!!!! There most probably are many, this is the first release.


Please test and send feedback if you (dis)like my work.


