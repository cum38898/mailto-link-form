<?php

defined('ABSPATH') || exit;

final class MALIFO_Shortcode
{
    private const SHORTCODE = 'mailto_link_form';
    private const POST_TYPE = 'mailto_form';
    private const META_RECIPIENT = '_malifo_recipient_email';
    private const META_FIELDS_JSON = '_malifo_fields_json';
    private const META_SUBMIT_LABEL = '_malifo_submit_label';
    private const META_HELP_TEXT = '_malifo_help_text';

    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_shortcode(self::SHORTCODE, [$this, 'render_shortcode']);
    }

    public function register_assets(): void
    {
        wp_register_style(
            'mailto-link-form-frontend',
            MALIFO_PLUGIN_URL . 'assets/frontend.css',
            [],
            MALIFO_VERSION
        );
        wp_register_script(
            'mailto-link-form-frontend',
            MALIFO_PLUGIN_URL . 'assets/frontend.js',
            [],
            MALIFO_VERSION,
            true
        );
    }

    /**
     * @param array<string, mixed> $atts
     */
    public function render_shortcode(array $atts): string
    {
        $atts = shortcode_atts(
            [
                'id' => 0,
            ],
            $atts,
            self::SHORTCODE
        );

        $formId = absint((string) $atts['id']);
        if ($formId <= 0) {
            return '';
        }

        $post = get_post($formId);
        if (!$post || $post->post_type !== self::POST_TYPE) {
            return '';
        }

        $recipient = (string) get_post_meta($formId, self::META_RECIPIENT, true);
        if (!is_email($recipient)) {
            return '';
        }

        $fields = $this->get_fields($formId);
        $renderableFields = array_values(array_filter($fields, 'mailto_link_form_is_complete_field'));
        $submitLabel = (string) get_post_meta($formId, self::META_SUBMIT_LABEL, true);
        if ($submitLabel === '') {
            $submitLabel = mailto_link_form_i18n('Go to Compose', 'メール作成へ');
        }

        $helpText = (string) get_post_meta($formId, self::META_HELP_TEXT, true);
        if ($helpText === '') {
            $helpText = mailto_link_form_i18n('Opening your email app.', 'メールアプリを開いています');
        }

        wp_enqueue_style('mailto-link-form-frontend');
        wp_enqueue_script('mailto-link-form-frontend');

        ob_start();
        ?>
        <form class="malifo-frontend-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" target="_blank">
            <input type="hidden" name="action" value="mailto_link_form_submit" />
            <input type="hidden" name="form_id" value="<?php echo esc_attr((string) $formId); ?>" />
            <input type="hidden" name="redirect_to" value="<?php echo esc_url((string) get_permalink()); ?>" />
            <?php wp_nonce_field('malifo_submit_' . $formId, 'malifo_nonce'); ?>

            <?php foreach ($renderableFields as $field) : ?>
                <?php
                $type = mailto_link_form_get_field_type($field);
                $key = isset($field['key']) ? (string) $field['key'] : '';
                $label = isset($field['label']) ? (string) $field['label'] : '';
                $value = isset($field['value']) ? (string) $field['value'] : '';
                $placeholder = isset($field['placeholder']) ? (string) $field['placeholder'] : '';
                $options = isset($field['options']) && is_array($field['options']) ? $field['options'] : [];
                $fieldId = 'malifo_field_' . $key;
                ?>
                <?php if ($type === 'select') : ?>
                    <p class="malifo-form-row malifo-form-row--select">
                        <select id="<?php echo esc_attr($fieldId); ?>" name="<?php echo esc_attr($fieldId); ?>" required>
                            <option value="" selected disabled><?php echo esc_html($label); ?></option>
                            <?php foreach ($options as $option) : ?>
                                <option value="<?php echo esc_attr((string) $option); ?>"><?php echo esc_html((string) $option); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                <?php elseif ($type === 'textarea') : ?>
                    <div class="malifo-form-row malifo-form-row--textarea">
                        <label class="malifo-field-label" for="<?php echo esc_attr($fieldId); ?>"><?php echo esc_html($label); ?></label>
                        <textarea id="<?php echo esc_attr($fieldId); ?>" name="<?php echo esc_attr($fieldId); ?>" rows="5" placeholder="<?php echo esc_attr($placeholder); ?>"><?php echo esc_textarea($value); ?></textarea>
                    </div>
                <?php elseif ($type === 'text') : ?>
                    <div class="malifo-form-row malifo-form-row--text">
                        <label class="malifo-field-label" for="<?php echo esc_attr($fieldId); ?>"><?php echo esc_html($label); ?></label>
                        <input type="text" id="<?php echo esc_attr($fieldId); ?>" name="<?php echo esc_attr($fieldId); ?>" value="<?php echo esc_attr($value); ?>" placeholder="<?php echo esc_attr($placeholder); ?>" />
                    </div>
                <?php elseif ($type === 'checkbox') : ?>
                    <div class="malifo-form-row malifo-form-row--checkbox">
                        <label class="malifo-checkbox-label" for="<?php echo esc_attr($fieldId); ?>">
                            <input type="checkbox" id="<?php echo esc_attr($fieldId); ?>" name="<?php echo esc_attr($fieldId); ?>" value="1" />
                            <span><?php echo esc_html($label); ?></span>
                        </label>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>

            <p class="malifo-actions">
                <button type="submit"><?php echo esc_html($submitLabel); ?></button>
            </p>
            <p class="malifo-help-text is-hidden" aria-live="polite">
                <?php echo esc_html($helpText); ?>
            </p>
        </form>
        <?php

        return (string) ob_get_clean();
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

        return array_map('mailto_link_form_normalize_field', $decoded);
    }
}
