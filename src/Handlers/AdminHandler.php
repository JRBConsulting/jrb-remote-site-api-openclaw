<?php
namespace JRB\RemoteApi\Handlers;

if (!defined('ABSPATH')) exit;

/**
 * Handles the WordPress Admin Menu and Settings Page
 */
class AdminHandler {
    
    public static function init() {
        add_action('admin_menu', [self::class, 'add_menu']);
        add_action('admin_init', [self::class, 'register_settings']);
    }

    public static function add_menu() {
        add_options_page(
            'JRB Remote API Settings',
            'JRB Remote API',
            'manage_options',
            'jrb-remote-api',
            [self::class, 'render_settings_page']
        );
    }

    public static function register_settings() {
        register_setting('jrb_remote_api_group', 'jrb_remote_api_token');
        register_setting('jrb_remote_api_group', 'jrb_remote_api_permissions');
    }

    public static function render_settings_page() {
        if (!current_user_can('manage_options')) return;
        
        $token = get_option('jrb_remote_api_token');
        $perms = get_option('jrb_remote_api_permissions', []);
        $available_perms = [
            'crm_subscribers_read'    => 'FluentCRM: Read Subscribers',
            'crm_lists_read'          => 'FluentCRM: Read Lists',
            'support_tickets_read'    => 'FluentSupport: Read Tickets',
            'support_customers_read'  => 'FluentSupport: Read Customers',
            'project_tasks_read'      => 'FluentProject: Read Tasks',
            'media_read'              => 'Media: Read Library',
            'media_upload'            => 'Media: Upload Files',
            'media_delete'            => 'Media: Delete Files',
        ];
        ?>
        <div class="wrap">
            <h1>JRB Remote Site API Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('jrb_remote_api_group'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">API Token</th>
                        <td>
                            <input type="text" name="jrb_remote_api_token" value="<?php echo esc_attr($token); ?>" class="regular-text">
                            <p class="description">Required for authentication (X-JRB-Token header).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Permissions</th>
                        <td>
                            <?php foreach ($available_perms as $key => $label): ?>
                                <label style="display:block; margin-bottom:5px;">
                                    <input type="checkbox" name="jrb_remote_api_permissions[]" value="<?php echo $key; ?>" <?php checked(in_array($key, $perms)); ?>>
                                    <?php echo esc_html($label); ?>
                                </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
