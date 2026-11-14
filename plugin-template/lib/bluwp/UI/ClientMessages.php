<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\UI;

final class ClientMessages
{
    public static function messages(): array
    {
        return [
            'configurationError' => __('Invalid admin configuration.', BLUWP),
            'networkError' => __('The server could not be reached.', BLUWP),
            'invalidEndpoint' => __('Invalid request endpoint.', BLUWP),
            'invalidSchema' => __('Invalid page schema.', BLUWP),
            'loadError' => __('The page could not be loaded.', BLUWP),
            'noPages' => __('No pages are available.', BLUWP),
            'pagesError' => __('The page list could not be loaded.', BLUWP),
            'sections' => __('Sections', BLUWP),
            'loading' => __('Loading...', BLUWP),
            'retry' => __('Try again', BLUWP),
            'dismiss' => __('Dismiss notification', BLUWP),
            'saved' => __('Changes saved.', BLUWP),
            'saveError' => __('Changes could not be saved.', BLUWP),
            'unsupportedField' => __('Unsupported field type.', BLUWP),
            'unsupportedPage' => __('Unsupported page type.', BLUWP),
            'saving' => __('Saving...', BLUWP),
            'save' => __('Save', BLUWP),
            'choose' => __('Choose an option', BLUWP),
            'noResults' => __('No results found', BLUWP),
            'noOptions' => __('No options available', BLUWP),
            'required' => __('This field is required.', BLUWP),
            'reviewFields' => __('Please correct the errors in the form.', BLUWP),
            'back' => __('Back', BLUWP),
            'previous' => __('Previous', BLUWP),
            'next' => __('Next', BLUWP),
            'pagination' => __('Pagination', BLUWP),
            'delete' => __('Delete', BLUWP),
            'addRow' => __('Add row', BLUWP),
            'deleteSelected' => __('Delete selected', BLUWP),
            'cancel' => __('Cancel', BLUWP),
            'deleting' => __('Deleting...', BLUWP),
            'deleteEntity' => __('Delete {item}', BLUWP),
            'deleteSelectedEntities' => __('Delete selected {items}', BLUWP),
            'deleteOneMessage' => __('Are you sure you want to delete this {item}? This action cannot be undone.', BLUWP),
            'deleteManyMessage' => __('Are you sure you want to delete {count} selected {items}? This action cannot be undone.', BLUWP),
        ];
    }

    public static function refreshSettings(): string
    {
        return __('Refresh settings', BLUWP);
    }

    public static function settingsRefreshed(): string
    {
        return __('Settings refreshed.', BLUWP);
    }
}
