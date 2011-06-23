<?php

namespace PHPPdf\Glyph;

use PHPPdf\Document,
    PHPPdf\Formatter\Formatter;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Container extends Glyph
{
    protected $children = array();
    private $document = null;

    /**
     * @param Glyph $glyph Child glyph object
     * @return PHPPdf\Glyph\Container
     */
    public function add(Glyph $glyph)
    {
        $glyph->setParent($this);
        $glyph->reset();
        $this->children[] = $glyph;
        $glyph->setPriorityFromParent();

        return $this;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function remove(Glyph $glyph)
    {
        foreach($this->children as $key => $child)
        {
            if($glyph === $child)
            {
                unset($this->children[$key]);
                return true;
            }
        }

        return false;
    }

    public function removeAll()
    {
        $this->children = array();
    }

    public function reset()
    {
        parent::reset();

        foreach($this->children as $child)
        {
            $child->reset();
        }
    }

    protected function preDraw(Document $document)
    {
        $this->document = $document;

        parent::preDraw($document);
    }

    public function getDocument()
    {
        return $this->document;
    }

    protected function doDraw(Document $document)
    {
        foreach($this->children as $glyph)
        {
            $tasks = $glyph->getDrawingTasks($document);
            foreach($tasks as $task)
            {
                $this->addDrawingTask($task);
            }
        }
    }

    public function copy()
    {
        $copy = parent::copy();

        foreach($this->children as $key => $child)
        {
            $clonedChild = $child->copy();
            $copy->children[$key] = $clonedChild;
            $clonedChild->setParent($copy);
        }

        return $copy;
    }

    public function translate($x, $y)
    {
        parent::translate($x, $y);

        foreach($this->getChildren() as $child)
        {
            $child->translate($x, $y);
        }
    }

    /**
     * Splits compose glyph.
     *
     * @todo refactoring
     *
     * @param integer $height
     * @return \PHPPdf\Glyph\Glyph
     */
    protected function doSplit($height)
    {
        $splitCompose = parent::doSplit($height);

        if(!$splitCompose)
        {
            return null;
        }

        $childrenToSplit = array();
        $childrenToMove = array();

        list(,$splitLine) = $this->getStartDrawingPoint();
        $splitLine -= $height;

        foreach($this->getChildren() as $child)
        {
            list(,$childStart) = $child->getStartDrawingPoint();
            list(,$childEnd) = $child->getEndDrawingPoint();

            if($splitLine < $childStart && $splitLine > $childEnd)
            {
                $childrenToSplit[] = $child;
            }
            elseif($splitLine >= $childStart)
            {
                $childrenToMove[] = $child;
            }
        }

        $splitProducts = array();
        $translates = array();
        foreach($childrenToSplit as $child)
        {
            list(,$childStart) = $child->getStartDrawingPoint();
            $childSplitLine = $childStart - $splitLine;
            $splitProduct = $child->split($childSplitLine);

            list(,$yChildStart) = $child->getStartDrawingPoint();
            list(,$yChildEnd) = $child->getEndDrawingPoint();
            if($splitProduct)
            {
                $translates[] = $childSplitLine - ($yChildStart - $yChildEnd);
                $splitProducts[] = $splitProduct;
            }
            else
            {
                $translates[] = ($yChildStart - $yChildEnd) - ($child->getHeight() - $childSplitLine);
                array_unshift($childrenToMove, $child);
            }
        }

        $splitCompose->removeAll();

        $splitProducts = array_merge($splitProducts, $childrenToMove);
        foreach($splitProducts as $child)
        {
            $splitCompose->add($child);
        }

        if(count($translates))
        {
            $translate = \max($translates);
            $splitCompose->setHeight($splitCompose->getHeight() + $translate);

            $boundary = $splitCompose->getBoundary();
            $points = $splitCompose->getBoundary()->getPoints();
            $boundary->reset();
            $boundary->setNext($points[0])
                     ->setNext($points[1])
                     ->setNext($points[2]->translate(0, $translate))
                     ->setNext($points[3]->translate(0, $translate))
                     ->close();

            foreach($childrenToMove as $child)
            {
                $child->translate(0, $translate);
            }
        }

        return $splitCompose;
    }

    public function getMinWidth()
    {
        $minWidth = $this->getAttributeDirectly('min-width');

        foreach($this->getChildren() as $child)
        {
            $minWidth = max(array($minWidth, $child->getMinWidth()));
        }

        return $minWidth + $this->getPaddingLeft() + $this->getPaddingRight();
    }
}