<?php

defined('ABSPATH') || exit;

final class MALIFO_Submit
{
    private const POST_TYPE = 'mailto_form';
    private const META_RECIPIENT = '_malifo_recipient_email';
    private const META_SUBJECT = '_malifo_subject';
    private const META_BODY_TEMPLATE = '_malifo_body_template';
    private const META_FIELDS_JSON = '_malifo_fields_json';

    public function __construct()
    {
        add_action('admin_post_mailto_link_form_submit', [$this, 'handle']);
        add_action('admin_post_nopriv_mailto_link_form_submit', [$this, 'handle']);
    }

    public function handle(): void
    {
        $formId = isset($_POST['form_id']) ? absint((string) $_POST['form_id']) : 0;
        $redirectTo = isset($_POST['redirect_to']) ? esc_url_raw((string) wp_unslash($_POST['redirect_to'])) : home_url('/');

        if ($formId <= 0) {
            $this->redirect_with_error($redirectTo, 'invalid_form');
        }

        $nonce = isset($_POST['malifo_nonce']) ? sanitize_text_field(wp_unslash((string) $_POST['malifo_nonce'])) : '';
        if ($nonce === '' || !wp_verify_nonce($nonce, 'malifo_submit_' . $formId)) {
            $this->redirect_with_error($redirectTo, 'invalid_nonce');
        }

        $post = get_post($formId);
        if (!$post || $post->post_type !== self::POST_TYPE) {
            $this->redirect_with_error($redirectTo, 'missing_form');
        }

        $recipient = (string) get_post_meta($formId, self::META_RECIPIENT, true);
        $subject = (string) get_post_meta($formId, self::META_SUBJECT, true);
        $bodyTemplate = (string) get_post_meta($formId, self::META_BODY_TEMPLATE, true);
        $fields = $this->get_fields($formId);

        if (!is_email($recipient) || empty($fields)) {
            $this->redirect_with_error($redirectTo, 'invalid_settings');
        }

        $values = [];
        foreach ($fields as $field) {
            $key = isset($field['key']) ? (string) $field['key'] : '';
            $options = isset($field['options']) && is_array($field['options']) ? $field['options'] : [];
            if ($key === '' || empty($options)) {
                continue;
            }

            $inputName = 'malifo_field_' . $key;
            $selected = isset($_POST[$inputName]) ? sanitize_text_field((string) wp_unslash($_POST[$inputName])) : '';

            if ($selected === '') {
                $this->redirect_with_error($redirectTo, 'required_option');
            }

            if (!in_array($selected, $options, true)) {
                $this->redirect_with_error($redirectTo, 'invalid_option');
            }

            $values[$key] = $selected;
        }

        $body = mailto_link_form_apply_template($bodyTemplate, $values);
        $body = str_replace(["\r\n", "\r"], "\n", $body);
        $body = str_replace("\n", "\r\n", $body);

        $query = [];
        if ($subject !== '') {
            $query[] = 'subject=' . rawurlencode($subject);
        }

        if ($body !== '') {
            $query[] = 'body=' . rawurlencode($body);
        }

        $mailtoUrl = 'mailto:' . $recipient;
        if (!empty($query)) {
            $mailtoUrl .= '?' . implode('&', $query);
        }

        // wp_redirect() removes %0D/%0A tokens, which breaks mail body newlines in mailto.
        // Here we build mailto from validated/sanitized parts, then send a direct Location header.
        header('Location: ' . $mailtoUrl, true, 302);
        exit;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function get_fields(int $formId): array
    {
        $json = (string) get_post_meta($formId, self::META_FIELDS_JSON, true);
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    private function redirect_with_error(string $redirectTo, string $code): void
    {
        $target = add_query_arg('malifo_error', $code, $redirectTo);
        wp_safe_redirect($target);
        exit;
    }

}
