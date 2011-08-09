<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
use PHPPdf\Parser\Exception\DuplicatedIdException;

class Manager
{
    private $glyphs = array();
    private $wrappers = array();
    
    public function register($id, Glyph $glyph)
    {
        if(isset($this->glyphs[$id]))
        {
            throw new DuplicatedIdException(sprintf('Duplicate of id "%s".', $id));
        }
        
        $this->glyphs[$id] = $glyph;

        if(isset($this->wrappers[$id]))
        {
            $this->wrappers[$id]->setGlyph($glyph);
        }
    }
    
    /**
     * @return GlyphAware
     */
    public function get($id)
    {
        if(isset($this->glyphs[$id]))
        {
            return $this->glyphs[$id];
        }
        
        if(isset($this->wrappers[$id]))
        {
            return $this->wrappers[$id];
        }
        
        $wrapper = new GlyphWrapper();
        
        $this->wrappers[$id] = $wrapper;
        
        return $wrapper;
    }
}