<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Font;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Registry implements \Countable, \Serializable
{
    private $fonts = array();

    public function register($name, $font)
    {
        if(is_array($font))
        {
            $font = new Font($font);
        }
        elseif(!$font instanceof Font)
        {
            throw new \InvalidArgumentException('Font should by type of PHPPdf\Font or array');
        }

            $this->add($name, $font);
    }

    private function add($name, Font $font)
    {
        $name = (string) $name;
        $this->fonts[$name] = $font;
    }

    public function get($name)
    {
        if($this->has($name))
        {
            return $this->fonts[$name];
        }

        throw new \PHPPdf\Exception\Exception(sprintf('Font "%s" is not registered.', $name));
    }

    public function has($name)
    {
        return isset($this->fonts[$name]);
    }

    public function count()
    {
        return count($this->fonts);
    }

    public function serialize()
    {
        return serialize(array(
            'fonts' => $this->fonts,
        ));
    }

    public function unserialize($serialized)
    {
        $data = \unserialize($serialized);

        $fonts = $data['fonts'];

        foreach($fonts as $name => $font)
        {
            $this->add($name, $font);
        }
    }
}