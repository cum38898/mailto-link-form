<?php

defined('ABSPATH') || exit;

final class MALIFO_Shortcode
{
    private const SHORTCODE = 'mailto_link_form';
    private const POST_TYPE = 'mailto_form';
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
    }

    /**
     * @param array<string, mixed> $atts
     */
    public function render_shortcode(array $atts): string
    {
        wp_enqueue_style('mailto-link-form-frontend');

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

        $fields = $this->get_fields($formId);
        if (empty($fields)) {
            return '';
        }
        $submitLabel = (string) get_post_meta($formId, self::META_SUBMIT_LABEL, true);
        if ($submitLabel === '') {
            $submitLabel = mailto_link_form_i18n('Go to Compose', 'メール作成へ');
        }

        $helpText = (string) get_post_meta($formId, self::META_HELP_TEXT, true);
        if ($helpText === '') {
            $helpText = mailto_link_form_i18n('Opening your email app.', 'メールアプリを開いています');
        }

        $noticeId = 'malifo-help-text-' . $formId;

        ob_start();
        ?>
        <form class="malifo-frontend-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" target="_blank">
            <input type="hidden" name="action" value="mailto_link_form_submit" />
            <input type="hidden" name="form_id" value="<?php echo esc_attr((string) $formId); ?>" />
            <input type="hidden" name="redirect_to" value="<?php echo esc_url((string) get_permalink()); ?>" />
            <?php wp_nonce_field('malifo_submit_' . $formId, 'malifo_nonce'); ?>

            <?php foreach ($fields as $field) : ?>
                <?php
                $key = isset($field['key']) ? (string) $field['key'] : '';
                $label = isset($field['label']) ? (string) $field['label'] : '';
                $options = isset($field['options']) && is_array($field['options']) ? $field['options'] : [];
                if ($key === '' || $label === '' || empty($options)) {
                    continue;
                }
                ?>
                <p class="malifo-form-row">
                    <select id="<?php echo esc_attr('malifo_field_' . $key); ?>" name="<?php echo esc_attr('malifo_field_' . $key); ?>" required>
                        <option value="" selected disabled><?php echo esc_html($label); ?></option>
                        <?php foreach ($options as $option) : ?>
                            <option value="<?php echo esc_attr((string) $option); ?>"><?php echo esc_html((string) $option); ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
            <?php endforeach; ?>

            <p class="malifo-actions">
                <button type="submit"><?php echo esc_html($submitLabel); ?></button>
            </p>
            <p id="<?php echo esc_attr($noticeId); ?>" class="malifo-help-text is-hidden" aria-live="polite">
                <?php echo esc_html($helpText); ?>
            </p>
        </form>
        <script>
            (function () {
                var form = document.currentScript ? document.currentScript.previousElementSibling : null;
                if (!form || !form.classList || !form.classList.contains('malifo-frontend-form')) {
                    return;
                }
                var help = form.querySelector('#<?php echo esc_js($noticeId); ?>');
                if (!help) {
                    return;
                }
                form.addEventListener('submit', function () {
                    help.classList.remove('is-hidden');
                });
            }());
        </script>
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

        return $decoded;
    }
}
