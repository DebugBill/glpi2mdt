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

## Restrictions
This plugin works fine on my specific configuration 
* MDT 2013 on Server 2012
* MS-SQL 2012
* GLPI 9.1.6 on Linux 
* PHP 5.5 
It is developed and tested in this environment only. If you experience problem with a different configuration please report the issue on GitHub. I'll do my best to make it compatible with other setups as long as I am aware of the issue.

## Prerequisites

* MDT must be installed in a MS-SQL database accessible from the GLPI server
* MDT must be fully operationnal by itself. The plugin will not fix a faulty MDT installation, it is only remote-controlling it.
* (MSSQL or ODBC) and SimpleXML PHP modules must be installed
* The "Control" directory in your MDT deployment share contains part of the MDT configuration (the other part is in the MS-SQL database). It needs to be mounted (read-only is OK) somewhere on your GLPI server and accessible to PHP scripts. Pay specific attention to files ownership and SELinux settings which can prevent proper functionning. 
* Fusion inventory, though not mandatory is really very very nice to have.

Starting with version 0.1.0 this plugin is now fully functionnal and in use at our premises. I hope to improve it with time but some feedback on this
version is welcome in order to direct my efforts in the right direction.
Still, it works for us, in our specific environment!

The plugin is available in French and English, other translators welcome.


## TODOs
* Manage ranks in applications (this is mainly UI interface stuff, the code can already handle it. My main question is "is it really needded? We don't as we don't have dependencies between applications (and no so many applications to install anyway).
* Handle models, packages in the same way applications are handled (but are you using those features in MDT)? Models are an issue because of a limitatio in Fusion Inventory. It may be aleviated with GLPI 9.2.x. More to come...
* Automate some actions based on information available in GLPI and not managed by MDT (location, entity....)
* Be multi-MDT-server, multi-deployment-share, multi-domain aware. Currently the plugin is not domain aware and is connected to only one MDT database. This raises quite a few questions as to how it should work then.
* Several coupling modes are proposed in the config page.... The strict and loose coupling are there, master-master is not for the time being. Is ithis mode desirable anyway? For me the "Strict Master-Slave is fine" as I want everything to be done in GLPI. Feedback is welcome.
* Fix Bugs!!!!! There most probably are many, this is the first release.


Please test and send feedback if you (dis)like my work.

For more information, please consult the Wiki on gitHub


