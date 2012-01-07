CHANGELOG
=========

* 1.1.1 (2012-01-07)

  * accommodate PHPPdf\Cache\CacheImpl class to new api of Zend\Cache component

* 1.1.0 (2012-01-01)

  * image generation engine
  * improve border and background roundings
  * support for palette of colors
  * refactoring exception hierarchy classes
  * numeric value support for background-position-x and background-position-y
  * ignore-error attribute for img tag
  * keep-ratio attribute for img tag
  * elastic-page tag
  * improve width and height attributes for page and dynamic-page tags
  * Facade::render method accepts array as second argument - support for number of stylesheets
  * remove dependency to Symfony components, remove Symfony DI configuration loader

* 1.0.3 (2011-11-27)

  * support for remote jpeg files
  * [#3] fixed padding-bottom for element that has children with float attribute set on left or right
  * new attribute "offset" for page-info and page-number tags

* 1.0.2 (2011-11-13)

  * change default Zend_Pdf dependant repository

* 1.0.1 (2011-11-11)

  * update Zend Framework components version to Zend Framework 2
  * remove Zend Framework sources from repository, ZF2 is now external dependency, use "vendors.php" file to download dependencies.

* 1.0.0 (2011-10-21)