<?php
namespace JRB\RemoteApi\Handlers;

if (!defined('ABSPATH')) exit;

class AdminHandler {
    
    public static function init() {
        add_action('admin_menu', [self::class, 'add_menu']);
    }

    public static function add_menu() {
        add_options_page(
            'JRB Remote API',
            'JRB Remote API',
            'manage_options',
            'jrb-remote-site-api-for-openclaw',
            [self::class, 'render_page']
        );
    }

    public static function render_page() {
        $new_token = null;
        if (isset($_POST['openclaw_generate']) && check_admin_referer('openclaw_settings')) {
            $token = wp_generate_password(64, false);
            update_option('openclaw_api_token_hash', wp_hash($token));
            delete_option('openclaw_api_token');
            $new_token = $token;
        }

        if (isset($_POST['openclaw_save_caps']) && check_admin_referer('openclaw_capabilities')) {
            $all_caps = self::get_all_capabilities();
            $to_save = [];
            foreach ($all_caps as $cap => $info) {
                $to_save[$cap] = isset($_POST['cap_' . $cap]);
            }
            update_option('openclaw_api_capabilities', $to_save);
            echo '<div class="notice notice-success is-dismissible"><p>Capabilities updated.</p></div>';
        }

        $has_token = (bool)get_option('openclaw_api_token_hash');
        $caps = get_option('openclaw_api_capabilities', []);
        $grouped_caps = self::get_grouped_capabilities();

        ?>
        <div class="wrap">
            <h1>JRB Remote API Settings</h1>
            
            <div class="card">
                <h2>API Authentication</h2>
                <?php if ($new_token): ?>
                    <div class="notice notice-warning"><p><strong>Copy this token now! It will not be shown again:</strong></p>
                    <code style="display:block; padding:10px; background:#f0f0f1;"><?php echo esc_html($new_token); ?></code></div>
                <?php elseif ($has_token): ?>
                    <p>✓ Token is configured (Stored Hashed).</p>
                <?php endif; ?>
                
                <form method="post">
                    <?php wp_nonce_field('openclaw_settings'); ?>
                    <button type="submit" name="openclaw_generate" class="button button-primary">
                        <?php echo $has_token ? 'Regenerate Token' : 'Generate Token'; ?>
                    </button>
                </form>
            </div>

            <form method="post" style="margin-top:20px;">
                <?php wp_nonce_field('openclaw_capabilities'); ?>
                <h2>Capabilities Control</h2>
                <?php foreach ($grouped_caps as $group => $items): ?>
                    <h3><?php echo esc_html($group); ?></h3>
                    <table class="form-table">
                        <?php foreach ($items as $slug => $label): ?>
                        <tr>
                            <th scope="row"><?php echo esc_html($label); ?></th>
                            <td>
                                <input type="checkbox" name="cap_<?php echo esc_attr($slug); ?>" value="1" <?php checked(!empty($caps[$slug])); ?>>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endforeach; ?>
                <?php submit_button('Save Capabilities', 'primary', 'openclaw_save_caps'); ?>
            </form>
        </div>
        <?php
    }

    private static function get_all_capabilities() {
        $all = [];
        foreach (self::get_grouped_capabilities() as $group => $items) {
            foreach ($items as $slug => $label) { $all[$slug] = $label; }
        }
        return $all;
    }

    private static function get_grouped_capabilities() {
        return [
            'System' => [
                'site_info' => 'Read Site Info',
                'plugins_read' => 'Read Plugins',
                'themes_read' => 'Read Themes'
            ],
            'Content' => [
                'posts_read' => 'Read Posts',
                'posts_create' => 'Create Posts',
                'pages_read' => 'Read Pages'
            ],
            'FluentCRM' => [
                'crm_subscribers_read' => 'Read Subscribers',
                'crm_lists_read' => 'Read Lists',
                'crm_campaigns_read' => 'Read Campaigns'
            ],
            'FluentSupport' => [
                'support_tickets_read' => 'Read Tickets',
                'support_customers_read' => 'Read Customers'
            ]
        ];
    }
}
