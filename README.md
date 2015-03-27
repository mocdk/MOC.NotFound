MOC.NotFound
=============

TYPO3 Neos package that loads a Neos page for displaying a 404 error

Works with TYPO3 Neos 1.0-2.0+

> !!! Not compatible with language dimensions.

Installation
------------
```composer require "moc/notfound" "1.0.*"```

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
