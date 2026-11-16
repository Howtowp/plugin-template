<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\UI\Views;

use Howtowp\Bluwp\UI\Pages\PageRegistry;

final class AppView
{
    public function __construct(private string $id, private PageRegistry $pages, private AppViewConfiguration $configuration)
    {
    }

    public function render(string $page): void
    {
        if (!$this->pages->canView($page)) {
            wp_die(esc_html__('You cannot access this page.', BLUWP), '', ['response' => 403]);
        }
        $config = $this->configuration->forPage($page);
        echo '<div id="' . esc_attr($this->id) . '" data-bluwp-admin="' . esc_attr($config['appId'])
            . '" data-config="' . esc_attr(wp_json_encode($config)) . '"></div>';
    }
}
