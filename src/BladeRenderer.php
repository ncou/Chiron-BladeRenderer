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
    use ExtensionTrait;

    /**
     * @var Factory
     */
    private $blade;

    /**
     * Constructor.
     *
     * @param Factory $blade Rendering engine
     */
    // TODO : passer dans le constructeur le path du cache, c'est un paramétre obligatoire pour Blade
    public function __construct(Factory $blade)
    {
        $this->blade = $blade;
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

        return $this->blade->make($name, $params)->render();
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
            $this->blade->getFinder()->addLocation($path);

            return;
        }
        $this->blade->getFinder()->addNamespace($namespace, $path);
    }

    /**
     * Get the template directories.
     *
     * @return TemplatePath[]
     */
    public function getPaths(): array
    {
        $templatePaths = [];

        $paths = $this->blade->getFinder()->getPaths();
        $hints = $this->blade->getFinder()->getHints();

        foreach ($paths as $path) {
            $templatePaths[] = new TemplatePath($path);
        }
        foreach ($hints as $namespace => $paths) {
            foreach ($paths as $path) {
                $templatePaths[] = new TemplatePath($path, $namespace);
            }
        }

        return array_reverse($templatePaths);
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
        return $this->blade->exists($name);
    }

    /**
     * Sets file extension for template loader.
     *
     * @param string $extension Template files extension
     *
     * @return $this
     */
    public function setFileExtension(string $extension): self
    {
        $this->extension = $extension;
        // TODO : attention cette méthode va ajouter plusieurs extension dans un tableau, dans notre cas on veux une seule extension, voir comment vider ce tableau d'extensions avant d'ajouter la string $extension.
        $this->blade->addExtension($extension, 'blade');

        return $this;
    }

    /**
     * Return the Blade Engine.
     */
    public function blade(): Factory
    {
        return $this->blade;
    }
}
