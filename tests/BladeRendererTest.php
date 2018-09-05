<?php

use Chiron\Views\BladeRenderer;
use Chiron\Views\TemplatePath;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use PHPUnit\Framework\TestCase;

class BladeRendererTest extends TestCase
{
    /**
     * @var Factory
     */
    private $bladeEngine;

    protected function setUp()
    {
        // initialise engine even if it's not really used for the tests, because we don't render using the engine, we always render with the default engine initialised inside the BladeRenderer constructor.
        $this->bladeEngine = new Factory(
            new EngineResolver(),
            new FileViewFinder(new Filesystem(), []),
            new Dispatcher()
        );
    }

    public function assertTemplatePath($path, TemplatePath $templatePath, $message = null)
    {
        $message = $message ?: sprintf('Failed to assert TemplatePath contained path %s', $path);
        $this->assertEquals($path, $templatePath->getPath(), $message);
    }

    public function assertTemplatePathString($path, TemplatePath $templatePath, $message = null)
    {
        $message = $message ?: sprintf('Failed to assert TemplatePath casts to string path %s', $path);
        $this->assertEquals($path, (string) $templatePath, $message);
    }

    public function assertTemplatePathNamespace($namespace, TemplatePath $templatePath, $message = null)
    {
        $message = $message
            ?: sprintf('Failed to assert TemplatePath namespace matched %s', var_export($namespace, true));
        $this->assertEquals($namespace, $templatePath->getNamespace(), $message);
    }

    public function assertEmptyTemplatePathNamespace(TemplatePath $templatePath, $message = null)
    {
        $message = $message ?: 'Failed to assert TemplatePath namespace was empty';
        $this->assertEmpty($templatePath->getNamespace(), $message);
    }

    public function assertEqualTemplatePath(TemplatePath $expected, TemplatePath $received, $message = null)
    {
        $message = $message ?: 'Failed to assert TemplatePaths are equal';
        if ($expected->getPath() !== $received->getPath()
            || $expected->getNamespace() !== $received->getNamespace()
        ) {
            $this->fail($message);
        }
    }

    public function testCanProvideEngineAtInstantiation()
    {
        $renderer = new BladeRenderer($this->bladeEngine);
        $this->assertInstanceOf(BladeRenderer::class, $renderer);
        $this->assertEmpty($renderer->getPaths());
    }

    public function testLazyLoadsEngineAtInstantiationIfNoneProvided()
    {
        $renderer = new BladeRenderer();
        $this->assertInstanceOf(BladeRenderer::class, $renderer);
        $this->assertEmpty($renderer->getPaths());
    }

    public function testCanAddPath()
    {
        $renderer = new BladeRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $paths = $renderer->getPaths();
        $this->assertInternalType('array', $paths);
        $this->assertCount(1, $paths);
        $this->assertTemplatePath(__DIR__ . '/TestAsset', $paths[0]);
        $this->assertTemplatePathString(__DIR__ . '/TestAsset', $paths[0]);
        $this->assertEmptyTemplatePathNamespace($paths[0]);

        return $renderer;
    }

    public function testCanAddPathWithNamespace()
    {
        $renderer = new BladeRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset', 'test');
        $paths = $renderer->getPaths();
        $this->assertInternalType('array', $paths);
        $this->assertCount(1, $paths);
        $this->assertTemplatePath(__DIR__ . '/TestAsset', $paths[0]);
        $this->assertTemplatePathString(__DIR__ . '/TestAsset', $paths[0]);
        $this->assertTemplatePathNamespace('test', $paths[0]);
    }

    public function testDelegatesRenderingToUnderlyingImplementation()
    {
        $renderer = new BladeRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $result = $renderer->render('testTemplate', ['hello' => 'Hi']);
        $this->assertEquals('Hi', $result);
    }
}
