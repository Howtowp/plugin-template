<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\UI\Controllers;

use Howtowp\Bluwp\UI\AppConfig;
use Howtowp\Bluwp\UI\Pages\PageRegistry;
use Howtowp\Bluwp\Http\API\Controller;
use WP_REST_Response;

final class PagesController extends Controller
{
    public function __construct(private PageRegistry $pages, private AppConfig $config)
    {
    }

    public function getPages(): WP_REST_Response
    {
        return $this->respond([
            'app' => $this->config->metadata(),
            'pages' => $this->pages->pages(),
        ]);
    }
}
