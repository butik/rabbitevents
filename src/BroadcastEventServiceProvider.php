<?php

namespace Butik\Events;

use Butik\Events\Console\EventsListCommand;
use Butik\Events\Console\ListenCommand;
use Butik\Events\Facades\BroadcastEvent;
use Illuminate\Contracts\Queue\Factory as QueueFactoryContract;
use Illuminate\Support\ServiceProvider;
use Interop\Amqp\AmqpTopic;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrTopic;

class BroadcastEventServiceProvider extends ServiceProvider
{
    protected $listen = [];

    private $exchangeName = 'events';

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ListenCommand::class,
                EventsListCommand::class,
            ]);

            foreach ($this->listen as $event => $listeners) {
                foreach ($listeners as $listener) {
                    BroadcastEvent::listen($event, $listener);
                }
            }
        }
    }

    public function register()
    {
        $this->registerBroadcastEvents();
        $this->registerQueueContext();
        $this->registerPsrTopic();
        $this->registerMessageFactory();
        $this->registerEventProducer();
    }

    protected function registerBroadcastEvents()
    {
        $this->app->singleton('broadcast.events', function ($app) {
            return (new Dispatcher($app))
                ->setQueueResolver(function () use ($app) {
                    return $app->make(QueueFactoryContract::class);
                });
        });
    }

    protected function registerQueueContext()
    {
        $this->app->singleton(PsrContext::class, function ($app) {
            return $app['queue']->connection()->getPsrContext();
        });
    }

    protected function registerPsrTopic()
    {
        $this->app->singleton(PsrTopic::class, function ($app) {
            $context = $app->make(PsrContext::class);

            $topic = $context->createTopic($this->exchangeName);
            $topic->setType(AmqpTopic::TYPE_TOPIC);
            $topic->addFlag(AmqpTopic::FLAG_DURABLE);

            $context->declareTopic($topic);

            return $topic;
        });
    }

    protected function registerMessageFactory()
    {
        $this->app->singleton(MessageFactory::class);
    }

    protected function registerEventProducer()
    {
        $this->app->singleton(BroadcastFactory::class);
    }
}
