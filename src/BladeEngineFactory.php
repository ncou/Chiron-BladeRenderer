<?php

declare(strict_types=1);

namespace Chiron\Views;

use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Psr\Container\ContainerInterface;

class BladeEngineFactory
{
    public function __invoke(ContainerInterface $container): Factory
    {
        $filesystem = new Filesystem();
        $resolver = new EngineResolver();

        // TODO : retrouver le cache path dans le container : https://github.com/harikt/blade-renderer/blob/master/src/BladeViewFactory.php#L37
        $cachePath = sys_get_temp_dir();
        $resolver->register(
            'blade',
            function () use ($filesystem, $cachePath) {
                return new CompilerEngine(new BladeCompiler($filesystem, $cachePath));
            }
        );

        return new Factory(
            $resolver,
            new FileViewFinder($filesystem, []),
            new Dispatcher()
        );
    }
}
