<?php

$vendorDir = __DIR__.'/lib/vendor';

$deps = array(
    array('Symfony/Component/DependencyInjection', 'git://github.com/symfony/DependencyInjection.git', 'master'),
    array('Symfony/Component/Config', 'git://github.com/symfony/Config.git', 'master'),
);

foreach ($deps as $dep) {
    list($name, $url, $rev) = $dep;

    echo "> Installing/Updating $name\n";

    $installDir = $vendorDir.'/'.$name;
    if (!is_dir($installDir)) {
        system(sprintf('git clone %s %s', $url, $installDir));
    }

    system(sprintf('cd %s && git fetch origin && git reset --hard %s', $installDir, $rev));
}