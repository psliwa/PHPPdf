<?php

namespace PHPPdf\Formatter;

use PHPPdf\Glyph\Glyph,
    PHPPdf\Glyph\Container;

/**
 * TODO: refactoring
 *
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

        $containers = $this->getSubjectOfSplitting()->getContainers();
        $indexOfLastContainer = count($containers) - 1;
        $numberOfColumns = $this->getSubjectOfSplitting()->getAttribute('number-of-columns');

        $columnNumber = $indexOfLastContainer % $numberOfColumns;

        if($container->getFirstPoint() === null)
        {
            $this->translateColumnContainer($container, $columnNumber);
        }

        if($columnNumber > 0)
        {
            $previousContainer = $containers[$indexOfLastContainer - 1];
        }

        $container->add($glyph);
        $glyph->translate($container->getFirstPoint()->getX() - $glyph->getFirstPoint()->getX(), 0);
    }

    protected function breakSubjectOfSplittingIncraseTranslation($verticalTranslation)
    {
        $this->getSubjectOfSplitting()->createNextContainer();

        $numberOfContainers = count($this->getSubjectOfSplitting()->getContainers()) - 1;
        $numberOfColumns = $this->getSubjectOfSplitting()->getAttribute('number-of-columns');

        $columnNumber = $numberOfContainers % $numberOfColumns;

        $this->translateColumnContainer($this->getSubjectOfSplitting()->getCurrentContainer(), $columnNumber);
        $isLastColumnInRow = $columnNumber == ($numberOfColumns - 1);

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
        $firstPoint = $columnableContainer->getFirstPoint()->translate($x, 0);

        $container->getBoundary()->setNext($firstPoint)
                                 ->setNext($firstPoint->translate($columnableContainer->getWidth(), 0));
    }

    protected function postFormat()
    {
        $columnableContainer = $this->getSubjectOfSplitting();

        $containers = $columnableContainer->getContainers();

        $numberOfContainers = count($containers);
        $numberOfColumns = $columnableContainer->getAttribute('number-of-columns');

        $bottomCoordYPerColumn = array();
        $bottomCoordYPerRow = array();
        $maxRightCoordX = 0;

        for($i=0, $row = 0; $i<$numberOfContainers; $i+=$numberOfColumns, $row++)
        {
            for($j=0, $currentIndex = $i; $j<$numberOfColumns && isset($containers[$currentIndex]); $j++, $currentIndex = $j+$i)
            {
                $container = $containers[$currentIndex];
                $children = $container->getChildren();
                $lastChild = end($children);

                if($lastChild)
                {
                    $bottomYCoord = $lastChild->getDiagonalPoint()->getY();
                    if(!isset($bottomCoordYPerColumn[$j]) || $bottomCoordYPerColumn[$j] > $bottomYCoord)
                    {
                        $bottomCoordYPerColumn[$j] = $bottomYCoord;
                    }

                    if(!isset($bottomCoordYPerRow[$row]) || $bottomCoordYPerRow[$row] > $bottomYCoord)
                    {
                        $bottomCoordYPerRow[$row] = $bottomYCoord;
                    }
                }

                $maxRightCoordX = max($container->getDiagonalPoint()->getX(), $maxRightCoordX);
            }
        }

        for($i=0, $row=0; $i<$numberOfContainers; $i+=$numberOfColumns, $row++)
        {
            for($j=0, $currentIndex = $i; $j<$numberOfColumns && isset($containers[$currentIndex]); $j++, $currentIndex = $j+$i)
            {
                $container = $containers[$currentIndex];

                $translate = 0;
                $previousIndex = $i-$numberOfColumns;
                while(isset($containers[$previousIndex]))
                {
                    $translate += $containers[$previousIndex]->getHeight();
                    $previousIndex -= $numberOfColumns;
                }

                $bottomYCoord = max($columnableContainer->getPage()->getDiagonalPoint()->getY(), $bottomCoordYPerRow[$row]);

                $boundary = $container->getBoundary();
                $boundary->setNext($boundary[1]->getX(), $bottomYCoord)
                                         ->setNext($boundary[0]->getX(), $bottomYCoord)
                                         ->close();
                $container->setHeight($container->getFirstPoint()->getY() - $bottomYCoord);

                $container->translate(0, $translate);

                $bottomCoordYPerColumn[$j] = min($container->getDiagonalPoint()->getY(), $bottomCoordYPerColumn[$j]);
            }
        }

        $columnBottomCoordY = min($bottomCoordYPerColumn);
        $diffVertical = $columnableContainer->getDiagonalPoint()->getY() - $columnBottomCoordY;
        $diffHorizontal = $maxRightCoordX - $columnableContainer->getDiagonalPoint()->getX();

        $columnableContainer->resize($diffHorizontal, $diffVertical);
        $columnableContainer->setHeight($columnableContainer->getHeight() + $diffVertical);
        $columnableContainer->setWidth($columnableContainer->getWidth() + $diffHorizontal);

        $columnableContainer->removeAll();

        foreach($columnableContainer->getContainers() as $container)
        {
            $columnableContainer->add($container);
        }
    }

    protected function addChildrenToCurrentPageAndTranslate(Glyph $glyph, $translation)
    {
        $container = $this->getSubjectOfSplitting()->getCurrentContainer();

        $boundary = $container->getBoundary();

        $container->add($glyph);
        $glyph->translate($container->getFirstPoint()->getX() - $glyph->getFirstPoint()->getX(), -$translation);
    }

    protected function getGlyphTranslation(Glyph $glyph, $glyphYCoordStart)
    {
        $translation = $this->getSubjectOfSplitting()->getCurrentContainer()->getFirstPoint()->getY() - $glyphYCoordStart;

        return $translation;
    }
}