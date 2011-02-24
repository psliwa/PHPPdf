<?php

namespace PHPPdf\Glyph;

/**
 * Factory of the glyphs based on Factory Method and Prototype design pattern
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Factory
{
    private $prototypes = array();

    public function addPrototype($name, Glyph $glyph)
    {
        $name = (string) $name;

        $this->prototypes[$name] = $glyph;
    }

    /**
     * Create copy of glyph stored under passed name
     *
     * @param string Name/key of prototype
     * @return PHPPdf\Glyph\Glyph Deep copy of glyph stored under passed name
     * @throws \InvalidArgumentException If prototype with passed name dosn't exist
     */
    public function create($name)
    {
        $prototype = $this->getPrototype($name);

        return $prototype->copy();
    }

    /**
     * @return PHPPdf\Glyph\Glyph
     * @throws \InvalidArgumentException If prototype with passed name dosn't exist
     * @todo change type of exception
     */
    public function getPrototype($name)
    {
        $name = (string) $name;

        if(!$this->hasPrototype($name))
        {
            throw new \InvalidArgumentException(sprintf('Prototype under key "%s" dosn\'t exist.', $name));
        }

        return $this->prototypes[$name];
    }

    public function hasPrototype($name)
    {
        $name = (string) $name;

        return isset($this->prototypes[$name]);
    }
}