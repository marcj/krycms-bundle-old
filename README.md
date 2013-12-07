Kryn.cms - A full-featured CMS bundle for the Symfony framework
========

A enterprise and high-speed open-source Content-Management-System (CMS) based on the Symfony framework with a full RESTful API,
a user friendly administration interface and rapid application development (RAD) framework using Composer, Propel and
other rock-solid libraries.


[![Build Status Travis](https://travis-ci.org/kryncms/kryn.cms.png?branch=master)](https://travis-ci.org/kryncms/Kryn.cms)

Installation
------------

This describes the customized installation, usually used by developers.

A end-user zip package can be downloaded at http://kryn.org when we've released the first alpha version

Read the [installation documentation](https://raw.github.com/kryncms/KrynCmsBundle/blob/master/Resources/doc/installation.md).

Requirements
------------

1. PHP 5.4+
2. *nix OS (Linux, BSD, OSX)
3. PHP extensions: PDO, mbstring, gd, zip
4. MySQL, PostgreSQL or SQLite (completely tested through unit tests)


Features
--------

 - Based on Symfony
 - Based on Propel ORM (Propel supports MySQL, PostgreSQL, SQLite, MSSQL, and Oracle), http://www.propelorm.org
 - Advanced, fast and fresh administration interface
 - The administration API is completely abstracted through a RESTful JSON API
 - File abstraction layer (for mounts with external storages [s3, ftp, dropbox, etc]), CDN
 - i18n using `getText` (with all of its features [e.g. including plural support, contexts]) compatible .po files
 - High-Performance through several cache layers
 - Session storage through several layers (distributed sessions supported)
 - Easy to extend through a fancy extension editor, completely modulized - the symfony way
 - CRUD window generator, without writing one line of code
 - Easy and integrated backup system, perfect for live/dev-scenarios
 - Extremely detailed permission system
 - Ships with a solid bunch of UI input widgets (input fields)
 - Several flexible authentication layers (e.g. changeable for administration, changeable per domain)
 - Secure password storage using up-to-date encryptions
 - Feature-rich inline editing (TinyMCE, Markdown, Drag'n'Drop', etc)
 - Awesome file manager

Screenshot
----------

![Administration Dashboard](https://raw.github.com/kryncms/KrynCmsBundle/master/Resources/doc/images/admin-dashboard.png)
![Administration Frontend Edit](https://raw.github.com/kryncms/KrynCmsBundle/master/Resources/doc/images/admin-frontend-edit.png)
![Administration File manager](https://raw.github.com/kryncms/KrynCmsBundle/master/Resources/doc/images/admin-files-context-image.png)
![Administration CRUD Framework Window List](https://raw.github.com/kryncms/KrynCmsBundle/master/Resources/doc/images/admin-list.png)
![Administration CRUD Framework Window](https://raw.github.com/kryncms/KrynCmsBundle/master/Resources/doc/images/admin-users.png)

[More Screenshots](https://github.com/kryncms/KrynCmsBundle/blob/master/Resources/doc/screenshots.markdown)

More information:
https://www.facebook.com/kryncms

PHPUnit
-------

- Preparing the database and models:

```bash
  Tests/Integration/skeletion/app/console kryncms:configuration --
  Tests/Integration/skeletion/app/console kryncms:models:build
  Tests/Integration/skeletion/app/console kryncms:schema:update
  Tests/Integration/skeletion/app/console kryncms:install:demo localhost /
```

- Fire `phpunit`.