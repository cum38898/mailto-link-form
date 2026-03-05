<?php

defined('ABSPATH') || exit;

final class MLF_Admin
{
    private const POST_TYPE = 'mailto_form';
    private const META_RECIPIENT = '_mlf_recipient_email';
    private const META_SUBJECT = '_mlf_subject';
    private const META_BODY_TEMPLATE = '_mlf_body_template';
    private const META_FIELDS_JSON = '_mlf_fields_json';
    private const META_SUBMIT_LABEL = '_mlf_submit_label';
    private const NOTICE_KEY = 'mlf_admin_error_';

    public function __construct()
    {
        add_action('init', [$this, 'register_post_type']);
        add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
        add_action('save_post_' . self::POST_TYPE, [$this, 'save_form']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_notices', [$this, 'render_admin_notices']);
    }

    public function register_post_type(): void
    {
        register_post_type(
            self::POST_TYPE,
            [
                'labels' => [
                    'name' => mailto_link_form_i18n('Mailto Forms', 'mailtoフォーム'),
                    'singular_name' => mailto_link_form_i18n('Mailto Form', 'mailtoフォーム'),
                    'add_new_item' => mailto_link_form_i18n('Add New Mailto Form', 'mailtoフォームを追加'),
                    'edit_item' => mailto_link_form_i18n('Edit Mailto Form', 'mailtoフォームを編集'),
                    'menu_name' => mailto_link_form_i18n('Mailto Forms', 'mailtoフォーム'),
                ],
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_rest' => false,
                'supports' => ['title'],
                'menu_position' => 58,
                'menu_icon' => 'dashicons-email',
            ]
        );
    }

    public function register_meta_boxes(): void
    {
        add_meta_box(
            'mlf_form_settings',
            mailto_link_form_i18n('Form Settings', 'フォーム設定'),
            [$this, 'render_form_settings_metabox'],
            self::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'mlf_form_shortcode',
            mailto_link_form_i18n('Shortcode', 'ショートコード'),
            [$this, 'render_shortcode_metabox'],
            self::POST_TYPE,
            'side',
            'default'
        );
    }

    public function enqueue_admin_assets(string $hook): void
    {
        if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== self::POST_TYPE) {
            return;
        }

        wp_enqueue_style(
            'mlf-admin-style',
            WP_MAILTO_LINK_FORM_PLUGIN_URL . 'assets/admin.css',
            [],
            WP_MAILTO_LINK_FORM_VERSION
        );

        wp_enqueue_script(
            'mlf-admin-script',
            WP_MAILTO_LINK_FORM_PLUGIN_URL . 'assets/admin.js',
            [],
            WP_MAILTO_LINK_FORM_VERSION,
            true
        );
    }

    public function render_form_settings_metabox(\WP_Post $post): void
    {
        wp_nonce_field('mlf_save_form_' . $post->ID, 'mlf_form_nonce');

        $recipient = (string) get_post_meta($post->ID, self::META_RECIPIENT, true);
        $subject = (string) get_post_meta($post->ID, self::META_SUBJECT, true);
        $bodyTemplate = (string) get_post_meta($post->ID, self::META_BODY_TEMPLATE, true);
        $submitLabel = (string) get_post_meta($post->ID, self::META_SUBMIT_LABEL, true);
        $fields = $this->get_fields($post->ID);

        if ($bodyTemplate === '' && !empty($fields)) {
            $bodyTemplate = mailto_link_form_generate_default_template($fields);
        }

        ?>
        <p>
            <label for="mlf_recipient_email"><strong><?php echo esc_html(mailto_link_form_i18n('Recipient Email', '送信先メールアドレス')); ?></strong></label><br />
            <input type="email" id="mlf_recipient_email" name="mlf_recipient_email" class="widefat" value="<?php echo esc_attr($recipient); ?>" />
        </p>

        <p>
            <label for="mlf_subject"><strong><?php echo esc_html(mailto_link_form_i18n('Subject', '件名')); ?></strong></label><br />
            <input type="text" id="mlf_subject" name="mlf_subject" class="widefat" value="<?php echo esc_attr($subject); ?>" />
        </p>

        <p>
            <label for="mlf_body_template"><strong><?php echo esc_html(mailto_link_form_i18n('Body Template', '本文テンプレート')); ?></strong></label><br />
            <textarea id="mlf_body_template" name="mlf_body_template" rows="8" class="widefat"><?php echo esc_textarea($bodyTemplate); ?></textarea>
            <small><?php echo esc_html(mailto_link_form_i18n('Use placeholders like {{contact_type}}.', '{{contact_type}} のようなプレースホルダーを使えます。')); ?></small>
        </p>

        <p>
            <label for="mlf_submit_label"><strong><?php echo esc_html(mailto_link_form_i18n('Submit Button Label', '送信ボタンラベル')); ?></strong></label><br />
            <input type="text" id="mlf_submit_label" name="mlf_submit_label" class="widefat" value="<?php echo esc_attr($submitLabel); ?>" placeholder="<?php echo esc_attr(mailto_link_form_i18n('Send', '送信')); ?>" />
        </p>

        <hr />
        <p><strong><?php echo esc_html(mailto_link_form_i18n('Select Fields', 'select項目')); ?></strong></p>
        <table class="widefat mlf-fields-table">
            <thead>
                <tr>
                    <th><?php echo esc_html(mailto_link_form_i18n('Field Key', 'フィールドキー')); ?></th>
                    <th><?php echo esc_html(mailto_link_form_i18n('Label', 'ラベル')); ?></th>
                    <th><?php echo esc_html(mailto_link_form_i18n('Options (comma-separated)', '選択肢（カンマ区切り）')); ?></th>
                    <th><?php echo esc_html(mailto_link_form_i18n('Action', '操作')); ?></th>
                </tr>
            </thead>
            <tbody id="mlf-fields-body">
            <?php if (!empty($fields)) : ?>
                <?php foreach ($fields as $field) : ?>
                    <tr class="mlf-field-row">
                        <td><input type="text" name="mlf_field_key[]" value="<?php echo esc_attr((string) $field['key']); ?>" /></td>
                        <td><input type="text" name="mlf_field_label[]" value="<?php echo esc_attr((string) $field['label']); ?>" /></td>
                        <td><input type="text" name="mlf_field_options[]" value="<?php echo esc_attr(implode(', ', (array) $field['options'])); ?>" /></td>
                        <td><button type="button" class="button button-secondary mlf-remove-row"><?php echo esc_html(mailto_link_form_i18n('Remove', '削除')); ?></button></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr class="mlf-field-row">
                    <td><input type="text" name="mlf_field_key[]" value="" placeholder="<?php echo esc_attr(mailto_link_form_i18n('(e.g.) contact_type', '（例）contact_type')); ?>" /></td>
                    <td><input type="text" name="mlf_field_label[]" value="" placeholder="<?php echo esc_attr(mailto_link_form_i18n('(e.g.) Inquiry Type', '（例）問い合わせ種別')); ?>" /></td>
                    <td><input type="text" name="mlf_field_options[]" value="" placeholder="<?php echo esc_attr(mailto_link_form_i18n('(e.g.) Press Inquiry, Careers, Other', '（例）取材について, 求人について, その他')); ?>" /></td>
                    <td><button type="button" class="button button-secondary mlf-remove-row"><?php echo esc_html(mailto_link_form_i18n('Remove', '削除')); ?></button></td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
        <p>
            <button type="button" class="button button-primary" id="mlf-add-row"><?php echo esc_html(mailto_link_form_i18n('Add Select Field', 'select項目を追加')); ?></button>
        </p>
        <template id="mlf-row-template">
            <tr class="mlf-field-row">
                <td><input type="text" name="mlf_field_key[]" value="" placeholder="<?php echo esc_attr(mailto_link_form_i18n('(e.g.) contact_type', '（例）contact_type')); ?>" /></td>
                <td><input type="text" name="mlf_field_label[]" value="" placeholder="<?php echo esc_attr(mailto_link_form_i18n('(e.g.) Inquiry Type', '（例）問い合わせ種別')); ?>" /></td>
                <td><input type="text" name="mlf_field_options[]" value="" placeholder="<?php echo esc_attr(mailto_link_form_i18n('(e.g.) Press Inquiry, Careers, Other', '（例）取材について, 求人について, その他')); ?>" /></td>
                <td><button type="button" class="button button-secondary mlf-remove-row"><?php echo esc_html(mailto_link_form_i18n('Remove', '削除')); ?></button></td>
            </tr>
        </template>
        <?php
    }

    public function render_shortcode_metabox(\WP_Post $post): void
    {
        if ((int) $post->ID <= 0) {
            echo '<p>' . esc_html(mailto_link_form_i18n('Save this form first to get its shortcode.', 'ショートコードを表示するには先に保存してください。')) . '</p>';
            return;
        }

        $shortcode = sprintf('[mailto_link_form id="%d"]', (int) $post->ID);
        ?>
        <p><?php echo esc_html(mailto_link_form_i18n('Use this shortcode:', 'このショートコードを使ってください:')); ?></p>
        <input type="text" class="widefat" readonly value="<?php echo esc_attr($shortcode); ?>" onclick="this.select();" />
        <?php
    }

    public function save_form(int $postId): void
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $nonce = isset($_POST['mlf_form_nonce']) ? sanitize_text_field(wp_unslash((string) $_POST['mlf_form_nonce'])) : '';
        if ($nonce === '' || !wp_verify_nonce($nonce, 'mlf_save_form_' . $postId)) {
            return;
        }

        if (!current_user_can('edit_post', $postId)) {
            return;
        }

        $recipient = isset($_POST['mlf_recipient_email']) ? sanitize_email(wp_unslash((string) $_POST['mlf_recipient_email'])) : '';
        $subject = isset($_POST['mlf_subject']) ? sanitize_text_field(wp_unslash((string) $_POST['mlf_subject'])) : '';
        $bodyTemplate = isset($_POST['mlf_body_template']) ? sanitize_textarea_field(wp_unslash((string) $_POST['mlf_body_template'])) : '';
        $submitLabel = isset($_POST['mlf_submit_label']) ? sanitize_text_field(wp_unslash((string) $_POST['mlf_submit_label'])) : '';

        if ($recipient === '' || !is_email($recipient)) {
            $this->set_admin_error(mailto_link_form_i18n('Recipient email is required.', '送信先メールアドレスは必須です。'));
            return;
        }

        if ($subject === '') {
            $this->set_admin_error(mailto_link_form_i18n('Subject is required.', '件名は必須です。'));
            return;
        }

        $keysInput = filter_input(INPUT_POST, 'mlf_field_key', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        $labelsInput = filter_input(INPUT_POST, 'mlf_field_label', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        $optionsInput = filter_input(INPUT_POST, 'mlf_field_options', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);

        $keys = is_array($keysInput) ? array_map('wp_unslash', $keysInput) : [];
        $labels = is_array($labelsInput) ? array_map('wp_unslash', $labelsInput) : [];
        $optionsList = is_array($optionsInput) ? array_map('wp_unslash', $optionsInput) : [];

        $fields = [];
        $seenKeys = [];
        $count = max(count($keys), count($labels), count($optionsList));

        for ($i = 0; $i < $count; $i++) {
            $rawKey = isset($keys[$i]) ? (string) $keys[$i] : '';
            $rawLabel = isset($labels[$i]) ? (string) $labels[$i] : '';
            $rawOptions = isset($optionsList[$i]) ? (string) $optionsList[$i] : '';

            if (trim($rawKey) === '' && trim($rawLabel) === '' && trim($rawOptions) === '') {
                continue;
            }

            $key = mailto_link_form_normalize_field_key($rawKey);
            $label = sanitize_text_field($rawLabel);
            $split = preg_split('/[,\x{FF0C}\x{3001}\r\n]+/u', $rawOptions);
            $options = [];
            foreach ((array) $split as $option) {
                $clean = sanitize_text_field(trim((string) $option));
                if ($clean !== '') {
                    $options[] = $clean;
                }
            }

            if ($label === '' || empty($options)) {
                $this->set_admin_error(mailto_link_form_i18n('Each select field needs key, label, and at least one option.', '各select項目には、キー・表示名・選択肢（1件以上）が必要です。'));
                return;
            }

            if ($key === '') {
                $key = mailto_link_form_normalize_field_key($label);
            }

            if ($key === '') {
                $key = 'field_' . ($i + 1);
            }

            if (isset($seenKeys[$key])) {
                $suffix = 2;
                $base = $key;
                while (isset($seenKeys[$base . '_' . $suffix])) {
                    $suffix++;
                }
                $key = $base . '_' . $suffix;
            }

            $seenKeys[$key] = true;

            $fields[] = [
                'key' => $key,
                'label' => $label,
                'options' => array_values(array_unique($options)),
                'required' => false,
            ];
        }

        if (empty($fields)) {
            $this->set_admin_error(mailto_link_form_i18n('At least one select field is required.', 'select項目を1件以上設定してください。'));
            return;
        }

        if ($bodyTemplate === '') {
            $bodyTemplate = mailto_link_form_generate_default_template($fields);
        }

        update_post_meta($postId, self::META_RECIPIENT, $recipient);
        update_post_meta($postId, self::META_SUBJECT, $subject);
        update_post_meta($postId, self::META_BODY_TEMPLATE, $bodyTemplate);
        update_post_meta($postId, self::META_SUBMIT_LABEL, $submitLabel);
        update_post_meta(
            $postId,
            self::META_FIELDS_JSON,
            wp_json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    public function render_admin_notices(): void
    {
        if (!is_admin()) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->post_type !== self::POST_TYPE) {
            return;
        }

        $key = self::NOTICE_KEY . get_current_user_id();
        $message = get_transient($key);
        if ($message === false) {
            return;
        }

        delete_transient($key);

        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html((string) $message)
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function get_fields(int $postId): array
    {
        $json = (string) get_post_meta($postId, self::META_FIELDS_JSON, true);
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    private function set_admin_error(string $message): void
    {
        $key = self::NOTICE_KEY . get_current_user_id();
        set_transient($key, $message, 60);
    }
}
