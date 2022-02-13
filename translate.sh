#!/bin/bash
#################################################################################
#  Update translation files                                                     #
#################################################################################

xgettext --keyword=__  --sort-output -L PHP \
	--copyright-holder='Copyright (C) 2022 Blaise Thauvin' \
	--package-name=glpi2mdt \
	--join-existing \
	--from-code=UTF-8 \
	--package-version='0.3.0' \
	--msgid-bugs-address='glpi2mdt at thauvin.org' \
	-o /locales/glpi2mdt.pot *.php inc/*.php front/*.php  


#xgettext /tmp/temp.pot locales/glpi2mdt.pot -o /temp2.pot
tx pull -a
cd locales
msgfmt cs_CZ.po -o cs_CZ.mo  
msgfmt en_US.po -o en_US.mo  
msgfmt fr.po -o fr.mo 
msgfmt fr_FR.po -o fr_FR.mo 
msgfmt ru_RU.po -o ru_RU.mo

# Srings to be translated for glpi2mdt.
# Copyright (C) 2017 Blaise Thauvin
# This file is distributed under the same license as the glpi2mdt package.
# Blaise Thauvin <glpi2mdt at thauvin.org>, 2017.
#
#, fuzzy
#"Language: English\n"
