<?php

namespace LaravelWebauthn\Events;

use Illuminate\Contracts\Events\Dispatcher;
use Psr\EventDispatcher\EventDispatcherInterface;

final class EventDispatcher implements EventDispatcherInterface
{
    /**
     * Create a new event dispatcher instance.
     *
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly Dispatcher $dispatcher,
    ) {}

    /**
     * Dispatch the given event.
     *
     * @return object
     */
    #[\Override]
    public function dispatch(object $event)
    {
        $this->dispatcher->dispatch($event);

        return $event;
    }
}
