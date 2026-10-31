<?php

$finder = new TwigCsFixer\File\Finder();
$finder->in(array_values(array_filter(
    [__DIR__.'/templates', __DIR__.'/tests/Application/templates'],
    is_dir(...),
)));

$ruleset = new TwigCsFixer\Ruleset\Ruleset();
$ruleset->addStandard(new TwigCsFixer\Standard\TwigCsFixer());

$config = new TwigCsFixer\Config\Config();
$config->setRuleset($ruleset);
$config->setFinder($finder);

return $config;
