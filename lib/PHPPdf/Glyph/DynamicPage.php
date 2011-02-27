<?php

namespace PHPPdf\Glyph;

use PHPPdf\Glyph\Page,
    PHPPdf\Glyph\PageContext,
    PHPPdf\Document;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class DynamicPage extends Page
{
    private $prototype = null;
    private $currentPage = null;
    private $pages = array();

    private $totalTranslation = 0;

    public function __construct(Page $prototype = null)
    {
        $this->setPrototypePage($prototype ? $prototype : new Page());

        $this->initialize();
    }

    public function getBoundary()
    {
        return $this->getCurrentPage()->getBoundary();
    }

    public function getCurrentPage()
    {
        if($this->currentPage === null)
        {
            $this->createNextPage();
        }

        return $this->currentPage;
    }

    /**
     * @return PHPPdf\Glyph\Page
     */
    public function getPrototypePage()
    {
        return $this->prototype;
    }

    private function setPrototypePage(Page $page)
    {
        $this->prototype = $page;
    }

    /**
     * @return PHPPdf\Glyph\Page
     */
    public function createNextPage()
    {
        $this->currentPage = $this->prototype->copy();

        $index = count($this->pages);
        $this->currentPage->setContext(new PageContext($index+1, $this));
        $this->pages[$index] = $this->currentPage;

        return $this->currentPage;
    }

    public function copy()
    {
        $copy = parent::copy();
        $copy->prototype = $this->prototype->copy();
        $copy->reset();

        return $copy;
    }

    public function reset()
    {
        $this->pages = array();
        $this->currentPage = null;
    }

    public function getPages()
    {
        return $this->pages;
    }

    protected function doDraw(Document $document)
    {
        $this->splitChildrenIntoPages();

        foreach($this->getPages() as $page)
        {
            $tasks = $page->getDrawingTasks($document);

            foreach($tasks as $task)
            {
                $this->addDrawingTask($task);
            }
        }
    }

    private function splitChildrenIntoPages()
    {
        $this->totalTranslation = 0;
        foreach($this->getChildren() as $child)
        {
            $this->splitChildIfNecessary($child);
        }
    }

    private function splitChildIfNecessary(Glyph $glyph)
    {
        $childHasBeenSplitted = false;
        $childMayBeSplitted = true;
        $glyph->translate(0, -$this->totalTranslation);
        
        if($glyph->getPageBreak())
        {
            $pageYCoordEnd = $glyph->getDiagonalPoint()->getY() + 1;
        }
        else
        {
            $pageYCoordEnd = $this->getDiagonalPoint()->getY();
        }
        
        do
        {
            if($this->shouldBeSplited($glyph, $pageYCoordEnd))
            {
                $glyph = $this->splitChildAndGetProductOfSplitting($glyph);
                $childHasBeenSplitted = true;
            }
            else
            {
                if(!$childHasBeenSplitted)
                {
                    $this->getCurrentPage()->add($glyph);
                }

                $childMayBeSplitted = false;
            }
        }
        while($childMayBeSplitted);
    }
    
    private function shouldBeSplited(Glyph $glyph, $pageYCoordEnd)
    {
        $yEnd = $glyph->getDiagonalPoint()->getY();

        return ($yEnd < $pageYCoordEnd);
    }

    /**
     * @return Glyph Product of splitting
     */
    private function splitChildAndGetProductOfSplitting(Glyph $glyph)
    {
        $originalHeight = $glyph->getFirstPoint()->getY() - $glyph->getDiagonalPoint()->getY();
        $glyphYCoordStart = $this->getChildYCoordOfFirstPoint($glyph);
        $splitLine = $glyphYCoordStart - $this->getDiagonalPoint()->getY();
        $splittedGlyph = $glyph->split($splitLine);

        $heightAfterSplit = $glyph->getFirstPoint()->getY() - $glyph->getDiagonalPoint()->getY();

        $gapBeetwenBottomOfOriginalGlyphAndEndOfPage = 0;

        if($splittedGlyph)
        {
            $gapBeetwenBottomOfOriginalGlyphAndEndOfPage = $glyph->getDiagonalPoint()->getY() - $this->getDiagonalPoint()->getY();
            $glyphYCoordStart = $splittedGlyph->getFirstPoint()->getY();
            $this->getCurrentPage()->add($glyph);
            $heightAfterSplit += $splittedGlyph->getFirstPoint()->getY() - $splittedGlyph->getDiagonalPoint()->getY();
            $glyph = $splittedGlyph;
        }

        $translation = $this->getGlyphTranslation($glyph, $glyphYCoordStart);
        $this->createNextPage();

        $this->totalTranslation += $translation - $gapBeetwenBottomOfOriginalGlyphAndEndOfPage;

        $this->addChildrenToCurrentPageAndTranslate($glyph, $translation);

        return $glyph;
    }

    private function getChildYCoordOfFirstPoint(Glyph $glyph)
    {
        $yCoordOfFirstPoint = $glyph->getFirstPoint()->getY();

        return $yCoordOfFirstPoint;
    }

    private function getGlyphTranslation(Glyph $glyph, $glyphYCoordStart)
    {
        $translation = $this->getHeight() + $this->getMarginBottom() - $glyphYCoordStart;

        return $translation;
    }

    private function addChildrenToCurrentPageAndTranslate(Glyph $glyph, $translation)
    {
        $this->getCurrentPage()->add($glyph);
        $glyph->translate(0, -$translation);
    }

    public function getAttribute($name)
    {
        return $this->getPrototypePage()->getAttribute($name);
    }

    public function setAttribute($name, $value)
    {
        foreach($this->pages as $page)
        {
            $page->setAttribute($name, $value);
        }

        return $this->getPrototypePage()->setAttribute($name, $value);
    }

    protected function getAttributeDirectly($name)
    {
        return $this->getPrototypePage()->getAttributeDirectly($name);
    }

    public function getWidth()
    {
        return $this->getPrototypePage()->getWidth();
    }

    public function getHeight()
    {
        return $this->getPrototypePage()->getHeight();
    }

    protected function getHeader()
    {
        return $this->getPrototypePage()->getHeader();
    }

    protected function getFooter()
    {
        return $this->getPrototypePage()->getFooter();
    }

    public function setHeader(Container $header)
    {
        return $this->getPrototypePage()->setHeader($header);
    }

    public function setFooter(Container $footer)
    {
        return $this->getPrototypePage()->setFooter($footer);
    }

    public function preFormat(Document $document)
    {
        $this->getPrototypePage()->prepareTemplate($document);
    }

    public function getDiagonalPoint()
    {
        return $this->getPrototypePage()->getDiagonalPoint();
    }

    public function getFirstPoint()
    {
        return $this->getPrototypePage()->getFirstPoint();
    }
}