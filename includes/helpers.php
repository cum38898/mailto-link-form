<?php

defined('ABSPATH') || exit;

/**
 * Normalize a field key into a stable template token key.
 */
function mailto_link_form_normalize_field_key(string $key): string
{
    $key = strtolower(trim($key));
    $key = preg_replace('/[^a-z0-9_]/', '_', $key);
    $key = preg_replace('/_+/', '_', $key);

    return trim((string) $key, '_');
}

/**
 * Generate a default body template from configured fields.
 *
 * @param array<int, array<string, mixed>> $fields
 */
function mailto_link_form_generate_default_template(array $fields): string
{
    $lines = [];

    foreach ($fields as $field) {
        $key = isset($field['key']) ? (string) $field['key'] : '';
        $label = isset($field['label']) ? (string) $field['label'] : $key;

        if ($key === '') {
            continue;
        }

        $lines[] = $label . ': {{' . $key . '}}';
    }

    return implode("\r\n", $lines);
}

/**
 * Replace template placeholders and clear unknown placeholders.
 *
 * @param array<string, string> $values
 */
function mailto_link_form_apply_template(string $template, array $values): string
{
    foreach ($values as $key => $value) {
        $template = str_replace('{{' . $key . '}}', $value, $template);
    }

    return (string) preg_replace('/{{\s*[^}]+\s*}}/', '', $template);
}

/**
 * Runtime bilingual helper for environments without compiled translation files.
 */
function mailto_link_form_i18n(string $en, string $ja): string
{
    $locale = function_exists('determine_locale') ? determine_locale() : get_locale();

    if (strpos((string) $locale, 'ja') === 0) {
        return $ja;
    }

    return $en;
}
