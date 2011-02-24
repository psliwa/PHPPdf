<?php

namespace PHPPdf\Parser;

use PHPPdf\Parser\Exception\ParseException;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class FormatterParser extends XmlParser
{
    const ROOT_TAG = 'formatters';
    const FORMATTER_TAG = 'formatter';

    private $document;

    public function __construct(\PHPPdf\Document $document)
    {
        $this->document = $document;
    }

    protected function createRoot()
    {
        return array();
    }

    protected function parseElement(\XMLReader $reader)
    {
        $tag = $reader->name;

        if($tag !== self::FORMATTER_TAG)
        {
            throw new ParseException(sprintf('Unexpected tag name: "%s", expected "%s".', $tag, self::FORMATTER_TAG));
        }

        $className = (string) $reader->getAttribute('class');

        if(!$className)
        {
            throw new ParseException('Class attribute is required.');
        }

        if(!\class_exists($className, true))
        {
            throw new ParseException(sprintf('Class "%s" dosn\'t exist.', $className));
        }

        $root = &$this->getLastElementFromStack();

        $class = new \ReflectionClass($className);

        if(!$class->implementsInterface('PHPPdf\Formatter\Formatter'))
        {
            throw new ParseException(sprintf('Class "%s" dosn\'t implement PHPPdf\Formatter\Formatter interface.', $className));
        }

        $formatter = $class->newInstance($this->document);

        $root[] = $formatter;
    }

    protected function parseEndElement(\XMLReader $reader)
    {
    }
}