<?php

defined('ABSPATH') || exit;

final class MLF_Shortcode
{
    private const SHORTCODE = 'mailto_link_form';
    private const POST_TYPE = 'mailto_form';
    private const META_FIELDS_JSON = '_mlf_fields_json';
    private const META_SUBMIT_LABEL = '_mlf_submit_label';
    private const META_HELP_TEXT = '_mlf_help_text';

    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_shortcode(self::SHORTCODE, [$this, 'render_shortcode']);
    }

    public function register_assets(): void
    {
        wp_register_style(
            'mailto-link-form-frontend',
            WP_MAILTO_LINK_FORM_PLUGIN_URL . 'assets/frontend.css',
            [],
            WP_MAILTO_LINK_FORM_VERSION
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

        $noticeId = 'mlf-help-text-' . $formId;

        ob_start();
        ?>
        <form class="mlf-frontend-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" target="_blank">
            <input type="hidden" name="action" value="mailto_link_form_submit" />
            <input type="hidden" name="form_id" value="<?php echo esc_attr((string) $formId); ?>" />
            <input type="hidden" name="redirect_to" value="<?php echo esc_url((string) get_permalink()); ?>" />
            <?php wp_nonce_field('mlf_submit_' . $formId, 'mlf_nonce'); ?>

            <?php foreach ($fields as $field) : ?>
                <?php
                $key = isset($field['key']) ? (string) $field['key'] : '';
                $label = isset($field['label']) ? (string) $field['label'] : '';
                $options = isset($field['options']) && is_array($field['options']) ? $field['options'] : [];
                if ($key === '' || $label === '' || empty($options)) {
                    continue;
                }
                ?>
                <p class="mlf-form-row">
                    <select id="<?php echo esc_attr('mlf_field_' . $key); ?>" name="<?php echo esc_attr('mlf_field_' . $key); ?>" required>
                        <option value="" selected disabled><?php echo esc_html($label); ?></option>
                        <?php foreach ($options as $option) : ?>
                            <option value="<?php echo esc_attr((string) $option); ?>"><?php echo esc_html((string) $option); ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
            <?php endforeach; ?>

            <p class="mlf-actions">
                <button type="submit"><?php echo esc_html($submitLabel); ?></button>
            </p>
            <p id="<?php echo esc_attr($noticeId); ?>" class="mlf-help-text is-hidden" aria-live="polite">
                <?php echo esc_html($helpText); ?>
            </p>
        </form>
        <script>
            (function () {
                var form = document.currentScript ? document.currentScript.previousElementSibling : null;
                if (!form || !form.classList || !form.classList.contains('mlf-frontend-form')) {
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
