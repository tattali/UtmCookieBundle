<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    // ctype_space() comes from the extension when it is loaded, else from the Symfony polyfill,
    // which is required so that the bundle installs without the extension
    ->ignoreErrorsOnExtension('ext-ctype', [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnPackage('symfony/polyfill-ctype', [ErrorType::UNUSED_DEPENDENCY]);
