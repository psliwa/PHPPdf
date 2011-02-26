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
        
        do
        {
            if($this->shouldBeSplited($glyph))
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
    
    private function shouldBeSplited(Glyph $glyph)
    {
        $yEnd = $glyph->getBoundary()->getDiagonalPoint()->getY();
        $pageEnd = $this->getBoundary()->getDiagonalPoint()->getY();

        return $yEnd < $pageEnd;
    }

    private function splitChildAndGetProductOfSplitting(Glyph $glyph)
    {
        $glyphYCoordStart = $glyph->getBoundary()->getFirstPoint()->getY();
        $splitLine = $glyphYCoordStart - $this->getMarginBottom();
        $splittedGlyph = $glyph->split($splitLine);

        if($splittedGlyph)
        {
            $glyphYCoordStart = $splittedGlyph->getBoundary()->getFirstPoint()->getY();
            $this->getCurrentPage()->add($glyph);
            $glyph = $splittedGlyph;
        }

        $translation = $this->getHeight() + $this->getMarginBottom() - $glyphYCoordStart;
        $this->createNextPage();

        $this->totalTranslation += $translation;

        $this->addChildrenToCurrentPageAndTranslate($glyph, $translation);

        return $glyph;
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
}