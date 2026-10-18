<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    // ctype_space() and mb_strpos() come from the extension when it is loaded, else from the
    // Symfony polyfill, which is required so that the bundle installs without the extension
    ->ignoreErrorsOnExtensions(['ext-ctype', 'ext-mbstring'], [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnPackages(['symfony/polyfill-ctype', 'symfony/polyfill-mbstring'], [ErrorType::UNUSED_DEPENDENCY])
    // no class of FrameworkBundle is used in src/, but the kernel.event_listener tag that
    // UtmCookieExtension adds to the service only works in an application that registers it
    ->ignoreErrorsOnPackage('symfony/framework-bundle', [ErrorType::PROD_DEPENDENCY_ONLY_IN_DEV]);
