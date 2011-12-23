<?php

namespace PHPPdf\Bridge\Markdown;

use PHPPdf\Exception\RuntimeException;
use PHPPdf\Parser\Parser;

class MarkdownParser implements Parser
{
    public function __construct()
    {
        if(!function_exists('Markdown'))
        {
            $markdownPath = __DIR__.'/../../../vendor/Markdown/markdown.php';
            if(file_exists($markdownPath))
            {
                require_once $markdownPath;
            }
            else
            {
                throw new RuntimeException('PHP Markdown library not found. Maybe you should call "> php vendors.php" command from root dir of PHPPdf library to download dependencies?');
            }
        }
    }
    
    public function parse($markdown)
    {
        return \Markdown($markdown);
    }
}