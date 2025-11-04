---
title: "blog"
description: "blog"
date: "2025-01-01"
category: "blog"
tags: []
excerpt: ""
metaTitle: "blog"
metaDescription: "blog"
cover: ""
---

An error is occured with the "PLXASTROSYNC" plugin :
type : 1 E_ERROR - See https://www.php.net/manual/en/errorfunc.constants.php#constant.e-error
message : Uncaught Error: Call to undefined method plxAstroSync::addConfigParameter() in /home/alaouibe/public_html/pluxml/plugins/plxAstroSync/plxAstroSync.php:13
Stack trace:
#0 /home/alaouibe/public_html/pluxml/core/lib/class.plx.plugins.php(203): plxAstroSync->__construct('en')
#1 /home/alaouibe/public_html/pluxml/core/lib/class.plx.plugins.php(371): plxPlugins->getInstance('plxAstroSync')
#2 /home/alaouibe/public_html/pluxml/core/admin/parametres_plugins.php(19): plxPlugins->saveConfig(Array)
#3 {main}
  thrown 
file : plxAstroSync/plxAstroSync.php 
line : 13 
============================================================
User / Profil : 001 / 0
PluXml version : 5.8.21
PLX_DEBUG : false
PHP version : 8.1.33
============================================================
About this server :
HTTP_ACCEPT_LANGUAGE : en-US,en;q=0.9,fr;q=0.8
HTTP_REFERER : /pluxml/core/admin/parametres_plugins.php
HTTP_USER_AGENT : Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36
REQUEST_URI : /pluxml/core/admin/parametres_plugins.php
SCRIPT_FILENAME : /pluxml/core/admin/parametres_plugins.php
SERVER_SOFTWARE : LiteSpeed
REQUEST_METHOD : POST
