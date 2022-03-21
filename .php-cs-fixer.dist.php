<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('tools')
    ->in('src')
;

$config = new PhpCsFixer\Config();
return $config->setRules([
        '@PSR12' => true,
    ])
    ->setFinder($finder)
;
