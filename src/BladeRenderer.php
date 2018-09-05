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

// TODO : regarder ici pour créer le répertoire du cache si il n'existe pas : https://github.com/arrilot/bitrix-blade/blob/master/src/BladeProvider.php#L165

class BladeRenderer implements TemplateRendererInterface
{
    use AttributesTrait;

    private $engine;

    /**
     * Constructor.
     *
     * @param Factory $renderer Rendering engine
     */
    // TODO : passer dans le constructeur le path du cache, c'est un paramétre obligatoire pour Blade
    public function __construct(Factory $engine = null)
    {
        $this->engine = $engine ?: $this->createBladeEngine();
    }

    /**
     * Create a default Blade engine.
     */
    private function createBladeEngine(): Factory
    {
        $filesystem = new Filesystem();
        $resolver = new EngineResolver();
        $resolver->register(
            'blade',
            function () use ($filesystem) {
                return new CompilerEngine(new BladeCompiler($filesystem, getcwd() . '/cache'));
            }
        );

        return new Factory(
            $resolver,
            new FileViewFinder($filesystem, []),
            new Dispatcher()
        );
    }

    /**
     * Render a template, optionally with parameters.
     *
     * Implementations MUST support the `namespace::template` naming convention,
     * and allow omitting the filename extension.
     *
     * @param string $name
     * @param array  $params
     */
    public function render(string $name, array $params = []): string
    {
        $params = array_merge($this->attributes, $params);

        return $this->engine->make($name, $params)->render();
    }

    /**
     * Add a template path to the engine.
     *
     * Adds a template path, with optional namespace the templates in that path
     * provide.
     */
    public function addPath(string $path, string $namespace = null): void
    {
        if (! $namespace) {
            $this->engine->getFinder()->addLocation($path);

            return;
        }
        $this->engine->getFinder()->addNamespace($namespace, $path);
    }

    /**
     * Get the template directories.
     *
     * @return TemplatePath[]
     */
    public function getPaths(): array
    {
        $templatePaths = [];

        $paths = $this->engine->getFinder()->getPaths();
        $hints = $this->engine->getFinder()->getHints();

        foreach ($paths as $path) {
            $templatePaths[] = new TemplatePath($path);
        }
        foreach ($hints as $namespace => $paths) {
            foreach ($paths as $path) {
                $templatePaths[] = new TemplatePath($path, $namespace);
            }
        }

        return $templatePaths;
    }

    /**
     * Checks if the view exists.
     *
     * @param string $name Full template path or part of a template path
     *
     * @return bool True if the path exists
     */
    public function exists(string $name): bool
    {
        return $this->engine->exists($name);
    }

    /**
     * Sets file extension for template loader.
     *
     * @param string $extension Template files extension
     *
     * @return $this
     */
    // TODO : méthode à virer ???? et donc forcer dans le constructeur d'avoir un objet Factory déjà initialisé avec les bonnes extensions ????
    public function addFileExtension(string $extension): self
    {
        $this->engine->addExtension($extension, 'blade');

        return $this;
    }
}
