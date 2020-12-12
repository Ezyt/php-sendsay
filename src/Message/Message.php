<?php

namespace Ezyt\Sendsay\Message;

class Message implements MessageInterface
{
    /** @var string */
    private $error;
    /** @var array */
    private $data;

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(string $value): MessageInterface
    {
        $this->error = $value;
        return $this;
    }

    public function hasError(): bool
    {
        return $this->error !== null;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setData($value): MessageInterface
    {
        $this->data = (array)$value;
        return $this;
    }
}
