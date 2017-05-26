<?php

namespace Http\HttplugBundle\Discovery;

use Http\Client\Common\PluginClientFactory;
use Http\Client\Common\PluginClientFactoryInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ProfilePluginClientFactoryDiscovery implements EventSubscriberInterface
{
    /**
     * @var PluginClientFactoryInterface
     */
    private static $factory;

    /**
     * @param PluginClientFactoryInterface $factory
     */
    public function __construct(PluginClientFactoryInterface $factory)
    {
        self::$factory = $factory;
    }

    /**
     * Make sure to use our custom strategy.
     *
     * @param Event $e
     */
    public function onEvent(Event $e)
    {
        PluginClientFactory::setFactory(self::$factory);
    }

    /**
     * Whenever these events occur we make sure to add our strategy to the discovery.
     *
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'kernel.request' => ['onEvent', 1024],
            'console.command' => ['onEvent', 1024],
        ];
    }
}
