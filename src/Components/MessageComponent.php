<?php

namespace App\Components;

use App\Entity\Message;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('message')]
class MessageComponent
{
    public Message $message;
}
