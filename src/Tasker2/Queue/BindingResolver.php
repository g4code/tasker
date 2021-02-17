<?php

namespace G4\Tasker\Tasker2\Queue;

use G4\Tasker\Consts;
use G4\Tasker\Tasker2\MessageOptions;
use G4\ValueObject\Dictionary;
use G4\ValueObject\StringLiteral;

class BindingResolver
{
    /**
     * @var MessageOptions
     */
    private $messageOptions;

    /**
     * BindingResolver constructor.
     * @param MessageOptions $messageOptions
     */
    public function __construct(MessageOptions $messageOptions)
    {
        $this->messageOptions = $messageOptions;
    }

    /**
     * @param Dictionary $messageBody
     * @param StringLiteral|null $task
     * @return string
     */
    public function resolve(Dictionary $messageBody, StringLiteral $task = null)
    {
        $additionalBindings = $this->messageOptions->getAdditionalBindings();
        if ($task instanceof StringLiteral
            && $additionalBindings instanceof Dictionary
            && $additionalBindings->has((string) $task)
        ) {
            return $additionalBindings->get((string) $task);
        }

        if ($this->messageOptions->hasBindingHP()
            && $messageBody->has(Consts::PARAM_PRIORITY)
            && ($messageBody->get(Consts::PARAM_PRIORITY) > Consts::PRIORITY_50)
        ) {
            return $this->messageOptions->getBindingHP();
        }

        return $this->messageOptions->getBinding();
    }
}