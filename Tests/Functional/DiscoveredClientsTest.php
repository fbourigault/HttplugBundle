<?php

namespace Http\HttplugBundle\Tests\Functional;

use Http\Client\Common\PluginClient;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\HttplugBundle\Collector\PluginClientFactorySubscriber;
use Http\HttplugBundle\Collector\StackPlugin;
use Nyholm\NSA;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class DiscoveredClientsTest extends WebTestCase
{
    public function testDiscoveredClient()
    {
        $container = $this->getContainer(false);

        $this->assertTrue($container->has('httplug.auto_discovery.auto_discovered_client'));

        $service = $container->get('httplug.auto_discovery.auto_discovered_client');

        $this->assertInstanceOf(PluginClient::class, $service);
        $this->assertInstanceOf(HttpClient::class, NSA::getProperty($service, 'client'));
        $this->assertEmpty(NSA::getProperty($service, 'plugins'));
    }

    public function testDiscoveredAsyncClient()
    {
        $container = $this->getContainer(false);

        $this->assertTrue($container->has('httplug.auto_discovery.auto_discovered_async'));

        $service = $container->get('httplug.auto_discovery.auto_discovered_async');

        $this->assertInstanceOf(PluginClient::class, $service);
        $this->assertInstanceOf(HttpAsyncClient::class, NSA::getProperty($service, 'client'));
        $this->assertEmpty(NSA::getProperty($service, 'plugins'));
    }

    public function testDiscoveredClientWithProfilingEnabled()
    {
        $container = $this->getContainer(true);

        $this->assertTrue($container->has('httplug.auto_discovery.auto_discovered_client'));

        $service = $container->get('httplug.auto_discovery.auto_discovered_client');

        var_dump($service);

        $this->assertInstanceOf(PluginClient::class, $service);
        $this->assertInstanceOf(HttpClient::class, NSA::getProperty($service, 'client'));

        $plugins = NSA::getProperty($service, 'plugins');
        $this->assertCount(1, $plugins);
        $this->assertInstanceOf(StackPlugin::class, $plugins[0]);
        $this->assertEquals('auto_discovered_client', NSA::getProperty($plugins[0], 'client'));
    }

    public function testDiscoveredAsyncClientWithProfilingEnabled()
    {
        $container = $this->getContainer(true);

        $this->assertTrue($container->has('httplug.auto_discovery.auto_discovered_async'));

        $service = $container->get('httplug.auto_discovery.auto_discovered_async');

        $this->assertInstanceOf(PluginClient::class, $service);
        $this->assertInstanceOf(HttpAsyncClient::class, NSA::getProperty($service, 'client'));

        $plugins = NSA::getProperty($service, 'plugins');
        $this->assertCount(1, $plugins);
        $this->assertInstanceOf(StackPlugin::class, $plugins[0]);
        $this->assertEquals('auto_discovered_async', NSA::getProperty($plugins[0], 'client'));
    }

    public function testDisabledDiscovery()
    {
        $container = $this->getContainer(true, 'discovery_disabled');

        $this->assertFalse($container->has('httplug.auto_discovery.auto_discovered_client'));
        $this->assertFalse($container->has('httplug.auto_discovery.auto_discovered_async'));
        $this->assertFalse($container->has('httplug.strategy'));
    }

    public function testForcedDiscovery()
    {
        $container = $this->getContainer(true, 'discovery_forced');

        $this->assertFalse($container->has('httplug.auto_discovery.auto_discovered_client'));
        $this->assertFalse($container->has('httplug.auto_discovery.auto_discovered_async'));
        $this->assertTrue($container->has('httplug.strategy'));

        $strategy = $container->get('httplug.strategy');

        $this->assertEquals($container->get('httplug.client.acme'), NSA::getProperty($strategy, 'client'));
        $this->assertEquals($container->get('httplug.client.acme'), NSA::getProperty($strategy, 'asyncClient'));
    }

    private function getContainer($debug, $environment = 'test')
    {
        static::bootKernel(['debug' => $debug, 'environment' => $environment]);

        $container = static::$kernel->getContainer();

        //As we won't be running neither a console command nor an HttpFoundation request, we are faking a kernel.request
        //event to get the PluginClientFactorySubscriber register it's callable against PluginClientFactory.
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $container->get('event_dispatcher');
        $dispatcher->dispatch(KernelEvents::REQUEST, new GetResponseEvent(static::$kernel, new Request(), HttpKernelInterface::MASTER_REQUEST));

        return $container;
    }
}
