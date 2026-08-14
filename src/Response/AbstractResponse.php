<?php

namespace EntelisTeam\Lbaf\Core\Response;

abstract class AbstractResponse
{

    /**
     * @var Header[]
     */
    protected array $headers = [];

    protected ?int $httpResponseCode = null;

    protected bool $stopSequenceAfterThisResponse = true;

    public final function stopSequenceAfterResponse(): bool
    {
        return $this->stopSequenceAfterThisResponse;

    }
    public final function setStopSequenceAfterResponse(bool $stop): self
    {
        $this->stopSequenceAfterThisResponse = $stop;
        return $this;
    }

    public final function setHttpResponseCode(int $httpResponseCode): self
    {
        $this->httpResponseCode = $httpResponseCode;
        return $this;
    }
    public final function getHttpResponseCode(): ?int
    {
        return $this->httpResponseCode;
    }

    /**
     * @return string данные ответа в виде строки
     */
    abstract public function pack(): string;

    public function unpack(string $input): mixed
    {
        throw new \Exception('unpack not implemented in ' . static::class);
    }

    /**
     * Устанавливает контент для ответа
     * @return self
     */
    abstract public function setContent(mixed $content): self;

    /**
     * @return Header[]
     */
    public final function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Добавляет заголовки
     * @param Header[] $headers
     * @return self
     */
    public final function setHeaders(array $headers): self
    {
        foreach ($headers as $header) {
            $this->setHeader($header);
        }
        return $this;
    }

    /**
     * Добавляет заголовок к отправке
     * @param Header $header
     * @return self
     */
    public final function setHeader(Header $header): self
    {
        $this->headers[$header->title] = $header;
        return $this;
    }

}