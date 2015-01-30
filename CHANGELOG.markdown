CHANGELOG
=========

* 1.2.8 (???)

  * (nothing)

* 1.2.7 (2015-01-30)

  * [#87] add "blockquote" tag

* 1.2.6 (2014-12-14)

  * [#83] remove Imagine\Image\Box, fix image drawing in Imagine engine

* 1.2.5 (2014-10-23)

  * [#80] fix memory leak when multiple documents was generated

* 1.2.4 (2014-09-28)

  * [#52] fix document-template support
  * [#64] fix dependencies versions in composer.json
  * [#77] fix placeholders support when document template is used

* 1.2.3 (2014-03-15)

  * [#37] break words longer than ancestor width
  * [#41] fix placeholders support when document-template is used
  * [#44] fix text-decoration attribute when text is justified

* 1.2.2 (2013-10-27)

  * fix page width when paragraph is direct child
  * fix handling errors in xml parsers: exception is thrown when file not exist or is unreadable
  
* 1.2.1 (2013-08-29)
  
  * [#26] fix utf-8 support for bookmarks
  * fix bugs in pie chart
  * [#10] fix support for some utf-8 chars

* 1.2.0 (2013-01-02)

  * fix bugs in pie-chart tag
  * fix set page width and height directly
  * fix converting percentage dimensions (width and height) of element, that parent has paddings set
  * add position attribute
  * rename "position" to "list-position" attribute of "ul" and "ol" tags - break backward compatibility!
  * composer.json: move zend-barcode, zend-validator and Imagine to "suggest" section

* 1.1.6 (2012-10-28)

  * "circle" tag support
  * simple pie chart support
  * fix stylesheet selectors
  * fix minor bugs

* 1.1.5 (2012-09-23)

  * [#15] fix nesting of linear tags
  * fix composer.json file

* 1.1.4 (2012-08-05)

  * fix compatibility with ZF2rc2

* 1.1.3 (2012-07-11)

  * fix compatibility with ZF2beta5 api
  * [#8] fixed specifing of width of table columns when one of cell has colspan
  * parameterized dpi value
  * padding-top on li element has influence on position of enumeration symbol
  * barcode support

* 1.1.2 (2012-02-01)

  * improve xml parsing error messages
  * [#7] fixed leaking pdf output file size - it was large

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