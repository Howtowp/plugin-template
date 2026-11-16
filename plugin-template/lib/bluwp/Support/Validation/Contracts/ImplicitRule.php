<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Support\Validation\Contracts;

/** Runs for absent attributes and for null values even when nullable is set. */
interface ImplicitRule extends Rule
{
}
