MOC.NotFound
=============

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mocdk/MOC.NotFound/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mocdk/MOC.NotFound/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/moc/notfound/v/stable)](https://packagist.org/packages/moc/notfound)
[![Total Downloads](https://poser.pugx.org/moc/notfound/downloads)](https://packagist.org/packages/moc/notfound)
[![License](https://poser.pugx.org/moc/notfound/license)](https://packagist.org/packages/moc/notfound)

Introduction
------------

Neos CMS package that loads a normal editable page for displaying a 404 error.

Compatible with Neos 3.*.

Supports multiple content dimensions with URI segments and empty segments for default dimensions.

Installation
------------
```composer require "moc/notfound:^3.0"```

Create a page with the URI segment "404" in the root of your site. If using content dimensions with URI segments,
ensure a page exists in all contexts or through fallbacks.

Alternatively set the following configuration in ``Settings.yaml``:

```yaml
Neos:
  Flow:
    error:
      exceptionHandler:
        renderingGroups:
          notFoundExceptions:
            options:
              variables:
                path: '404.html' # skip suffix if unset
```
