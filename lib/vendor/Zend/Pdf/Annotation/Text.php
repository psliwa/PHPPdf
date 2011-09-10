<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Pdf
 * @subpackage Annotation
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Text.php 20096 2010-01-06 02:05:09Z bkarwin $
 */

/** Internally used classes */








/** Zend_Pdf_Annotation */


/**
 * A text annotation represents a "sticky note" attached to a point in the PDF document.
 *
 * @package    Zend_Pdf
 * @subpackage Annotation
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Pdf_Annotation_Text extends Zend_Pdf_Annotation
{
    /**
     * Annotation object constructor
     *
     * @throws Zend_Pdf_Exception
     */
    public function __construct(Zend_Pdf_Element $annotationDictionary)
    {
        if ($annotationDictionary->getType() != Zend_Pdf_Element::TYPE_DICTIONARY) {
            
            throw new Zend_Pdf_Exception('Annotation dictionary resource has to be a dictionary.');
        }

        if ($annotationDictionary->Subtype === null  ||
            $annotationDictionary->Subtype->getType() != Zend_Pdf_Element::TYPE_NAME  ||
            $annotationDictionary->Subtype->value != 'Text') {
            
            throw new Zend_Pdf_Exception('Subtype => Text entry is requires');
        }

        parent::__construct($annotationDictionary);
    }

    /**
     * Create link annotation object
     *
     * @param float $x1
     * @param float $y1
     * @param float $x2
     * @param float $y2
     * @param string $text
     * @return Zend_Pdf_Annotation_Text
     */
    public static function create($x1, $y1, $x2, $y2, $text)
    {
        $annotationDictionary = new Zend_Pdf_Element_Dictionary();

        $annotationDictionary->Type    = Zend_Pdf_Element_Name::getInstance('Annot');
        $annotationDictionary->Subtype = Zend_Pdf_Element_Name::getInstance('Text');

        $rectangle = new Zend_Pdf_Element_Array();
        $rectangle->items[] = Zend_Pdf_Element_Numeric::getInstance($x1);
        $rectangle->items[] = Zend_Pdf_Element_Numeric::getInstance($y1);
        $rectangle->items[] = Zend_Pdf_Element_Numeric::getInstance($x2);
        $rectangle->items[] = Zend_Pdf_Element_Numeric::getInstance($y2);
        $annotationDictionary->Rect = $rectangle;

        $annotationDictionary->Contents = Zend_Pdf_Element_String::getInstance($text);

        return new Zend_Pdf_Annotation_Text($annotationDictionary);
    }
}
