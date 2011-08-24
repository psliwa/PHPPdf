<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Engine;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface GraphicsContext
{
    const DASHING_PATTERN_SOLID = 0;
    const DASHING_PATTERN_DOTTED = 1;
    
    const SHAPE_DRAW_STROKE = 0;
    const SHAPE_DRAW_FILL = 1;
    const SHAPE_DRAW_FILL_AND_STROKE = 2;
    
    public function clipRectangle($x1, $y1, $x2, $y2);

    public function saveGS();

    public function restoreGS();

    public function drawImage(Image $image, $x1, $y1, $x2, $y2);

    public function drawLine($x1, $y1, $x2, $y2);

    public function setFont(Font $font, $size);

    /**
     * @param string|PHPPdf\Engine\Color String representing color or color object
     */
    public function setFillColor($color);

    /**
     * @param string|PHPPdf\Engine\Color String representing color or color object
     */
    public function setLineColor($color);
    
    public function drawPolygon(array $x, array $y, $type);

    public function drawText($text, $width, $height, $encoding);

    public function drawRoundedRectangle($x1, $y1, $x2, $y2, $radius, $fillType = null);
    
    public function setLineWidth($width);

    public function setLineDashingPattern($pattern);
    
    public function uriAction($x1, $y1, $x2, $y2, $uri);
    
    public function goToAction(GraphicsContext $gc, $x1, $y1, $x2, $y2, $top);
    
    public function addBookmark($identifier, $name, $top, $ancestorsIdentifier = null);
    
    public function attachStickyNote($x1, $y1, $x2, $y2, $text);
    
    public function setAlpha($float);
}