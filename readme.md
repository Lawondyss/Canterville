Canterville
===========
Canterville is library for work with web pages without browser. Browser here is [PhantomJS] or [SlimerJS] controlled using [CasperJS].
All libraries is auto installed.

**WARNING:**
SlimerJS required Firefox.

Installation
------------
Over Composer, `required lawondyss/canterville`.

For installation PhantomJS, SlimmerJS and/or CasperJS include to your composer.json file code to section `scripts`.

Example installation CasperJS with PhantomJS:
```json
  "scripts": {
    "post-install-cmd": [
      "Canterville\\Installer::casperOnPhantom"
    ],
    "post-update-cmd": [
      "Canterville\\Installer::casperOnPhantom"
    ]
  }
```

### Options
 - Installer::casperOnPhantom
 - Installer::casperOnSlimer
 - Installer::casper
 - Installer::phantom
 - Installer::slimer



[PhantomJS]: http://phantomjs.org/
[SlimerJS]: http://slimerjs.org/
[CasperJS]: http://casperjs.org/
