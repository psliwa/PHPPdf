<?php


namespace PHPPdf\Test\Helper;

use PHPPdf\Core\Node\Node;

class NodeAssert extends \PHPUnit_Framework_Assert
{
    private $node;

    /**
     * @param Node $node
     *
     * @return NodeAssert
     */
    public static function create(Node $node)
    {
        return new self($node);
    }

    public function __construct(Node $node)
    {
        $this->node = $node;
    }

    /**
     * @param $width
     *
     * @return NodeAssert
     */
    public function width($width)
    {
        $this->assertEquals($width, $this->node->getWidth(), 'invalid width');
        return $this;
    }

    /**
     * @param $height
     *
     * @return NodeAssert
     */
    public function height($height)
    {
        $this->assertEquals($height, $this->node->getHeight(), 'invalid height');

        return $this;
    }

    /**
     * @return NodeAssert
     */
    public function widthAsTheSameAsParentsWithoutPaddings()
    {
        $this->assertEquals($this->node->getParent()->getWidthWithoutPaddings(), $this->node->getWidth());
        return $this;
    }
} 