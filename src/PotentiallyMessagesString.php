<?php

namespace MB\Messages;

use MB\Messages\Contracts\MessagesInterface;
use Stringable;

class PotentiallyMessagesString implements Stringable
{
    public function __construct(
        protected string $message,
        protected ?MessagesInterface $messages = null,
        protected ?string $attribute = null,
    ) {}

    public function __toString(): string
    {
        if ($this->messages !== null) {
            $key = $this->message;
            if (str_contains($key, ' ')) {
                return $this->message;
            }
            $value = $this->messages->get($key);
            if ($value !== $key) {
                return $value;
            }
        }
        return $this->message;
    }
}
