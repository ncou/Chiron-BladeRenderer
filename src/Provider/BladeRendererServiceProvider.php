<?php

namespace Chiron\Views\Provider;

use Chiron\Views\BladeEngineFactory;
use Chiron\Views\BladeRenderer;
use Chiron\Views\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class BladeRendererServiceProvider
{
    /**
     * You should have in your container the config informations using the following structure :.
     *
     * 'templates' => [
     *     'extension' => 'file extension used by templates; defaults to html',
     *     'paths' => [
     *         // namespace / path pairs
     *         //
     *         // Numeric namespaces imply the default/main namespace. Paths may be
     *         // strings or arrays of string paths to associate with the namespace.
     *     ],
     * ],
     */
    public function register(ContainerInterface $container)
    {
        // add default config settings if not already presents in the container.
        if (! $container->has('templates')) {
            $container['templates'] = [
                'extension' => 'html',
                'paths'     => [],
            ];
        }

        // *** Factories ***
        $container[BladeEngineFactory::class] = function ($c) {
            return call_user_func(new BladeEngineFactory(), $c);
        };

        $container[BladeRenderer::class] = function ($c) {
            // init the blade engine and instanciate the renderer using this engine.
            $blade = $c->get(BladeEngineFactory::class);
            $renderer = new BladeRenderer($blade);
            // grab the config settings in the container.
            $config = $c->get('templates');
            // Add template file extension.
            $renderer->setExtension($config['extension']);
            // Add template paths.
            $allPaths = isset($config['paths']) && is_array($config['paths']) ? $config['paths'] : [];
            foreach ($allPaths as $namespace => $paths) {
                $namespace = is_numeric($namespace) ? null : $namespace;
                foreach ((array) $paths as $path) {
                    $renderer->addPath($path, $namespace);
                }
            }

            return $renderer;
        };

        // *** Alias ***
        $container[TemplateRendererInterface::class] = function ($c) {
            return $c->get(BladeRenderer::class);
        };
    }
}
