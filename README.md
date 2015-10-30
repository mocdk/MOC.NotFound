MOC.NotFound
=============

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mocdk/MOC.NotFound/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mocdk/MOC.NotFound/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/moc/notfound/v/stable)](https://packagist.org/packages/moc/notfound)
[![Total Downloads](https://poser.pugx.org/moc/notfound/downloads)](https://packagist.org/packages/moc/notfound)
[![License](https://poser.pugx.org/moc/notfound/license)](https://packagist.org/packages/moc/notfound)

Introduction
------------

Neos CMS package that loads a normal editable page for displaying a 404 error

Compatible with Neos 1.x-2.x+

**!!! Not compatible with language dimensions**

Installation
------------
```composer require "moc/notfound:~1.0"```

Create a page with the URI segment "404" in the root of your site.

Alternatively set the following configuration in ``Settings.yaml``:

```yaml
  TYPO3:
    Flow:
      error:
        exceptionHandler:
          renderingGroups:
            notFoundExceptions:
              options:
                variables:
                  path: '404'
```
