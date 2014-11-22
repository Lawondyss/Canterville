Canterville
===========
Canterville is library for work with web pages without browser. Browser here is PhantomJS controlled using CasperJS.
Both libraries is auto installed.

Installation
------------
Over Composer `require lawondyss/canterville:dev-master`.

After installation include to your composer.json file this code:
```json
  "config": {
    "bin-dir": "bin"
  },
  "scripts": {
    "post-install-cmd": [
      "Canterville\\Installer::install"
    ],
    "post-update-cmd": [
      "Canterville\\Installer::install"
    ]
  }
```