<?php

use Chiron\Views\BladeRenderer;
use Chiron\Views\TemplatePath;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;

class BladeRendererTest extends TestCase
{
    /**
     * @var Factory
     */
    private $bladeEngine;

    protected function setUp()
    {
        // create the Blade Engine
        $filesystem = new Filesystem();
        $resolver = new EngineResolver();

        $cachePath = sys_get_temp_dir();
        $resolver->register(
            'blade',
            function () use ($filesystem, $cachePath) {
                return new CompilerEngine(new BladeCompiler($filesystem, $cachePath));
            }
        );

        $this->bladeEngine = new Factory(
            $resolver,
            new FileViewFinder($filesystem, []),
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

    public function testConstructor()
    {
        $renderer = new BladeRenderer($this->bladeEngine);
        $this->assertInstanceOf(BladeRenderer::class, $renderer);
        $this->assertEmpty($renderer->getPaths());

        $blade = $renderer->blade();
        $this->assertInstanceOf(Factory::class, $blade);
    }

    public function testCanAddPath()
    {
        $renderer = new BladeRenderer($this->bladeEngine);
        $renderer->addPath(__DIR__ . '/Fixtures');
        $paths = $renderer->getPaths();
        $this->assertInternalType('array', $paths);
        $this->assertCount(1, $paths);
        $this->assertTemplatePath(__DIR__ . '/Fixtures', $paths[0]);
        $this->assertTemplatePathString(__DIR__ . '/Fixtures', $paths[0]);
        $this->assertEmptyTemplatePathNamespace($paths[0]);

        return $renderer;
    }

    public function testCanAddPathWithNamespace()
    {
        $renderer = new BladeRenderer($this->bladeEngine);
        $renderer->addPath(__DIR__ . '/Fixtures', 'test');
        $paths = $renderer->getPaths();
        $this->assertInternalType('array', $paths);
        $this->assertCount(1, $paths);
        $this->assertTemplatePath(__DIR__ . '/Fixtures', $paths[0]);
        $this->assertTemplatePathString(__DIR__ . '/Fixtures', $paths[0]);
        $this->assertTemplatePathNamespace('test', $paths[0]);
    }

    public function testDelegatesRenderingToUnderlyingImplementation()
    {
        $renderer = new BladeRenderer($this->bladeEngine);
        $renderer->addPath(__DIR__ . '/Fixtures');
        $result = $renderer->render('testTemplate', ['hello' => 'Hi']);
        $this->assertEquals('Hi', $result);
    }

/*
    public function testTemplateExistsWithExtensionInFileName()
    {
        $renderer = new BladeRenderer($this->bladeEngine);
        $renderer->addPath(__DIR__ . '/Fixtures');
        $result = $renderer->exists('testTemplate.blade.php');
        $this->assertTrue($result);
    }
*/

    public function testTemplateExistsWithoutExtensionInFileName()
    {
        $renderer = new BladeRenderer($this->bladeEngine);
        $renderer->addPath(__DIR__ . '/Fixtures');
        $result = $renderer->exists('testTemplate');
        $this->assertTrue($result);
    }
}
