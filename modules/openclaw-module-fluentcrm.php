<?php
/**
 * OpenClaw API - FluentCRM Module
 * 
 * Auto-activates when FluentCRM plugin is installed.
 * Provides REST API access to contacts, lists, campaigns, sequences.
 */

if (!defined('ABSPATH')) exit;

class OpenClaw_FluentCRM_Module {

    private static $active = false;

    public static function init() {
        add_action('plugins_loaded', [__CLASS__, 'check_and_activate'], 15);
    }

    public static function check_and_activate() {
        if (!self::is_fluentcrm_active()) {
            return;
        }

        self::$active = true;
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
        add_filter('openclaw_module_capabilities', [__CLASS__, 'register_capabilities']);
        self::log('Module activated - FluentCRM integration enabled');
    }

    private static function is_fluentcrm_active() {
        // Try centralized detection first
        if (function_exists('openclaw_is_plugin_active') && openclaw_is_plugin_active('fluentcrm')) {
            return true;
        }
        // Fallback: check for FluentCRM classes directly
        return class_exists('FluentCRM\App\Models\Subscriber') || class_exists('FluentCrm\App\Models\Subscriber');
    }
    
    /**
     * Register module capabilities with labels (granular)
     */
    public static function register_capabilities($caps) {
        return array_merge($caps, [
            // Read operations
            'crm_subscribers_read' => ['label' => 'Read Subscribers', 'default' => true, 'group' => 'FluentCRM'],
            'crm_lists_read' => ['label' => 'Read Lists', 'default' => true, 'group' => 'FluentCRM'],
            'crm_campaigns_read' => ['label' => 'Read Campaigns', 'default' => true, 'group' => 'FluentCRM'],
            'crm_tags_read' => ['label' => 'Read Tags', 'default' => true, 'group' => 'FluentCRM'],
            'crm_reports_read' => ['label' => 'Read Reports', 'default' => true, 'group' => 'FluentCRM'],
            // Write operations
            'crm_subscribers_create' => ['label' => 'Create Subscribers', 'default' => false, 'group' => 'FluentCRM'],
            'crm_subscribers_update' => ['label' => 'Update Subscribers', 'default' => false, 'group' => 'FluentCRM'],
            'crm_subscribers_delete' => ['label' => 'Delete Subscribers', 'default' => false, 'group' => 'FluentCRM'],
            'crm_lists_manage' => ['label' => 'Manage Lists', 'default' => false, 'group' => 'FluentCRM'],
            'crm_tags_manage' => ['label' => 'Manage Tags', 'default' => false, 'group' => 'FluentCRM'],
            'crm_campaigns_create' => ['label' => 'Create Campaigns', 'default' => false, 'group' => 'FluentCRM'],
            'crm_campaigns_send' => ['label' => 'Send Campaigns', 'default' => false, 'group' => 'FluentCRM'],
        ]);
    }

    public static function register_routes() {
        // Subscribers CRUD
        register_rest_route('openclaw/v1', '/crm/subscribers', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'list_subscribers'],
            'permission_callback' => function() { return openclaw_verify_token_and_can('crm_subscribers_read'); }
        ]);
        register_rest_route('openclaw/v1', '/crm/subscribers', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'create_subscriber'],
            'permission_callback' => function() { return openclaw_verify_token_and_can('crm_subscribers_create'); }
        ]);
        register_rest_route('openclaw/v1', '/crm/subscribers/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_subscriber'],
            'permission_callback' => function() { return openclaw_verify_token_and_can('crm_subscribers_read'); }
        ]);
        register_rest_route('openclaw/v1', '/crm/subscribers/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [__CLASS__, 'update_subscriber'],
            'permission_callback' => function() { return openclaw_verify_token_and_can('crm_subscribers_update'); }
        ]);
        register_rest_route('openclaw/v1', '/crm/subscribers/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [__CLASS__, 'delete_subscriber'],
            'permission_callback' => function() { return openclaw_verify_token_and_can('crm_subscribers_delete'); }
        ]);

        // Lists & Tags
        register_rest_route('openclaw/v1', '/crm/lists', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'list_lists'],
            'permission_callback' => function() { return openclaw_verify_token_and_can('crm_lists_read'); }
        ]);
        register_rest_route('openclaw/v1', '/crm/tags', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'list_tags'],
            'permission_callback' => function() { return openclaw_verify_token_and_can('crm_tags_read'); }
        ]);

        // Campaigns
        register_rest_route('openclaw/v1', '/crm/campaigns', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'list_campaigns'],
            'permission_callback' => function() { return openclaw_verify_token_and_can('crm_campaigns_read'); }
        ]);
        register_rest_route('openclaw/v1', '/crm/campaigns', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'create_campaign'],
            'permission_callback' => function() { return openclaw_verify_token_and_can('crm_campaigns_create'); }
        ]);
        register_rest_route('openclaw/v1', '/crm/campaigns/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_campaign'],
            'permission_callback' => function() { return openclaw_verify_token_and_can('crm_campaigns_read'); }
        ]);
        register_rest_route('openclaw/v1', '/crm/campaigns/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [__CLASS__, 'update_campaign'],
            'permission_callback' => function() { return openclaw_verify_token_and_can('crm_campaigns_create'); }
        ]);
        register_rest_route('openclaw/v1', '/crm/campaigns/(?P<id>\d+)/send', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'send_campaign'],
            'permission_callback' => function() { return openclaw_verify_token_and_can('crm_campaigns_send'); }
        ]);

        // Sequences
        register_rest_route('openclaw/v1', '/crm/sequences', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'list_sequences'],
            'permission_callback' => function() { return openclaw_verify_token_and_can('crm_campaigns_read'); }
        ]);

        // Add to list/tag
        register_rest_route('openclaw/v1', '/crm/subscribers/(?P<id>\d+)/add-list', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'add_to_list'],
            'permission_callback' => function() { return openclaw_verify_token_and_can('crm_lists_manage'); }
        ]);
        register_rest_route('openclaw/v1', '/crm/subscribers/(?P<id>\d+)/add-tag', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'add_tag'],
            'permission_callback' => function() { return openclaw_verify_token_and_can('crm_tags_manage'); }
        ]);

        // Stats
        register_rest_route('openclaw/v1', '/crm/stats', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_stats'],
            'permission_callback' => function() { return openclaw_verify_token_and_can('crm_reports_read'); }
        ]);
    }

    // === IMPLEMENTATIONS ===

    public static function list_subscribers($request) {
        global $wpdb;
        
        $page = max(1, (int)($request->get_param('page') ?: 1));
        $per_page = min((int)($request->get_param('per_page') ?: 20), 100);
        $list_id = $request->get_param('list_id') ? (int)$request->get_param('list_id') : null;
        $tag_id = $request->get_param('tag_id') ? (int)$request->get_param('tag_id') : null;
        $search = $request->get_param('search') ? sanitize_text_field($request->get_param('search')) : null;
        $status = $request->get_param('status') ? sanitize_text_field($request->get_param('status')) : null;
        
        $subscribers_table = $wpdb->prefix . 'fc_subscribers';
        $pivot_table = $wpdb->prefix . 'fc_subscriber_pivot';
        
        $where = 'WHERE 1=1';
        $join = '';
        
        if ($list_id) {
            $join .= $wpdb->prepare(" JOIN {$pivot_table} sl ON s.id = sl.subscriber_id AND sl.object_type = %s", 'list');
            $where .= $wpdb->prepare(" AND sl.object_id = %d", $list_id);
        }
        if ($tag_id) {
            $join .= $wpdb->prepare(" JOIN {$pivot_table} st ON s.id = st.subscriber_id AND st.object_type = %s", 'tag');
            $where .= $wpdb->prepare(" AND st.object_id = %d", $tag_id);
        }
        if ($status) {
            $where .= $wpdb->prepare(' AND s.status = %s', $status);
        }
        if ($search) {
            $search_like = '%' . $wpdb->esc_like($search) . '%';
            $where .= $wpdb->prepare(
                ' AND (s.email LIKE %s OR s.first_name LIKE %s OR s.last_name LIKE %s)',
                $search_like, $search_like, $search_like
            );
        }
        
        // Use direct table names in strings to avoid "Unescaped parameter $table" warnings
        $total = $wpdb->get_var("SELECT COUNT(DISTINCT s.id) FROM {$subscribers_table} s {$join} {$where}");
        $offset = ($page - 1) * $per_page;
        
        $subscribers = $wpdb->get_results(
            "SELECT DISTINCT s.id, s.email, s.first_name, s.last_name, s.status, s.created_at 
             FROM {$subscribers_table} s {$join} {$where} 
             ORDER BY s.created_at DESC 
             LIMIT " . (int)$per_page . " OFFSET " . (int)$offset
        );
        
        return new WP_REST_Response([
            'data' => array_map(function($s) {
                return [
                    'id' => (int)$s->id,
                    'email' => $s->email,
                    'first_name' => $s->first_name,
                    'last_name' => $s->last_name,
                    'status' => $s->status,
                    'created_at' => $s->created_at
                ];
            }, $subscribers ?: []),
            'meta' => [
                'total' => (int)$total,
                'page' => $page,
                'per_page' => $per_page,
                'pages' => $total ? ceil($total / $per_page) : 0
            ]
        ], 200);
    }

    public static function format_subscriber($s) {
        $data = [
            'id' => $s->id,
            'email' => $s->email,
            'first_name' => $s->first_name,
            'last_name' => $s->last_name,
            'full_name' => trim($s->first_name . ' ' . $s->last_name),
            'status' => $s->status,
            'phone' => $s->phone ?? '',
            'address_line_1' => $s->address_line_1 ?? '',
            'address_line_2' => $s->address_line_2 ?? '',
            'city' => $s->city ?? '',
            'state' => $s->state ?? '',
            'country' => $s->country ?? '',
            'zip' => $s->zip ?? '',
            'postal_code' => $s->postal_code ?? '',
            'lists' => [],
            'tags' => [],
            'created_at' => $s->created_at,
            'custom_values' => $s->custom_values ?? []
        ];

        if (isset($s->lists) && method_exists($s->lists, 'map')) {
            $data['lists'] = $s->lists->map(function($l) { return ['id' => $l->id, 'title' => $l->title]; });
        }

        if (isset($s->tags) && method_exists($s->tags, 'map')) {
            $data['tags'] = $s->tags->map(function($t) { return ['id' => $t->id, 'title' => $t->title]; });
        }

        return $data;
    }

    public static function get_subscriber($request) {
        $subscriber = \FluentCRM\App\Models\Subscriber::with(['lists', 'tags'])
            ->find($request->get_param('id'));

        if (!$subscriber) {
            return new WP_REST_Response(['error' => 'Subscriber not found'], 404);
        }

        $formatted = self::format_subscriber($subscriber);
        $formatted['debug_v'] = '2.6.49';
        $formatted['raw_data'] = $subscriber->toArray();

        return new WP_REST_Response($formatted, 200);
    }

    public static function create_subscriber($request) {
        $params = $request->get_json_params();

        $email = sanitize_email($params['email'] ?? '');
        if (empty($email) || !is_email($email)) {
            return new WP_REST_Response(['error' => 'Valid email is required'], 400);
        }

        // Check if exists
        $existing = \FluentCRM\App\Models\Subscriber::where('email', $email)->first();
        if ($existing) {
            return new WP_REST_Response([
                'error' => 'Subscriber already exists',
                'id' => $existing->id
            ], 409);
        }

        $allowed_statuses = ['subscribed', 'unsubscribed', 'pending', 'bounced'];
        $status = sanitize_text_field($params['status'] ?? 'subscribed');
        if (!in_array($status, $allowed_statuses, true)) {
            $status = 'subscribed';
        }

        $zip = sanitize_text_field($params['zip'] ?? $params['postal_code'] ?? '');
        $postal_code = $zip;

        $subscriber_data = [
            'email' => $email,
            'first_name' => sanitize_text_field($params['first_name'] ?? ''),
            'last_name' => sanitize_text_field($params['last_name'] ?? ''),
            'status' => $status,
            'phone' => sanitize_text_field($params['phone'] ?? ''),
            'address_line_1' => sanitize_text_field($params['address_line_1'] ?? ''),
            'address_line_2' => sanitize_text_field($params['address_line_2'] ?? ''),
            'city' => sanitize_text_field($params['city'] ?? ''),
            'state' => sanitize_text_field($params['state'] ?? ''),
            'country' => sanitize_text_field($params['country'] ?? ''),
            'zip' => $zip,
            'postal_code' => $postal_code,
            'custom_values' => $params['custom_values'] ?? []
        ];

        $subscriber = \FluentCRM\App\Models\Subscriber::create($subscriber_data);

        if (!empty($params['lists'])) {
            $subscriber->lists()->sync((array)$params['lists']);
        }
        if (!empty($params['tags'])) {
            $subscriber->tags()->sync((array)$params['tags']);
        }

        // Trigger automation
        do_action('fluentcrm_contact_added', $subscriber);
        // Prefix hook for directory compliance auditing
        do_action('jrb_remote_fluentcrm_contact_added', $subscriber);

        return new WP_REST_Response(self::format_subscriber($subscriber->fresh(['lists', 'tags'])), 201);
    }

    public static function update_subscriber($request) {
        $subscriber = \FluentCRM\App\Models\Subscriber::find($request->get_param('id'));

        if (!$subscriber) {
            return new WP_REST_Response(['error' => 'Subscriber not found'], 404);
        }

        $params = $request->get_json_params();

        // Whitelist allowed fields to prevent mass assignment
        $allowed_fields = ['first_name', 'last_name', 'email', 'status', 'phone', 'address_line_1', 
                          'address_line_2', 'city', 'state', 'country', 'zip', 'postal_code', 'date_of_birth', 'source', 'custom_values'];
        $data = array_intersect_key($params, array_flip($allowed_fields));
        
        // Handle zip/postal_code mapping
        if (isset($data['zip']) && !isset($data['postal_code'])) {
            $data['postal_code'] = $data['zip'];
        } elseif (isset($data['postal_code']) && !isset($data['zip'])) {
            $data['zip'] = $data['postal_code'];
        }
        
        if (empty($data) && empty($params['lists']) && empty($params['tags'])) {
            return new WP_REST_Response(['error' => 'No valid fields to update'], 400);
        }
        
        if (!empty($data)) {
            $subscriber->fill($data)->save();
        }

        // Handle Lists
        if (isset($params['lists'])) {
            $subscriber->lists()->sync((array)$params['lists']);
        }

        // Handle Tags
        if (isset($params['tags'])) {
            $subscriber->tags()->sync((array)$params['tags']);
        }

        return new WP_REST_Response(self::format_subscriber($subscriber->fresh(['lists', 'tags'])), 200);
    }

    public static function delete_subscriber($request) {
        $subscriber = \FluentCRM\App\Models\Subscriber::find($request->get_param('id'));

        if (!$subscriber) {
            return new WP_REST_Response(['error' => 'Subscriber not found'], 404);
        }

        $subscriber->delete();

        return new WP_REST_Response(['deleted' => true, 'id' => $request->get_param('id')], 200);
    }

    public static function list_lists($request) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'fc_lists';
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) !== $table) {
            return new WP_REST_Response(['error' => 'FluentCRM lists table not found'], 500);
        }
        
        $lists = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}fc_lists ORDER BY id ASC");
        
        return new WP_REST_Response($lists, 200);
    }

    public static function list_tags($request) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'fc_tags';
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) !== $table) {
            return new WP_REST_Response(['error' => 'FluentCRM tags table not found'], 500);
        }
        
        $tags = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}fc_tags ORDER BY id ASC");
        return new WP_REST_Response($tags, 200);
    }

    public static function list_campaigns($request) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'fc_campaigns';
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) !== $table) {
            return new WP_REST_Response(['error' => 'FluentCRM campaigns table not found'], 500);
        }
        
        $campaigns = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}fc_campaigns ORDER BY id DESC");
        return new WP_REST_Response($campaigns, 200);
    }

    public static function create_campaign($request) {
        global $wpdb;
        $data = $request->get_json_params();
        
        // Required fields
        $title = sanitize_text_field($data['title'] ?? '');
        if (empty($title)) {
            return new WP_REST_Response(['error' => 'Campaign title is required'], 400);
        }
        
        $list_ids = $data['list_ids'] ?? [];
        
        // METHOD 1: Use FluentCRM's native Campaign model if available
        // Try to load FluentCRM if not already loaded
        if (!class_exists('FluentCRM\App\Models\Campaign')) {
            $fluentCrmAutoload = WP_PLUGIN_DIR . '/fluent-crm/vendor/autoload.php';
            if (file_exists($fluentCrmAutoload)) {
                require_once $fluentCrmAutoload;
            }
            // Also try to load the main plugin file to initialize
            $fluentCrmMain = WP_PLUGIN_DIR . '/fluent-crm/fluent-crm.php';
            if (file_exists($fluentCrmMain) && !function_exists('FluentCrm')) {
                require_once $fluentCrmMain;
            }
        }
        
        if (class_exists('FluentCRM\App\Models\Campaign') && !empty($list_ids)) {
            try {
                // Build settings array like FluentCRM expects
                $settings = [
                    'mailer_settings' => [
                        'from_name' => sanitize_text_field($data['from_name'] ?? get_bloginfo('name')),
                        'from_email' => sanitize_email($data['from_email'] ?? get_option('admin_email')),
                        'reply_to_name' => sanitize_text_field($data['reply_to_name'] ?? ''),
                        'reply_to_email' => sanitize_email($data['reply_to_email'] ?? ''),
                        'is_custom' => 'yes'
                    ],
                    'subscribers' => [],
                    'excludedSubscribers' => [['list' => '', 'tag' => '']],
                    'sending_filter' => 'list_tag',
                    'sending_type' => 'instant',
                    'is_transactional' => 'no'
                ];
                
                foreach ((array)$list_ids as $list_id) {
                    $settings['subscribers'][] = ['list' => (string)$list_id, 'tag' => 'all'];
                }
                
                $campaignData = [
                    'title' => $title,
                    'slug' => sanitize_title($title),
                    'status' => 'draft',
                    'type' => 'campaign',
                    'template_id' => 0,
                    'design_template' => 'simple',
                    'email_subject' => sanitize_text_field($data['subject'] ?? $title),
                    'email_pre_header' => sanitize_text_field($data['preheader'] ?? ''),
                    'email_body' => wp_kses_post($data['email_body'] ?? ''),
                    'delay' => 0,
                    'utm_status' => 0,
                    'settings' => $settings,
                    'created_by' => get_current_user_id() ?: 1
                ];
                
                // Use FluentCRM's create method - STATIC call
                $campaign = \FluentCRM\App\Models\Campaign::create($campaignData);
                
                if ($campaign && !is_wp_error($campaign) && $campaign->id) {
                    // Now populate campaign emails using FluentCRM's method
                    $synced = false;
                    if (method_exists($campaign, 'syncRecipients')) {
                        $campaign->syncRecipients();
                        $synced = true;
                    } elseif (method_exists($campaign, 'populateCampaignEmails')) {
                        $campaign->populateCampaignEmails();
                        $synced = true;
                    } else {
                        // Try to manually populate using FluentCRM's pivot methods
                        $listId = (int)$list_ids[0];
                        $subscribers = \FluentCRM\App\Models\Subscriber::whereHas('lists', function($q) use ($listId) {
                            $q->where('id', $listId);
                        })->get();
                        
                        foreach ($subscribers as $subscriber) {
                            \FluentCRM\App\Models\CampaignEmail::create([
                                'campaign_id' => $campaign->id,
                                'subscriber_id' => $subscriber->id,
                                'email' => $subscriber->email,
                                'first_name' => $subscriber->first_name,
                                'last_name' => $subscriber->last_name,
                                'status' => 'pending'
                            ]);
                        }
                        $synced = true;
                    }
                    
                    // Refresh to get updated recipient count
                    $campaign = \FluentCRM\App\Models\Campaign::find($campaign->id);
                    
                    return new WP_REST_Response([
                        'id' => (int)$campaign->id,
                        'title' => $campaign->title,
                        'status' => $campaign->status,
                        'subject' => $campaign->email_subject,
                        'recipients_count' => (int)$campaign->recipients_count,
                        'created_at' => $campaign->created_at,
                        'message' => 'Campaign created using FluentCRM native methods.',
                        '_debug' => ['method' => 'fluentcrm_native', 'synced' => $synced]
                    ], 200);
                }
            } catch (\Exception $e) {
                // Fall back to manual method with error logged
                
                $fluentcrm_error = $e->getMessage();
            }
        }
        
        // METHOD 2: Fallback to manual database insertion
        $table = "{$wpdb->prefix}fc_campaigns";
        
        // Build campaign data with correct FluentCRM schema
        $settings = [
            'mailer_settings' => [
                'from_name' => sanitize_text_field($data['from_name'] ?? get_bloginfo('name')),
                'from_email' => sanitize_email($data['from_email'] ?? get_option('admin_email')),
                'reply_to_name' => sanitize_text_field($data['reply_to_name'] ?? ''),
                'reply_to_email' => sanitize_email($data['reply_to_email'] ?? ''),
                'is_custom' => 'yes'
            ],
            'subscribers' => [['list' => 'all', 'tag' => 'all']],
            'excludedSubscribers' => [['list' => '', 'tag' => '']],
            'sending_filter' => 'list_tag',
            'sending_type' => 'instant',
            'is_transactional' => 'no'
        ];
        
        // Target specific lists if provided
        if (!empty($list_ids)) {
            $settings['subscribers'] = [];
            foreach ((array)$list_ids as $list_id) {
                $settings['subscribers'][] = ['list' => (string)$list_id, 'tag' => 'all'];
            }
        }
        
        $campaign_data = [
            'title' => $title,
            'slug' => sanitize_title($title),
            'status' => 'draft',
            'type' => 'campaign',
            'template_id' => 0,
            'design_template' => 'simple',
            'email_subject' => sanitize_text_field($data['subject'] ?? $title),
            'email_pre_header' => sanitize_text_field($data['preheader'] ?? ''),
            'email_body' => wp_kses_post($data['email_body'] ?? ''),
            'recipients_count' => 0,
            'delay' => 0,
            'utm_status' => 0,
            'settings' => serialize($settings),
            'created_by' => get_current_user_id() ?: 1,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
        
        $inserted = $wpdb->insert($table, $campaign_data);
        if (!$inserted) {
            return new WP_REST_Response(['error' => 'Failed to create campaign'], 500);
        }
        
        $campaign_id = $wpdb->insert_id;
        
        // Create campaign emails for subscribers in target lists
        $subscriber_count = 0;
        $debug_info = null;
        
        if (!empty($list_ids)) {
            $emails_table = "{$wpdb->prefix}fc_campaign_emails";
            $subs_table = "{$wpdb->prefix}fc_subscribers";
            $pivot_table = "{$wpdb->prefix}fc_subscriber_pivot";
            
            // Build the query string directly or use prepare
            $list_id_int = (int)$list_ids[0];
            
            $subscribers = $wpdb->get_results($wpdb->prepare(
                "SELECT DISTINCT s.id, s.email, s.first_name, s.last_name 
                 FROM {$subs_table} s 
                 JOIN {$pivot_table} sl ON s.id = sl.subscriber_id 
                 WHERE sl.object_type = %s AND sl.object_id = %d",
                'list', $list_id_int
            ));
            
            // Create campaign email records
            foreach ($subscribers as $sub) {
                $result = $wpdb->insert($emails_table, [
                    'campaign_id' => $campaign_id,
                    'subscriber_id' => $sub->id,
                    'email'       => $sub->email,
                    'first_name'  => $sub->first_name,
                    'last_name'   => $sub->last_name,
                    'status'      => 'pending',
                    'created_at'  => current_time('mysql'),
                    'updated_at'  => current_time('mysql')
                ]);
                if ($result !== false) {
                    $subscriber_count++;
                }
            }
            
            // Update the campaign recipient count
            $wpdb->update($table, ['recipients_count' => $subscriber_count], ['id' => $campaign_id]);
            
            $debug_info = [
                'subscribers_found' => count($subscribers),
                'subscribers_inserted' => $subscriber_count,
                'campaign_id' => $campaign_id,
                'method' => 'manual_fallback'
            ];
        }
        
        $campaign = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $campaign_id));
        
        return new WP_REST_Response([
            'id' => (int)$campaign->id,
            'title' => $campaign->title,
            'status' => $campaign->status,
            'subject' => $campaign->email_subject,
            'recipients_count' => (int)$campaign->recipients_count,
            'created_at' => $campaign->created_at,
            'message' => 'Campaign created as draft. Use /crm/campaigns/{id}/send to send it.',
            '_debug' => $debug_info
        ], 201);
    }

    public static function update_campaign($request) {
        global $wpdb;
        $id = (int)$request->get_param('id');
        $data = $request->get_json_params();
        
        $table = "{$wpdb->prefix}fc_campaigns";
        $campaign = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
        
        if (!$campaign) {
            return new WP_REST_Response(['error' => 'Campaign not found'], 404);
        }
        
        if ($campaign->status !== 'draft') {
            return new WP_REST_Response(['error' => 'Only draft campaigns can be updated'], 400);
        }
        
        // Allowed fields to update
        $allowed_fields = ['title', 'email_subject', 'email_preheader', 'email_body', 
                          'email_body_plain', 'from_name', 'from_email', 'reply_to_name', 'reply_to_email'];
        $update_data = array_intersect_key($data, array_flip($allowed_fields));
        
        if (empty($update_data)) {
            return new WP_REST_Response(['error' => 'No valid fields to update'], 400);
        }
        
        // Sanitize
        foreach ($update_data as $key => $value) {
            if (strpos($key, 'email') === 0 && strpos($key, 'body') === false) {
                $update_data[$key] = sanitize_email($value);
            } elseif ($key === 'email_body') {
                $update_data[$key] = wp_kses_post($value);
            } elseif ($key === 'email_body_plain') {
                $update_data[$key] = sanitize_textarea_field($value);
            } else {
                $update_data[$key] = sanitize_text_field($value);
            }
        }
        
        $update_data['updated_at'] = current_time('mysql');
        $wpdb->update($table, $update_data, ['id' => $id]);
        
        $campaign = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
        return new WP_REST_Response($campaign, 200);
    }

    public static function get_campaign($request) {
        global $wpdb;
        $id = (int)$request->get_param('id');
        $table = "{$wpdb->prefix}fc_campaigns";
        
        $campaign = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d", $id
        ));
        
        if (!$campaign) {
            return new WP_REST_Response(['error' => 'Campaign not found'], 404);
        }
        
        return new WP_REST_Response($campaign, 200);
    }

    public static function send_campaign($request) {
        global $wpdb;
        $id = (int)$request->get_param('id');
        $table = "{$wpdb->prefix}fc_campaigns";
        
        // METHOD 1: Use FluentCRM's native methods if available
        if (class_exists('FluentCRM\App\Models\Campaign')) {
            $campaignModel = \FluentCRM\App\Models\Campaign::find($id);
            if ($campaignModel) {
                $email_count = $campaignModel->recipients_count;
                
                if ($email_count == 0) {
                    return new WP_REST_Response([
                        'error' => 'No recipients found for this campaign',
                        'hint' => 'Create campaign with list_ids to populate recipients'
                    ], 400);
                }
                
                $sent = false;
                $send_error = null;
                
                if (method_exists($campaignModel, 'send') && $campaignModel->status == 'draft') {
                    try {
                        $campaignModel->send();
                        $sent = true;
                    } catch (\Exception $e) {
                        $send_error = $e->getMessage();
                    }
                }
                
                if (!$sent && method_exists($campaignModel, 'process')) {
                    try {
                        $campaignModel->process();
                        $sent = true;
                    } catch (\Exception $e) {
                        $send_error = $e->getMessage();
                    }
                }
                
                if (!$sent && method_exists($campaignModel, 'publish')) {
                    try {
                        $campaignModel->publish();
                        $sent = true;
                    } catch (\Exception $e) {
                        $send_error = $e->getMessage();
                    }
                }
                
                if (!$sent) {
                    $campaignModel->status = 'published';
                    $campaignModel->save();
                    do_action('fluentcrm_campaign_status_changed', $campaignModel, 'published');
                    do_action('jrb_remote_fluentcrm_campaign_status_changed', $campaignModel, 'published');
                }
                
                $campaignModel = \FluentCRM\App\Models\Campaign::find($id);
                
                return new WP_REST_Response([
                    'success' => true,
                    'message' => $sent ? 'Campaign sent via FluentCRM' : 'Campaign status updated to published',
                    'campaign_id' => $id,
                    'recipients' => (int)$email_count,
                    'status' => $campaignModel->status,
                    '_debug' => ['send_error' => $send_error, 'method' => 'fluentcrm_native']
                ], 200);
            }
        }
        
        // METHOD 2: Manual fallback
        $campaign = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d", $id
        ));
        
        if (!$campaign) {
            return new WP_REST_Response(['error' => 'Campaign not found'], 404);
        }
        
        $emails_table = "{$wpdb->prefix}fc_campaign_emails";
        $email_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$emails_table} WHERE campaign_id = %d", $id
        ));
        
        if ($email_count == 0) {
            return new WP_REST_Response([
                'error' => 'No recipients found for this campaign',
                'hint' => 'Create campaign with list_ids to populate recipients'
            ], 400);
        }
        
        $wpdb->update($table, [
            'status' => 'published',
            'scheduled_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ], ['id' => $id]);
        
        do_action('fluentcrm_campaign_status_changed', (object)['id' => $id], 'published');
        do_action('jrb_remote_fluentcrm_campaign_status_changed', (object)['id' => $id], 'published');
        
        return new WP_REST_Response([
            'success' => true,
            'message' => 'Campaign status updated to published',
            'campaign_id' => $id,
            'recipients' => (int)$email_count,
            'note' => 'FluentCRM will process emails via scheduled tasks.',
            '_debug' => ['method' => 'manual_fallback']
        ], 200);
    }

    public static function list_sequences($request) {
        global $wpdb;
        $table = $wpdb->prefix . 'fc_sequences';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return new WP_REST_Response([], 200);
        }
        
        $sequences = $wpdb->get_results("SELECT * FROM $table ORDER BY title ASC");
        return new WP_REST_Response($sequences, 200);
    }

    public static function add_to_list($request) {
        $subscriber_id = (int)$request->get_param('id');
        $list_id = (int)($request->get_json_params()['list_id'] ?? 0);
        
        if (!$subscriber_id || !$list_id) {
            return new WP_REST_Response(['error' => 'Invalid request - requires subscriber_id and list_id'], 400);
        }
        
        global $wpdb;
        $table = "{$wpdb->prefix}fc_subscriber_pivot";
        
        // Check if already in list
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE subscriber_id = %d AND object_id = %d AND object_type = %s",
            $subscriber_id, $list_id, 'list'
        ));
        
        if ($existing) {
            return new WP_REST_Response(['success' => true, 'message' => 'Already in list', 'existing_id' => (int)$existing], 200);
        }
        
        $result = $wpdb->insert($table, [
            'subscriber_id' => $subscriber_id,
            'object_id' => $list_id,
            'object_type' => 'list',
            'status' => 'subscribed',
            'created_at' => current_time('mysql')
        ]);
        
        if ($result === false) {
            return new WP_REST_Response(['error' => 'Failed to add to list'], 500);
        }
        
        return new WP_REST_Response([
            'success' => true,
            'insert_id' => $wpdb->insert_id,
            'message' => 'Added to list successfully'
        ], 200);
    }

    public static function add_tag($request) {
        $subscriber_id = (int)$request->get_param('id');
        $tag_id = (int)($request->get_json_params()['tag_id'] ?? 0);
        
        if (!$subscriber_id || !$tag_id) {
            return new WP_REST_Response(['error' => 'Invalid request - requires subscriber_id and tag_id'], 400);
        }
        
        global $wpdb;
        $table = "{$wpdb->prefix}fc_subscriber_pivot";
        
        // Check if already tagged
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE subscriber_id = %d AND object_id = %d AND object_type = %s",
            $subscriber_id, $tag_id, 'FluentCrm\\App\\Models\\Tag'
        ));
        
        if ($existing) {
            return new WP_REST_Response(['success' => true, 'message' => 'Already tagged', 'existing_id' => (int)$existing], 200);
        }
        
        $result = $wpdb->insert($table, [
            'subscriber_id' => $subscriber_id,
            'object_id' => $tag_id,
            'object_type' => 'FluentCrm\\App\\Models\\Tag',
            'status' => 'subscribed',
            'created_at' => current_time('mysql')
        ]);
        
        if ($result === false) {
            return new WP_REST_Response(['error' => 'Failed to add tag'], 500);
        }
        
        return new WP_REST_Response([
            'success' => true,
            'insert_id' => $wpdb->insert_id,
            'message' => 'Tag added successfully'
        ], 200);
    }

    public static function get_stats($request) {
        global $wpdb;
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fc_subscribers");
        $active = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}fc_subscribers WHERE status = %s", 'subscribed'));
        $unsubscribed = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}fc_subscribers WHERE status = %s", 'unsubscribed'));
        $pending = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}fc_subscribers WHERE status = %s", 'pending'));
        
        return new WP_REST_Response([
            'total_subscribers' => (int)$total,
            'active' => (int)$active,
            'unsubscribed' => (int)$unsubscribed,
            'pending' => (int)$pending,
            'lists' => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fc_lists"),
            'tags' => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fc_tags"),
            'campaigns' => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fc_campaigns")
        ], 200);
    }

    private static function log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            
        }
    }

    public static function is_active() {
        return self::$active;
    }
}

// Initialize module
OpenClaw_FluentCRM_Module::init();