<?php


namespace PHPPdf\Test\Helper;

/**
 * Test specific subclass for Container - support for real-width and real-height attributes
 * for tests purpose
 */
class Container extends \PHPPdf\Core\Node\Container
{
    protected static function setDefaultAttributes()
    {
        parent::setDefaultAttributes();

        static::addAttribute('real-height');
        static::addAttribute('real-width');
    }

    protected static function initializeType()
    {
        parent::initializeType();
        static::setAttributeGetters(array('real-height' => 'getRealHeight', 'real-width' => 'getRealWidth'));
    }

    public function getRealHeight()
    {
        return $this->getAttributeDirectly('real-height') ?: parent::getRealHeight();
    }

    public function getRealWidth()
    {
        return $this->getAttributeDirectly('real-width') ?: parent::getRealWidth();
    }
} 