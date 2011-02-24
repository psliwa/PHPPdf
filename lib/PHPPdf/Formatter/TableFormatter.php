<?php

namespace PHPPdf\Formatter;

use PHPPdf\Glyph\Glyph,
    PHPPdf\Glyph\Table;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class TableFormatter extends BaseFormatter
{
    //TODO refactoring
    public function postFormat(Glyph $glyph)
    {
        if($glyph instanceof Table)
        {
            $rows = $glyph->getChildren();

            $cellMaxWidths = array();
            $cellMaxWidthsWidthMargins = array();
            foreach($rows as $row)
            {
                $cells = $row->getChildren();

                foreach($cells as $index => $cell)
                {
                    $width = $cell->getWidth();
                    $widthWidthMargin = $width + $cell->getMarginLeft() + $cell->getMarginRight();

                    if(!isset($cellMaxWidths[$index]) || $cellMaxWidths[$index] < $width)
                    {
                        $cellMaxWidths[$index] = $width;
                    }

                    if(!isset($cellMaxWidthsWidthMargins[$index]) || $cellMaxWidthsWidthMargins[$index] < $widthWidthMargin)
                    {
                        $cellMaxWidthsWidthMargins[$index] = $widthWidthMargin;
                    }
                }
            }

            $widthOfAllCells = array_sum($cellMaxWidths);
            $diff = $glyph->getWidth() - $widthOfAllCells;

            $widthWithMarginOfAllCells = array_sum($cellMaxWidthsWidthMargins);
            $diffWithMargin = $glyph->getWidth() - $widthWithMarginOfAllCells;

            if($diffWithMargin > 0)
            {
                $cellsExpand = $diff/count($cellMaxWidths);

                array_walk($cellMaxWidths, function(&$value, $key) use ($cellsExpand)
                {
                    $value += $cellsExpand;
                });
            }
            else
            {
                $glyph->setWidth($glyph->getWidth()-$diffWithMargin);
                $boundary = $glyph->getBoundary();
                $boundary->pointTranslate(1, -$diffWithMargin, 0);
                $boundary->pointTranslate(2, -$diffWithMargin, 0);

                foreach($glyph->getChildren() as $row)
                {
                    $boundary = $row->getBoundary();
                    $boundary->pointTranslate(1, -$diffWithMargin, 0);
                    $boundary->pointTranslate(2, -$diffWithMargin, 0);
                }
            }

            foreach($rows as $row)
            {
                $cells = $row->getChildren();
                $translate = 0;
                foreach($cells as $index => $cell)
                {
                    $diffInHeight = $row->getHeight() - ($cell->getHeight() + $cell->getMarginTop() + $cell->getMarginBottom());
                    $cellExpand = $cellMaxWidths[$index] - $cell->getWidth();
                    $cell->translate($translate, 0);
                    $boundary = $cell->getBoundary();
                    $boundary->pointTranslate(1, $cellExpand, 0);
                    $boundary->pointTranslate(2, $cellExpand, $diffInHeight);
                    $boundary->pointTranslate(3, 0, $diffInHeight);
                    $cell->setWidth($cell->getWidth() + $cellExpand);
                    $cell->setHeight($cell->getHeight() + $diffInHeight);

                    $translate += $cellExpand;
                }
            }
        }
    }
}