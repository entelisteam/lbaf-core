<?php

namespace EntelisTeam\Lbaf\Core\Response;

use EntelisTeam\Lbaf\Core\Packer\PackerInterface;

class ApiResponse extends AbstractResponse
{

    public function __construct(protected PackerInterface $packer, protected mixed $content, int $httpCode = 200)
    {
        $this
            ->setHttpResponseCode($httpCode)
            ->setHeaders([
                new Header('Content-Type', 'text/html; charset=UTF-8'),
                new Header('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT'),
                new Header('Cache-Control', 'private, no-cache'),
                new Header('Pragma', 'no-cache'),
            ])
            ->setHeaders($this->packer->getHeaders());
    }

    /**
     * @inheritDoc
     */
    public function pack(): string
    {
        return $this->packer->pack($this->content);
    }

    public function unpack(string $input): mixed
    {
        return $this->packer->unpack($input);
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
