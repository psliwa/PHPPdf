<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Engine;

use PHPPdf\Exception\InvalidArgumentException;

/**
 * Abstract font
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
abstract class AbstractFont implements Font
{
    protected $fontResources = array();
    protected $currentStyle = null;

    public function __construct(array $fontResources)
    {
        $this->throwsExceptionIfFontsAreInvalid($fontResources);

        $this->fontResources = $fontResources;
        $this->setStyle(self::STYLE_NORMAL);
    }

    private function throwsExceptionIfFontsAreInvalid(array $fonts)
    {
        $types = array(
            self::STYLE_NORMAL,
            self::STYLE_BOLD,
            self::STYLE_ITALIC,
            self::STYLE_BOLD_ITALIC,
            self::STYLE_LIGHT,
            self::STYLE_LIGHT_ITALIC,
        );

        if(count($fonts) === 0)
        {
            throw new InvalidArgumentException('Passed empty map of fonts.');
        }
        elseif(count(\array_diff(array_keys($fonts), $types)) > 0)
        {
            throw new InvalidArgumentException('Invalid font types in map of fonts.');
        }
        elseif(!isset($fonts[self::STYLE_NORMAL]))
        {
            throw new InvalidArgumentException('Path for normal font must by passed.');
        }
    }

    public function setStyle($style)
    {
        $style = $this->convertStyleType($style);

        $this->currentStyle = $this->fontStyle($style);
    }

    public function getCurrentStyle()
    {
        return $this->currentStyle;
    }

    private function convertStyleType($style)
    {
        if(is_string($style))
        {
            $style = str_replace('-', '_', strtoupper($style));
            $const = sprintf('%s::STYLE_%s', __CLASS__, $style);

            if(defined($const))
            {
                $style = constant($const);
            }
            else
            {
                $style = self::STYLE_NORMAL;
            }
        }

        return $style;
    }

    public function hasStyle($style)
    {
        $style = $this->convertStyleType($style);
        return isset($this->fontResources[$style]);
    }

    private function fontStyle($style)
    {
        $font = !$this->hasStyle($style) ? self::STYLE_NORMAL : $style;

        return $font;
    }
}