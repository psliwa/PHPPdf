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
        $prototype = $prototype ? $prototype : new Page();
        $this->setPrototypePage($prototype);

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
        $originalPageEnd = $this->getBoundary()->getDiagonalPoint()->getY();
        $this->totalTranslation = 0;
        foreach($this->getChildren() as $child)
        {
            $this->splitChildIfNecessary($child, $originalPageEnd);
        }
    }

    private function splitChildIfNecessary(Glyph $glyph, $pageEndYCoord)
    {
        $originalChild = $glyph;
        $glyph->translate(0, -$this->totalTranslation);

        $translation = 0;

        if($glyph->getPageBreak())
        {
            $glyphEndYCoord = $glyph->getBoundary()->getDiagonalPoint()->getY();
            $translation += $pageEndYCoord - $glyphEndYCoord;
            $pageEndYCoord = $glyphEndYCoord + 1;
        }

        while($glyph)
        {
            $yStart = $glyph->getBoundary()->getFirstPoint()->getY();
            $glyphEndYCoord = $glyph->getBoundary()->getDiagonalPoint()->getY();

            if($glyphEndYCoord < $pageEndYCoord)
            {
                $splitLine = $translation + $yStart - $this->getMarginBottom();
                $glyph = $this->splitChild($glyph, $splitLine, $translation);

                $translation = 0;
                $originalChild = null;
            }
            else
            {
                $glyph = null;
                if($originalChild)
                {
                    $this->getCurrentPage()->add($originalChild);
                    $originalChild->translate(0, -$translation);
                    $originalChild = null;
                }
            }
        }
    }

    private function splitChild(Glyph $glyph, $splitLine, $translation)
    {
        $yStart = $glyph->getBoundary()->getFirstPoint()->getY();
        $splitedGlyph = $splitLine > 0 ? $glyph->split($splitLine) : null;
        $currentPageHeight = $this->getHeight() + $this->getMarginBottom();

        if($splitedGlyph)
        {
            $yStart = $splitedGlyph->getBoundary()->getFirstPoint()->getY();
            $this->getCurrentPage()->add($glyph);
            $glyph->translate(0, -$translation);
            $glyph = $splitedGlyph;
        }

        $translation += $currentPageHeight - ($translation + $yStart);
        $this->createNextPage();

        $this->getCurrentPage()->add($glyph);
        $glyph->translate(0, -$translation);

        $this->totalTranslation += $translation;

        return $glyph;
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