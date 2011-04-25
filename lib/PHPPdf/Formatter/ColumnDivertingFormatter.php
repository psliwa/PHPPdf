<?php

namespace PHPPdf\Formatter;

use PHPPdf\Glyph\Glyph,
    PHPPdf\Glyph\Container;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ColumnDivertingFormatter extends AbstractDivertingFormatter
{
    protected function shouldParentBeAutomaticallyBroken(Glyph $glyph)
    {
        return false;
    }

    protected function addToSubjectOfSplitting(Glyph $glyph)
    {
        $container = $this->getSubjectOfSplitting()->getCurrentContainer();

        if($container->getFirstPoint() === null)
        {
            $numberOfContainers = count($this->getSubjectOfSplitting()->getContainers()) - 1;
            $numberOfColumns = $this->getSubjectOfSplitting()->getAttribute('number-of-columns');

            $columnNumber = $numberOfContainers % $numberOfColumns;

            $this->translateColumnContainer($container, $columnNumber);
        }

        $container->add($glyph);
    }

    protected function breakSubjectOfSplittingIncraseTranslation($verticalTranslation)
    {
        $this->getSubjectOfSplitting()->createNextContainer();

        $numberOfContainers = count($this->getSubjectOfSplitting()->getContainers()) - 1;
        $numberOfColumns = $this->getSubjectOfSplitting()->getAttribute('number-of-columns');

        $columnNumber = $numberOfContainers % $numberOfColumns;

        $this->translateColumnContainer($this->getSubjectOfSplitting()->getCurrentContainer(), $columnNumber);

        $isLastColumnInRow = $columnNumber == 0;
        if($isLastColumnInRow)
        {
            $this->totalVerticalTranslation += $verticalTranslation;
        }
    }

    private function translateColumnContainer(Container $container, $columnNumber)
    {
        $columnableContainer = $this->getSubjectOfSplitting();
        $numberOfColumns = $columnableContainer->getAttribute('number-of-columns');

        $x = ($columnableContainer->getWidth() + $columnableContainer->getAttribute('margin-between-columns')) * $columnNumber;
        $firstPoint = $columnableContainer->getFirstPoint()->translate($x, $this->totalVerticalTranslation);

        $container->getBoundary()->setNext($firstPoint)
                                 ->setNext($firstPoint->translate($columnableContainer->getWidth(), 0));
    }

    protected function postFormat()
    {
        $columnableContainer = $this->getSubjectOfSplitting();

        $containers = $columnableContainer->getContainers();

        $numberOfContainers = count($containers);
        $numberOfColumns = $columnableContainer->getAttribute('number-of-columns');

        $bottomCoordYPerContainer = array();

        //TODO: refactoring
        for($i=0; $i<$numberOfContainers; $i+=$numberOfContainers)
        {
            for($j=0, $currentIndex = $i; $j<$numberOfColumns && isset($containers[$currentIndex]); $j++, $currentIndex = $j+$i)
            {
                $container = $containers[$currentIndex];
                $children = $container->getChildren();
                $lastChild = $children[count($children) -1];

                $bottomYCoord = $lastChild->getDiagonalPoint()->getY();

                if(!isset($bottomCoordYPerContainer[$i]) || $bottomCoordYPerContainer[$i] > $bottomYCoord)
                {
                    $bottomCoordYPerContainer[$i] = $bottomYCoord;
                }
            }
        }

        for($i=0; $i<$numberOfContainers; $i+=$numberOfContainers)
        {
            for($j=0, $currentIndex = $i; $j<$numberOfColumns && isset($containers[$currentIndex]); $j++, $currentIndex = $j+$i)
            {
                $container = $containers[$currentIndex];

                $boundary = $container->getBoundary();
                $boundary->setNext($boundary[1]->getX(), $bottomCoordYPerContainer[$i])
                                         ->setNext($boundary[0]->getX(), $bottomCoordYPerContainer[$i])
                                         ->close();
            }
        }

        $columnBottomCoordY = min($bottomCoordYPerContainer);
        $diff = $columnableContainer->getDiagonalPoint()->getY() - $columnBottomCoordY;
        $columnableContainer->resize(0, $diff);
    }

    protected function addChildrenToCurrentPageAndTranslate(Glyph $glyph, $translation)
    {
        $container = $this->getSubjectOfSplitting()->getCurrentContainer();

        $boundary = $container->getBoundary();

        $container->add($glyph);
        $x = $container->getFirstPoint()->getX();
        $glyph->translate($container->getFirstPoint()->getX() - $glyph->getFirstPoint()->getX(), -$translation);
    }
}