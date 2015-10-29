Canterville
===========
Canterville is library for work with web pages without browser. Browser here is PhantomJS controlled using CasperJS.
Both libraries is auto installed.

Installation
------------
Over Composer. Include to your composer.json file this code:
```json
  "required": {
    "lawondyss/canterville": "0.2"
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
