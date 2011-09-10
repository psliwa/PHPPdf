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
 * @version    $Id: FileAttachment.php 20096 2010-01-06 02:05:09Z bkarwin $
 */

/** Internally used classes */








/** Zend_Pdf_Annotation */


/**
 * A file attachment annotation contains a reference to a file,
 * which typically is embedded in the PDF file.
 *
 * @package    Zend_Pdf
 * @subpackage Annotation
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Pdf_Annotation_FileAttachment extends Zend_Pdf_Annotation
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
            $annotationDictionary->Subtype->value != 'FileAttachment') {
            
            throw new Zend_Pdf_Exception('Subtype => FileAttachment entry is requires');
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
     * @param string $fileSpecification
     * @return Zend_Pdf_Annotation_FileAttachment
     */
    public static function create($x1, $y1, $x2, $y2, $fileSpecification)
    {
        $annotationDictionary = new Zend_Pdf_Element_Dictionary();

        $annotationDictionary->Type    = Zend_Pdf_Element_Name::getInstance('Annot');
        $annotationDictionary->Subtype = Zend_Pdf_Element_Name::getInstance('FileAttachment');

        $rectangle = new Zend_Pdf_Element_Array();
        $rectangle->items[] = Zend_Pdf_Element_Numeric::getInstance($x1);
        $rectangle->items[] = Zend_Pdf_Element_Numeric::getInstance($y1);
        $rectangle->items[] = Zend_Pdf_Element_Numeric::getInstance($x2);
        $rectangle->items[] = Zend_Pdf_Element_Numeric::getInstance($y2);
        $annotationDictionary->Rect = $rectangle;

        $fsDictionary = new Zend_Pdf_Element_Dictionary();
        $fsDictionary->Type = Zend_Pdf_Element_Name::getInstance('Filespec');
        $fsDictionary->F    = Zend_Pdf_Element_String::getInstance($fileSpecification);

        $annotationDictionary->FS = $fsDictionary;


        return new Zend_Pdf_Annotation_FileAttachment($annotationDictionary);
    }
}
