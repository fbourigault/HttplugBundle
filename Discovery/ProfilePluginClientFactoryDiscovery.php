<?php


namespace Http\HttplugBundle\Discovery;

use Http\Client\Common\PluginClientFactoryInterface;
use Http\Discovery\Exception\StrategyUnavailableException;
use Http\Discovery\PluginClientFactoryDiscovery;
use Http\Discovery\Strategy\DiscoveryStrategy;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ProfilePluginClientFactoryDiscovery implements DiscoveryStrategy, EventSubscriberInterface
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
     * Find a resource of a specific type.
     *
     * @param string $type
     *
     * @return array The return value is always an array with zero or more elements. Each
     *               element is an array with two keys ['class' => string, 'condition' => mixed].
     *
     * @throws StrategyUnavailableException if we cannot use this strategy.
     */
    public static function getCandidates($type)
    {
        if ($type === PluginClientFactoryInterface::class && self::$factory !== null) {
            return [['class' => function () {
                return self::$factory;
            }]];
        }

        return [];
    }

    /**
     * Make sure to use our custom strategy.
     *
     * @param Event $e
     */
    public function onEvent(Event $e)
    {
        PluginClientFactoryDiscovery::prependStrategy(self::class);
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
