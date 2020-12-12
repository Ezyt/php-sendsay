<?php

namespace Ezyt\Sendsay\Message;

interface MessageInterface
{
    public function getError(): ?string;

    public function setError(string $value): self;

    public function hasError(): bool;

    public function getData(): ?array;

    /**
     * @param mixed $value
     * @return $this
     */
    public function setData($value): self;
}
