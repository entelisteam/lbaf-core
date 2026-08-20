<?php

namespace EntelisTeam\Lbaf\Core\Response;

class RedirectResponse extends AbstractResponse
{

    public function __construct(string $url, int $code = 302)
    {
        $this
            ->setHttpResponseCode($code)
            ->setHeader( new Header('Location', $url))
            ->setStopSequenceAfterResponse(true);
    }

    /**
     * @inheritDoc
     */
    public function pack(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function setContent(mixed $content): AbstractResponse
    {
       return $this;
    }
}
