# Changelog
All notable changes to this project will be documented in this file.

## [0.2.1] - 2017-12-20
### Added
 - Russian language now available thanks to Nikolaï (AircraftRu)

## Changed
 - None

## Removed
 - None


## [0.2.0] - 2017-10-22
### Added
 - New variables to control installation assistant dialogs and supersede default settings

## Changed
 - Master and Strict coupling modes improved

## Removed
 - Support for PHP MSSQL module removed due to lack of functionalities


## [0.1.3] - 2017-10-09
### Added
 - None

## Changed
 - Several bugs and warnings are corrected.

## Removed
 - None


## [0.1.2] - 2017-10-03
### Added
 - Task sequence can now be undefined in order to use default task for model, or global to MDT

## Changed
 - Several bugs and warnings linked to PHP7 are corrected.

## Removed
 - None


## [0.1.1] - 2017-09-27
### Added
 - None

## Changed
 - A few bug fixes for uncommon situations

## Removed
 - None


## [0.1.0] - 2017-09-20
### Added
 - The plugin now uses bith mssql and odbc PHP modules making PHP7 compatible 
 - Crontask is added in order to disable automatic install after a specific date
 - Crontask is added to synchronise base data from MTD to GLPI on a regular basis (applications, roles,....)
 - Several applications have been added.
 - A major bug is removed wich was probably preventing most of you from using the plugin (some platform specific configuraiton was hard coded)
 - TCP port configuration for DB access is now working

## Changed
 - The coupling mode now has an effect. Strict and Loose coupling are working. Master-Master still doesn't

## Removed
 - None


## [0.0.1] - 2017-08-25
### Added
 - A few preliminary administrative files
 - Basic directory structure
 - Plugin code template

### Changed
 - None

### Removed
 - None
### Fixed
 -None

