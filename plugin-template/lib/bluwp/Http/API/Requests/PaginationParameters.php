<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\API\Requests;

final class PaginationParameters
{
    /** @return array{page: int, perPage: int}|\WP_Error */
    public static function parse(array $input, int $default = 20, int $maximum = 100): array|\WP_Error
    {
        if ($default < 1 || $maximum < $default) {
            throw new \InvalidArgumentException(__('Invalid pagination limits.', BLUWP));
        }
        $page = $input['page'] ?? 1;
        $perPage = $input['per_page'] ?? $default;
        if ((!is_int($page) && !is_string($page)) || (!is_int($perPage) && !is_string($perPage))) {
            return new \WP_Error('invalid_pagination', __('Page and page size must be integers.', BLUWP), ['status' => 400]);
        }
        $page = filter_var($page, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $perPage = filter_var($perPage, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => $maximum]]);
        if ($page === false || $perPage === false || ($page - 1) > intdiv(PHP_INT_MAX, $perPage)) {
            return new \WP_Error('invalid_pagination', __('Invalid page or page size.', BLUWP), ['status' => 400]);
        }
        return ['page' => $page, 'perPage' => $perPage];
    }
}
