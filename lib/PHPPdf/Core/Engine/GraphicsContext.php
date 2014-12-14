<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Engine;

use Zend\Barcode\Object\ObjectInterface;

/**
 * Interface of graphics context.
 * 
 * All of method expects getters, copy and commit should be buffered, and
 * invoked on commit method.
 *
 * Coordinate system:
 *
 * * left bottom (not upper!) corner point has (0,0) coordinates
 * * when there are two points in method, first one is left bottom, second one is right upper
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface GraphicsContext
{
    const DASHING_PATTERN_SOLID = 0;
    const DASHING_PATTERN_DOTTED = 1;
    
    const SHAPE_DRAW_STROKE = 0;
    const SHAPE_DRAW_FILL = 1;
    const SHAPE_DRAW_FILL_AND_STROKE = 2;
    
    public function getWidth();
    public function getHeight();
    
    public function commit();
    
    public function clipRectangle($x1, $y1, $x2, $y2);

    public function saveGS();

    public function restoreGS();

    public function drawImage(Image $image, $x1, $y1, $x2, $y2);

    public function drawLine($x1, $y1, $x2, $y2);

    public function setFont(Font $font, $size);

    /**
     * @param string String representing color
     */
    public function setFillColor($color);

    /**
     * @param string String representing color
     */
    public function setLineColor($color);
    
    public function drawPolygon(array $x, array $y, $type);

    public function drawText($text, $x, $y, $encoding, $wordSpacing = 0, $fillType = self::SHAPE_DRAW_FILL);

    public function drawRoundedRectangle($x1, $y1, $x2, $y2, $radius, $fillType = null);
    
    public function drawEllipse($x, $y, $width, $height, $fillType = self::SHAPE_DRAW_FILL);
    
    public function drawArc($x, $y, $width, $height, $start, $end, $fillType = self::SHAPE_DRAW_FILL);
    
    public function setLineWidth($width);

    public function setLineDashingPattern($pattern);
    
    public function uriAction($x1, $y1, $x2, $y2, $uri);
    
    public function goToAction(GraphicsContext $gc, $x1, $y1, $x2, $y2, $top);
    
    public function addBookmark($identifier, $name, $top, $ancestorsIdentifier = null);
    
    public function attachStickyNote($x1, $y1, $x2, $y2, $text);
    
    public function setAlpha($float);
    
    public function rotate($x, $y, $angle);
    
    public function drawBarcode($x, $y, ObjectInterface $barcode);
       
    public function copy();
}