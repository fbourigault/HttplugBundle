<?php


namespace Http\HttplugBundle\Collector;

use Http\Client\Common\Plugin;
use Http\Client\Common\PluginClient;
use Http\Client\Common\PluginClientFactory;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Stopwatch\Stopwatch;

final class PluginClientFactorySubscriber implements EventSubscriberInterface
{
    /**
     * @var Collector
     */
    private $collector;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var Stopwatch
     */
    private $stopwatch;

    /**
     * @param Collector $collector
     * @param Formatter $formatter
     * @param Stopwatch $stopwatch
     */
    public function __construct(Collector $collector, Formatter $formatter, Stopwatch $stopwatch)
    {
        $this->collector = $collector;
        $this->formatter = $formatter;
        $this->stopwatch = $stopwatch;
    }

    /**
     * Make sure to use our custom strategy.
     *
     * @param Event $e
     */
    public function onEvent(Event $e)
    {
        PluginClientFactory::setFactory(function ($client, array $plugins, array $options) {
            $plugins = array_map(   function (Plugin $plugin) {
                return new ProfilePlugin($plugin, $this->collector, $this->formatter);
            }, $plugins);

            $clientName = empty($options['client_name']) ? 'Default' : $options['client_name'];
            array_unshift($plugins, new StackPlugin($this->collector, $this->formatter, $clientName));

            if (!$client instanceof ProfileClient) {
                $client = new ProfileClient($client, $this->collector, $this->formatter, $this->stopwatch);
            }

            unset($options['client_name']);

            return new PluginClient($client, $plugins, $options);
        });
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
