<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;
use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer;

return static function (ECSConfig $ecsConfig): void {

    if (is_file(__DIR__.'/vendor/contao/easy-coding-standard/config/contao.php')) {
        //.github/workflows/ci.yaml
        $ecsConfig->sets([__DIR__.'/vendor/contao/easy-coding-standard/config/contao.php']);
    } else {
        // local development
        $ecsConfig->sets([__DIR__.'/../../../../../vendor/contao/easy-coding-standard/config/contao.php']);
    }

    $ecsConfig->ruleWithConfiguration(HeaderCommentFixer::class, [
        'header' => "This file is part of Calendar Event Booking PayPal.\n\n(c) Marko Cupic ".date('Y')." <m.cupic@gmx.ch>\n@license GPL-3.0-or-later\nFor the full copyright and license information,\nplease view the LICENSE file that was distributed with this source code.\n@link https://github.com/markocupic/sac-event-registration-reminder",
    ]);

    $ecsConfig->skip([
        '*/contao/dca*',
        MethodChainingIndentationFixer::class => [
            'DependencyInjection/Configuration.php',
        ],
    ]);

    $ecsConfig->parallel();
    $ecsConfig->lineEnding("\n");

    $ecsConfig->cacheDirectory(sys_get_temp_dir().'/ecs_default_cache');

};
