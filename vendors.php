<?php
define('PHPPDF_VENDOR_DIR', __DIR__.'/lib/vendor');

fetchGitVendors();

function fetchGitVendors()
{
    $deps = array(
        array('Markdown', 'git://github.com/wolfie/php-markdown.git', 'd464071334'),
        array('Zend/Pdf', 'git://github.com/psliwa/zend-pdf.git', 'master'),
        array('Zend/Memory', 'git://github.com/KnpLabs/zend-memory.git', 'master'),
        array('Zend/Cache', 'git://github.com/KnpLabs/zend-cache.git', 'master'),
        array('Imagine', 'git://github.com/avalanche123/Imagine.git', 'v0.2.6')
    );
    
    foreach ($deps as $dep) {
        list($name, $url, $rev) = $dep;
    
        echo "> Installing/Updating $name\n";
    
        $installDir = PHPPDF_VENDOR_DIR.'/'.$name;
        if (!is_dir($installDir)) {
            system(sprintf('git clone %s %s', $url, $installDir));
        }
    
        system(sprintf('cd %s && git fetch origin && git reset --hard %s', $installDir, $rev));
    }
}
