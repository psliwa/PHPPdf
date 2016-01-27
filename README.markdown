Information
===========

[![Build Status](https://secure.travis-ci.org/psliwa/PHPPdf.png?branch=master)](http://travis-ci.org/psliwa/PHPPdf)

Examples
=========

Sample documents are in the "examples" directory. "index.php" file is the web interface to browse examples, "cli.php" is a console interface.
Via the web interface, the documents are available in pdf and jpeg format (the jpeg format requires Imagick).

Documentation
=============

Table of contents
-----------------

1. [Introduction](#intro)
1. [Installation](#installation)
1. [Symfony2 bundle](#symfony2-bundle)
1. [FAQ](#faq)
1. [Document parsing and creating pdf file](#parsing)
1. [Basic document structure](#structure)
1. [Inheritance](#inheritance)
1. [Stylesheet structure](#stylesheet)
1. [Palette of colors](#color-palette)
1. [Standard tags](#tags)
1. [Attributes](#attributes)
1. [Complex attributes](#complex-attributes)
1. [Units](#units)
1. [Barcodes](#barcodes)
1. [Charts](#charts)
1. [Hyperlinks](#hyperlinks)
1. [Bookmarks](#bookmarks)
1. [Sticky notes](#notes)
1. [Repetitive headers and footers](#headers)
1. [Watermarks](#watermarks)
1. [Page numbering](#page-numbering)
1. [Using the pdf document as a template](#templates)
1. [Separate page on columns](#columns)
1. [Breaking pages and columns](#page-break)
1. [Metadata](#metadata)
1. [Configuration](#configuration)
1. [Markdown support](#markdown)
1. [Image generation engine] (#image-generation)
1. [Known limitations](#limitations)
1. [TODO - plans](#todo)
1. [Technical requirements](#requirements)

<a name="intro"></a>
Introduction
------------

PHPPdf is library that transforms an XML document to a PDF document or graphics files.
The XML source document is similar to HTML, but there are lots of differences in names and properties of attributes, properties of tags, and there are a lot of not standard tags,
not all tags from html are supported, stylesheet is described in an xml document, not in css.

Assumption of this library is not HTML -> PDF / JPEG / PNG, but XML -> PDF / JPEG / PNG transformation.
Some tags and attributes are the same as in HTML in order decrease the learning curve of this library.

<a name="installation"></a>
Installation
------------

PHPPdf is available at packagist.org, so you can use composer to download this library and all dependencies.

*(add to require section in your composer.json file)*

```json
    "psliwa/php-pdf": "*"
```

You should choose last stable version (or wildcard of stable version), wildcard char ("*") is only an example.

If you want to use as features as barcodes or image generation, you should add extra dependencies:

```json

    "zendframework/zend-barcode": ">=2.0.0,<2.4",
    "zendframework/zend-validator": ">=2.0.0,<2.4",
    "imagine/Imagine": ">=0.2.0,<0.6.0"

```
    
<a name="symfony2-bundle"></a>
Symfony2 bundle
----------------

There is a [Symfony2 bundle][1] which integrates this library with the Symfony2 framework.

<a name="faq"></a>
FAQ
---

**Diacritical marks are not displayed, what should I do?**

You should set a font that supports the encoding that you are using, and set this encoding as "encoding" attribute for "page" and/or "dynamic-page" tags.
PHPPdf provides some free fonts that support utf-8 encoding, for example, DejaVuSans. The "Font" example shows how to change the font type by using a stylesheet.

You can also add custom fonts, in order that you should prepare xml config file and configure Facade object as shown below:

```xml
    <!-- xml config file code -->
    <fonts>
        <font name="DejaVuSans">
            <normal src="%resources%/fonts/DejaVuSans/normal.ttf" /><!-- "%resources%" will be replaced by path to PHPPdf/Resources directory -->
            <bold src="%resources%/fonts/DejaVuSans/bold.ttf" />
            <italic src="%resources%/fonts/DejaVuSans/oblique.ttf" />
            <bold-italic src="%resources%/fonts/DejaVuSans/bold+oblique.ttf" />
            <light src="%resources%/fonts/DejaVuSans/light.ttf" />
            <light-italic src="%resources%/fonts/DejaVuSans/light+oblique.ttf" />
        </font>
    </fonts>
```

```php
    //php code
    $loader = new PHPPdf\Core\Configuration\LoaderImpl();
    $loader->setFontFile(/* path to fonts configuration file */);
    $builder = PHPPdf\Core\FacadeBuilder::create($loader);
    $facade = $builder->build();
```

```xml    
    //xml document code
    <pdf>
        <dynamic-page encoding="UTF-8" font-type="DejaVuSans">
        </dynamic-page>
    </pdf>
```

You can find more datails in the [Configuration](#configuration) section.

**Generating of a simple pdf file with png images takes a lot of time and memory, what should I do?**

PHPPdf uses the Zend_Pdf library that poorly supports png files without compression. You should compress the png files. 

**How can I change the page size/orientation?**

To set the page dimensions you use the "page-size" attribute of the page or dynamic-page tags.

The value syntax of this attribute is "width:height".

There are however standard predefined values:
  * A format: from 4A0 to A10
  * B format: from B0 to B10
  * C format: from C0 to C10
  * US sizes: legal and letter

All formats are supported in portrait and lanscape.

Example:

```xml
    <page page-size="100:50">text</page>
    <page page-size="a4">text</page>
    <page page-size="letter-landscape">text</page>
```

<a name="parsing"></a>
Document parsing and creating a pdf file
----------------------------------------

The simplest way of using the library is:

```php
    //register the PHPPdf and vendor (Zend_Pdf and other dependencies) autoloaders
    require_once 'PHPPdf/Autoloader.php';
    PHPPdf\Autoloader::register();
    PHPPdf\Autoloader::register('/path/to/library/lib/vendor/Zend/library');
    
    //if you want to generate graphic files
    PHPPdf\Autoloader::register('sciezka/do/biblioteki/lib/vendor/Imagine/lib');

    $facade = new PHPPdf\Core\Facade(new PHPPdf\Core\Configuration\Loader());

    //$documentXml and $stylesheetXml are strings contains XML documents, $stylesheetXml is optional
    $content = $facade->render($documentXml, $stylesheetXml);

    header('Content-Type: application/pdf');
    echo $content;
```    

<a name="structure"></a>
Basic document structure
------------------------

The library bases pages on an XML format similar to HTML, but this format isn't HTML - some tags are diffrent, interpretation of some attributes is not the as same as in the HTML and CSS standards,
adding attributes is also different.

A simple document has following structure:

```xml
    <pdf>
        <dynamic-page>
            <h1>Header</h1>
            <p>paragraph</p>
            <div color="red">Layer</div>
            <table>
                <tr>
                    <td>Column</td>
                    <td>Column</td>
                </tr>
            </table>
        </dynamic-page>
    </pdf>
```

Adding a DOCTYPE declaration is strongly recommended in order to replace html entities on values:

```xml
    <!DOCTYPE pdf SYSTEM "%resources%/dtd/doctype.dtd">
```

The root name of a document must be "pdf".
The "dynamic-page" tag is an auto breakable page.
The "page" tag is an alternative, and represents only a single, no breakable page.

**The way of attribute setting is different than in HTML.**

In order to set a background and border you need to use complex attributes,
where first part of attribute name is a complex attribute type, and the second part is the property of this attribute.

Complex attribute parts are separated by a dot (".").

An another way of setting complex attributes is by using the "complex-attribute" tag.

Example:

```
    <pdf>
        <dynamic-page>
            <div color="red" border.color="black" background.color="pink">
                This text is red on pink backgroun into black border
            </div>
        </dynamic-page>
    </pdf>
```

Alternative syntax ("stylesheet" tag):

```xml
    <pdf>
        <dynamic-page>
            <div>
                <stylesheet>
                    <attribute color="red" />
                    <complex-attribute name="border" color="black" />
                    <complex-attribute name="background" color="pink" />
                </stylesheet>
                This text is red on pink backgroun into black border
            </div>
        </dynamic-page>
    </pdf>
```

Attributes can by set as XML attributes, directly after a tag name or by using the mentioned "stylesheet" tag.
The HTML "style" attribute does not exist the PHPPdf XML dialect.

The library is very strict in respecting the corectness of tags and attributes.
If an unexisted tag or attribute is detected, the document parser will stop and throw an exception.

<a name="inheritance"></a>
Inheritance
-----------

The "id" attribute has an different usage than in HTML.
The id attribute is used to identify tags when using inheritance.

The "name" attribute can also be used as an alias to "id".

An id must by unique throughout the document, otherwise a parsing error is thrown.

Example:

```xml
    <pdf>
        <dynamic-page>
            <div id="layer-1" color="red" font-type="judson" font-size="16px">
                <stylesheet>
                    <complex-attribute name="border" color="green" />
                </stylesheet>
                Layer 1
            </div>
            <div extends="layer-1">
                Layer 2 inherits style (type, simple and complex attributes) from layer 1)
            </div>
        </dynamic-page>
    </pdf>
```    

The Second layer inherits all attributes (simple and complex), and also those from external stylesheets.

Priorites in attributes setting:

1. Stylesheet tag directly in an element tag
2. Attributes directly after a tag name (XML attributes)
3. Attributes from external stylesheets
4. Inherited attributes from a parent tag

Example:

```xml
    <pdf>
        <page>
            <div id="1" color="#cccccc" height="100px" text-align="right">
            </div>
            <div extends="1" color="#aaaaaa" height="150px">
                <stylesheet>
                    <attribute name="height" value="200px" />
                </stylesheet>
            </div>
        </page>
    </pdf>
```

The second "div" will now have the following attributes:

- text-align: right
- color: #aaaaaa
- height: 200px

<a name="stylesheet"></a>
Stylesheet structure
--------------------

Stylesheets are defined in external files, stylesheet short and long declarations of attributes are supported.

Syntax of the stylesheet:

Short style:

```xml
    <stylesheet>
        <!-- style attributes are embeded as xml attributes, class attribute has the same meaning as in HTML/CSS -->
        <div class="class" font-size="12px" color="gray" background.color="yellow">
            <!-- nested element, equivalent CSS selector syntax: "div.class p" -->
            <p margin="10px 15px">
            </p>
        </div>

        <!-- equivalent CSS selector syntax: ".another-class", "any" tag is wildcard (mean any tag) -->
        <any class="another-class" text-align="right">
        </any>

        <h2 class="header">
            <span font-size="9px">
            </span>
            
            <div font-style="bold">
            </div>
        </h2>
    </stylesheet>
```

Long style:

```xml
    <stylesheet>
        <div class="class">
            <!-- simple and complex attributes are nested in "div.class" selector path -->
            <attribute name="font-size" value="12px" />
            <attribute name="color" value="grey" />
            <!-- equivalent of background.color attribute -->
            <complex-attribute name="background" color="yellow" />

            <!-- another nested element, equivalent CSS selector syntax: "div.class p" -->
            <p>
                <attribute name="margin" value="10px 15px" />
            </p>
        </div>

        <!-- equivalent CSS selector syntax: ".another-class", "any" tag is wildcard (mean any tag) -->
        <any class="another-class">
            <attribute name="text-align" value="right" />
        </any>

        <h2 class="header">
            <span>
                <attribute name="font-size" value="9px" />
            </span>
            <div>
                <attribute name="font-style" value="bold" />
            </div>
        </h2>
    </stylesheet>
```

<a name="color-palette"></a>
Palette of colors
--------------

PHPPdf supports color palettes, - mapping of logical names to real colors.

Color palettes gives you the opportunity to create or overwrite default named colors.
By default, PHPPdf supports named colors from the W3C standard (for example "black" = "#000000").

You can use a palette for the DRY principle, because information about used colors will be kept in one place.
And you can also generate one document with different palettes.

Example:

```xml
    <!-- colors.xml file -->
    <colors>
        <color name="header-color" hex="#333333" />
        <color name="line-color" hex="#eeeeee" />
    </colors>
    
    <!-- stylesheet.xml file -->
    <h2 color="header-color" />
    <hr background-color="line-color" />
    <table>
        <td border-color="line-color" />
    </table>
    
    <!-- document.xml file -->
    <pdf>
        <page>
            <h2>Header</h2>
            <hr />
            <table>
                <tr>
                    <td>Data</td>
                    <td>Data</td>
                </tr>
            </table>
        </page>
    </pdf>
```
    
```php
    //php code
    use PHPPdf\DataSource\DataSource;
    
    $facade = ...;

    $content = $facade->render(
        DataSource::fromFile(__DIR__.'/document.xml'),
        DataSource::fromFile(__DIR__.'/stylesheet.xml'),
        DataSource::fromFile(__DIR__.'/colors.xml')
    );
```

<a name="tags"></a>
Standard tags
-------------

The library supports primary HTML tags: ```div, p, table, tr, td, b, strong, span, a, h1, h2, h3, h4, h5, img, br, ul, li```

In addition there are also not standard tags:

* dynamic-page - auto breakable page
* page - single page with fixed size
* elastic-page - single page that accommodates its height to its children as same as another tags (for example "div"). Header, footer, watermark, template-document attribute do not work with this tag. Useful especially in graphic files generation (image engine).
* page-break, column-break, break - breaks page or column, this tag must be direct child of "dynamic-page" or "column-layout"!
* column-layout - separate workspace on columns, additional attributes: number-of-columns, margin-between-columns, equals-columns
* barcode - more information in <a href="#barcodes">barcode</a> chapter
* circle - element that border and backgroud are in circle shape. Additional attributes: radius (it overwrites width and height attributes)
* pie-chart - element that can be used to draw simple pie chart (more information in <a href="#charts">charts</a> chapter.

There are tags that are only bags for attributes, a set of tags etc:

* stylesheet - stylesheet for parent
* attribute - simple attribute declaration, direct child of "stylesheet" tag. Required attributes of this element: name - attribute name, value - attribute value
* complex-attribute - complex attribute declaration, direct child of "stylesheet" tag. Required attributes of this element: name - complex attribute name
* placeholders - defines placeholders for parent tag. Children tags of placeholder are specyfic for every parent tag. **It should be first tag in parent**
* metadata - defines metadata of pdf document, direct child of document root
* behaviours - defines behaviours for a parent tag. Supported behaviours: href, ref, bookmark, note (action as same as for attributes with as same as name)

<a name="attributes"></a>
Attributes
----------

* width and height: rigidly sets height and width, supported units are described in separate [section](#units). Relative values in percent are supported. 
* margin (margin-top, margin-bottom, margin-left, margin-right): margin similar to margin from HTML/CSS. Margins of simblings are pooled. For side margins possible is "auto" value, it works similar as in HTML/CSS.
* padding (padding-top, padding-bottom, padding-left, padding-right): works similiar as in HTML/CSS
* font-type - font name must occurs in fonts.xml config file, otherwise exception will be thrown
* font-size - file size in points, there are no any unit
* font-style - allowed values: normal, bold, italic, bold-italic, light, light-italic
* color - text color. HTML/CSS style values are supported
* breakable - if true, element is able to be broken in several pages. Default value for most tags is true..
* float - works similar but not the same as in HTML/CSS. Allowed values: left|none|right, default none
* line-height - works similar as in HTML/CSS. Default value: 1.2*font-size
* text-align - works as same as in HTML/CSS. Allowed values: left|center|right|justify, default left.
* text-decoration - allowed values: none, underline, overline, line-through
* break - breaks page or column in the end of the owner of this attribute. Owner of this attribute must by directly child of dynamic-page or column-layout tag!
* colspan, rowspan - works similar as in HTML (TODO: rowspan isn't implemented yet)
* href - external url where element should linking
* ref - id of element where owner should linking
* bookmark - create bookmark with given title associated with the tag
* note - create sticky note associated with tag
* dump - allowed values: true or false. Create sticky note with debug informations (attributes, position etc.)
* rotate - angle of element rotation. This attribute isn't fully implemented, works corectly for watermarks (see "Watermarks" section). Possible values: XXdeg (in degrees), XX (in radians), diagonally, -diagonally.
* alpha - possible values: from 0 to 1. Transparency for element and his children.
* line-break - line break (true or false), by default set on true only for "br" tag
* style - as same as in html, this attribute can be used to set another attributes, for example: style="width: 100px; height: 200px; margin: 20px 0;". Every attribute must be finished by ";" char, even the last.
* ignore-error (only for img tag) - ignore file loading error or throw exception? False by default, that means exception will be thrown.
* keep-ratio (only for img tag) - keeps ratio by cutting fragment of image even if ratio of setted dimension is not the same as ratio of original source image. False by default.
* position - the same as in html. Allowed values: static (default), relative, absolute
* left and top - the same as in html, works with position relative or absolute. Right and bottom is not supported. 

<a name="complex-attributes"></a>
Complex attributes
------------------

Complex attributes can be set by notation "attributeName.attributeProperty" or "attributeName-attributeProperty"

For example: ```border.color="black"``` or ```border-color="black"```

* border:
    - color: border color
    - style: posible values: solid (solid line), dotted (predefined dotted line) or any definition in the form of integers separated by space
    - type: which edges will be shown - default "top+bottom+left+right" (all edges). "none" value is possible (it disable border)
    - size: border size
    - radius: corner rounding in units of length (attention: if this parameter is set, type paramete will be ignored, rounded border always will be full - this will be fixed in future)
    - position: border translation relative to original position. Positive values extends border, negative values decrases border. Owing to manipulation of this parameter, you can obtain complex pattern as border if you add another borders with different styles and positions. 

* background:
    - color: background color
    - image: background image
    - repeat: way of image repeating (none|x|y|all)
    - radius: rounding background corners in units of length (for now only works with color background)
    - use-real-dimension: attribute only used by page (or dynamic-page). True for filling also margins, false in otherwise.
    - image-width: custom width of background image, percentage values are allowed
    - image-height: custom height of background image, percentage values are allowed
    - position-x: horizontal position for image of background, allowed values: left, center, right or numeric value (default: left)
    - position-y: vertical position for image of background, allowed values: top, center, bottom or numeric value (default: top)

It is possible to add several complex attributes in the same type (for instance 3 different borders).

You can achieve that by using the "stylesheet" tag instead of the short notation.

```xml
    <pdf>
        <dynamic-page>
            <div>
                <stylesheet>
                    <!-- Top and bootom edges are red, side edges are yellow-gray --> 
                    <complex-attribute name="border" color="red" type="top+bottom" />
                    <complex-attribute id="borderLeftAndRight" name="border" color="yellow" type="left+right" size="4px" />
                    <complex-attribute id="outerBorderLeftAndRight" name="border" color="gray" type="left+right" size="2px" position="1px" />
                </stylesheet>
            </div>
        </dynamic-page>
    </pdf>
```

In this example, the second border has a "borderLeftAndRight" indentifie, if this border had no id, the attributes from second border would be merged with the attributes from first border.

Remeber the default identifier "id" is as same as the "name" attribute.
The "id" attributes for complex attributes has nothing to do with the "id" attribute of tags (used in inheritance).

It is possible to create complex borders the same as in the previous example (outerBorderLeftAndRight).

<a name="units"></a>
Units
-----

Supported units for numerical attributes:

* in (inches)
* cm (centimeters)
* mm (milimeters)
* pt (points)
* pc (pica)*
* px (pixels)
* * % (percent - only for width and height).

Currently unsupported units are: em and ex

When the unit is missing (for example: font-size="10"), then the default unit is points (pt). 1pt = 1/72 inch

<a name="barcodes"></a>
Barcodes
--------

Barcodes are supported by the ```<barcode>``` tag.

PHPPdf uses the Zend\Barcode library in order to generate barcodes.

Example:

```xml
    <pdf>
        <dynamic-page>
            <barcode type="code128" code="PHPPdf" />
        </dynamic-page>
    </pdf>
```    

```<barcode>``` tag supports the most of standard attributes and has some other attributes:

* type - typ of barcode, supported values: code128, code25, code25interleaved, code39, ean13, ean2, ean5, ean8, identcode, itf14, leitcode, planet, postnet, royalmail, upca, upce
* draw-code - equivalent of drawCode option from Zend\Barcode
* bar-height - equivalent of barHeight option from Zend\Barcode
* with-checksum - equivalent of withChecksum option from Zend\Barcode
* with-checksum-in-text - equivalent of withChecksumInText option from Zend\Barcode
* bar-thin-width - equivalent of barThinWidth option from Zend\Barcode
* bar-thick-width - equivalent of barThickWidth option from Zend\Barcode
* rotate - equivalent of orientation option from Zend\Barcode

You can find the description of these options and there default values in the [Zend\Barcode documentation][3].

In order to render textual barcodes, you can't use to following embeded pdf fonts: courier, times-roman and helvetica.
This will soon be fixed.

<a name="charts"></a>
Charts
------

PHPPdf supports drawing simple charts.

For now there is only support for s simple pie chart.

Example:

```xml
    <pdf>
        <dynamic-page>
            <pie-chart radius="200px" chart-values="10|20|30|40" chart-colors="black|red|green|blue"></pie-chart>
        </dynamic-page>
    </pdf>
```
    
The ```<pie-chart>``` tag has three extra attributes:

* radius - radius of the chart
* chart-values - values of the chart, together they must be summing to 100. Each value must be separated by "|".
* chart-colors - colors of each value. Each color must be separated by "|".

<a name="hyperlinks"></a>
Hyperlinks
----------

The library supports external and internal hyperlinks.

External hyperlinks link to url's, while internal links link to other tags inside the pdf document.

Example:

```xml
    <pdf>
        <dynamic-page>
            <a href="http://google.com">go to google.com</a>
            <br />
            <a ref="some-id">go to another tag</a>
            <a href="#some-id">go to another tag</a> <!-- anchor style ref -->
            <page-break />
            <p id="some-id">Yep, this is another tag! ;)</p>
        </dynamic-page>
    </pdf>
```

Every element has a "href" and "ref" attribute, even div.
You can't nest elements inside an "a" tag.

If you want to use img elements as a link, you should use the href (external link) or ref (internal link) attribute directly in img tag.

<a name="bookmarks"></a>
Bookmarks
---------

The preferred way of creating bookmarks is by using the "behaviours" tag.

This doesn't restrict the structure of the document, the owner of a parent bookmark doesn't have to be a parent of a child's bookmark owner.

Example:

```xml
    <pdf>
	    <dynamic-page>
		    <div>
		        <behaviours>
		            <bookmark id="1">parent bookmark</bookmark>
		        </behaviours>
		        Some content
		    </div>
		    <div>
		        <behaviours>
		            <bookmark parentId="1">children bookmark</bookmark>
		        </behaviours>
		        Some another content
		    </div>
		    <div>
		        <behaviours>
		            <bookmark parentId="1">another children bookmark</bookmark>
		        </behaviours>
		        Some another content
		    </div>
		    <div>
		        <behaviours>
		            <bookmark>another parent bookmark</bookmark>
		        </behaviours>
		       Some content
		    </div>
		</dynamic-page>
    </pdf>
```

A shortcut for the "bookmark" behaviour is the "bookmark" attribute,
if you assign some value to this attribute, bookmarks that refers to this tag will be automatically created.

The bookmark of a parent tag is also the parent of a children's bookmarks.

Example:

```xml
    <pdf>
	    <dynamic-page>
		    <div bookmark="parent bookmark">
		        Some content
		        <div bookmark="children bookmark">
		            Some another content
		        </div>
		        <div bookmark="another children bookmark">
		            Some another content
		        </div>
		    </div>
		    <div bookmark="another parent bookmark">
		       Some content
		    </div>
		</dynamic-page>
    </pdf>
```

The above structures (both examples) will create this bookmarks structure:

* parent bookmark
    - children bookmark
    - another children bookmark
* another parent bookmark

<a name="notes"></a>
Sticky notes
------------

Sticky notes can be created by using the "note" attribute.

Example:

```xml
    <pdf>
        <dynamic-page>
            <div note="note text"></div>
        </dynamic-page>
    </pdf>
```
    
The XML parser normalizes values of attributes, wich results ignoring new line characters.

If you want to add a note with new line characters, you should use this syntax:

```xml
    <pdf>
        <dynamic-page>
            <div>
                <behaviours>
                    <note>note text</note>
                </behaviours>
            </div>
        </dynamic-page>
    </pdf>
```


<a name="headers"></a>
Repetitive headers and footers
------------------------------

"placeholders" can be used in for adding a repetitive header or/and footer.

Some elements have special "placeholders": page has header and footer,
table also has header and footer (TODO: not implemented yet) etc.

```xml
    <pdf>
        <dynamic-page>
            <placeholders>
                <header>
                    <div height="50px" width="100%">
                        Header
                    </div>
                </header>
                <footer>
                    <div height="50px" width="100%">
                        Footer
                    </div>
                </footer>
            </placeholders>
        </dynamic-page>
    </pdf>
```

Header and footer need to have a height attribute set.
This height is pooled with page top and bottom margins.

Workspace is the page size reduced by page margins and placeholders (footer and header) height.

<a name="watermarks"></a>
Watermarks
----------

Page can have a "watermark" placeholder.

The watermark may be set on block's and container elements, for instance: div, p, h1 (no span, plain text or img).

If you want to use an image as a watermark, you should wrap the img tag in a div.

Example:

```xml
    <pdf>
        <dynamic-page>
            <placeholders>
                <watermark>
                    <!-- rotate can have absolute values (45deg - in degrees, 0.123 - in radians) or relative values ("diagonally" and "-diagonally" - angle between diagonal and base side of the page) -->
                    <div rotate="diagonally" alpha="0.1">
                        <img src="path/to/image.png" />
                    </div>
                </watermark>
            </placeholders>
        </dynamic-page>
    </pdf>
```

<a name="page-numbering"></a>
Page numbering
--------------

There are two tags that can be used to show page information in a footer, header or watermark: page-info and page-number.

This element only works with dynamic-page, not single pages.
Page-info shows the current and total page number, page-number shows only the current page number.

Attributes of this tags:

* format - format of output string that will be used as argument of sprintf function. Default values: "%s." for page-number, "%s / %s" for page-info.
* offset - value that will be added to current page number and total page number. Usefull if you want to count pages from a diffrent value than zero. Default: 0.

Example:

```xml
    <pdf>
        <dynamic-page>
            <placeholders>
                <header>
                    <div height="20px">
                        <page-info format="page %s for %s" />

                        <!-- when we would like to number from 2 -->
                        <page-info offset="1" format="page %s for %s" />

                        <!-- when we would like to display only current page number -->
                        <page-info format="%1$s." />
                        <!-- or -->
                        <page-number />

                        <!-- when we would like to display only total pages number -->
                        <page-info format="%2$s pages" />
                    </div>
                </header>
            </placeholders>
            Some text
        </dynamic-page>
    </pdf>
```

<a name="templates"></a>
Using a pdf document as a template
----------------------------------

The "page" and "dynamic-page" tags can have a "document-template" attribute,
that allows you to use an external pdf document as a template.

For the "page" tag, the page's template will be the first page of an external document.

For the "dynamic-page" tag, the template for each page will be the corresponding page of an external document.

Example:

```xml
    <pdf>
        <dynamic-page document-template="path/to/file.pdf">
            <div>Some content</div>
        </dynamic-page>
    </pdf>
```

<a name="columns"></a>
Separate page on columns
-------------------------

Page can be separated on columns:

```xml
    <pdf>
        <dynamic-page>
            <column-layout>
                <div width="100%" height="2500px" background.color="green">
                </div>
            </column-layout>
        </dynamic-page>
    </pdf>
```

The above XML describes several pages of the pdf document, with green rectangles separated on two columns.

The "column-layout" tag has three additional parameters: number-of-columns, margin-between-columns and equals-columns.

Default values for this attributes are 2, 10 and false respectlivy.
If the equals-columns attribute is set, columns will have more or less equals height.

<a name="page-break"></a>
Breaking pages and columns
--------------------------

Page and column may by manually broken using one of these tags: page-break, column-break, break.

All these tags are the same. These tags need to be direct children of the breaking element (dynamic-page or column-layout).

If you want to avoid automatic page or column break on certain tags, you should set the "breakable" attribute of this tag to "off.

Example:

```xml
    <pdf>
        <dynamic-page>
            <div breakable="false">this div won't be automatically broken</div>
        </dynamic-page>
    </pdf>
```

<a name="metadata"></a>
Metadata
--------

Metadata can be added by attributes at the document's root.

Supported metadata is: Creator, Keywords, Subject, Author, Title, ModDate, CreationDate and Trapped.

** These attribute names are case sensitive. **

Example:

```xml
    <pdf Author="Piotr Sliwa" Title="Test document">
        <!-- some other elements -->
    </pdf>
```

<a name="configuration"></a>
Configuration
-------------

The library has four primary config files that allow you to adopt the library for specyfic needs and extending.

* complex-attributes.xml - declarations of complex attributes classes to logical names that identify attribute in whole library.
* nodes.xml - definitions of allowed tags in xml document with default attributes and formatting objects.
* fonts.xml - definitions of fonts and assigning them to logical names that identify font in whole library.
* colors.xml - palette of colors definitions

In order to change default the config files, you must pass to Facade constructor configured Loader object:

```php
    $loader = new PHPPdf\Core\Configuration\LoaderImpl(
        '/path/to/file/nodes.xml',
        '/path/to/file/enhancements.xml',
        '/path/to/file/fonts.xml',
        '/path/to/file/colors.xml'
    );
    
    $facade = new PHPPdf\Core\Facade($loader);
```
    
If you want to change only one config file, you should use LoaderImpl::set* method:

```php
    $loader = new PHPPdf\Core\Configuration\LoaderImpl();
    $loader->setFontFile('/path/to/file/fonts.xml');//there are setFontFile, setNodeFile, setComplexAttributeFile and setColorFile methods
    $facade = new PHPPdf\Core\Facade($loader);
```

FacadeBuilder can be uset to build and configure Facade. FacadeBuilder is able to configure cache, rendering engine and document parser. 
    
```php
    $builder = PHPPdf\Core\FacadeBuilder::create(/* you can pass specyfic configuration loader object */)
                                        ->setCache('File', array('cache_dir' => './cache'))
                                        ->setUseCacheForStylesheetConstraint(true); //stylesheets will be also use cache

    $facade = $builder->build();
```

<a name="markdown"></a>
Markdown support
----------------

Library supports basic (official) markdown syntax. To convert markdown document to pdf, you should configure Facade object by MarkdownDocumentParser. You also might to use FacadeBuilder to do this for you.

Example:

```php
    $facade = PHPPdf\Core\FacadeBuilder::create()
                                       ->setDocumentParserType(PHPPdf\Core\FacadeBuilder::PARSER_MARKDOWN)
                                       ->setMarkdownStylesheetFilepath(/** optionaly path to stylesheet in xml format */)
                                       ->build();
```

By default, in markdown pdf document, helvetica font is used.
If you want to use utf-8 characters or customize a pdf document, you should provide your own stylesheet by using the FacadeBuilder::setMarkdownStylesheetFilepath method.

The stylesheet structure has been described in the [stylesheet](#stylesheet) chapter.
By default the stylesheet is empty, if you want to set another font type, the stylesheet should look like this:

```xml
    <stylesheet>
        <any font-type="DejaVuSans" />
    </stylesheet>
```

Internally the MarkdownDocumentParser converts a markdown document to html (via the [PHP markdown](https://github.com/wolfie/php-markdown) library), then converts html to xml, and at last xml to a pdf document.

Be aware of that, if you in a markdown document use raw html that will be incompatible with the xml syntax of PHPPdf (for example unexistend attributes or tags), the parser will throw an exception then.

	Not all tags used in the markdown implementation are propertly supported by PHPPdf, for example "pre" and "code" tags.
	For now "pre" tag is an alias for "div", and "code" tag is an alias for "span", be aware of that.

<a name="image-generation"></a>
Image generation engine
-----------------------

PHPPdf is able to generate image (jpg or png) files insted of a pdf document.
To achieve that, you must configure the FacadeBuilder, example:

```php
    $facade = PHPPdf\Core\FacadeBuilder::create()
                                       ->setEngineType('image')
                                       ->build();

    //render method returns array of images' sources, one pdf page is generated to single image file 
    $images = $facade->render(...);
```
    
By default the GD library is used to render an image.

But you can also use Imagick, which offers a better quality, so it is recommended that if you have the opportiunity to install Imagick on your server.
To switch the graphic library, you must configure the FacadeBuilder object using the setEngineOptions method:

```php
    $builder = ...;
    $builder->setEngineOptions(array(
        'engine' => 'imagick',
        'format' => 'png',//png, jpeg or wbmp
        'quality' => 60,//int from 0 to 100
    ));
```
    
Supported graphic libraries are: GD (default), imagick, gmagick. PHPPdf uses the [Imagine][2] library as an interface for graphics file generation.


<a name="limitations"></a>
Known limitations
----------------

Below is a list of known limitations of the current version of the library:

* there no way to inject an image into a text with floating - will be introduced in the next releases
* partial support for float attribute within table element (floats might work improperly within a table)
* vertical-align attribute works improperly if in element with this attribute set, are more than one element
* border doesn't change dimensions of the element (while in HTML they do)
* png files (expecially without compression) are inefficient. png files with high compression (compression level 6 or higher) or jpeg should be used instead
* not all tags are propertly supported, for example the "pre" tag is an alias to "div" and the "code" tag is an alias for "span"
* nesting of linear tags (text, span, code, page-info, page-number, a, b, i, em) is not properly supported. If one linear tag contains another, only text within this tags is merged, styles are taken from the most outher linear tag.

<a name="todo"></a>
TODO - plans
----------------

* automatic generating of "table of contents"
* improve table, header and footer for table, rowspan. Fix calculation of cell's min height when colspan is used.
* support for simple bar and pie charts

<a name="requirements"></a>
Technical requirements
----------------

This library works with php 5.3 and up.

[1]: https://github.com/psliwa/PdfBundle
[2]: https://github.com/avalanche123/Imagine
[3]: http://framework.zend.com/manual/en/zend.barcode.objects.html
