<?php


namespace Http\HttplugBundle\Collector;

use Http\Client\Common\Plugin;
use Http\Client\Common\PluginClient;
use Http\Client\Common\PluginClientFactoryInterface;
use Symfony\Component\Stopwatch\Stopwatch;

final class ProfilePluginClientFactory implements PluginClientFactoryInterface
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
     * {@inheritdoc}
     */
    public function createClient($client, array $plugins = [], array $options = [])
    {
        $plugins = array_map(function (Plugin $plugin) {
            return new ProfilePlugin($plugin, $this->collector, $this->formatter, get_class($plugin));
        }, $plugins);

        array_unshift($plugins, new StackPlugin($this->collector, $this->formatter, 'GitHub'));

        if (!$client instanceof ProfileClient) {
            $client = new ProfileClient($client, $this->collector, $this->formatter, $this->stopwatch);
        }

        return new PluginClient($client, $plugins, $options);
    }
}
