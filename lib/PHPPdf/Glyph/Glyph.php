<?php

namespace PHPPdf\Glyph;

use PHPPdf\Document,
    PHPPdf\Glyph\Container,
    PHPPdf\Formatter\Formatter,
    PHPPdf\Enhancement\Enhancement;

/**
 * Glyph represents pdf drawable element
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface Glyph
{
    /**
     * @return array Array of PHPPdf\Util\DrawingTask objects
     */
    public function getDrawingTasks(Document $document);
    
    public function setParent(Container $glyph);
    public function getParent();

    public function getAttribute($name);

    /**
     * @return PHPPdf\Glyph\Glyph Self, influent interface
     */
    public function setAttribute($name, $value);

    public function hasAttribute($name);

    /**
     * @return float Get preffered width
     */
    public function getWidth();

    /**
     * @return float Get preffered height
     */
    public function getHeight();

    /**
     * Reset internal state (connections with other glyphs, cached dimensions etc.)
     */
    public function reset();

    /**
     * @return array Array of top left point
     */
    public function getStartDrawingPoint();

    /**
     * @return array Array of bottom right point
     */
    public function getEndDrawingPoint();

    public function translate($x, $y);

    /**
     * @return PHPPdf\Util\Boundary
     */
    public function getBoundary();

    public function getEnhancementsAttributes($name = null);

    public function mergeEnhancementAttributes($name, array $attributes = array());

    public function add(Glyph $glyph);

    public function remove(Glyph $glyph);

    public function getChildren();

    public function removeAll();

    public function format(Document $document);

    public function setFormattersNames(array $formatters);

    public function makeAttributesSnapshot();
    public function getAttributesSnapshot();

    public function split($height);

    /**
     * Makes deep copy of the glyph
     *
     * @return PHPPdf\Glyph\Glyph Copy of the glyph
     */
    public function copy();

    public function getPlaceholder($name);
}