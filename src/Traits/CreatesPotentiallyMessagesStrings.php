<?php

namespace MB\Messages\Traits;

use MB\Messages\Contracts\MessagesInterface;
use MB\Messages\PotentiallyMessagesString;

trait CreatesPotentiallyMessagesStrings
{
    /**
     * Create a potentially translated string for the given attribute and message.
     * Used when returning from a validation rule's fail() callback.
     *
     * @param  string  $attribute
     * @param  string|null  $message
     * @return PotentiallyMessagesString
     */
    protected function pendingPotentiallyMessagesString(string $attribute, ?string $message = null): PotentiallyMessagesString
    {
        $translator = null;
        if (isset($this->validator) && method_exists($this->validator, 'getTranslator')) {
            $translator = $this->validator->getTranslator();
            if (!$translator instanceof MessagesInterface) {
                $translator = null;
            }
        }

        return new PotentiallyMessagesString(
            $message ?? $attribute,
            $translator,
            $attribute
        );
    }
}
