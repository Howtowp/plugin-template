<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\API\Responses;

/** Marks an already enveloped response so the REST boundary does not wrap it twice. */
final class EnvelopedResponse extends \WP_REST_Response
{
}
