<?php

declare(strict_types=1);

namespace Chiron\Views\Tests;

use Chiron\Container\Container;
use Chiron\Views\BladeRenderer;
use Chiron\Views\Provider\BladeRendererServiceProvider;
use Chiron\Views\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;

class BladeRendererServiceProviderTest extends TestCase
{
    public function testWithoutTemplatesSettingsInTheContainer()
    {
        $c = new Container();
        (new BladeRendererServiceProvider())->register($c);

        $renderer = $c->get(BladeRenderer::class);
        $this->assertInstanceOf(BladeRenderer::class, $renderer);
        $this->assertEmpty($renderer->getPaths());

        $this->assertEquals($renderer->getExtension(), 'html');

        // test the instance using the container alias
        $alias = $c->get(TemplateRendererInterface::class);
        $this->assertInstanceOf(BladeRenderer::class, $alias);
    }

    public function testWithTemplatesSettingsInTheContainer()
    {
        $c = new Container();
        $c['templates'] = ['extension' => 'blade.php', 'paths'     => ['foobar' => '/', 'tests/']];
        (new BladeRendererServiceProvider())->register($c);

        $renderer = $c->get(BladeRenderer::class);
        $this->assertInstanceOf(BladeRenderer::class, $renderer);
        $this->assertNotEmpty($renderer->getPaths());

        $this->assertEquals($renderer->getExtension(), 'blade.php');

        $paths = $renderer->getPaths();

        $this->assertEquals($paths[0]->getNamespace(), 'foobar');
        $this->assertEquals($paths[0]->getPath(), '/');

        $this->assertNull($paths[1]->getNamespace());
        $this->assertEquals($paths[1]->getPath(), 'tests/');
    }
}
