<?php

namespace EntelisTeam\Lbaf\Core\Response;

/**
 * @todo вставить поддержку шаблонов
 */
class WebResponse extends AbstractResponse
{

    public function __construct(protected ?string $content, int $httpCode = 200)
    {
        $this
            ->setHttpResponseCode($httpCode)
            ->setHeaders([
                new Header('Content-Type', 'text/html; charset=UTF-8'),
                new Header('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT'),
                new Header('Cache-Control', 'private, no-cache'),
                new Header('Pragma', 'no-cache'),
            ]);
    }

    /**
     * @inheritDoc
     */
    public function pack(): string
    {
        return $this->content ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setContent(mixed $content): self
    {
        $this->content = $content;
        return $this;
    }
}