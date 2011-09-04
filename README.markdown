Documentation
=============

Table of contents
-----------------

1. [Installation](#installation)
1. [Symfony2 bundle](#symfony2-bundle)
1. [Document parsing and createing pdf file](#parsing)
1. [Basic document structure](#structure)
1. [Inheritance](#inheritance)
1. [Stylesheet structure](#stylesheet)
1. [Standard tags](#tags)
1. [Attributes](#attributes)
1. [Complex attributes](#complex-attributes)
1. [Hyperlinks](#hyperlinks)
1. [Bookmarks](#bookmarks)
1. [Sticky notes](#notes)
1. [Repetitive headers and footers](#headers)
1. [Watermarks](#watermarks)
1. [Using pdf document as template](#templates)
1. [Separate page on columns](#columns)
1. [Breaking pages and columns](#page-break)
1. [Metadata](#metadata)
1. [Configuration](#configuration)
1. [Known limitations](#limitations)
1. [TODO - plans](#todo)
1. [Technical requirements](#requirements)

<a name="installation"></a>
Installation
----------------

Library optionally uses external libraries (DependencyInjection from Symfony2) that be able to download by command:

    php vendors.php
    
<a name="symfony2-bundle"></a>
Symfony2 bundle
----------------

There is [Symfony2 bundle][1] integrates this library with Symfony2 framework.

<a name="parsing"></a>
Document parsing and creating pdf file
----------------

The simplest way of library using:

    //register PHPPdf and vendor (Zend_Pdf) autoloaders
    require_once 'PHPPdf/Autoloader.php';
    PHPPdf\Autoloader::register();
    PHPPdf\Autoloader::register('/path/to/library/lib/vendor');

    $facade = new PHPPdf\Parser\Facade(new PHPPdf\Configuration\Loader());

    //$documentXml and $stylesheetXml are strings contains XML documents, $stylesheetXml is optional
    $content = $facade->render($documentXml, $stylesheetXml);

    header('Content-Type: application/pdf');
    echo $content;

<a name="structure"></a>
Basic document structure
----------------

Library bases on XML format similar to HTML but this format isn't HTML - some tags are diffrent, interpretation of some attributes is not as same as in HTML and CSS standards, way of attributes adding is also diffrent. The simplest document has following structure:

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

Adding DOCTYPE declaration is strongly recommended in order to replace html entities on values:

    <!DOCTYPE pdf SYSTEM "%resources%/dtd/doctype.dtd">

Root of document has to be "pdf". "dynamic-page" tag is auto breakable page. "page" tag is an alternative, represents only single, no breakable page. Way of attribute setting is different than in HTML. In order to set background and border you have to use complex attributes, where first part of attribute name is complex attribute type, second part is property of this attribute. Complex attribute parts are separate by dot ("."). Other way to setting complex attributes is using "enhancement" tag. Example:

    <pdf>
        <dynamic-page>
            <div color="red" border.color="black" background.color="pink">
                This text is red on pink backgroun into black border
            </div>
        </dynamic-page>
    </pdf>
    
Alternative syntax ("stylesheet" tag):

    <pdf>
        <dynamic-page>
            <div>
                <stylesheet>
                    <attribute color="red" />
                    <enhancement name="border" color="black" />
                    <enhancement name="background" color="pink" />
                </stylesheet>
                This text is red on pink backgroun into black border
            </div>
        </dynamic-page>
    </pdf>

Attributes can by setted as XML attributes directly after tag name or by means of mentioned "stylesheet" tag. HTML "style" attribute dosn't exist.

Library is very strict in respect of corectness of tags and attributes. If unexisted tag or attribute is used, document won't parse - suitable exception will be thrown.

<a name="inheritance"></a>
Inheritance
----------------

"id" attribute has entirely different mean than in HTML. Id must by unique in whole document, otherwise parsing error occurs. Id attribute is used to identify tags in inheritance. Example:

    <pdf>
        <dynamic-page>
            <div id="layer-1" color="red" font-type="judson" font-size="16">
                <stylesheet>
                    <enhancement name="border" color="green" />
                </stylesheet>
                Layer 1
            </div>
            <div extends="layer-1">
                Layer 2 inherits style (type, simple and complex attributes) from layer 1)
            </div>
        </dynamic-page>
    </pdf>

Second layer inherits all attributes (simple and complex), also these from external stylesheet.

Priorites in attributes setting:

1. Stylesheet tag directly in element tag
2. Attributes directly after tag name (XML attributes)
3. Attributes from external stylesheet
4. Inherited attributes from another tag

Example:

    <pdf>
        <page>
            <div id="1" color="#cccccc" height="100" text-align="right">
            </div>
            <div extends="1" color="#aaaaaa" height="150">
                <stylesheet>
                    <attribute name="height" value="200" />
                </stylesheet>
            </div>
        </page>
    </pdf>

Second "div" will have following attributes:
- text-align: right
- color: #aaaaaa
- height: 200

<a name="stylesheet"></a>
Stylesheet structure
----------------

Stylesheets have to be in external file, stylesheet short and long declarations of attributes are supported. Syntax of stylesheet:

Short style:

    <stylesheet>
        <!-- style attributes are embeded as xml attributes, class attribute has the same meaning as in HTML/CSS -->
        <div class="class" font-size="12" color="gray" background.color="yellow">
            <!-- nested element, equivalent CSS selector syntax: "div.class p" -->
            <p margin="10 15">
            </p>
        </div>

        <!-- equivalent CSS selector syntax: ".another-class", "any" tag is wildcard (mean any tag) -->
        <any class="another-class" text-align="right">
        </any>

        <h2 class="header">
            <span font-size="9">
            </span>
            
            <div font-style="bold">
            </div>
        </h2>
    </stylesheet>

Long style:

    <stylesheet>
        <div class="class">
            <!-- simple and complex (enhancements) attributes are nested in "div.class" selector path -->
            <attribute name="font-size" value="12" />
            <attribute name="color" value="grey" />
            <!-- equivalent of background.color attribute -->
            <enhancement name="background" color="yellow" />

            <!-- another nested element, equivalent CSS selector syntax: "div.class p" -->
            <p>
                <attribute name="margin" value="10 15" />
            </p>
        </div>

        <!-- equivalent CSS selector syntax: ".another-class", "any" tag is wildcard (mean any tag) -->
        <any class="another-class">
            <attribute name="text-align" value="right" />
        </any>

        <h2 class="header">
            <span>
                <attribute name="font-size" value="9" />
            </span>
            <div>
                <attribute name="font-style" value="bold" />
            </div>
        </h2>
    </stylesheet>

<a name="tags"></a>
Standard tags
----------------

Library supports primary HTML tags: div, p, table, tr, td, b, strong, span, a, h1, h2, h3, h4, h5, img, br, ul, li
In addition there are not standard tags:

* dynamic-page - auto breakable page
* page - single page
* page-break, column-break, break - breaks page or column, this tag must be direct child of "dynamic-page" or "column-layout"!
* column-layout - separate workspace on columns, additional attributes: number-of-columns, margin-between-columns, equals-columns

There are tags that only are bags for attributes, set of tags etc:

* stylesheet - stylesheet for parent
* attribute - simple attribute declaration, direct child of "stylesheet" tag. Required attributes of this element: name - attribute name, value - attribute value
* enhancement - complex attribute declaration, direct child of "stylesheet" tag. Required attributes of this element: name - complex attribute name
* placeholders - defines placeholders for parent tag. Children tags of placeholder are specyfic for every parent tag.
* metadata - defines metadata of pdf document, direct child of document root (TODO: not implemented yet)
* behaviours - defines behaviours for parent tag. Supported behaviours: href, ref, bookmark, note (action as same as for attributes with as same as name)

<a name="attributes"></a>
Attributes
----------------

* width and height: rigidly sets height and width, there are no units. Relative values in percent are supported. 
* margin (margin-top, margin-bottom, margin-left, margin-right): margin similar to margin from HTML/CSS. Margins of simblings are pooled. For side margins possible is "auto" value, it works similar as in HTML/CSS.
* padding (padding-top, padding-bottom, padding-left, padding-right): works similiar as in HTML/CSS
* font-type - font name must occurs in fonts.xml config file, otherwise exception will be thrown
* font-size - file size in points, there are no any unit
* font-style - allowed values: normal, bold, italic, bold-italic
* color - text color. HTML/CSS style values are supported
* splittable - if true, element is able to be splitted in several pages. Default value for most tags is true..
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

<a name="complex-attributes"></a>
Complex attributes
----------------

* border:
    - color: border color
    - style: posible values: solid (solid line), dotted (predefined dotted line) or any definition in the form of integers separated by space
    - type: which edges will be shown - default "top+bottom+left+right" (all edges). "none" value is possible (it disable border)
    - size: border size, there are no unit
    - radius: corner rounding in radians (attention: if this parameter is set, type paramete will be ignored, rounded border always will be full - this will be fixed in future)
    - position: border translation relative to original position. Positive values extends border, negative values decrases border. Owing to manipulation of this parameter, you can obtain complex pattern as border if you add another borders with different styles and positions. 

* background:
    - color: background color
    - image: background image
    - repeat: way of image repeating (none|x|y|all)
    - radius: rounding background corners (for now only works with color background)
    - use-real-dimension: attribute uses only by page (or dynamic-page). True for filling also margins, false in otherwise.
    - image-width: custom width of background image, percentage values are allowed
    - image-height: custom height of background image, percentage values are allowed

It is possible to add several complex attributes in the same type (for instance 3 different borders). You can achieve that by using "stylesheet" tag instead of short notation.

    <pdf>
        <dynamic-page>
            <div>
                <stylesheet>
                    <!-- Top and bootom edges are red, side edges are yellow-gray --> 
                    <enhancement name="border" color="red" type="top+bottom" />
                    <enhancement id="borderLeftAndRight" name="border" color="yellow" type="left+right" size="4" />
                    <enhancement id="outerBorderLeftAndRight" name="border" color="gray" type="left+right" size="2" position="1" />
                </stylesheet>
            </div>
        </dynamic-page>
    </pdf>

In this example second border has "borderLeftAndRight" indentifie, if this border had not id, attributes from second border would be merged with attributes from first border. Default identifier "id" is as same as "name" attribute. "id" attributes for complex attributes hasn't nothing to attribute "id" of tags (using in inheritance). It is possible to create complex borders as same as in previous example (outerBorderLeftAndRight).

<a name="hyperlinks"></a>
Hyperlinks
----------------

Library supports external and internal hyperlinks. External hyperlink links to urls, internal links to another tag inside pdf.

Example:

    <pdf>
        <dynamic-page>
            <a href="http://google.com">go to google.com</a>
            <br />
            <a ref="some-id">go to another tag</a>
            <page-break />
            <p id="some-id">Yep, this is another tag! ;)</p>
        </dynamic-page>
    </pdf>

Every element has "href" and "ref" attribute, even div. You can't nest elements inner "a" tag. If you want to use img element as link, you should use href (external link) or ref (internal link) attribute directly in img tag.

<a name="bookmarks"></a>
Bookmarks
----------------

Every tag has "bookmark" attribute, if you assign some value to this attribute, bookmark that refers to this tag will be automatically created. Bookmark of parent tag is also parent of children's bookmarks.

Example:

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

Above structure will create this bookmarks structure:

* parent bookmark
    - children bookmark
    - another children bookmark
* another parent bookmark

<a name="notes"></a>
Sticky notes
----------------

Sticky note can be created by "note" attribute.

Example:

    <pdf>
        <dynamic-page>
            <div note="note text"></div>
        </dynamic-page>
    </pdf>
    
Xml parser normalizes values of attributes, wich results ignoring new line chars. If you want to add note with new line chars, you should use syntax:

    <pdf>
        <dynamic-page>
            <div>
                <behaviours>
                    <note>note text</note>
                </behaviours>
            </div>
        </dynamic-page>
    </pdf>


<a name="headers"></a>
Repetitive headers and footers
----------------

"placeholders" can be used in order to adding repetitive header or/and footer. Some elemnts has special "placeholders": page has header and footer, table also has header and footer (TODO: not implemented yet) etc.

    <pdf>
        <dynamic-page>
            <placeholders>
                <header>
                    <div height="50" width="100%">
                        Header
                    </div>
                </header>
                <footer>
                    <div height="50" width="100%">
                        Footer
                    </div>
                </footer>
            </placeholders>
        </dynamic-page>
    </pdf>

Header and footer has to have directly setted height. This height is pooled with page top and bottom margins. Workspace is page size reduced by page margins and placeholders (footer and header) height.

In header and footer is special tag "page-info". It displays current page information in configurable format. This element works only with dynamic-page, not single pages. This tag has some attributes, the most important is "format".

    <!-- ... -->
        <header>
            <page-info format="page %s from %s"></page-info>
        </header>
    <!-- ... -->

<a name="watermarks"></a>
Watermarks
----------------

Page has "watermark" placeholder. As watermark may be set block and container elements, for instance: div, p, h1 (no span, plain text or img). If you want to use image as watermark, you should wrap tag img into div tag.

Example:

    <pdf>
        <dynamic-page>
            <placeholders>
                <watermark>
                    <!-- as rotate can you use absolute values (45deg - in degrees, 0.123 - in radians) or relative values ("diagonally" and "-diagonally" - angle between diagonal and base side of the page) -->
                    <div rotate="diagonally" alpha="0.1">
                        <img src="path/to/image.png" />
                    </div>
                </watermark>
            </placeholders>
        </dynamic-page>
    </pdf>

<a name="templates"></a>
Using pdf document as template
------------------------------

"page" and "dynamic-page" tags have "document-template" attribute, that is able to use external pdf document as template. For "page" tag page's template will be first page of external document. For "dynamic-page" tag template for each page will be corresponding page of external document.

Example:

    <pdf>
        <dynamic-page document-template="path/to/file.pdf">
            <div>Some content</div>
        </dynamic-page>
    </pdf>

<a name="columns"></a>
Separate page on columns
----------------

Page can be separated on columns:

    <pdf>
        <dynamic-page>
            <column-layout>
                <div width="100%" height="2500">
                    <stylesheet>
                        <enhancement name="background" color="green" />
                    </stylesheet>
                </div>
            </column-layout>
        </dynamic-page>
    </pdf>

Above XML describes several pages of pdf document with green rectangles separated on two columns. "column-layout" tag has three additional parameters: number-of-columns, margin-between-columns and equals-columns. Default values for this attributes are 2, 10 and false respectlivy. If equals-columns attribute is set, columns will have more or less equals height.

<a name="page-break"></a>
Breaking pages and columns
----------------

Page and column may by manually broken by one of tags: page-break, column-break, break. All those tags are the same. Those tags have to be direct children of breaking element (dynamic-page or column-layout).

If you want to avoid automatic page or column break on certain tag, you should set off "splittable" attribute of this tag. 

Example:

    <pdf>
        <dynamic-page>
            <div splittable="false">this div won't be automatically broken</div>
        </dynamic-page>
    </pdf>

<a name="metadata"></a>
Metadata
--------

Metadata can be added by attributes of document's root. Supported metadata: Creator, Keywords, Subject, Author, Title, ModDate, CreationDate and Trapped. Names of this attributes are case sensitive.

Example:

    <pdf Author="Piotr Sliwa" Title="Test document">
        <!-- some other elements -->
    </pdf>

<a name="configuration"></a>
Configuration
----------------

Library has three primary config files that allow you to adopt library to specyfic needs and to extending.

* enhancements.xml - declarations of complex attributes classes to logical names that identify attribute in whole library.
* nodes.xml - definitions of allowed tags in xml document with default attributes and formatting objects.
* fonts.xml - definitions of fonts and assigning them to logical names that identify font in whole library.

In order to change default config files, you must pass to Facade constructor configured Loader object:

    $loader = new PHPPdf\Configuration\LoaderImpl('/path/to/file/nodes.xml', '/path/to/file/enhancements.xml', '/path/to/file/fonts.xml');
    $facade = new PHPPdf\Parser\Facade($loader);

FacadeBuilder can be uset to build and configure Facade. Nowaday builder has only cache setting responsibilites:
    
    $builder = PHPPdf\Parser\FacadeBuilder::create(/* you can pass specyfic configuration loader object */)
                                          ->setCache('File', array('cache_dir' => './cache'))
                                          ->setUseCacheForStylesheetConstraint(true); //stylesheets will be also use cache

    $facade = $builder->build();

There are two implementation of configuration loaders, standard and using DependencyInjection component from Symfony2. Second implementation is more flexible in configuration, but is less efficently. Default loader dosn't use DI container.

<a name="limitations"></a>
Known limitations
----------------

Below is list of known limitations of library current version:

* there no way to inject image into text with floating - will be introduced in next releases
* border doesn't change dimensions of the element (in HTML do)

<a name="todo"></a>
TODO - plans
----------------

* automatic generating table of contents
* document metadata
* improve interpretation of attributes and fixing found bugs
* improve table, header and footer for table, rowspan. Fix calculation of cell's min height when colspan is used.
* refactoring

<a name="requirements"></a>
Technical requirements
----------------

Library works on php 5.3+

[1]: https://github.com/psliwa/PdfBundle
