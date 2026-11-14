<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Support;

use Forouzandea\Bluwp\Contracts\Htmlable;

final class HtmlString implements Htmlable, \Stringable
{
    public function __construct(private string $html = '')
    {
    }
    public function toHtml(): string
    {
        return $this->html;
    }
    public function __toString(): string
    {
        return $this->html;
    }
    public function isEmpty(): bool
    {
        return $this->html === '';
    }
}
