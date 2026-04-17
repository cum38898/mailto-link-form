<?php

defined('ABSPATH') || exit;

/**
 * Normalize a field key into a stable template token key.
 */
function mailto_link_form_normalize_field_key(string $key): string
{
    $key = trim($key);
    $key = preg_replace('/[{}]+/u', '', $key);
    $key = preg_replace('/[^\p{L}\p{N}_-]+/u', '_', $key);
    $key = preg_replace('/_+/u', '_', $key);

    return trim((string) $key, '_');
}

/**
 * @return array<int, string>
 */
function mailto_link_form_get_supported_field_types(): array
{
    return ['select', 'textarea', 'text', 'checkbox'];
}

function mailto_link_form_get_field_type(array $field): string
{
    $type = isset($field['type']) ? (string) $field['type'] : 'select';

    return in_array($type, mailto_link_form_get_supported_field_types(), true) ? $type : 'select';
}

/**
 * @param array<string, mixed> $field
 * @return array<string, mixed>
 */
function mailto_link_form_normalize_field(array $field): array
{
    $type = mailto_link_form_get_field_type($field);
    $normalized = [
        'type' => $type,
        'key' => isset($field['key']) ? mailto_link_form_normalize_field_key((string) $field['key']) : '',
        'label' => isset($field['label']) ? sanitize_text_field((string) $field['label']) : '',
        'required' => !empty($field['required']),
    ];

    if ($type === 'select') {
        $options = isset($field['options']) && is_array($field['options']) ? $field['options'] : [];
        $cleanOptions = [];
        foreach ($options as $option) {
            $clean = sanitize_text_field(trim((string) $option));
            if ($clean !== '') {
                $cleanOptions[] = $clean;
            }
        }

        $normalized['options'] = array_values(array_unique($cleanOptions));
        $normalized['value'] = '';

        return $normalized;
    }

    $normalized['value'] = isset($field['value']) ? sanitize_text_field((string) $field['value']) : '';
    $normalized['options'] = [];

    return $normalized;
}

function mailto_link_form_build_template_token(string $key): string
{
    $normalized = mailto_link_form_normalize_field_key($key);

    return $normalized === '' ? '' : '{{' . $normalized . '}}';
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

/**
 * Default settings shown only on a brand-new unsaved form.
 *
 * @return array{subject:string, body:string, fields:array<int, array<string, mixed>>}
 */
function mailto_link_form_get_default_new_form_values(): array
{
    if (strpos((string) (function_exists('determine_locale') ? determine_locale() : get_locale()), 'ja') === 0) {
        return [
            'subject' => '{{問い合わせ種別}}',
            'body' => "サンプル株式会社 メール受付担当\r\n\r\n（ご用件をこちらにご記入ください）\r\n\r\n--------------------------------------------------\r\n・問い合わせ種別：{{問い合わせ種別}}",
            'fields' => [
                [
                    'type' => 'select',
                    'key' => '問い合わせ種別',
                    'label' => '問い合わせ種別',
                    'options' => ['取材について', '求人について', '商品の発送について', '返品対応について', 'その他'],
                    'required' => false,
                ],
            ],
        ];
    }

    return [
        'subject' => '{{inquiry_type}}',
        'body' => "Sample Inc. Mail Desk\r\n\r\n(Please describe your request here)\r\n\r\n--------------------------------------------------\r\n- Inquiry Type: {{inquiry_type}}",
        'fields' => [
            [
                'type' => 'select',
                'key' => 'inquiry_type',
                'label' => 'Inquiry Type',
                'options' => ['Press Inquiry', 'Careers', 'Shipping Question', 'Returns', 'Other'],
                'required' => false,
            ],
        ],
    ];
}

/**
 * Determine whether a field can be rendered on the frontend.
 *
 * @param array<string, mixed> $field
 */
function mailto_link_form_is_complete_field(array $field): bool
{
    $field = mailto_link_form_normalize_field($field);
    $key = (string) $field['key'];

    if ($key === '') {
        return false;
    }

    if ($field['type'] === 'select') {
        return !empty($field['options']);
    }

    return true;
}
