<?php

namespace PHPPdf\Font;

/**
 * Wrapper for font resources. Resource is lazy loaded.
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ResourceWrapper
{
    private $file;
    private $name;

    private $resource;

    private function __construct()
    {
    }

    /**
     * Create ResourceWrapper from file
     *
     * @param string $filePath Path to font file
     * @return ResourceWrapper
     */
    public static function fromFile($filePath)
    {
        $wrapper = new self();
        $wrapper->file = $filePath;

        return $wrapper;
    }

    /**
     * Create ResourceWrapper from name
     *
     * @param string $name Name of font supported by Zend_Pdf library
     * @return ResourceWrapper
     */
    public static function fromName($name)
    {
        $wrapper = new self();
        $wrapper->name = self::retrieveFontName($name);

        return $wrapper;
    }

    private static function retrieveFontName($name)
    {
        $const = sprintf('\Zend_Pdf_Font::FONT_%s', str_replace('-', '_', strtoupper($name)));

        if(!defined($const))
        {
            throw new \InvalidArgumentException(sprintf('Unrecognized font name: "%s".".', $name));
        }

        return constant($const);
    }

    /**
     * Gets font resource
     *
     * @return Zend_Pdf_Resource_Font
     */
    public function getResource()
    {
        if($this->resource === null)
        {
            $this->createResource();
        }

        return $this->resource;
    }

    private function createResource()
    {
        if($this->name)
        {
            $this->createResourceFromName();
        }
        else
        {
            $this->createResourceFromFile();
        }
    }

    private function createResourceFromName()
    {
        $this->resource = \Zend_Pdf_Font::fontWithName($this->name);
    }

    private function createResourceFromFile()
    {
        $this->resource = \Zend_Pdf_Font::fontWithPath($this->file);
    }
}