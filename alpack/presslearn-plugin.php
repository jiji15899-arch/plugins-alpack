<?php
/**
 * Plugin Name: AL Pack - 워드프레스를 위한 통계, 광고 도구
 * Plugin URI: https://alpack.dev
 * Description: 통계, 글쓰기 SEO, 무효 트래픽 차단, 빠른 버튼 생성, 카카오 공유 버튼, 스크롤 팝업, 애드클리커 등 워드프레스를 위한 통합 플러그인
 * Version: 1.2.2
 * Author: 프레스런
 * Author URI: https://alpack.dev
 * Text Domain: alpack
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PRESSLEARN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PRESSLEARN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PRESSLEARN_PLUGIN_VERSION', time());

register_activation_hook(__FILE__, 'presslearn_plugin_activate');
register_deactivation_hook(__FILE__, 'presslearn_plugin_deactivate');
register_uninstall_hook(__FILE__, 'presslearn_plugin_uninstall');

function presslearn_plugin_activate() {
    $is_first_install = empty(get_option('presslearn_plugin_header_version', ''));
    
    update_option('presslearn_plugin_key', '');
    update_option('presslearn_plugin_activated_time', time());
    update_option('presslearn_activation_logs', array());
    update_option('presslearn_plugin_settings', array(
        'initialized' => true,
        'version' => PRESSLEARN_PLUGIN_VERSION,
        'activation_date' => current_time('mysql')
    ));
    
    if ($is_first_install) {
        update_option('presslearn_scroll_depth_enabled', 'no');
        update_option('presslearn_analytics_enabled', 'no');
        update_option('presslearn_dynamic_banner_enabled', 'no');
        update_option('presslearn_click_protection_enabled', 'no');
        update_option('presslearn_ad_clicker_enabled', 'no');
        update_option('presslearn_social_share_enabled', 'no');
        add_option('presslearn_quick_button_enabled', 'no');
        add_option('presslearn_button_transition_enabled', 'no');
        add_option('presslearn_auto_index_enabled', 'no');
        add_option('presslearn_header_footer_enabled', 'no');
    } else {
        add_option('presslearn_scroll_depth_enabled', 'no');
        add_option('presslearn_analytics_enabled', 'no');
        add_option('presslearn_dynamic_banner_enabled', 'no');
        add_option('presslearn_click_protection_enabled', 'no');
        add_option('presslearn_ad_clicker_enabled', 'no');
        add_option('presslearn_social_share_enabled', 'no');
        add_option('presslearn_quick_button_enabled', 'no');
        add_option('presslearn_button_transition_enabled', 'no');
        add_option('presslearn_auto_index_enabled', 'no');
        add_option('presslearn_header_footer_enabled', 'no');
    }
        presslearn_create_tables();
    
    set_transient('presslearn_plugin_activation_redirect', true, 30);
}

function presslearn_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $table_name = $wpdb->prefix . 'presslearn_logs';
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        event varchar(255) NOT NULL,
        details longtext NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    $banners_table = $wpdb->prefix . 'presslearn_banners';
    $sql .= "CREATE TABLE IF NOT EXISTS $banners_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        type varchar(50) NOT NULL DEFAULT 'custom',
        banner_url text,
        cover_banner_url text,
        link text,
        iframe_code longtext,
        width int(11),
        height int(11),
        status tinyint(1) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    $indexing_table = $wpdb->prefix . 'presslearn_indexing_logs';
    $sql .= "CREATE TABLE IF NOT EXISTS $indexing_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        post_url text NOT NULL,
        post_title text NOT NULL,
        request_type varchar(50) NOT NULL DEFAULT 'auto',
        response_code int(11) DEFAULT NULL,
        response_message text DEFAULT NULL,
        indexed_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY post_id (post_id),
        KEY indexed_at (indexed_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function presslearn_ensure_banners_table_exists() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'presslearn_banners';
    
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
    
    if ($table_exists != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            type varchar(50) NOT NULL DEFAULT 'custom',
            banner_url text,
            cover_banner_url text,
            link text,
            iframe_code longtext,
            width int(11),
            height int(11),
            status tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        if (function_exists('error_log')) {
            error_log('PressLearn: wp_presslearn_banners 테이블이 생성되었습니다.');
        }
    }
}

function presslearn_plugin_deactivate() {
    delete_transient('presslearn_plugin_activation_redirect');
    
    delete_option('presslearn_quick_button_enabled');
    delete_option('presslearn_button_transition_enabled');
    delete_option('presslearn_auto_index_enabled');
    delete_option('presslearn_header_footer_enabled');
}

function presslearn_plugin_activation_redirect() {
    if (get_transient('presslearn_plugin_activation_redirect')) {
        delete_transient('presslearn_plugin_activation_redirect');
        if (is_admin() && !isset($_GET['activate-multi'])) {
            wp_redirect(admin_url('admin.php?page=presslearn-settings'));
            exit;
        }
    }
}
add_action('admin_init', 'presslearn_plugin_activation_redirect');


function presslearn_plugin_uninstall() {
    delete_option('presslearn_plugin_key');
    delete_option('presslearn_plugin_activated_time');
    delete_option('presslearn_activation_logs');
    delete_option('presslearn_plugin_settings');
    
    delete_option('presslearn_scroll_depth_enabled');
    delete_option('presslearn_analytics_enabled');
    delete_option('presslearn_dynamic_banner_enabled');
    delete_option('presslearn_click_protection_enabled');
    delete_option('presslearn_ad_clicker_enabled');
    delete_option('presslearn_social_share_enabled');
    delete_option('presslearn_quick_button_enabled');
    delete_option('presslearn_button_transition_enabled');
    delete_option('presslearn_auto_index_enabled');
    delete_option('presslearn_header_footer_enabled');
    delete_transient('presslearn_plugin_activation_redirect');
    
    $users = get_users(array('fields' => 'ID'));
    foreach($users as $user_id) {
        delete_user_meta($user_id, 'presslearn_user_data');
    }
    
    presslearn_drop_tables();
}


function presslearn_drop_tables() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'presslearn_logs';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
    
    $banners_table = $wpdb->prefix . 'presslearn_banners';
    $wpdb->query("DROP TABLE IF EXISTS $banners_table");
        
    $indexing_table = $wpdb->prefix . 'presslearn_indexing_logs';
    $wpdb->query("DROP TABLE IF EXISTS $indexing_table");
}

require_once PRESSLEARN_PLUGIN_DIR . 'includes/admin.php';
require_once PRESSLEARN_PLUGIN_DIR . 'includes/api.php';
require_once PRESSLEARN_PLUGIN_DIR . 'includes/share.php';
require_once PRESSLEARN_PLUGIN_DIR . 'includes/buttons.php';
require_once PRESSLEARN_PLUGIN_DIR . 'includes/analytics.php';
require_once PRESSLEARN_PLUGIN_DIR . 'includes/protection.php';
require_once PRESSLEARN_PLUGIN_DIR . 'includes/dynamic.php';
require_once PRESSLEARN_PLUGIN_DIR . 'includes/scroll.php';
require_once PRESSLEARN_PLUGIN_DIR . 'includes/indexing.php';

function presslearn_scroll_depth_frontend_script() {
    if (is_admin()) {
        return;
    }
    
    $scroll_depth_enabled = get_option('presslearn_scroll_depth_enabled', 'no');
    $popup_content = get_option('presslearn_popup_content', '');
    
    if ($scroll_depth_enabled !== 'yes' || empty($popup_content)) {
        return;
    }
    
    $scroll_percentage = get_option('presslearn_scroll_percentage', 50);
    $popup_animation = get_option('presslearn_popup_animation', 'fade');
    $repeat_setting = get_option('presslearn_repeat_setting', 'once');
    
    $animation_class = 'popupFadeIn';
    if ($popup_animation === 'slide') {
        $animation_class = 'popupSlideIn';
    } elseif ($popup_animation === 'zoom') {
        $animation_class = 'popupZoomIn';
    }
    
    $cookie_check = $repeat_setting === 'once' ? 'true' : 'false';
    
    wp_register_style('presslearn-popup-css', false);
    wp_enqueue_style('presslearn-popup-css');
    
    $popup_styles = "
    .pl-popup-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        z-index: 99999;
        display: none;
        justify-content: center;
        align-items: center;
    }
    
    .pl-popup-container {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        height: 100%;
    }
    
    .pl-popup-window {
        background-color: #fff;
        width: 90%;
        max-width: 500px;
        border-radius: 8px;
        box-shadow: 0 0 30px rgba(0, 0, 0, 0.5);
        display: flex;
        flex-direction: column;
        max-height: 80vh;
    }
    
    .pl-popup-body {
        padding: 30px;
        overflow-y: auto;
        flex: 1;
        line-height: 1.6;
    }
    
    .pl-popup-body img {
        max-width: 100%;
        height: auto;
        display: block;
        margin: 0 auto;
    }
    
    .pl-close-popup {
        position: fixed;
        top: 20px;
        right: 20px;
        background: none;
        border: none;
        font-size: 40px;
        cursor: pointer;
        color: #fff;
        z-index: 100000;
        padding: 0;
        line-height: 1;
    }
    
    .pl-close-popup:hover {
        color: #ddd;
    }
    
    @keyframes popupFadeIn {
        from {
            opacity: 0;
            transform: scale(0.9);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    @keyframes popupSlideIn {
        from {
            opacity: 0;
            transform: translateY(-50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes popupZoomIn {
        from {
            opacity: 0;
            transform: scale(0.5);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    .popup-animation {
        animation: " . $animation_class . " 0.3s ease-out;
    }
    
    @media screen and (max-width: 768px) {
        .pl-popup-window {
            width: 95%;
            max-width: 95%;
        }
        
        .pl-popup-body {
            padding: 20px;
        }
        
        .pl-close-popup {
            top: 10px;
            right: 10px;
            font-size: 32px;
        }
    }";
    
    wp_add_inline_style('presslearn-popup-css', $popup_styles);
    
    add_action('wp_footer', function() use ($popup_content) {
        ?>
        <div id="pl-popup-overlay" class="pl-popup-overlay">
            <button type="button" class="pl-close-popup">&times;</button>
            <div class="pl-popup-container">
                <div class="pl-popup-window popup-animation">
                    <div class="pl-popup-body">
                        <?php echo wp_kses_post(wpautop($popup_content)); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    });
    
    wp_register_script('presslearn-popup-js', false, array(), PRESSLEARN_PLUGIN_VERSION, true);
    wp_enqueue_script('presslearn-popup-js');
    
    $popup_script = "
    (function() {
        function setCookie(name, value, days) {
            var expires = '';
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = '; expires=' + date.toUTCString();
            }
            document.cookie = name + '=' + (value || '') + expires + '; path=/';
        }
        
        function getCookie(name) {
            var nameEQ = name + '=';
            var ca = document.cookie.split(';');
            for(var i=0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        }
        
        var cookieName = 'pl_popup_shown';
        var checkCookie = " . esc_js($cookie_check) . ";
        
        if (checkCookie && getCookie(cookieName)) {
            return; 
        }
        
        var scrollTriggered = false;
        window.addEventListener('scroll', function() {
            if (scrollTriggered) return;
            
            var scrollPercentage = " . esc_js($scroll_percentage) . ";
            var scrollPosition = window.scrollY;
            var documentHeight = document.documentElement.scrollHeight - window.innerHeight;
            var currentScrollPercent = (scrollPosition / documentHeight) * 100;
            
            if (currentScrollPercent >= scrollPercentage) {
                scrollTriggered = true;
                document.getElementById('pl-popup-overlay').style.display = 'flex';
                
                if (checkCookie) {
                    setCookie(cookieName, 'true', 30);
                }
            }
        });
        
        document.querySelector('.pl-close-popup').addEventListener('click', function() {
            document.getElementById('pl-popup-overlay').style.display = 'none';
        });
        
        document.getElementById('pl-popup-overlay').addEventListener('click', function(e) {
            if (e.target === this || e.target.classList.contains('pl-popup-container')) {
                this.style.display = 'none';
            }
        });
    })();";
    
    wp_add_inline_script('presslearn-popup-js', $popup_script);
}

add_action('wp_enqueue_scripts', 'presslearn_scroll_depth_frontend_script');

class PressLearn_Plugin {
    private static $instance = null;
    
    private $is_activated = false;
    
    private $option_key = 'presslearn_plugin_key';
    
    private $exempt_pages = array('presslearn-settings');
    
    private $db_version = '1.0';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    

    private function __construct() {
        add_action('init', array($this, 'init'));
        
        add_action('admin_menu', array($this, 'register_admin_menu'));
        
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        $this->check_activation();
        
        add_action('admin_init', array($this, 'check_page_access'));
        
        add_action('admin_init', array($this, 'check_version_upgrade'));
        
        add_action('admin_notices', array($this, 'show_update_notice'));

        add_action('admin_bar_menu', array($this, 'add_admin_bar_menu'), 100);
    }
    

    public function check_version_upgrade() {
        if (!function_exists('get_plugin_data')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        
        $plugin_data = get_plugin_data(__FILE__);
        $current_header_version = $plugin_data['Version'];
        
        $saved_header_version = get_option('presslearn_plugin_header_version', '');
        
        if (!empty($saved_header_version) && $saved_header_version !== $current_header_version) {
            update_option('presslearn_plugin_key', '');
            $this->is_activated = false;
            
            set_transient('presslearn_plugin_updated_notice', array(
                'from_version' => $saved_header_version,
                'to_version' => $current_header_version,
                'updated_time' => current_time('mysql')
            ), 86400);
            
            $this->log_upgrade($saved_header_version, $current_header_version);
        }
        
        update_option('presslearn_plugin_header_version', $current_header_version);
        
        $settings = get_option('presslearn_plugin_settings', array());
        $current_version = isset($settings['version']) ? $settings['version'] : '0';
        $current_db_version = isset($settings['db_version']) ? $settings['db_version'] : '0';
        
        if ($current_version != PRESSLEARN_PLUGIN_VERSION) {
            if (version_compare($current_db_version, $this->db_version, '<')) {
                $this->run_db_migration($current_db_version);
            }
            
            $settings['version'] = PRESSLEARN_PLUGIN_VERSION;
            $settings['db_version'] = $this->db_version;
            $settings['last_upgraded'] = current_time('mysql');
            
            update_option('presslearn_plugin_settings', $settings);
        }
    }
    

    private function run_db_migration($from_version) {
        global $wpdb;
        
        if (version_compare($from_version, '0.5', '<')) {
            $table_name = $wpdb->prefix . 'presslearn_logs';
            
            $wpdb->query("ALTER TABLE $table_name ADD ip_address varchar(45) DEFAULT '' AFTER event");
        }
        
        if (version_compare($from_version, '0.8', '<')) {

        }
        
    }
    
    private function log_upgrade($from_version, $to_version) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'presslearn_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'time' => current_time('mysql'),
                'event' => 'plugin_upgrade',
                'details' => json_encode(array(
                    'from_version' => $from_version,
                    'to_version' => $to_version,
                    'user_id' => get_current_user_id(),
                    'site_url' => get_site_url()
                ))
            )
        );
    }
    
    public function show_update_notice() {
        $update_notice = get_transient('presslearn_plugin_updated_notice');
        
        if ($update_notice && is_array($update_notice)) {
            $from_version = esc_html($update_notice['from_version']);
            $to_version = esc_html($update_notice['to_version']);
            $updated_time = esc_html($update_notice['updated_time']);
            
            ?>

            <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#presslearn-update-notice .notice-dismiss').on('click', function() {
                    $.post(ajaxurl, {
                        action: 'presslearn_dismiss_update_notice',
                        nonce: '<?php echo esc_js(wp_create_nonce('presslearn_dismiss_notice')); ?>'
                    });
                });
            });
            </script>
            <?php
        }
    }

    public function check_page_access() {
        $current_page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        
        if (strpos($current_page, 'presslearn-') === 0 && !in_array($current_page, $this->exempt_pages)) {
            if (!$this->is_activated) {
                wp_redirect(admin_url('admin.php?page=presslearn-settings&access_denied=true'));
                exit;
            }
        }
    }
    
    /**
     * Initialize
     */
    public function init() {
        if (isset($_GET['presslearn_key']) && !empty($_GET['presslearn_key'])) {
            if (!current_user_can('manage_options')) {
                wp_die('관리자 권한이 필요합니다.');
            }
            
            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'presslearn_activate_plugin')) {
                wp_die('보안 검증에 실패했습니다.');
            }
            
            $this->activate_plugin(sanitize_text_field(wp_unslash($_GET['presslearn_key'])));
            wp_redirect(admin_url('admin.php?page=presslearn-settings&activated=true'));
            exit;
        }
        
        if (get_option('presslearn_analytics_enabled', 'no') === 'yes') {
            $tables_created = $this->create_analytics_tables();
            
            if ($tables_created) {
                add_action('wp_head', array($this, 'add_tracking_code'));
                $this->register_tracking_ajax();
            } else {
                update_option('presslearn_analytics_enabled', 'no');
                
                if (is_admin()) {
                    add_action('admin_notices', function() {
                        ?>
                        <div class="notice notice-error">
                            <p><?php echo esc_html('씬 애널리틱스 기능 활성화 중 오류가 발생하여 비활성화되었습니다. 데이터베이스 테이블을 생성할 수 없습니다.'); ?></p>
                        </div>
                        <?php
                    });
                }
            }
        }
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'presslearn') === false) {
            return;
        }
        
        wp_enqueue_style('presslearn-admin-css', PRESSLEARN_PLUGIN_URL . 'assets/css/admin.css', array(), PRESSLEARN_PLUGIN_VERSION);
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('presslearn-admin-js', PRESSLEARN_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), PRESSLEARN_PLUGIN_VERSION, true);
        
        wp_register_script('presslearn-chart-js', PRESSLEARN_PLUGIN_URL . 'assets/js/chart.min.js', array(), '3.7.1', true);
        
        if (strpos($hook, 'presslearn-analytics') !== false) {
            wp_enqueue_script('presslearn-chart-js');
            wp_register_style('presslearn-analytics-css', false);
            wp_enqueue_style('presslearn-analytics-css');
            
            wp_register_script('presslearn-analytics-js', false, array('jquery', 'presslearn-chart-js', 'moment'), PRESSLEARN_PLUGIN_VERSION, true);
            wp_enqueue_script('presslearn-analytics-js');
        }
        
        if (strpos($hook, 'presslearn-scroll-depth') !== false) {
            wp_register_style('presslearn-scroll-depth-css', false);
            wp_enqueue_style('presslearn-scroll-depth-css');
            
            wp_register_script('presslearn-scroll-depth-js', false, array('jquery'), PRESSLEARN_PLUGIN_VERSION, true);
            wp_enqueue_script('presslearn-scroll-depth-js');
        }
        
        if (strpos($hook, 'presslearn-click-protection') !== false) {
            wp_register_style('presslearn-click-protection-css', false);
            wp_enqueue_style('presslearn-click-protection-css');
            
            wp_register_script('presslearn-click-protection-js', false, array('jquery'), PRESSLEARN_PLUGIN_VERSION, true);
            wp_enqueue_script('presslearn-click-protection-js');
        }
        
        if (strpos($hook, 'presslearn-ad-clicker') !== false) {
            wp_register_style('presslearn-ad-clicker-css', false);
            wp_enqueue_style('presslearn-ad-clicker-css');
            
            wp_register_script('presslearn-ad-clicker-js', false, array('jquery'), PRESSLEARN_PLUGIN_VERSION, true);
            wp_enqueue_script('presslearn-ad-clicker-js');
            
            wp_register_script('presslearn-adclicker-admin', false, array(), PRESSLEARN_PLUGIN_VERSION, true);
            wp_enqueue_script('presslearn-adclicker-admin');
            
            $adclicker_overlay_range = get_option('presslearn_adclicker_overlay_range', 100);
            $adclicker_overlay_color = get_option('presslearn_adclicker_overlay_color', '#000000');
            $adclicker_button_color = get_option('presslearn_adclicker_button_color', '#2196F3');
            $adclicker_button_text_color = get_option('presslearn_adclicker_button_text_color', '#ffffff');
            $adclicker_display_time = get_option('presslearn_adclicker_display_time', 'null');
            
            wp_localize_script('presslearn-adclicker-admin', 'pressleanAdclickerConfig', array(
                'overlayRange' => $adclicker_overlay_range,
                'overlayColor' => $adclicker_overlay_color,
                'buttonColor' => $adclicker_button_color,
                'buttonTextColor' => $adclicker_button_text_color,
                'displayTime' => $adclicker_display_time
            ));
            
            $adclicker_script = "
            document.addEventListener('DOMContentLoaded', function() {
                const previewButton = document.getElementById('preview-adclicker');
                
                if (previewButton) {
                    previewButton.addEventListener('click', function() {
                        showAdClickerPreview();
                    });
                }
                
                const urlParams = new URLSearchParams(window.location.search);
                const showPreview = urlParams.get('preview');
                
                if (previewButton && (showPreview === '1' || showPreview === 'true')) {
                    setTimeout(function() {
                        showAdClickerPreview();
                    }, 500);
                }
                
                function showAdClickerPreview() {
                    const overlay = document.createElement('div');
                    overlay.id = 'adclicker-overlay-preview';
                    overlay.style.position = 'fixed';
                    overlay.style.bottom = '0';
                    overlay.style.left = '0';
                    overlay.style.width = '100%';
                    overlay.style.height = pressleanAdclickerConfig.overlayRange + 'vh';
                    overlay.style.background = 'linear-gradient(to bottom, rgba(255, 255, 255, 0.1) 0%, ' + pressleanAdclickerConfig.overlayColor + ' 100%)';
                    overlay.style.zIndex = '999999';
                    
                    const closeButton = document.createElement('div');
                    closeButton.id = 'adclicker-close-button-preview';
                    closeButton.setAttribute('data-ad-link', '#previewAdLink');
                    closeButton.style.position = 'fixed';
                    closeButton.style.bottom = '60px';
                    closeButton.style.left = '50%';
                    closeButton.style.transform = 'translateX(-50%)';
                    closeButton.style.padding = '15px 30px';
                    closeButton.style.backgroundColor = pressleanAdclickerConfig.buttonColor;
                    closeButton.style.color = pressleanAdclickerConfig.buttonTextColor;
                    closeButton.style.border = 'none';
                    closeButton.style.borderRadius = '8px';
                    closeButton.style.fontSize = '20px';
                    closeButton.style.cursor = 'pointer';
                    closeButton.style.zIndex = '1000001';
                    closeButton.style.textDecoration = 'none';
                    closeButton.style.display = 'inline-block';
                    closeButton.style.textAlign = 'center';
                    
                    const previewLink = document.createElement('a');
                    previewLink.href = '#previewAdLink';
                    previewLink.textContent = '미리보기 예시용';
                    previewLink.setAttribute('target', '_blank');
                    previewLink.style.color = 'inherit';
                    previewLink.style.textDecoration = 'inherit';
                    previewLink.style.display = 'block';
                    
                    closeButton.appendChild(previewLink);
                    
                    const hoverStyle = document.createElement('style');
                    hoverStyle.innerHTML = 
                        '#adclicker-close-button-preview:hover,' +
                        '#adclicker-close-button-preview:focus,' +
                        '#adclicker-close-button-preview:active {' +
                            'background-color: ' + closeButton.style.backgroundColor + ' !important;' +
                            'color: ' + closeButton.style.color + ' !important;' +
                            'text-decoration: none !important;' +
                            'outline: none !important;' +
                        '}';
                    document.head.appendChild(hoverStyle);
                    
                    const backButtonMessage = document.createElement('div');
                    backButtonMessage.textContent = '원치않으시면 뒤로가기를 해주세요';
                    backButtonMessage.style.position = 'fixed';
                    backButtonMessage.style.bottom = '50px';
                    backButtonMessage.style.left = '50%';
                    backButtonMessage.style.transform = 'translateX(-50%)';
                    backButtonMessage.style.fontSize = '12px';
                    backButtonMessage.style.color = pressleanAdclickerConfig.buttonTextColor;
                    backButtonMessage.style.opacity = '0.8';
                    backButtonMessage.style.zIndex = '1000001';
                    backButtonMessage.style.marginBottom = '-15px';
                    
                    const countdownLabel = document.createElement('span');
                    countdownLabel.id = 'adclicker-countdown-label-preview';
                    countdownLabel.style.position = 'absolute';
                    countdownLabel.style.top = '-10px';
                    countdownLabel.style.right = '-10px';
                    countdownLabel.style.width = '30px';
                    countdownLabel.style.height = '30px';
                    countdownLabel.style.borderRadius = '50%';
                    countdownLabel.style.backgroundColor = '#ff3b30';
                    countdownLabel.style.color = 'white';
                    countdownLabel.style.fontSize = '14px';
                    countdownLabel.style.fontWeight = 'bold';
                    countdownLabel.style.textAlign = 'center';
                    countdownLabel.style.lineHeight = '30px';
                    countdownLabel.style.zIndex = '1000002';
                    
                    closeButton.addEventListener('click', function(e) {
                        if (e.target.tagName.toLowerCase() !== 'a') {
                            e.preventDefault();
                            
                            document.body.removeChild(overlay);
                            document.body.removeChild(closeButton);
                            document.body.removeChild(backButtonMessage);
                            if (countdownInterval) {
                                clearInterval(countdownInterval);
                            }
                        }
                    });
                    
                    document.body.appendChild(overlay);
                    document.body.appendChild(closeButton);
                    document.body.appendChild(backButtonMessage);
                    
                    const displayTime = pressleanAdclickerConfig.displayTime;
                    let countdownInterval;
                    
                    if (displayTime !== 'null') {
                        let timeLeft = parseInt(displayTime);
                        countdownLabel.textContent = timeLeft;
                        closeButton.appendChild(countdownLabel);
                        
                        countdownInterval = setInterval(function() {
                            timeLeft--;
                            countdownLabel.textContent = timeLeft;
                            
                            if (timeLeft <= 0) {
                                clearInterval(countdownInterval);
                                countdownLabel.textContent = '✕';
                            }
                        }, 1000);
                    }
                }
            });
            ";
            
            wp_add_inline_script('presslearn-adclicker-admin', $adclicker_script);
        }
        
        if (strpos($hook, 'presslearn-dynamic-banner') !== false) {
            wp_register_style('presslearn-dynamic-banner-css', false);
            wp_enqueue_style('presslearn-dynamic-banner-css');
            
            wp_register_script('presslearn-dynamic-banner-js', false, array('jquery'), PRESSLEARN_PLUGIN_VERSION, true);
            wp_enqueue_script('presslearn-dynamic-banner-js');
        }
        
        if (strpos($hook, 'presslearn-social-share') !== false) {
            wp_register_style('presslearn-social-share-css', false);
            wp_enqueue_style('presslearn-social-share-css');
            
            wp_register_script('presslearn-social-share-js', false, array('jquery'), PRESSLEARN_PLUGIN_VERSION, true);
            wp_enqueue_script('presslearn-social-share-js');
        }
        
        if (strpos($hook, 'presslearn-quick-button') !== false) {
            wp_register_style('presslearn-quick-button-css', false);
            wp_enqueue_style('presslearn-quick-button-css');
            
            wp_register_script('presslearn-quick-button-js', false, array('jquery'), PRESSLEARN_PLUGIN_VERSION, true);
            wp_enqueue_script('presslearn-quick-button-js');
        }
        
        wp_localize_script('presslearn-admin-js', 'presslearn_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('presslearn_admin_nonce'),
            'api_url' => rest_url('presslearn/v1'),
            'site_url' => site_url(),
            'ip_address' => presslearn_plugin()->get_ip_address(),
            'is_default_permalink' => empty(get_option('permalink_structure')),
            'block_expiry_days' => get_option('presslearn_click_protection_block_expiry_days', 30)
        ));
    }
    
    public function register_admin_menu() {
        $icon_url = PRESSLEARN_PLUGIN_URL . 'assets/images/admin_badge.png';
        
        add_menu_page(
            'AL Pack 설정',
            'AL Pack',
            'manage_options',
            'presslearn-settings',
            array($this, 'render_settings_page'),
            $icon_url,
            2 
        );
        
        add_submenu_page(
            'presslearn-settings',
            'AL Pack 대시보드',
            '대시보드',
            'manage_options',
            'presslearn-settings',
            array($this, 'render_settings_page')
        );
        
        if ($this->is_activated) {
            add_submenu_page(
                'presslearn-settings',
                '스마트 스크롤',
                '스마트 스크롤',
                'manage_options',
                'presslearn-scroll-depth',
                array($this, 'render_advanced_page')
            );
            
            add_submenu_page(
                'presslearn-settings',
                '씬 애널리틱스',
                '씬 애널리틱스',
                'manage_options',
                'presslearn-analytics',
                array($this, 'render_analytics_page')
            );

            add_submenu_page(
                'presslearn-settings',
                '애드 프로텍터',
                '애드 프로텍터',
                'manage_options',
                'presslearn-click-protection',
                array($this, 'render_click_protection_page')
            );

            add_submenu_page(
                'presslearn-settings',
                '애드클리커',
                '애드클리커',
                'manage_options',
                'presslearn-ad-clicker',
                array($this, 'render_ad_clicker_page')
            );

            add_submenu_page(
                'presslearn-settings',
                '다이나믹 배너',
                '다이나믹 배너',
                'manage_options',
                'presslearn-dynamic-banner',
                array($this, 'render_dynamic_banner_page')
            );

            add_submenu_page(
                'presslearn-settings',
                '소셜 공유',
                '소셜 공유',
                'manage_options',
                'presslearn-social-share',
                array($this, 'render_social_share_page')
            );

            add_submenu_page(
                'presslearn-settings',
                '빠른 버튼 생성',
                '빠른 버튼 생성',
                'manage_options',
                'presslearn-quick-button',  
                array($this, 'render_quick_button_page')
            );

            add_submenu_page(
                'presslearn-settings',
                'Ads 매니저',
                'Ads 매니저',
                'manage_options',
                'presslearn-ads',
                array($this, 'render_ads_page')
            );

            add_submenu_page(
                'presslearn-settings',
                '자동 인덱싱',
                '자동 인덱싱',
                'manage_options',
                'presslearn-auto-index',
                array($this, 'render_auto_index_page')
            );
            
            add_submenu_page(
                'presslearn-settings',
                '헤더 & 푸터',
                '헤더 & 푸터',
                'manage_options',
                'presslearn-header-footer',
                array($this, 'render_header_footer_page')
            );
        }
    }
    
    public function render_settings_page() {
        $show_activated_notice = false;
        $show_access_denied_notice = false;
        
        if (current_user_can('manage_options') && is_admin()) {
            if (isset($_GET['activated']) && sanitize_text_field(wp_unslash($_GET['activated'])) === 'true') {
                $show_activated_notice = true;
            }
            
            if (isset($_GET['access_denied']) && sanitize_text_field(wp_unslash($_GET['access_denied'])) === 'true') {
                $show_access_denied_notice = true;
            }
        }
        
        if ($show_activated_notice) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>프레스런 통합 플러그인이 성공적으로 활성화되었습니다</p>
            </div>
            <?php
        }
        
        if ($show_access_denied_notice) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p>프레스런 통합 플러그인이 활성화되지 않았습니다. 플러그인을 활성화해야 모든 기능에 접근할 수 있습니다.</p>
            </div>
            <?php
        }
        
        include_once PRESSLEARN_PLUGIN_DIR . 'templates/admin-settings.php';
    }
    

    public function render_analytics_page() {
        if (!$this->verify_activation()) {
            return;
        }
        
        include_once PRESSLEARN_PLUGIN_DIR . 'templates/admin-analytics.php';
    }

    public function render_advanced_page() {
        if (!$this->verify_activation()) {
            return;
        }
        
        include_once PRESSLEARN_PLUGIN_DIR . 'templates/admin-scroll-depth.php';
    }

    public function render_click_protection_page() {
        if (!$this->verify_activation()) {
            return;
        }
        
        include_once PRESSLEARN_PLUGIN_DIR . 'templates/admin-click-protection.php';
    }

    public function render_ad_clicker_page() {
        if (!$this->verify_activation()) {
            return;
        }
        
        include_once PRESSLEARN_PLUGIN_DIR . 'templates/admin-adclicker.php';
    }

    public function render_dynamic_banner_page() {
        if (!$this->verify_activation()) {
            return;
        }
        
        include_once PRESSLEARN_PLUGIN_DIR . 'templates/admin-dynamic-banner.php';
    }

    public function render_social_share_page() {
        if (!$this->verify_activation()) {
            return;
        }
        
        include_once PRESSLEARN_PLUGIN_DIR . 'templates/admin-social-share.php';
    }

    public function render_quick_button_page() {
        if (!$this->verify_activation()) {
            return;
        }
        
        include_once PRESSLEARN_PLUGIN_DIR . 'templates/admin-quick-button.php';
    }
    
    public function render_ads_page() {
        if (!$this->verify_activation()) {
            return;
        }
        
        include_once PRESSLEARN_PLUGIN_DIR . 'templates/admin-ads.php';
    }

    public function render_auto_index_page() {
        if (!$this->verify_activation()) {
            return;
        }
        
        include_once PRESSLEARN_PLUGIN_DIR . 'templates/admin-auto-index.php';
    }

    public function render_header_footer_page() {
        if (!$this->verify_activation()) {
            return;
        }
        
        include_once PRESSLEARN_PLUGIN_DIR . 'templates/admin-header-footer.php';
    }

    private function verify_activation() {
        if (!$this->is_activated) {
            wp_redirect(admin_url('admin.php?page=presslearn-settings&access_denied=true'));
            exit;
        }
        
        return true;
    }
    

    public function activate_plugin($key) {
        $is_valid = strlen($key) >= 32;
        
        if ($is_valid) {
            update_option($this->option_key, $key);
            $this->is_activated = true;
            return true;
        }
        
        return false;
    }

    private function check_activation() {
        $key = get_option($this->option_key, '');
        $this->is_activated = !empty($key);
    }

    public function is_plugin_activated() {
        return $this->is_activated;
    }

    public function get_kakao_login_url() {
        $https = isset($_SERVER['HTTPS']) && sanitize_text_field(wp_unslash($_SERVER['HTTPS'])) === 'on';
        $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
        $request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        
        $current_url = ($https ? "https" : "http") . "://" . $host . $request_uri;
        $kakao_login_url = 'https://presslearn.co.kr/login?redirect_url=' . urlencode($current_url) . '&popup=true';
        return $kakao_login_url;
    }

    public function add_tracking_code() {
        $analytics_enabled = get_option('presslearn_analytics_enabled', 'no');
        $exclude_admin = get_option('presslearn_analytics_exclude_admin', '');
        
        if ($analytics_enabled !== 'yes') {
            return;
        }
        
        if (current_user_can('manage_options') && get_option('presslearn_analytics_exclude_admin') === 'yes') {
            return;
        }
        
        $excluded_ips = array_map('trim', explode(',', $exclude_admin));
        $user_ip = $this->get_ip_address();
        
        if (in_array($user_ip, $excluded_ips)) {
            return;
        }
        
        wp_enqueue_script(
            'presslearn-analytics-tracking',
            PRESSLEARN_PLUGIN_URL . 'assets/js/analytics-tracking.js',
            array('jquery'),
            PRESSLEARN_PLUGIN_VERSION,
            true
        );
        
        wp_localize_script(
            'presslearn-analytics-tracking',
            'pressleanAnalytics',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('presslearn_tracking_nonce')
            )
        );
    }
    
    public function get_ip_address() {
        $ip = '';
        $use_cloudflare = get_option('presslearn_analytics_use_cloudflare', 'no');
        
        if ($use_cloudflare === 'yes' && isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CF_CONNECTING_IP']));
        } else {
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip_list = explode(',', sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR'])));
                $ip = trim($ip_list[0]);
            } else {
                $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
            }
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    }
    
    public function get_visitor_country($ip) {
        $ip = filter_var($ip, FILTER_VALIDATE_IP);
        
        if (empty($ip) || $ip == '127.0.0.1' || $ip == '::1') {
            return '';
        }
        
        $api_url = 'http://ip-api.com/json/' . esc_attr($ip);
        $response = wp_remote_get($api_url, array(
            'timeout' => 5,
            'headers' => array(
                'Accept' => 'application/json'
            )
        ));
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (isset($data['country']) && $data['status'] === 'success') {
                return sanitize_text_field($data['country']);
            }
        }
        
        return '';
    }
    
    public function register_tracking_ajax() {
        add_action('wp_ajax_presslearn_track_pageview', array($this, 'track_pageview'));
        add_action('wp_ajax_nopriv_presslearn_track_pageview', array($this, 'track_pageview'));
    }
    
    public function track_pageview() {
        check_ajax_referer('presslearn_tracking_nonce', 'nonce');
        
        $visitor_id = isset($_POST['visitor_id']) ? sanitize_text_field(wp_unslash($_POST['visitor_id'])) : '';
        $url = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';
        $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        $referrer = isset($_POST['referrer']) ? esc_url_raw(wp_unslash($_POST['referrer'])) : '';
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
        $ip = $this->get_ip_address();
        $country = $this->get_visitor_country($ip);
        
        if (!empty($referrer)) {
            if (strpos($referrer, 'http') !== 0 && strpos($referrer, '//') !== 0) {
                $referrer = 'https://' . $referrer;
            }
            
            if (strpos($referrer, '//') === 0) {
                $referrer = 'https:' . $referrer;
            }
            
            $site_host = wp_parse_url(site_url(), PHP_URL_HOST);
            $referrer_host = wp_parse_url($referrer, PHP_URL_HOST);
            
            if ($referrer_host === $site_host) {
            }
            
        } else {
        }
        
        $this->create_analytics_tables();
        
        global $wpdb;
        $table_pageviews = $wpdb->prefix . 'presslearn_pageviews';
        $table_visitors = $wpdb->prefix . 'presslearn_visitors';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_pageviews'") != $table_pageviews) {
            $this->create_analytics_tables();
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_pageviews'") != $table_pageviews) {
                wp_send_json_error('데이터베이스 테이블을 생성할 수 없습니다.');
                wp_die();
            }
        }
        
        $result = $wpdb->insert(
            $table_pageviews,
            array(
                'url' => $url,
                'title' => $title,
                'visitor_id' => $visitor_id,
                'referrer' => $referrer,
                'user_agent' => $user_agent,
                'country' => $country,
                'ip' => $ip,
                'created_at' => current_time('mysql')
            )
        );
        
        if ($wpdb->last_error) {
            wp_send_json_error('데이터베이스 오류: ' . $wpdb->last_error);
            wp_die();
        } else {
            error_log('Pageview record inserted successfully: ' . $result);
            
            $post_id = url_to_postid($url);
            if ($post_id > 0) {
                $cache_date = current_time('Y-m-d');
                wp_cache_delete("presslearn_visitors_{$post_id}_{$cache_date}", 'presslearn_analytics');
                wp_cache_delete("presslearn_today_{$post_id}_{$cache_date}", 'presslearn_analytics');
                wp_cache_delete("presslearn_week_{$post_id}_{$cache_date}", 'presslearn_analytics');
                wp_cache_delete("presslearn_month_{$post_id}_{$cache_date}", 'presslearn_analytics');
                
                delete_post_meta($post_id, '_presslearn_post_views');
            }
        }
        
        $visitor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_visitors WHERE visitor_id = %s",
            $visitor_id
        ));
        
        if ($visitor) {
            $wpdb->update(
                $table_visitors,
                array(
                    'last_visit' => current_time('mysql'),
                    'visits' => $visitor->visits + 1
                ),
                array('visitor_id' => $visitor_id)
            );
        } else {
            $wpdb->insert(
                $table_visitors,
                array(
                    'visitor_id' => $visitor_id,
                    'first_visit' => current_time('mysql'),
                    'last_visit' => current_time('mysql')
                )
            );
        }
        
        if (!empty($referrer)) {
            $referrer_host = wp_parse_url($referrer, PHP_URL_HOST);
            error_log('Referrer host: ' . $referrer_host);
            
            if (!empty($referrer_host)) {
                $table_referrers = $wpdb->prefix . 'presslearn_referrers';
                
                if ($wpdb->get_var("SHOW TABLES LIKE '$table_referrers'") != $table_referrers) {
                    $this->create_analytics_tables();
                }
                
                $existing_referrer = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table_referrers WHERE referrer_host = %s",
                    $referrer_host
                ));
                
                if ($existing_referrer) {
                    $wpdb->update(
                        $table_referrers,
                        array(
                            'count' => $existing_referrer->count + 1,
                            'last_visit' => current_time('mysql')
                        ),
                        array('id' => $existing_referrer->id)
                    );
                } else {
                    $wpdb->insert(
                        $table_referrers,
                        array(
                            'referrer_host' => $referrer_host,
                            'referrer_url' => $referrer,
                            'count' => 1,
                            'last_visit' => current_time('mysql')
                        )
                    );
                }
                
                if ($wpdb->last_error) {
                    error_log('Referrer insert/update error: ' . $wpdb->last_error);
                } else {
                    error_log('Referrer record processed for: ' . $referrer_host);
                }
            }
        }
        
        wp_send_json_success();
        wp_die();
    }
    
    public function create_analytics_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_pageviews = $wpdb->prefix . 'presslearn_pageviews';
        $table_visitors = $wpdb->prefix . 'presslearn_visitors';
        $table_referrers = $wpdb->prefix . 'presslearn_referrers';
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $wpdb->suppress_errors();
        
        $tables_created = true;
        
        if($wpdb->get_var("SHOW TABLES LIKE '$table_pageviews'") != $table_pageviews) {
            $sql = "CREATE TABLE IF NOT EXISTS $table_pageviews (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                url varchar(255) NOT NULL,
                title text NOT NULL,
                visitor_id varchar(32) NOT NULL,
                referrer text,
                user_agent text,
                country varchar(50),
                ip varchar(100),
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY visitor_id (visitor_id),
                KEY url (url),
                KEY created_at (created_at)
            ) $charset_collate;";
            
            $result = dbDelta($sql);
            if (empty($result)) {
                $tables_created = false;
                error_log('Error creating presslearn_pageviews table');
            }
        }
        
        if($wpdb->get_var("SHOW TABLES LIKE '$table_visitors'") != $table_visitors) {
            $sql = "CREATE TABLE IF NOT EXISTS $table_visitors (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                visitor_id varchar(32) NOT NULL,
                first_visit datetime DEFAULT CURRENT_TIMESTAMP,
                last_visit datetime DEFAULT CURRENT_TIMESTAMP,
                visits int(11) DEFAULT 1,
                PRIMARY KEY  (id),
                UNIQUE KEY visitor_id (visitor_id),
                KEY first_visit (first_visit),
                KEY last_visit (last_visit)
            ) $charset_collate;";
            
            $result = dbDelta($sql);
            if (empty($result)) {
                $tables_created = false;
                error_log('Error creating presslearn_visitors table');
            }
        }
        
        if($wpdb->get_var("SHOW TABLES LIKE '$table_referrers'") != $table_referrers) {
            $sql = "CREATE TABLE IF NOT EXISTS $table_referrers (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                referrer_host varchar(255),
                referrer_url text,
                count int(11) DEFAULT 1,
                last_visit datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY referrer_host (referrer_host),
                KEY last_visit (last_visit)
            ) $charset_collate;";
            
            $result = dbDelta($sql);
            if (empty($result)) {
                $tables_created = false;
                error_log('Error creating presslearn_referrers table');
            }
        }
        
        return $tables_created;
    }

    public function get_visitor_ip_for_protection() {
        $use_cloudflare = get_option('presslearn_click_protection_use_cloudflare', 'no');
        
        $ip = '';
        
        if ($use_cloudflare === 'yes' && isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CF_CONNECTING_IP']));
        } 
        else {
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip_list = explode(',', sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR'])));
                $ip = trim($ip_list[0]);
            } else {
                $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
            }
        }
        
        return $ip;
    }

    public function add_admin_bar_menu($wp_admin_bar) {
        if (!$this->is_activated) {
            return;
        }

        $wp_admin_bar->add_node(array(
            'id'    => 'presslearn-menu',
            'title' => 'AL Pack',
            'href'  => admin_url('admin.php?page=presslearn-settings'),
            'meta'  => array(
                'title' => 'AL Pack'
            )
        ));

        $wp_admin_bar->add_node(array(
            'id'     => 'presslearn-settings',
            'parent' => 'presslearn-menu',
            'title'  => '대시보드',
            'href'   => admin_url('admin.php?page=presslearn-settings')
        ));

        $wp_admin_bar->add_node(array(
            'id'     => 'presslearn-analytics',
            'parent' => 'presslearn-menu',
            'title'  => '씬 애널리틱스',
            'href'   => admin_url('admin.php?page=presslearn-analytics')
        ));

        $wp_admin_bar->add_node(array(
            'id'     => 'presslearn-scroll-depth',
            'parent' => 'presslearn-menu',
            'title'  => '스마트 스크롤',
            'href'   => admin_url('admin.php?page=presslearn-scroll-depth')
        ));

        $wp_admin_bar->add_node(array(
            'id'     => 'presslearn-click-protection',
            'parent' => 'presslearn-menu',
            'title'  => '애드 프로텍터',
            'href'   => admin_url('admin.php?page=presslearn-click-protection')
        ));

        $wp_admin_bar->add_node(array(
            'id'     => 'presslearn-ad-clicker',
            'parent' => 'presslearn-menu',
            'title'  => '애드클리커',
            'href'   => admin_url('admin.php?page=presslearn-ad-clicker')
        ));

        $wp_admin_bar->add_node(array(
            'id'     => 'presslearn-dynamic-banner',
            'parent' => 'presslearn-menu',
            'title'  => '다이나믹 배너',
            'href'   => admin_url('admin.php?page=presslearn-dynamic-banner')
        ));

        $wp_admin_bar->add_node(array(
            'id'     => 'presslearn-social-share',
            'parent' => 'presslearn-menu',
            'title'  => '소셜 공유',
            'href'   => admin_url('admin.php?page=presslearn-social-share')
        ));

        $wp_admin_bar->add_node(array(
            'id'     => 'presslearn-quick-button',
            'parent' => 'presslearn-menu',
            'title'  => '빠른 버튼 생성',
            'href'   => admin_url('admin.php?page=presslearn-quick-button')
        ));
        
        $wp_admin_bar->add_node(array(
            'id'     => 'presslearn-ads',
            'parent' => 'presslearn-menu',
            'title'  => 'Ads 매니저',
            'href'   => admin_url('admin.php?page=presslearn-ads')
        ));
        
        $wp_admin_bar->add_node(array(
            'id'     => 'presslearn-auto-index',
            'parent' => 'presslearn-menu',
            'title'  => '자동 인덱싱',
            'href'   => admin_url('admin.php?page=presslearn-auto-index')
        ));
        
        $wp_admin_bar->add_node(array(
            'id'     => 'presslearn-header-footer',
            'parent' => 'presslearn-menu',
            'title'  => '헤더 & 푸터',
            'href'   => admin_url('admin.php?page=presslearn-header-footer')
        ));
    }
}

function presslearn_plugin() {
    return PressLearn_Plugin::get_instance();
}

presslearn_plugin();

add_action('wp_ajax_presslearn_get_allowed_ips', 'presslearn_get_allowed_ips');
add_action('wp_ajax_presslearn_add_allowed_ip', 'presslearn_add_allowed_ip');
add_action('wp_ajax_presslearn_delete_allowed_ip', 'presslearn_delete_allowed_ip');
add_action('wp_ajax_presslearn_get_blocked_ips', 'presslearn_get_blocked_ips');
add_action('wp_ajax_presslearn_add_blocked_ip', 'presslearn_add_blocked_ip');
add_action('wp_ajax_presslearn_delete_blocked_ip', 'presslearn_delete_blocked_ip');
add_action('wp_ajax_presslearn_delete_analytics_data', 'presslearn_delete_analytics_data');

function presslearn_get_allowed_ips() {
    check_ajax_referer('presslearn_ip_nonce', 'nonce');
    
    if (current_user_can('manage_options')) {
        $allowed_ips = get_option('presslearn_click_protection_allowed_ips', array());
        
        wp_send_json_success(array(
            'ips' => $allowed_ips
        ));
    }
    
    wp_send_json_error(array('message' => '권한이 없습니다.'));
    wp_die();
}

function presslearn_add_allowed_ip() {
    check_ajax_referer('presslearn_ip_nonce', 'nonce');
    
    if (current_user_can('manage_options')) {
        $ip = isset($_POST['ip']) ? sanitize_text_field(wp_unslash($_POST['ip'])) : '';
        
        if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
            wp_send_json_error(array('message' => '유효하지 않은 IP 주소입니다.'));
            wp_die();
        }
        
        $allowed_ips = get_option('presslearn_click_protection_allowed_ips', array());
        
        foreach ($allowed_ips as $item) {
            if ($item['ip'] === $ip) {
                wp_send_json_error(array('message' => '이미 등록된 IP 주소입니다.'));
                wp_die();
            }
        }
        
        $allowed_ips[] = array(
            'ip' => $ip,
            'date' => current_time('Y-m-d')
        );
        
        update_option('presslearn_click_protection_allowed_ips', $allowed_ips);
        
        wp_send_json_success(array(
            'message' => 'IP가 성공적으로 추가되었습니다.',
            'ips' => $allowed_ips
        ));
    }
    
    wp_die();
}

function presslearn_delete_allowed_ip() {
    check_ajax_referer('presslearn_ip_nonce', 'nonce');
    
    if (current_user_can('manage_options')) {
        $ip = isset($_POST['ip']) ? sanitize_text_field(wp_unslash($_POST['ip'])) : '';
        
        if (empty($ip)) {
            wp_send_json_error(array('message' => '삭제할 IP 주소가 지정되지 않았습니다.'));
            wp_die();
        }
        
        $allowed_ips = get_option('presslearn_click_protection_allowed_ips', array());
        
        foreach ($allowed_ips as $key => $item) {
            if ($item['ip'] === $ip) {
                unset($allowed_ips[$key]);
                break;
            }
        }
        
        $allowed_ips = array_values($allowed_ips);
        
        update_option('presslearn_click_protection_allowed_ips', $allowed_ips);
        
        wp_send_json_success(array(
            'message' => 'IP가 성공적으로 삭제되었습니다.',
            'ips' => $allowed_ips
        ));
    }
    
    wp_die();
}

function presslearn_get_blocked_ips() {
    check_ajax_referer('presslearn_ip_nonce', 'nonce');
    
    if (current_user_can('manage_options')) {
        $blocked_ips = get_option('presslearn_click_protection_blocked_ips', array());
        
        wp_send_json_success(array(
            'ips' => $blocked_ips
        ));
    }
    
    wp_send_json_error(array('message' => '권한이 없습니다.'));
    wp_die();
}

function presslearn_add_blocked_ip() {
    check_ajax_referer('presslearn_ip_nonce', 'nonce');
    
    if (current_user_can('manage_options')) {
        $ip = isset($_POST['ip']) ? sanitize_text_field(wp_unslash($_POST['ip'])) : '';
        $permanent = isset($_POST['permanent']) ? sanitize_text_field(wp_unslash($_POST['permanent'])) : '0';
        
        if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
            wp_send_json_error(array('message' => '유효하지 않은 IP 주소입니다.'));
            wp_die();
        }
        
        $blocked_ips = get_option('presslearn_click_protection_blocked_ips', array());
        
        foreach ($blocked_ips as $item) {
            if ($item['ip'] === $ip) {
                wp_send_json_error(array('message' => '이미 차단된 IP 주소입니다.'));
                wp_die();
            }
        }
        
        $current_date = current_time('Y-m-d');

        if ($permanent === '1') {
            $expires = '';
            $reason = '수동 차단 (영구)';
        } else {
            $block_expiry_days = get_option('presslearn_click_protection_block_expiry_days', 30);
            $expires = $block_expiry_days > 0 ? gmdate('Y-m-d', strtotime("+{$block_expiry_days} days")) : '';
            $reason = '수동 차단';
        }
        
        $blocked_ips[] = array(
            'ip' => $ip,
            'date' => $current_date,
            'block_date' => $current_date,
            'reason' => $reason,
            'expires' => $expires
        );
        
        update_option('presslearn_click_protection_blocked_ips', $blocked_ips);
        
        wp_send_json_success(array(
            'message' => 'IP가 성공적으로 차단되었습니다.',
            'ips' => $blocked_ips
        ));
    }
    
    wp_die();
}

function presslearn_delete_blocked_ip() {
    check_ajax_referer('presslearn_ip_nonce', 'nonce');
    
    if (current_user_can('manage_options')) {
        $ip = isset($_POST['ip']) ? sanitize_text_field(wp_unslash($_POST['ip'])) : '';
        
        if (empty($ip)) {
            wp_send_json_error(array('message' => '삭제할 IP 주소가 지정되지 않았습니다.'));
            wp_die();
        }
        
        $lock_key = 'presslearn_delete_blocked_' . md5($ip);
        if (get_transient($lock_key)) {
            wp_send_json_error(array('message' => '다른 요청이 처리 중입니다. 잠시 후 다시 시도해주세요.'));
            wp_die();
        }
        
        set_transient($lock_key, true, 3);
        
        try {
            $blocked_ips = get_option('presslearn_click_protection_blocked_ips', array());
            $ip_found = false;
            
            foreach ($blocked_ips as $key => $item) {
                if ($item['ip'] === $ip) {
                    unset($blocked_ips[$key]);
                    $ip_found = true;
                    break;
                }
            }
            
            if (!$ip_found) {
                delete_transient($lock_key);
                wp_send_json_error(array('message' => '차단 목록에서 해당 IP를 찾을 수 없습니다.'));
                wp_die();
            }
            
            $blocked_ips = array_values($blocked_ips);
            
            update_option('presslearn_click_protection_blocked_ips_backup_delete', get_option('presslearn_click_protection_blocked_ips', array()));
            
            update_option('presslearn_click_protection_blocked_ips', $blocked_ips);
            
            $reset_signals = get_option('presslearn_reset_localStorage_signals', array());
            $reset_signals[$ip] = array(
                'timestamp' => time(),
                'expires' => time() + 3600
            );
            update_option('presslearn_reset_localStorage_signals', $reset_signals);
            
            delete_transient($lock_key);
            
            wp_send_json_success(array(
                'message' => 'IP 차단이 성공적으로 해제되었습니다.',
                'ips' => $blocked_ips,
                'reset_signal_sent' => true
            ));
            
        } catch (Exception $e) {
            delete_transient($lock_key);
            wp_send_json_error(array('message' => '서버 오류가 발생했습니다.'));
        }
    }
    
    wp_die();
}

add_action('init', 'presslearn_setup_cron_for_ip_unblock');

function presslearn_setup_cron_for_ip_unblock() {
    if (!wp_next_scheduled('presslearn_check_blocked_ips_expiry')) {
        wp_schedule_event(strtotime('tomorrow midnight'), 'daily', 'presslearn_check_blocked_ips_expiry');
    }
}

add_action('presslearn_check_blocked_ips_expiry', 'presslearn_check_and_remove_expired_blocked_ips');

function presslearn_cleanup_expired_reset_signals() {
    $reset_signals = get_option('presslearn_reset_localStorage_signals', array());
    $current_time = time();
    $updated = false;
    
    foreach ($reset_signals as $ip => $signal) {
        if (isset($signal['expires']) && $current_time > $signal['expires']) {
            unset($reset_signals[$ip]);
            $updated = true;
        }
    }
    
    if ($updated) {
        update_option('presslearn_reset_localStorage_signals', $reset_signals);
    }
}

add_action('presslearn_check_blocked_ips_expiry', 'presslearn_cleanup_expired_reset_signals');

function presslearn_check_and_remove_expired_blocked_ips() {
    $blocked_ips = get_option('presslearn_click_protection_blocked_ips', array());
    if (empty($blocked_ips)) {
        return;
    }
    
    $block_expiry_days = get_option('presslearn_click_protection_block_expiry_days', 30);
    
    $current_date = new DateTime(current_time('Y-m-d'));
    $updated = false;
    
    $new_blocked_ips = array();
    
    foreach ($blocked_ips as $item) {
        $block_date = isset($item['block_date']) ? $item['block_date'] : $item['date'];
        
        $block_date_obj = new DateTime($block_date);
        
        $interval = $current_date->diff($block_date_obj);
        $days_since_blocked = $interval->days;
        
        if ($days_since_blocked < $block_expiry_days) {
            $new_blocked_ips[] = $item;
        } else {
            $updated = true;
        }
    }
    
    if ($updated) {
        update_option('presslearn_click_protection_blocked_ips', $new_blocked_ips);
    }
}

register_deactivation_hook(__FILE__, 'presslearn_clear_cron_for_ip_unblock');

function presslearn_clear_cron_for_ip_unblock() {
    $timestamp = wp_next_scheduled('presslearn_check_blocked_ips_expiry');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'presslearn_check_blocked_ips_expiry');
    }
        
    delete_option('presslearn_reset_localStorage_signals');
    
    delete_option('presslearn_click_protection_blocked_ips_backup');
    delete_option('presslearn_click_protection_blocked_ips_backup_delete');
}

function presslearn_block_ads_for_blocked_ips() {
    if (is_admin()) {
        return;
    }

    $click_protection_enabled = get_option('presslearn_click_protection_enabled', 'no');
    if ($click_protection_enabled !== 'yes') {
        return;
    }

    $use_cloudflare = get_option('presslearn_click_protection_use_cloudflare', 'no');
    
    $blocked_ips = get_option('presslearn_click_protection_blocked_ips', array());
    if (empty($blocked_ips)) {
        return;
    }

    $user_ip = presslearn_plugin()->get_visitor_ip_for_protection();
    
    $is_blocked = false;

    foreach ($blocked_ips as $item) {
        if ($item['ip'] === $user_ip) {
            $is_blocked = true;
            break;
        }
    }

    if (!$is_blocked) {
        return;
    }

    setcookie('pl_ad_blocked', '1', time() + 86400, '/');
    
    wp_register_style('presslearn-ad-block-css', false);
    wp_enqueue_style('presslearn-ad-block-css');
    
    $ad_block_css = "
    #google_esf, 
    .adsbygoogle,
    iframe[id^=\"google_ads_\"],
    ins.adsbygoogle,
    ins.adsbygoogle-noablate,
    iframe[id^=\"aswift_\"],
    div[id^=\"aswift_\"],
    [data-ad-status],
    [data-adsbygoogle-status],
    [data-google-query-id],
    [data-google-container-id],
    [data-ad-format],
    ins[class*=\"adsbygoogle\"] {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        width: 0 !important;
        height: 0 !important;
        position: absolute !important;
        left: -9999px !important;
        top: -9999px !important;
        pointer-events: none !important;
        max-width: 0 !important;
        max-height: 0 !important;
        overflow: hidden !important;
    }
    
    .right-side-rail-edge,
    .right-side-rail-dismiss-btn,
    [data-side-rail-status] {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
    }
    ";
    
    wp_add_inline_style('presslearn-ad-block-css', $ad_block_css);
    
    wp_register_script('presslearn-ad-block-js', false, array(), PRESSLEARN_PLUGIN_VERSION, true);
    wp_enqueue_script('presslearn-ad-block-js');
    
    $ad_block_script = "
    (function() {
        function preventAdLoad() {
            
            window.adsbygoogle = [];
            window.google_ad_status = 1;
            
            var adScripts = document.querySelectorAll('script[src*=\"pagead2.googlesyndication.com\"], script[src*=\"googleads\"], script[src*=\"adsbygoogle\"]');
            adScripts.forEach(function(script) {
                if (script && script.parentNode) {
                    script.parentNode.removeChild(script);
                }
            });
        }
        
        preventAdLoad();
    })();
    
    document.addEventListener('DOMContentLoaded', function() {
        
        function removeAdsElements() {
            const esfElements = document.querySelectorAll('#google_esf');
            esfElements.forEach(element => {
                element.style.display = 'none !important';
                if (element.parentNode) {
                    element.parentNode.removeChild(element);
                }
            });
            
            const adsSelectors = [
                '#adsbygoogle', 
                '.adsbygoogle', 
                'ins.adsbygoogle',
                'ins.adsbygoogle-noablate',
                'iframe[id^=\"google_ads_\"]',
                'iframe[id^=\"aswift_\"]',
                'div[id^=\"aswift_\"]',
                '[data-ad-status]',
                '[data-adsbygoogle-status]',
                '[data-google-query-id]',
                '[data-google-container-id]',
                '[data-ad-format]'
            ];
            
            adsSelectors.forEach(selector => {
                const elements = document.querySelectorAll(selector);
                elements.forEach(element => {
                    element.style.display = 'none !important';
                    if (element.parentNode) {
                        element.parentNode.removeChild(element);
                    }
                });
            });
            
            const styleElement = document.createElement('style');
            styleElement.textContent = 
                '#google_esf, ' + 
                '#adsbygoogle,' +
                '.adsbygoogle,' +
                'ins.adsbygoogle,' +
                'ins.adsbygoogle-noablate,' +
                'iframe[id^=\"google_ads_\"],' +
                'iframe[id^=\"aswift_\"],' +
                'div[id^=\"aswift_\"],' +
                '[data-ad-status],' +
                '[data-adsbygoogle-status],' +
                '[data-google-query-id],' +
                '[data-google-container-id],' +
                '[data-ad-format] { ' + 
                    'display: none !important; ' + 
                    'visibility: hidden !important;' +
                    'opacity: 0 !important;' +
                    'pointer-events: none !important;' +
                    'width: 0px !important;' +
                    'height: 0px !important;' +
                    'position: absolute !important;' +
                    'top: -9999px !important;' +
                    'left: -9999px !important;' +
                '}';
            document.head.appendChild(styleElement);
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', removeAdsElements);
        } else {
            removeAdsElements();
        }
        
        const adObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                    for (let i = 0; i < mutation.addedNodes.length; i++) {
                        const node = mutation.addedNodes[i];
                        if (node.id === 'google_esf' || node.id === 'adsbygoogle') {
                            node.style.display = 'none !important';
                            if (node.parentNode) {
                                node.parentNode.removeChild(node);
                            }
                        }
                    }
                }
            });
        });
        
        adObserver.observe(document.documentElement, {
            childList: true,
            subtree: true
        });
    });
    ";
    
    wp_add_inline_script('presslearn-ad-block-js', $ad_block_script);
        
    wp_register_script('presslearn-clear-blocked-flag-js', false, array(), PRESSLEARN_PLUGIN_VERSION, true);
    wp_enqueue_script('presslearn-clear-blocked-flag-js');
    
    $clear_flag_script = "
    (function() {
        try {
            localStorage.setItem('adsenseBlocked', 'false');
            localStorage.setItem('adsenseClickCount', '0');
            localStorage.setItem('adsenseFirstClickTime', '0');
        } catch (e) {}
    })();
    ";
    
    wp_add_inline_script('presslearn-clear-blocked-flag-js', $clear_flag_script);
}

add_action('wp_body_open', 'presslearn_block_ads_for_blocked_ips', 1);
add_action('wp_footer', 'presslearn_check_blocked_status', 1);

function presslearn_check_blocked_status() {
    if (is_admin()) {
        return;
    }

    $click_protection_enabled = get_option('presslearn_click_protection_enabled', 'no');
    if ($click_protection_enabled !== 'yes') {
        return;
    }
    
    $blocked_ips = get_option('presslearn_click_protection_blocked_ips', array());
    if (empty($blocked_ips)) {
        return;
    }

    $user_ip = presslearn_plugin()->get_visitor_ip_for_protection();
    $is_blocked = false;

    foreach ($blocked_ips as $item) {
        if ($item['ip'] === $user_ip) {
            $is_blocked = true;
            break;
        }
    }

    if (!$is_blocked) {
        return;
    }
    
    wp_register_script('presslearn-blocked-status-js', false, array(), PRESSLEARN_PLUGIN_VERSION, true);
    wp_enqueue_script('presslearn-blocked-status-js');
    
    $blocked_status_script = "
    (function() {
        
        function removeAds() {
            var selectors = [
                '#google_esf', 
                '.adsbygoogle', 
                'ins.adsbygoogle',
                'ins.adsbygoogle-noablate',
                'iframe[id^=\"google_ads_\"]',
                'iframe[id^=\"aswift_\"]',
                'div[id^=\"aswift_\"]',
                '[data-ad-status]',
                '[data-adsbygoogle-status]',
                '[data-google-query-id]',
                '[data-google-container-id]',
                '[data-ad-format]',
                'ins[class*=\"adsbygoogle\"],
                .right-side-rail-edge,
                .right-side-rail-dismiss-btn,
                '[data-side-rail-status]'
            ];
            
            selectors.forEach(function(selector) {
                var elements = document.querySelectorAll(selector);
                elements.forEach(function(element) {
                    if (element && element.parentNode) {
                        element.parentNode.removeChild(element);
                    }
                });
            });
        }
        
        removeAds();
        setTimeout(removeAds, 1000);
    })();
    ";
    
    wp_add_inline_script('presslearn-blocked-status-js', $blocked_status_script);
}

function presslearn_add_ad_protection_script() {
    if (is_admin()) {
        return;
    }

    $click_protection_enabled = get_option('presslearn_click_protection_enabled', 'no');
    if ($click_protection_enabled !== 'yes') {
        return;
    }

    $blocked_ips = get_option('presslearn_click_protection_blocked_ips', array());
    $user_ip = presslearn_plugin()->get_visitor_ip_for_protection();
    
    $allowed_ips = get_option('presslearn_click_protection_allowed_ips', array());
    $is_allowed = false;
    
    foreach ($allowed_ips as $item) {
        if ($item['ip'] === $user_ip) {
            $is_allowed = true;
            break;
        }
    }
    
    $is_blocked = false;
    foreach ($blocked_ips as $item) {
        if ($item['ip'] === $user_ip) {
            $is_blocked = true;
            break;
        }
    }
    
    if ($is_allowed) {
        return;
    }
    
    if ($is_blocked) {
        presslearn_block_ads_for_blocked_ips();
        return;
    }
    
    $max_click_count = intval(get_option('presslearn_max_click_count', 10));
    $click_time_window = intval(get_option('presslearn_click_time_window', 30));

    wp_register_style('presslearn-protection-modal-css', false);
    wp_enqueue_style('presslearn-protection-modal-css');
    
    $modal_css = "
    .pl-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        z-index: 999999;
        justify-content: center;
        align-items: center;
        overflow: hidden;
    }
    
    .pl-modal {
        background: white;
        padding: 30px;
        border-radius: 8px;
        max-width: 500px;
        width: 90%;
        text-align: center;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
        position: relative;
    }
    
    .pl-modal h2 {
        margin-top: 0;
        font-size: 24px;
        color: #d32f2f;
    }
    
    .pl-modal p {
        margin: 15px 0;
        font-size: 16px;
        line-height: 1.5;
    }
    
    .pl-modal-close {
        display: inline-block;
        background: #d32f2f;
        color: white;
        padding: 10px 20px;
        margin-top: 20px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
        border: none;
    }
    
    .pl-modal-close:hover {
        background: #b71c1c;
    }
    
    .pl-blocked-ad-notice {
        display: block;
        padding: 15px;
        background-color: #fff8f8;
        border: 1px solid #ffdddd;
        text-align: center;
        font-size: 14px;
        color: #d32f2f;
        margin: 15px 0;
        border-radius: 4px;
        font-weight: bold;
    }
    
    body.pl-modal-open {
        overflow: hidden;
    }
    ";
    
    wp_add_inline_style('presslearn-protection-modal-css', $modal_css);
    
    add_action('wp_footer', function() {
        ?>
        <div class="pl-modal-overlay" id="pl-block-modal">
            <div class="pl-modal">
                <h2><?php echo esc_html(get_option('presslearn_modal_title', '광고 차단 알림')); ?></h2>
                <p><?php echo esc_html(get_option('presslearn_modal_message', '광고 클릭 제한을 초과하여 광고가 차단되었습니다.')); ?></p>
                <p><?php echo esc_html(get_option('presslearn_modal_submessage', '단시간에 반복적인 광고 클릭은 시스템에 의해 감지되며, IP가 수집되어 사이트 관리자가 확인 가능합니다.')); ?></p>
                <button class="pl-modal-close" id="pl-modal-close"><?php echo esc_html(get_option('presslearn_modal_button_text', '확인')); ?></button>
            </div>
        </div>
        <?php
    });
    
    wp_register_script('presslearn-protection-script-js', false, array(), PRESSLEARN_PLUGIN_VERSION, true);
    wp_enqueue_script('presslearn-protection-script-js');
    
    $user_ip = presslearn_plugin()->get_visitor_ip_for_protection();
    $reset_signals = get_option('presslearn_reset_localStorage_signals', array());
    $should_reset = false;
    
    if (isset($reset_signals[$user_ip])) {
        $signal = $reset_signals[$user_ip];
        if (time() - $signal['timestamp'] < 3600) {
            $should_reset = true;
            unset($reset_signals[$user_ip]);
            update_option('presslearn_reset_localStorage_signals', $reset_signals);
        }
    }
    
    $protection_script = "
    (function() {
        function getStorageItem(name, defaultValue) {
            try {
                const item = localStorage.getItem(name);
                return item !== null ? item : defaultValue;
            } catch (e) {
                return defaultValue;
            }
        }
        
        function setStorageItem(name, value) {
            try {
                localStorage.setItem(name, value);
                return true;
            } catch (e) {
                return false;
            }
        }

        function resetLocalStorageForUnblock() {
            try {
                localStorage.removeItem('adsenseBlocked');
                localStorage.removeItem('adsenseClickCount');
                localStorage.removeItem('adsenseBlockedTime');
                localStorage.removeItem('lastClickTime');
                localStorage.removeItem('adsenseFirstClickTime');
                
                setStorageItem('adsenseBlocked', 'false');
                setStorageItem('adsenseClickCount', '0');
                setStorageItem('adsenseFirstClickTime', '0');
                
                return true;
            } catch (e) {
                return false;
            }
        }
        
        " . ($should_reset ? "resetLocalStorageForUnblock();" : "") . "
        
        const maxClickCount = " . esc_js($max_click_count) . ";
        const clickTimeWindow = " . esc_js($click_time_window) . ";
        
        function checkServerBlockStatus() {
            fetch('" . esc_js(admin_url('admin-ajax.php')) . "', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'presslearn_check_ip_block_status',
                    nonce: '" . esc_js(wp_create_nonce('presslearn_check_block_status')) . "'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (!data.data.is_blocked) {
                        const localBlocked = getStorageItem('adsenseBlocked') === 'true';
                        if (localBlocked) {
                            setStorageItem('adsenseBlocked', 'false');
                            setStorageItem('adsenseBlockedTime', '0');
                        }
                    } else {
                        setStorageItem('adsenseBlocked', 'true');
                        if (!getStorageItem('adsenseBlockedTime')) {
                            setStorageItem('adsenseBlockedTime', Date.now().toString());
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Block status check failed:', error);
            });
        }
        
        checkServerBlockStatus();

        function checkAndResetClickCount() {
            const firstClickTime = getStorageItem('adsenseFirstClickTime', '0');
            const currentTime = Date.now();
            const timeWindowMs = clickTimeWindow * 60 * 1000;
            
            if (firstClickTime !== '0' && (currentTime - parseInt(firstClickTime)) > timeWindowMs) {
                setStorageItem('adsenseClickCount', '0');
                setStorageItem('adsenseFirstClickTime', '0');
                return 0;
            }
            
            return getStorageItem('adsenseClickCount') ? 
                parseInt(getStorageItem('adsenseClickCount')) : 0;
        }
        
        const clickCount = checkAndResetClickCount();
        
        let isBlocked = getStorageItem('adsenseBlocked') === 'true';
        const blockedTime = getStorageItem('adsenseBlockedTime');
        
        if (isBlocked) {
            if (!blockedTime || isNaN(parseInt(blockedTime))) {
                setStorageItem('adsenseBlocked', 'false');
                setStorageItem('adsenseBlockedTime', '0');
                isBlocked = false;
            } else {
                const timeDiff = Date.now() - parseInt(blockedTime);
                const blockExpiryDays = window.presslearn_protection_config ? window.presslearn_protection_config.block_expiry_days : 30;
                if (timeDiff > blockExpiryDays * 24 * 60 * 60 * 1000) {
                    setStorageItem('adsenseBlocked', 'false');
                    setStorageItem('adsenseClickCount', '0');
                    setStorageItem('adsenseFirstClickTime', '0');
                    setStorageItem('adsenseBlockedTime', '0');
                    isBlocked = false;
                }
            }
        }
        
        if (clickCount >= maxClickCount || isBlocked) {
            removeAllAds();
        }
        
        function isAdsenseAd(element) {
            if (!element) return false;
            
            if (element.tagName === 'IFRAME' && element.src && 
                element.src.includes('google')) {
                return true;
            }
            
            if (element.tagName === 'INS' && 
                element.hasAttribute('data-ad-client') && 
                element.getAttribute('data-ad-client').includes('pub-')) {
                return true;
            }
            
            if (element.tagName === 'DIV' && 
                (element.id === 'google_vignette' || 
                 element.id === 'google_anchor' || 
                 element.className.includes('google-auto-placed') ||
                 element.hasAttribute('data-vignette-loaded') ||
                 element.hasAttribute('data-anchor-status'))) {
                return true;
            }
            
            if (element.tagName === 'IFRAME' && 
                (element.id.includes('google_ads_iframe') || 
                 element.hasAttribute('data-google-container-id') ||
                 (element.src && (element.src.includes('googleads') || 
                                 element.src.includes('doubleclick'))))) {
                return true;
            }
            
            return false;
        }
        
        function removeAllAds() {
            const selectors = [
                '#google_esf', 
                '.adsbygoogle', 
                'ins.adsbygoogle',
                'ins.adsbygoogle-noablate',
                'iframe[id^=\"google_ads_\"]',
                'iframe[id^=\"aswift_\"]',
                'div[id^=\"aswift_\"]',
                '[data-ad-status]',
                '[data-adsbygoogle-status]',
                '[data-google-query-id]',
                '[data-google-container-id]',
                '[data-ad-format]',
                '#google_vignette',
                '#google_anchor',
                '.google-auto-placed',
                '[data-vignette-loaded]',
                '[data-anchor-status]',
                'iframe[id*=\"google_ads_iframe\"]',
                'iframe[src*=\"googleads\"]',
                'iframe[src*=\"doubleclick\"]',
                'div[id^=\"google_ads_iframe\"]',
                'div.right-side-rail-edge',
                'div[data-side-rail-status]'
            ];
            
            const style = document.createElement('style');
            style.textContent = 
                '#google_esf, ' +
                '.adsbygoogle,' +
                'iframe[id^=\"google_ads_\"],' +
                'ins.adsbygoogle,' +
                'ins.adsbygoogle-noablate,' +
                'iframe[id^=\"aswift_\"],' +
                'div[id^=\"aswift_\"],' +
                '[data-ad-status],' +
                '[data-adsbygoogle-status],' +
                '[data-google-query-id],' +
                '[data-google-container-id],' +
                '[data-ad-format],' +
                '#google_vignette,' +
                '#google_anchor,' +
                '.google-auto-placed,' +
                '[data-vignette-loaded],' +
                '[data-anchor-status],' +
                'iframe[id*=\"google_ads_iframe\"],' +
                'iframe[src*=\"googleads\"],' +
                'iframe[src*=\"doubleclick\"],' +
                'div[id^=\"google_ads_iframe\"],' +
                'div.right-side-rail-edge,' +
                'div[data-side-rail-status] {' +
                    'display: none !important;' +
                    'visibility: hidden !important;' +
                    'opacity: 0 !important;' +
                    'width: 0 !important;' +
                    'height: 0 !important;' +
                    'position: absolute !important;' +
                    'left: -9999px !important;' +
                    'top: -9999px !important;' +
                '}';
            document.head.appendChild(style);
            
            selectors.forEach(selector => {
                document.querySelectorAll(selector).forEach(element => {
                    if (element && element.parentNode) {
                        element.parentNode.removeChild(element);
                    }
                });
            });
        }
        
        function blockCurrentIP() {
            const formData = new FormData();
            formData.append('action', 'presslearn_block_current_ip');
            formData.append('nonce', '" . esc_js(wp_create_nonce('presslearn_block_ip_nonce')) . "');
            
            fetch('" . esc_url(admin_url('admin-ajax.php')) . "', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });
        }
        
        function showBlockModal() {
            removeAllAds();
            
            const modal = document.getElementById('pl-block-modal');
            modal.style.display = 'flex';
            document.body.classList.add('pl-modal-open');
        }
        
        document.getElementById('pl-modal-close').addEventListener('click', function() {
            const modal = document.getElementById('pl-block-modal');
            modal.style.display = 'none';
            document.body.classList.remove('pl-modal-open');
            removeAllAds();
                        
            setInterval(removeAllAds, 1000);
        });
        
        function addClickCount() {
            let clickCount = checkAndResetClickCount();
            
            if (clickCount < maxClickCount) {
                if (clickCount === 0) {
                    setStorageItem('adsenseFirstClickTime', Date.now().toString());
                }

                clickCount++;
                setStorageItem('adsenseClickCount', clickCount.toString());
                
                setStorageItem('lastClickTime', Date.now().toString());
            }
            
            if (clickCount >= maxClickCount) {
                removeAllAds();
                
                blockCurrentIP();
                
                showBlockModal();
                
                setStorageItem('adsenseBlocked', 'true');
                setStorageItem('adsenseBlockedTime', Date.now().toString());
            }
        }
        
        window.addEventListener('blur', function() {
            const activeElement = document.activeElement;
            
            if (isAdsenseAd(activeElement)) {
                if (window.location.href.includes('#google_vignette')) {
                    return;
                }
                
                addClickCount();
                
                setTimeout(function() {
                    activeElement.blur();
                }, 1);
            }
        });
        
        document.addEventListener('click', function(e) {
            const target = e.target;
            
            let currentElement = target;
            for (let i = 0; i < 5; i++) {
                if (!currentElement) break;
                
                if (isAdsenseAd(currentElement)) {
                    addClickCount();
                    break;
                }
                
                currentElement = currentElement.parentElement;
            }
        }, true);
        
        window.addEventListener('message', function(event) {
            try {
                if (typeof event.data === 'string' && 
                    (event.data.includes('google_ads') || 
                     event.data.includes('doubleclick') || 
                     event.data.includes('GoogleAdServingTest'))) {
                    
                    const lastClickTime = getStorageItem('lastClickTime', '0');
                    const now = Date.now();
                    
                    if (now - parseInt(lastClickTime) < 2000) {
                        addClickCount();
                    }
                }
            } catch (e) {}
        });
    })();
    ";
    
    wp_localize_script('presslearn-protection-script-js', 'presslearn_protection_config', array(
        'block_expiry_days' => get_option('presslearn_click_protection_block_expiry_days', 30)
    ));
    
    wp_add_inline_script('presslearn-protection-script-js', $protection_script);
}

add_action('wp_body_open', 'presslearn_add_ad_protection_script', 1);

function presslearn_block_current_ip_ajax() {
    check_ajax_referer('presslearn_block_ip_nonce', 'nonce');
    
    $click_protection_enabled = get_option('presslearn_click_protection_enabled', 'no');
    if ($click_protection_enabled !== 'yes') {
        wp_send_json_error(array('message' => '애드 프로텍터가 비활성화되어 있습니다.'));
        wp_die();
    }
    
    $user_ip = presslearn_plugin()->get_visitor_ip_for_protection();
    
    $allowed_ips = get_option('presslearn_click_protection_allowed_ips', array());
    foreach ($allowed_ips as $item) {
        if ($item['ip'] === $user_ip) {
            wp_send_json_error(array('message' => '허용된 IP입니다.'));
            wp_die();
        }
    }
    
    $blocked_ips = get_option('presslearn_click_protection_blocked_ips', array());
    $is_already_blocked = false;
    
    foreach ($blocked_ips as $item) {
        if ($item['ip'] === $user_ip) {
            $is_already_blocked = true;
            break;
        }
    }
    
    if (!$is_already_blocked) {
        $current_date = current_time('Y-m-d');
        
        $block_expiry_days = get_option('presslearn_click_protection_block_expiry_days', 30);
        
        $blocked_ips[] = array(
            'ip' => $user_ip,
            'date' => $current_date,
            'block_date' => $current_date,
            'reason' => '광고 클릭 제한 초과',
            'expires' => $block_expiry_days > 0 ? gmdate('Y-m-d', strtotime("+{$block_expiry_days} days")) : ''
        );
        
        update_option('presslearn_click_protection_blocked_ips', $blocked_ips);
        
        wp_send_json_success(array(
            'message' => 'IP가 성공적으로 차단되었습니다.',
            'blocked_ip' => $user_ip,
            'block_date' => $current_date,
            'is_new' => true
        ));
    } else {
        wp_send_json_success(array(
            'message' => '이미 차단된 IP입니다.',
            'blocked_ip' => $user_ip,
            'is_new' => false
        ));
    }
    
    wp_die();
}

add_action('wp_ajax_presslearn_block_current_ip', 'presslearn_block_current_ip_ajax');
add_action('wp_ajax_nopriv_presslearn_block_current_ip', 'presslearn_block_current_ip_ajax');

function presslearn_check_ip_block_status_ajax() {
    check_ajax_referer('presslearn_check_block_status', 'nonce');
    
    $click_protection_enabled = get_option('presslearn_click_protection_enabled', 'no');
    if ($click_protection_enabled !== 'yes') {
        wp_send_json_success(array('is_blocked' => false));
        wp_die();
    }
    
    $user_ip = presslearn_plugin()->get_visitor_ip_for_protection();
    
    $allowed_ips = get_option('presslearn_click_protection_allowed_ips', array());
    foreach ($allowed_ips as $item) {
        if ($item['ip'] === $user_ip) {
            wp_send_json_success(array('is_blocked' => false, 'reason' => 'allowed'));
            wp_die();
        }
    }
    
    $blocked_ips = get_option('presslearn_click_protection_blocked_ips', array());
    $is_blocked = false;
    
    foreach ($blocked_ips as $item) {
        if ($item['ip'] === $user_ip) {
            $is_blocked = true;
            break;
        }
    }
    
    wp_send_json_success(array(
        'is_blocked' => $is_blocked,
        'ip' => $user_ip
    ));
    
    wp_die();
}

add_action('wp_ajax_presslearn_check_ip_block_status', 'presslearn_check_ip_block_status_ajax');
add_action('wp_ajax_nopriv_presslearn_check_ip_block_status', 'presslearn_check_ip_block_status_ajax');

function presslearn_unblock_ip_with_reset() {
    check_ajax_referer('presslearn_ip_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => '권한이 없습니다.'));
        wp_die();
    }
    
    $ip = isset($_POST['ip']) ? sanitize_text_field(wp_unslash($_POST['ip'])) : '';
    
    if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
        wp_send_json_error(array('message' => '유효하지 않은 IP 주소입니다.'));
        wp_die();
    }
    
    $lock_key = 'presslearn_unblock_' . md5($ip);
    if (get_transient($lock_key)) {
        wp_send_json_error(array('message' => '다른 요청이 처리 중입니다. 잠시 후 다시 시도해주세요.'));
        wp_die();
    }
    
    set_transient($lock_key, true, 5);
    
    try {
        $blocked_ips = get_option('presslearn_click_protection_blocked_ips', array());
        $ip_found = false;
        $new_blocked_ips = array();
        
        foreach ($blocked_ips as $item) {
            if ($item['ip'] === $ip) {
                $ip_found = true;
            } else {
                $new_blocked_ips[] = $item;
            }
        }
        
        if (!$ip_found) {
            delete_transient($lock_key);
            wp_send_json_error(array('message' => '차단 목록에서 해당 IP를 찾을 수 없습니다.'));
            wp_die();
        }
        
        update_option('presslearn_click_protection_blocked_ips_backup', $blocked_ips);
        
        update_option('presslearn_click_protection_blocked_ips', $new_blocked_ips);
        
        $reset_signals = get_option('presslearn_reset_localStorage_signals', array());
        $reset_signals[$ip] = array(
            'timestamp' => time(),
            'expires' => time() + 3600
        );
        update_option('presslearn_reset_localStorage_signals', $reset_signals);
        
        delete_transient($lock_key);
        
        wp_send_json_success(array(
            'message' => 'IP 차단이 성공적으로 해제되었습니다.',
            'ip' => $ip,
            'reset_signal_sent' => true
        ));
        
    } catch (Exception $e) {
        delete_transient($lock_key);
        wp_send_json_error(array('message' => '서버 오류가 발생했습니다.'));
    }
    
    wp_die();
}

add_action('wp_ajax_presslearn_unblock_ip_with_reset', 'presslearn_unblock_ip_with_reset');

add_action('wp_head', 'presslearn_add_adclicker_script', 1);

add_action('wp_ajax_presslearn_get_blocked_logs', 'presslearn_get_blocked_logs');

function presslearn_get_blocked_logs() {
    check_ajax_referer('presslearn_ip_nonce', 'nonce');
    
    if (current_user_can('manage_options')) {
        $blocked_ips = get_option('presslearn_click_protection_blocked_ips', array());
        $period = isset($_POST['period']) ? sanitize_text_field(wp_unslash($_POST['period'])) : '30';
        
        if ($period !== 'all' && !empty($blocked_ips)) {
            $filtered_logs = array();
            $cutoff_date = gmdate('Y-m-d', strtotime("-{$period} days"));
            
            foreach ($blocked_ips as $item) {
                $block_date = isset($item['block_date']) ? $item['block_date'] : $item['date'];
                if ($block_date >= $cutoff_date) {
                    $filtered_logs[] = $item;
                }
            }
            
            $blocked_ips = $filtered_logs;
        }
        
        usort($blocked_ips, function($a, $b) {
            $date_a = isset($a['block_date']) ? $a['block_date'] : $a['date'];
            $date_b = isset($b['block_date']) ? $b['block_date'] : $b['date'];
            return strtotime($date_b) - strtotime($date_a);
        });
        
        wp_send_json_success(array(
            'logs' => $blocked_ips
        ));
    }
    
    wp_send_json_error(array('message' => '권한이 없습니다.'));
    wp_die();
}

function presslearn_add_adclicker_script() {
    if (!function_exists('presslearn_plugin') || !presslearn_plugin()->is_plugin_activated()) {
        return;
    }
    
    $adclicker_enabled = get_option('presslearn_ad_clicker_enabled', 'no');
    if ($adclicker_enabled !== 'yes') {
        return;
    }
    
    if (!is_single()) {
        return;
    }

    global $post;
    $post_adclicker_enabled = get_post_meta($post->ID, '_presslearn_adclicker_enabled', true);
    $adclicker_global_enabled = get_option('presslearn_adclicker_global_enabled', 'no');
    
    if ($adclicker_global_enabled === 'yes') {
    } else {
        if ($post_adclicker_enabled === '' || $post_adclicker_enabled === 'no') {
            return;
        }
    }
    
    $adclicker_button_text = get_post_meta($post->ID, '_presslearn_adclicker_button_text', true);
    if (empty($adclicker_button_text)) {
        $adclicker_button_text = '광고보고 콘텐츠 계속 읽기';
    }
    
    $adclicker_ad_link = get_post_meta($post->ID, '_presslearn_adclicker_ad_link', true);
    
    $adclicker_frequency = get_option('presslearn_adclicker_frequency', 'once');
    $adclicker_overlay_color = get_option('presslearn_adclicker_overlay_color', '#000000');
    $adclicker_overlay_range = get_option('presslearn_adclicker_overlay_range', 100);
    $adclicker_display_time = get_option('presslearn_adclicker_display_time', 'null');
    $adclicker_button_color = get_option('presslearn_adclicker_button_color', '#2196F3');
    $adclicker_button_text_color = get_option('presslearn_adclicker_button_text_color', '#ffffff');
    
    wp_register_style('presslearn-adclicker-css', false);
    wp_enqueue_style('presslearn-adclicker-css');
    
    $adclicker_css = "
    .pl-adclicker-overlay {
        display: none;
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        height: " . esc_attr($adclicker_overlay_range) . "vh;
        background: linear-gradient(to bottom, rgba(255, 255, 255, 0.1) 0%, " . esc_attr($adclicker_overlay_color) . " 100%);
        z-index: 999999;
    }
    
    .pl-adclicker-close-button {
        display: none;
        position: fixed;
        bottom: 60px;
        left: 50%;
        transform: translateX(-50%);
        padding: 15px 30px;
        background-color: " . esc_attr($adclicker_button_color) . ";
        color: " . esc_attr($adclicker_button_text_color) . ";
        border: none;
        border-radius: 8px;
        font-size: 20px;
        font-weight: bold;
        cursor: pointer;
        z-index: 1000001;
        text-decoration: none;
        text-align: center;
    }
    
    .pl-adclicker-close-button:hover,
    .pl-adclicker-close-button:focus,
    .pl-adclicker-close-button:active {
        background-color: " . esc_attr($adclicker_button_color) . ";
        color: " . esc_attr($adclicker_button_text_color) . ";
        text-decoration: none;
        outline: none;
    }
    
    .pl-back-message {
        display: none;
        position: fixed;
        bottom: 40px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 12px;
        color: " . esc_attr($adclicker_button_text_color) . ";
        opacity: 0.8;
        z-index: 1000001;
    }
    
    @media screen and (max-width: 768px) {
        .pl-adclicker-close-button {
            width: 80%;
        }
        .pl-back-message {
            bottom: 30px;
        }
    }
    
    .pl-countdown-label {
        display: none;
        position: absolute;
        top: -10px;
        right: -10px;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background-color: #ff3b30;
        color: white;
        font-size: 14px;
        font-weight: bold;
        text-align: center;
        line-height: 30px;
        z-index: 1000002;
    }
    
    body.pl-adclicker-open {
        overflow: hidden;
    }

    .presslearn-ai-icon {
        display: inline-block;
        width: 16px;
        height: 16px;
        background-image: url('" . esc_url(PRESSLEARN_PLUGIN_URL) . "assets/images/icons-meta.png');
        background-repeat: no-repeat;
        background-size: contain;
        vertical-align: middle;
        margin-left: 8px;
        position: relative;
        top: -1px;
    }
    
    #presslearn_ai_writer .postbox-header .hndle {
        display: flex !important;
        justify-content: flex-start !important;
        align-items: center !important;
    }
    
    .presslearn-ai-title-container {
        display: flex;
        align-items: center;
    }
    ";
    
    wp_add_inline_style('presslearn-adclicker-css', $adclicker_css);
    
    wp_register_script('presslearn-adclicker-js', false, array(), PRESSLEARN_PLUGIN_VERSION, true);
    wp_enqueue_script('presslearn-adclicker-js');
    
    $adclicker_js = "
    (function() {
        function getAdClickerStorage(name, defaultValue) {
            try {
                const item = localStorage.getItem('adclicker_' + name);
                return item !== null ? item : defaultValue;
            } catch (e) {
                return defaultValue;
            }
        }
        
        function setAdClickerStorage(name, value) {
            try {
                localStorage.setItem('adclicker_' + name, value);
                return true;
            } catch (e) {
                return false;
            }
        }
        
        function showAdClicker() {
            const overlay = document.getElementById('pl-adclicker-overlay');
            const closeButton = document.getElementById('pl-adclicker-close-button');
            const countdownLabel = document.getElementById('pl-countdown-label');
            const backMessage = document.getElementById('pl-back-message');
            
            overlay.style.display = 'block';
            closeButton.style.display = 'block';
            backMessage.style.display = 'block';
            document.body.classList.add('pl-adclicker-open');
            
            const displayTime = '" . esc_js($adclicker_display_time) . "';
            if (displayTime !== 'null') {
                let timeLeft = parseInt(displayTime);
                countdownLabel.textContent = timeLeft;
                countdownLabel.style.display = 'block';
                
                const countdownInterval = setInterval(function() {
                    timeLeft--;
                    countdownLabel.textContent = timeLeft;
                    
                    if (timeLeft <= 0) {
                        clearInterval(countdownInterval);
                        countdownLabel.textContent = '✕';
                    }
                }, 1000);
            }
        }
        
        function hideAdClicker() {
            const overlay = document.getElementById('pl-adclicker-overlay');
            const closeButton = document.getElementById('pl-adclicker-close-button');
            const countdownLabel = document.getElementById('pl-countdown-label');
            const backMessage = document.getElementById('pl-back-message');
            
            overlay.style.display = 'none';
            closeButton.style.display = 'none';
            countdownLabel.style.display = 'none';
            backMessage.style.display = 'none';
            document.body.classList.remove('pl-adclicker-open');
            
            const frequency = '" . esc_js($adclicker_frequency) . "';
            if (frequency === 'once') {
                setAdClickerStorage('shown', 'true');
            } else if (frequency === '5min') {
                const now = new Date().getTime();
                setAdClickerStorage('last_shown', now.toString());
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const closeButton = document.getElementById('pl-adclicker-close-button');
            if (closeButton) {
                closeButton.addEventListener('click', function(e) {
                    const adLink = this.getAttribute('data-ad-link');
                    
                    if (e.target.tagName.toLowerCase() === 'a') {
                        hideAdClicker();
                    } else {
                        e.preventDefault();
                        hideAdClicker();
                    }
                });
            }
            
            const frequency = '" . esc_js($adclicker_frequency) . "';
            let shouldShow = false;
            
            if (frequency === 'once') {
                shouldShow = getAdClickerStorage('shown', '') !== 'true';
            } else if (frequency === '5min') {
                const lastShown = parseInt(getAdClickerStorage('last_shown', '0'));
                const now = new Date().getTime();
                const fiveMinutes = 5 * 60 * 1000;
                shouldShow = (now - lastShown) > fiveMinutes;
            } else if (frequency === 'loop') {
                shouldShow = true;
            }
            
            if (shouldShow) {
                setTimeout(function() {
                    showAdClicker();
                }, 1000);
            }
        });
    })();
    ";
    
    wp_add_inline_script('presslearn-adclicker-js', $adclicker_js);
    
    add_action('wp_footer', function() use ($adclicker_ad_link, $adclicker_button_text) {
        ?>
        <div class="pl-adclicker-overlay" id="pl-adclicker-overlay"></div>
        <div class="pl-adclicker-close-button" id="pl-adclicker-close-button" data-ad-link="<?php echo esc_attr($adclicker_ad_link); ?>">
            <?php if ($adclicker_ad_link): ?>
            <a href="<?php echo esc_url($adclicker_ad_link); ?>" target="_blank" style="color: inherit; text-decoration: inherit; display: block;"><?php echo esc_html($adclicker_button_text); ?></a>
            <?php else: ?>
            <?php echo esc_html($adclicker_button_text); ?>
            <?php endif; ?>
            <span class="pl-countdown-label" id="pl-countdown-label"></span>
        </div>
        <div class="pl-back-message" id="pl-back-message"><?php echo esc_html('원치않으시면 뒤로가기를 해주세요'); ?></div>
        <?php
    });
}

add_action('wp_body_open', 'presslearn_add_adclicker_script', 1);


function presslearn_add_adclicker_metabox() {
    $adclicker_enabled = get_option('presslearn_ad_clicker_enabled', 'no');
    if ($adclicker_enabled !== 'yes') {
        return;
    }
    
    add_meta_box(
        'presslearn_adclicker_settings',
        '<span class="presslearn-ai-title-container"><span class="presslearn-ai-icon"></span>애드클리커 설정</span>',
        'presslearn_render_adclicker_metabox',
        'post',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'presslearn_add_adclicker_metabox');

function presslearn_render_adclicker_metabox($post) {
    wp_nonce_field('presslearn_adclicker_metabox_nonce', 'presslearn_adclicker_metabox_nonce');
    
    $adclicker_enabled = get_post_meta($post->ID, '_presslearn_adclicker_enabled', true);
    $adclicker_button_text = get_post_meta($post->ID, '_presslearn_adclicker_button_text', true);
    $adclicker_ad_link = get_post_meta($post->ID, '_presslearn_adclicker_ad_link', true);
    $adclicker_global_enabled = get_option('presslearn_adclicker_global_enabled', 'no');
    
    if ($adclicker_enabled === '') {
        $adclicker_enabled = $adclicker_global_enabled === 'yes' ? 'yes' : 'no';
    }
    
    if (empty($adclicker_button_text)) {
        $adclicker_button_text = '광고보고 콘텐츠 계속 읽기';
    }
    
    ?>
    <p>
        <label for="presslearn_adclicker_enabled">이 게시글에서 애드클리커 동작 여부:</label><br>
        <select name="presslearn_adclicker_enabled" id="presslearn_adclicker_enabled" class="widefat" style="box-sizing: border-box;" <?php echo $adclicker_global_enabled === 'yes' ? 'disabled' : ''; ?>>
            <option value="yes" <?php selected($adclicker_enabled, 'yes'); ?>>예</option>
            <option value="no" <?php selected($adclicker_enabled, 'no'); ?>>아니오</option>
        </select>
        <?php if ($adclicker_global_enabled === 'yes'): ?>
        <small style="color: #666;">전체 글 활성화가 켜져있어 이 설정은 무시되고 애드클리커가 강제 활성화됩니다.</small>
        <?php else: ?>
        <small style="color: #666;">전체 글 활성화가 비활성화되어 있어 이 설정에 따라 동작합니다.</small>
        <?php endif; ?>
    </p>
    
    <p>
        <label for="presslearn_adclicker_button_text">애드클리커 버튼 텍스트:</label><br>
        <input type="text" name="presslearn_adclicker_button_text" id="presslearn_adclicker_button_text" 
               class="widefat" value="<?php echo esc_attr($adclicker_button_text); ?>">
    </p>
    
    <p>
        <label for="presslearn_adclicker_ad_link">광고 링크 URL:</label><br>
        <input type="url" name="presslearn_adclicker_ad_link" id="presslearn_adclicker_ad_link" 
               class="widefat" value="<?php echo esc_url($adclicker_ad_link); ?>" placeholder="https://...">
        <small>버튼 클릭 시 이동할 광고 링크</small>
    </p>
    <?php
}

function presslearn_save_adclicker_metabox_data($post_id) {
    if (!isset($_POST['presslearn_adclicker_metabox_nonce'])) {
        return;
    }
    
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['presslearn_adclicker_metabox_nonce'])), 'presslearn_adclicker_metabox_nonce')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (isset($_POST['post_type']) && sanitize_text_field(wp_unslash($_POST['post_type'])) === 'post') {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }
    
    if (isset($_POST['presslearn_adclicker_enabled'])) {
        $adclicker_enabled = sanitize_text_field(wp_unslash($_POST['presslearn_adclicker_enabled']));
        if ($adclicker_enabled === 'yes' || $adclicker_enabled === 'no') {
            update_post_meta($post_id, '_presslearn_adclicker_enabled', $adclicker_enabled);
        }
    }
    
    if (isset($_POST['presslearn_adclicker_button_text'])) {
        $adclicker_button_text = sanitize_text_field(wp_unslash($_POST['presslearn_adclicker_button_text']));
        update_post_meta($post_id, '_presslearn_adclicker_button_text', $adclicker_button_text);
    }
    
    if (isset($_POST['presslearn_adclicker_ad_link'])) {
        $adclicker_ad_link = esc_url_raw(wp_unslash($_POST['presslearn_adclicker_ad_link']));
        update_post_meta($post_id, '_presslearn_adclicker_ad_link', $adclicker_ad_link);
    }
}
add_action('save_post', 'presslearn_save_adclicker_metabox_data'); 

add_action('wp_ajax_presslearn_upload_banner', 'presslearn_handle_banner_upload');
add_action('wp_ajax_nopriv_presslearn_upload_banner', 'presslearn_handle_banner_upload');

function presslearn_handle_banner_upload() {
    $dynamic_banner_enabled = get_option('presslearn_dynamic_banner_enabled', 'no');
    if ($dynamic_banner_enabled !== 'yes') {
        wp_send_json_error(array('message' => '다이나믹 배너 기능이 활성화되어 있지 않습니다.'));
        wp_die();
    }
    
    check_ajax_referer('presslearn_upload_banner_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => '파일 업로드 권한이 없습니다.'));
        wp_die();
    }
    
    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }
    
    if (!isset($_FILES['banner_image'])) {
        wp_send_json_error(array('message' => '업로드할 파일이 없습니다.'));
        wp_die();
    }
    
    $file = $_FILES['banner_image'];
    
    $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
    $file_type = sanitize_mime_type($file['type']);
    if (!in_array($file_type, $allowed_types)) {
        wp_send_json_error(array('message' => '허용되지 않는 파일 형식입니다. JPG, PNG, GIF, WEBP 이미지만 업로드 가능합니다.'));
        wp_die();
    }
    
    $upload_dir = wp_upload_dir();
    
    $banner_type = isset($_POST['banner_type']) ? sanitize_text_field(wp_unslash($_POST['banner_type'])) : 'normal';
    
    $upload_path = $upload_dir['path'];
    if ($banner_type === 'cover') {
        $upload_path = trailingslashit($upload_dir['path']) . 'cover_banners';
        if (!file_exists($upload_path)) {
            wp_mkdir_p($upload_path);
        }
    }
    
    $file_name = wp_unique_filename($upload_path, sanitize_file_name($file['name']));
    
    $upload_overrides = array(
        'test_form' => false,
        'test_size' => true,
        'test_upload' => true,
        'mimes' => array(
            'jpg|jpeg|jpe' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp'
        )
    );
    
    $movefile = wp_handle_upload($file, $upload_overrides);
    
    if ($movefile && !isset($movefile['error'])) {
        wp_send_json_success(array(
            'url' => esc_url_raw($movefile['url']),
            'message' => '이미지가 성공적으로 업로드되었습니다.'
        ));
    } else {
        wp_send_json_error(array(
            'message' => isset($movefile['error']) ? esc_html($movefile['error']) : '파일 업로드 중 오류가 발생했습니다.'
        ));
    }
    
    wp_die();
}

add_action('init', 'presslearn_setup_cron_for_ip_unblock');

add_action('wp_ajax_presslearn_add_campaign', 'presslearn_add_campaign');
add_action('wp_ajax_presslearn_get_campaigns', 'presslearn_get_campaigns');
add_action('wp_ajax_presslearn_delete_campaign', 'presslearn_delete_campaign');
add_action('wp_ajax_presslearn_get_campaign', 'presslearn_get_campaign');
add_action('wp_ajax_presslearn_update_campaign', 'presslearn_update_campaign');

function presslearn_add_campaign() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => '권한이 없습니다.'));
        wp_die();
    }
    
    $dynamic_banner_enabled = get_option('presslearn_dynamic_banner_enabled', 'no');
    if ($dynamic_banner_enabled !== 'yes') {
        wp_send_json_error(array('message' => '다이나믹 배너 기능이 활성화되어 있지 않습니다.'));
        wp_die();
    }
    
    presslearn_ensure_banners_table_exists();
    
    check_ajax_referer('presslearn_campaign_nonce', 'nonce');
    
    $campaign_data = isset($_POST['campaign_data']) ? wp_unslash($_POST['campaign_data']) : array();
    
    if (empty($campaign_data) || !is_array($campaign_data)) {
        wp_send_json_error(array('message' => '유효하지 않은 캠페인 데이터입니다.'));
        wp_die();
    }
    
    if (empty($campaign_data['name'])) {
        wp_send_json_error(array('message' => '캠페인 이름은 필수 항목입니다.'));
        wp_die();
    }
    
    if ($campaign_data['type'] === 'custom') {
        if (empty($campaign_data['banner_url']) || empty($campaign_data['link']) || 
            empty($campaign_data['width']) || empty($campaign_data['height'])) {
            wp_send_json_error(array('message' => '배너 URL, 링크 URL, 가로/세로 크기는 필수 항목입니다.'));
            wp_die();
        }
    } else if ($campaign_data['type'] === 'iframe') {
        if (empty($campaign_data['iframe_code']) || empty($campaign_data['width']) || empty($campaign_data['height'])) {
            wp_send_json_error(array('message' => 'iframe 코드, 가로/세로 크기는 필수 항목입니다.'));
            wp_die();
        }
    } else {
        wp_send_json_error(array('message' => '유효하지 않은 배너 유형입니다.'));
        wp_die();
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'presslearn_banners';
    
    $data = array(
        'name' => sanitize_text_field($campaign_data['name']),
        'type' => sanitize_text_field($campaign_data['type']),
        'width' => intval($campaign_data['width']),
        'height' => intval($campaign_data['height']),
        'status' => 1, 
        'created_at' => current_time('mysql')
    );
    
    if ($campaign_data['type'] === 'custom') {
        $data['banner_url'] = esc_url_raw($campaign_data['banner_url']);
        $data['link'] = esc_url_raw($campaign_data['link']);
        if (!empty($campaign_data['cover_banner_url'])) {
            $data['cover_banner_url'] = esc_url_raw($campaign_data['cover_banner_url']);
        }
    } else if ($campaign_data['type'] === 'iframe') {
        $data['iframe_code'] = stripslashes($campaign_data['iframe_code']);
        if (!empty($campaign_data['link'])) {
            $data['link'] = esc_url_raw($campaign_data['link']);
        }
        if (!empty($campaign_data['cover_banner_url'])) {
            $data['cover_banner_url'] = esc_url_raw($campaign_data['cover_banner_url']);
        }
    }
    
    $result = $wpdb->insert($table_name, $data);
    
    if ($result === false) {
        wp_send_json_error(array('message' => '캠페인 추가 중 오류가 발생했습니다: ' . $wpdb->last_error));
        wp_die();
    }
    
    $campaign_id = $wpdb->insert_id;
    
    wp_send_json_success(array(
        'id' => $campaign_id,
        'message' => '캠페인이 성공적으로 추가되었습니다.',
        'shortcode' => $campaign_data['type'] === 'custom' ? 
                        '[presslearn_banner id="' . $campaign_id . '"]' : 
                        '[presslearn_iframe id="' . $campaign_id . '"]'
    ));
    
    wp_die();
}

function presslearn_get_campaigns() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => '권한이 없습니다.'));
        wp_die();
    }
    
    $dynamic_banner_enabled = get_option('presslearn_dynamic_banner_enabled', 'no');
    if ($dynamic_banner_enabled !== 'yes') {
        wp_send_json_error(array('message' => '다이나믹 배너 기능이 활성화되어 있지 않습니다.'));
        wp_die();
    }
    
    presslearn_ensure_banners_table_exists();
    
    check_ajax_referer('presslearn_campaign_nonce', 'nonce');
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'presslearn_banners';
    
    $campaigns = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC", ARRAY_A);
    
    if ($campaigns === null) {
        wp_send_json_error(array('message' => '캠페인 목록을 가져오는 중 오류가 발생했습니다: ' . $wpdb->last_error));
        wp_die();
    }
    
    foreach ($campaigns as &$campaign) {
        $campaign['shortcode'] = $campaign['type'] === 'custom' ? 
                                '[presslearn_banner id="' . $campaign['id'] . '"]' : 
                                '[presslearn_iframe id="' . $campaign['id'] . '"]';
    }
    
    wp_send_json_success(array('campaigns' => $campaigns));
    wp_die();
}

function presslearn_delete_campaign() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => '권한이 없습니다.'));
        wp_die();
    }
    
    $dynamic_banner_enabled = get_option('presslearn_dynamic_banner_enabled', 'no');
    if ($dynamic_banner_enabled !== 'yes') {
        wp_send_json_error(array('message' => '다이나믹 배너 기능이 활성화되어 있지 않습니다.'));
        wp_die();
    }
    
    presslearn_ensure_banners_table_exists();
    
    check_ajax_referer('presslearn_campaign_nonce', 'nonce');
    
    $campaign_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($campaign_id <= 0) {
        wp_send_json_error(array('message' => '유효하지 않은 캠페인 ID입니다.'));
        wp_die();
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'presslearn_banners';
    
    $result = $wpdb->delete($table_name, array('id' => $campaign_id), array('%d'));
    
    if ($result === false) {
        wp_send_json_error(array('message' => '캠페인 삭제 중 오류가 발생했습니다: ' . $wpdb->last_error));
        wp_die();
    }
    
    wp_send_json_success(array('message' => '캠페인이 성공적으로 삭제되었습니다.'));
    wp_die();
}

function presslearn_get_campaign() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => '권한이 없습니다.'));
        wp_die();
    }
    
    $dynamic_banner_enabled = get_option('presslearn_dynamic_banner_enabled', 'no');
    if ($dynamic_banner_enabled !== 'yes') {
        wp_send_json_error(array('message' => '다이나믹 배너 기능이 활성화되어 있지 않습니다.'));
        wp_die();
    }
    
    presslearn_ensure_banners_table_exists();
    
    check_ajax_referer('presslearn_campaign_nonce', 'nonce');
    
    $campaign_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($campaign_id <= 0) {
        wp_send_json_error(array('message' => '유효하지 않은 캠페인 ID입니다.'));
        wp_die();
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'presslearn_banners';
    
    $campaign = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $campaign_id), ARRAY_A);
    
    if ($campaign === null) {
        wp_send_json_error(array('message' => '캠페인 정보를 가져오는 중 오류가 발생했습니다: ' . $wpdb->last_error));
        wp_die();
    }
    
    wp_send_json_success(array('campaign' => $campaign));
    wp_die();
}

function presslearn_update_campaign() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => '권한이 없습니다.'));
        wp_die();
    }
    
    $dynamic_banner_enabled = get_option('presslearn_dynamic_banner_enabled', 'no');
    if ($dynamic_banner_enabled !== 'yes') {
        wp_send_json_error(array('message' => '다이나믹 배너 기능이 활성화되어 있지 않습니다.'));
        wp_die();
    }
    
    presslearn_ensure_banners_table_exists();
    
    check_ajax_referer('presslearn_campaign_nonce', 'nonce');
    
    $campaign_data = isset($_POST['campaign_data']) ? wp_unslash($_POST['campaign_data']) : array();
    
    if (empty($campaign_data) || !is_array($campaign_data) || empty($campaign_data['id'])) {
        wp_send_json_error(array('message' => '유효하지 않은 캠페인 데이터입니다.'));
        wp_die();
    }
    
    $campaign_id = intval($campaign_data['id']);
    
    if (empty($campaign_data['name'])) {
        wp_send_json_error(array('message' => '캠페인 이름은 필수 항목입니다.'));
        wp_die();
    }
    
    if ($campaign_data['type'] === 'custom') {
        if (empty($campaign_data['banner_url']) || empty($campaign_data['link']) || 
            empty($campaign_data['width']) || empty($campaign_data['height'])) {
            wp_send_json_error(array('message' => '배너 URL, 링크 URL, 가로/세로 크기는 필수 항목입니다.'));
            wp_die();
        }
    } else if ($campaign_data['type'] === 'iframe') {
        if (empty($campaign_data['iframe_code']) || empty($campaign_data['width']) || empty($campaign_data['height'])) {
            wp_send_json_error(array('message' => 'iframe 코드, 가로/세로 크기는 필수 항목입니다.'));
            wp_die();
        }
    } else {
        wp_send_json_error(array('message' => '유효하지 않은 배너 유형입니다.'));
        wp_die();
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'presslearn_banners';
    
    $data = array(
        'name' => sanitize_text_field($campaign_data['name']),
        'type' => sanitize_text_field($campaign_data['type']),
        'width' => intval($campaign_data['width']),
        'height' => intval($campaign_data['height']),
        'status' => 1,
        'updated_at' => current_time('mysql')
    );
    
    if ($campaign_data['type'] === 'custom') {
        $data['banner_url'] = esc_url_raw($campaign_data['banner_url']);
        $data['link'] = esc_url_raw($campaign_data['link']);
        $data['iframe_code'] = '';
        
        if (!empty($campaign_data['cover_banner_url'])) {
            $data['cover_banner_url'] = esc_url_raw($campaign_data['cover_banner_url']);
        } else {
            $data['cover_banner_url'] = '';
        }
    } else if ($campaign_data['type'] === 'iframe') {
        $data['iframe_code'] = stripslashes($campaign_data['iframe_code']);
        $data['banner_url'] = '';
                
        if (!empty($campaign_data['link'])) {
            $data['link'] = esc_url_raw($campaign_data['link']);
        } else {
            $data['link'] = '';
        }
        
        if (!empty($campaign_data['cover_banner_url'])) {
            $data['cover_banner_url'] = esc_url_raw($campaign_data['cover_banner_url']);
        } else {
            $data['cover_banner_url'] = '';
        }
    }
    
    $result = $wpdb->update(
        $table_name,
        $data,
        array('id' => $campaign_id),
        array('%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s'),
        array('%d')
    );
    
    if ($result === false) {
        wp_send_json_error(array('message' => '캠페인 수정 중 오류가 발생했습니다: ' . $wpdb->last_error));
        wp_die();
    }
    
    wp_send_json_success(array(
        'message' => '캠페인이 성공적으로 수정되었습니다.',
        'shortcode' => $campaign_data['type'] === 'custom' ? 
                        '[presslearn_banner id="' . $campaign_id . '"]' : 
                        '[presslearn_iframe id="' . $campaign_id . '"]'
    ));
    
    wp_die();
}

function presslearn_register_banner_shortcodes() {
    add_shortcode('presslearn_banner', 'presslearn_banner_shortcode');
    add_shortcode('presslearn_iframe', 'presslearn_iframe_shortcode');
    
    wp_register_style('presslearn-banner-styles', false);
    wp_enqueue_style('presslearn-banner-styles');
    
    $custom_css = "
    .pl-dynamic-area {
        display: inline-block;
        position: relative;
        overflow: hidden;
    }
    .pl-dynamic-cover {
        position: absolute;
        top: 0;
        left: -40px;
        width: 100%;
        height: 100%;
        display: none;
        z-index: 1;
        touch-action: pan-x;
        user-select: none;
        cursor: grab;
    }
    .pl-dynamic-cover.enable {
        display: block !important;
        animation: sliding 1.5s ease-in-out infinite;
    }
    .pl-dynamic-cover.dragging {
        animation: none !important;
        cursor: grabbing;
    }
    .pl-dynamic-cover::after {
        content: attr(data-message);
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(0,0,0,0.7);
        color: white;
        padding: 8px 12px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        transition: opacity 0.3s;
    }
    .pl-dynamic-cover img {
        max-width: 100%;
        height: auto;
    }
    @keyframes sliding {
        0% {
            transform: translate3d(-7%, 0, 0);
        }

        20% {
            transform: translate3d(-10%, 0, 0);
        }

        40% {
            transform: translate3d(-5%, 0, 0);
        }

        60% {
            transform: translate3d(-10%, 0, 0);
        }

        80% {
            transform: translate3d(-5%, 0, 0);
        }

        100% {
            transform: translate3d(-7%, 0, 0);
        }
    }
    ";
    
    wp_add_inline_style('presslearn-banner-styles', $custom_css);
    
    wp_register_script('presslearn-banner-script', false, array(), PRESSLEARN_PLUGIN_VERSION, true);
    wp_enqueue_script('presslearn-banner-script');
    
    $script = "
    document.addEventListener('DOMContentLoaded', function() {
        let startX = 0;
        let isDragging = false;
        let currentCover = null;
        let parentWidth = 0;
        let dragThreshold = false;
        
        function triggerIframeClick(coverElement) {
            const dynamicArea = coverElement.closest('.pl-dynamic-area');
            if (!dynamicArea) return;
            
            const customLink = dynamicArea.getAttribute('data-link');
            const iframeContainer = dynamicArea.querySelector('.presslearn-iframe-container iframe');
            const bannerLink = dynamicArea.querySelector('.pl-dynamic-area-item a');
            
            if (customLink) {
                window.location.href = customLink;
            } else if (iframeContainer && iframeContainer.src) {
                window.location.href = iframeContainer.src;
            } else if (bannerLink && bannerLink.href) {
                try {
                    bannerLink.click();
                } catch (e) {
                    window.location.href = bannerLink.href;
                }
            }
            
            coverElement.classList.remove('enable', 'dragging');
            coverElement.style.transform = '';
        }
        
        window.addEventListener('blur', function() {
            if (currentCover && dragThreshold) {
                triggerIframeClick(currentCover);
                isDragging = false;
                currentCover = null;
                dragThreshold = false;
            }
        });
        
        document.addEventListener('touchstart', function(e) {
            const target = e.target.closest('.pl-dynamic-cover');
            if (target && target.classList.contains('enable')) {
                e.preventDefault();
                startX = e.touches[0].clientX;
                isDragging = true;
                currentCover = target;
                parentWidth = target.parentElement.offsetWidth;
                dragThreshold = false;
                
                currentCover.classList.add('dragging');
            }
        });
        
        document.addEventListener('mousedown', function(e) {
            const target = e.target.closest('.pl-dynamic-cover');
            if (target && target.classList.contains('enable')) {
                e.preventDefault();
                startX = e.clientX;
                isDragging = true;
                currentCover = target;
                parentWidth = target.parentElement.offsetWidth;
                dragThreshold = false;
                
                currentCover.classList.add('dragging');
            }
        });
        
        document.addEventListener('click', function(e) {
            const target = e.target.closest('.pl-dynamic-cover');
            if (target && target.classList.contains('enable')) {
                e.preventDefault();
                triggerIframeClick(target);
            }
        });
        
        document.addEventListener('touchmove', function(e) {
            if (!isDragging || !currentCover) return;
            
            const currentX = e.touches[0].clientX;
            const diff = currentX - startX;
            
            if (diff < 0) {
                currentCover.style.transform = 'translate3d(' + (diff/3) + 'px, 0, 0)';
                
                const threshold = parentWidth * 0.2;
                if (Math.abs(diff) > threshold) {
                    dragThreshold = true;
                    triggerIframeClick(currentCover);
                    isDragging = false;
                    currentCover = null;
                    dragThreshold = false;
                }
            }
        });
        
        document.addEventListener('mousemove', function(e) {
            if (!isDragging || !currentCover) return;
            
            const currentX = e.clientX;
            const diff = currentX - startX;
            
            if (diff < 0) {
                currentCover.style.transform = 'translate3d(' + (diff/3) + 'px, 0, 0)';
                
                const threshold = parentWidth * 0.2;
                if (Math.abs(diff) > threshold) {
                    dragThreshold = true;
                    triggerIframeClick(currentCover);
                    isDragging = false;
                    currentCover = null;
                    dragThreshold = false;
                }
            }
        });
        
        document.addEventListener('touchend', function() {
            if (currentCover) {
                currentCover.classList.remove('dragging');
                currentCover.style.transform = '';
                isDragging = false;
                dragThreshold = false;
                currentCover = null;
            }
        });
        
        document.addEventListener('mouseup', function() {
            if (currentCover) {
                currentCover.classList.remove('dragging');
                currentCover.style.transform = '';
                isDragging = false;
                dragThreshold = false;
                currentCover = null;
            }
        });
        
        document.addEventListener('pointerdown', function(e) {
            const target = e.target.closest('.pl-dynamic-cover');
            if (target && target.classList.contains('enable')) {
                setTimeout(function() {
                    if (target.classList.contains('enable')) {
                        dragThreshold = true;
                        currentCover = target;
                    }
                }, 100);
            }
        });
    });
    ";
    
    wp_add_inline_script('presslearn-banner-script', $script);
}
add_action('init', 'presslearn_register_banner_shortcodes');

function presslearn_banner_shortcode($atts) {
    $dynamic_banner_enabled = get_option('presslearn_dynamic_banner_enabled', 'no');
    if ($dynamic_banner_enabled !== 'yes') {
        return '';
    }
    
    presslearn_ensure_banners_table_exists();
    
    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts, 'presslearn_banner');
    
    $banner_id = intval($atts['id']);
    
    if ($banner_id <= 0) {
        return '';
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'presslearn_banners';
    
    $banner = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d AND type = 'custom' AND status = 1", $banner_id), ARRAY_A);
    
    if ($banner === null || empty($banner)) {
        return '';
    }
    
    $banner_url = esc_url($banner['banner_url']);
    $cover_banner_url = !empty($banner['cover_banner_url']) ? esc_url($banner['cover_banner_url']) : '';
    
    $link_url = esc_url($banner['link']);
    $width = intval($banner['width']);
    $height = intval($banner['height']);
    
    if (empty($cover_banner_url)) {
        return '
        <div class="pl-dynamic-area">
            <div class="pl-dynamic-area-item">
                <a href="' . esc_url($link_url) . '" target="_blank" rel="nofollow noopener">
                    <img src="' . esc_url($banner_url) . '" alt="' . esc_attr($banner['name']) . '" width="' . esc_attr($width) . '" height="' . esc_attr($height) . '" style="max-width:100%;height:auto;display:block;" />
                </a>
            </div>
        </div>
        ';
    }
    
    return '
    <div class="pl-dynamic-area">
        <div class="pl-dynamic-area-item">
            <a href="' . esc_url($link_url) . '" target="_blank" rel="nofollow noopener">
                <img src="' . esc_url($banner_url) . '" alt="' . esc_attr($banner['name']) . '" width="' . esc_attr($width) . '" height="' . esc_attr($height) . '" style="max-width:100%;height:auto;display:block;" />
            </a>
        </div>
        <div class="pl-dynamic-cover enable" data-message="밀어서 제거">
            <img src="' . esc_url($cover_banner_url) . '" alt="' . esc_attr($banner['name']) . '" width="' . esc_attr($width) . '" height="' . esc_attr($height) . '" style="max-width:100%;height:auto;display:block;" draggable="true" />
        </div>
    </div>
    ';
}


function presslearn_iframe_shortcode($atts) {
    $dynamic_banner_enabled = get_option('presslearn_dynamic_banner_enabled', 'no');
    if ($dynamic_banner_enabled !== 'yes') {
        return '';
    }
    
    presslearn_ensure_banners_table_exists();
    
    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts, 'presslearn_iframe');
    
    $banner_id = intval($atts['id']);
    
    if ($banner_id <= 0) {
        return '';
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'presslearn_banners';
    
    $banner = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d AND type = 'iframe' AND status = 1", $banner_id), ARRAY_A);
    
    if ($banner === null || empty($banner)) {
        return '';
    }
    
    $iframe_code = $banner['iframe_code'];
    $width = intval($banner['width']);
    $height = intval($banner['height']);
    $link_url = !empty($banner['link']) ? esc_url($banner['link']) : '';
    $cover_banner_url = !empty($banner['cover_banner_url']) ? esc_url($banner['cover_banner_url']) : '';
    
    $iframe_code = stripslashes($iframe_code);
    
    $iframe_code = preg_replace('/width\s*=\s*(["\']?)([^"\'\s>]*)(["\']?)/', 'width="' . esc_attr($width) . 'px"', $iframe_code);
    $iframe_code = preg_replace('/height\s*=\s*(["\']?)([^"\'\s>]*)(["\']?)/', 'height="' . esc_attr($height) . 'px"', $iframe_code);
    
    if (strpos($iframe_code, 'style=') !== false) {
        $iframe_code = preg_replace('/style\s*=\s*(["\'])(.*?)(["\'])/', 'style=${1}max-width:100%;${2}${3}', $iframe_code);
    } else {
        $iframe_code = str_replace('<iframe', '<iframe style="max-width:100%;"', $iframe_code);
    }
    
    $data_link_attr = !empty($link_url) ? ' data-link="' . esc_attr($link_url) . '"' : '';
    
    if (empty($cover_banner_url)) {
        $safe_iframe_code = presslearn_sanitize_iframe($iframe_code);
        return '<div class="pl-dynamic-area"' . $data_link_attr . '><div class="presslearn-iframe-container" style="width:' . esc_attr($width) . 'px;max-width:100%;margin:0 auto;">' . $safe_iframe_code . '</div></div>';
    }
    
    $safe_iframe_code = presslearn_sanitize_iframe($iframe_code);
    
    return '
    <div class="pl-dynamic-area"' . $data_link_attr . '>
        <div class="pl-dynamic-area-item">
            <div class="presslearn-iframe-container" style="width:' . esc_attr($width) . 'px;max-width:100%;margin:0 auto;">' . $safe_iframe_code . '</div>
        </div>
        <div class="pl-dynamic-cover enable" data-message="밀어서 제거">
            <img src="' . esc_url($cover_banner_url) . '" alt="' . esc_attr($banner['name']) . '" width="' . esc_attr($width) . '" height="' . esc_attr($height) . '" style="max-width:100%;height:auto;display:block;" draggable="true" />
        </div>
    </div>
    ';
}

function presslearn_sanitize_iframe($iframe_code) {
    if (empty($iframe_code)) {
        return '';
    }
    
    if (!preg_match('/<iframe[^>]*>/i', $iframe_code)) {
        return '';
    }
    
    $iframe_code = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $iframe_code);
    $iframe_code = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $iframe_code);
    $iframe_code = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $iframe_code);
    $iframe_code = preg_replace('/\s*javascript\s*:/i', '', $iframe_code);
    
    if (preg_match('/<iframe[^>]*>.*?<\/iframe>/is', $iframe_code, $matches)) {
        $iframe_tag = $matches[0];
    } else if (preg_match('/<iframe[^>]*\/?>/i', $iframe_code, $matches)) {
        $iframe_tag = $matches[0];
        if (!preg_match('/\/\s*>$/', $iframe_tag)) {
            $iframe_tag .= '</iframe>';
        }
    } else {
        return '';
    }
    
    $trusted_domains = array(
        'ads-partners.coupang.com',
        'partner.googleadservices.com',
        'googleads.g.doubleclick.net',
        'www.googletagmanager.com',
        'youtube.com',
        'www.youtube.com',
        'player.vimeo.com',
        'cdnjs.cloudflare.com',
        'fonts.googleapis.com'
    );
    
    if (preg_match('/src\s*=\s*["\']([^"\']*)["\']/', $iframe_tag, $src_matches)) {
        $src_url = $src_matches[1];
        $parsed_url = parse_url($src_url);
        
        if (!empty($parsed_url['host'])) {
            $is_trusted = false;
            foreach ($trusted_domains as $trusted_domain) {
                if ($parsed_url['host'] === $trusted_domain || 
                    substr($parsed_url['host'], -strlen('.' . $trusted_domain)) === '.' . $trusted_domain) {
                    $is_trusted = true;
                    break;
                }
            }
            
            if (!$is_trusted && !current_user_can('manage_options')) {
                return '';
            }
        }
    }
    
    $allowed_attributes = array(
        'src', 'width', 'height', 'frameborder', 'scrolling', 
        'allowfullscreen', 'loading', 'title', 'name', 'id', 
        'class', 'style', 'referrerpolicy', 'browsingtopics'
    );
    
    $iframe_tag = preg_replace_callback(
        '/(\w+)\s*=\s*["\']([^"\']*)["\']/',
        function($matches) use ($allowed_attributes) {
            if (in_array(strtolower($matches[1]), $allowed_attributes)) {
                return $matches[1] . '="' . esc_attr($matches[2]) . '"';
            }
            return '';
        },
        $iframe_tag
    );
    
    return $iframe_tag;
}

function presslearn_add_analytics_column($columns) {
    $columns['presslearn_analytics'] = '<span class="dashicons dashicons-chart-bar" style="font-size: 16px; vertical-align: text-top;"></span> 통계';
    return $columns;
}
add_filter('manage_posts_columns', 'presslearn_add_analytics_column');
add_filter('manage_pages_columns', 'presslearn_add_analytics_column');

function presslearn_analytics_column_content($column, $post_id) {
    if ($column !== 'presslearn_analytics') {
        return;
    }
    
    global $wpdb;
    $permalink = get_permalink($post_id);
    $table_pageviews = $wpdb->prefix . 'presslearn_pageviews';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_pageviews'") != $table_pageviews) {
        ?>
        <div class="presslearn-analytics-stats">
            <span class="presslearn-views">0 뷰</span>
            <span class="presslearn-visitors">0 방문</span>
        </div>
        <?php
        return;
    }
    
    $cache_time = get_option('presslearn_analytics_cache_time', 300);
    $cache_date = current_time('Y-m-d');
    $cache_key_visitors = "presslearn_visitors_{$post_id}_{$cache_date}";
    $cache_key_today = "presslearn_today_{$post_id}_{$cache_date}";
    $cache_key_week = "presslearn_week_{$post_id}_{$cache_date}";
    $cache_key_month = "presslearn_month_{$post_id}_{$cache_date}";
    
    $total_views = get_post_meta($post_id, '_presslearn_post_views', true);
    if (empty($total_views)) {
        $total_views = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_pageviews WHERE url = %s",
            $permalink
        ));
        $total_views = intval($total_views);
        
        update_post_meta($post_id, '_presslearn_post_views', $total_views);
    }
    
    $total_visitors = wp_cache_get($cache_key_visitors, 'presslearn_analytics');
    if (false === $total_visitors) {
        $total_visitors = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT visitor_id) FROM $table_pageviews WHERE url = %s",
            $permalink
        ));
        $total_visitors = intval($total_visitors);
        wp_cache_set($cache_key_visitors, $total_visitors, 'presslearn_analytics', $cache_time);
    }
    
    $today_views = wp_cache_get($cache_key_today, 'presslearn_analytics');
    if (false === $today_views) {
        $today_views = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_pageviews WHERE url = %s AND DATE(created_at) = CURDATE()",
            $permalink
        ));
        $today_views = intval($today_views);
        wp_cache_set($cache_key_today, $today_views, 'presslearn_analytics', $cache_time);
    }
    
    $week_views = wp_cache_get($cache_key_week, 'presslearn_analytics');
    if (false === $week_views) {
        $week_views = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_pageviews WHERE url = %s AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)",
            $permalink
        ));
        $week_views = intval($week_views);
        wp_cache_set($cache_key_week, $week_views, 'presslearn_analytics', $cache_time);
    }
    
    $month_views = wp_cache_get($cache_key_month, 'presslearn_analytics');
    if (false === $month_views) {
        $month_views = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_pageviews WHERE url = %s AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
            $permalink
        ));
        $month_views = intval($month_views);
        wp_cache_set($cache_key_month, $month_views, 'presslearn_analytics', $cache_time);
    }
    
    ?>
    <div class="presslearn-analytics-stats">
        <div class="presslearn-stats-row">
            <span class="presslearn-total">
                <span class="dashicons dashicons-chart-bar" style="font-size: 16px; color: #2196F3; opacity: 0.8;"></span> 
                <?php echo esc_html(number_format($total_views)); ?>
            </span>
        </div>
        
        <div class="presslearn-stats-periods">
            <div class="presslearn-period today" title="<?php echo esc_attr('오늘 조회수'); ?>">
                <span><?php echo esc_html('오늘'); ?></span> <?php echo esc_html(number_format($today_views)); ?>
            </div>
            <div class="presslearn-period week" title="<?php echo esc_attr('최근 7일 조회수'); ?>">
                <span><?php echo esc_html('7일'); ?></span> <?php echo esc_html(number_format($week_views)); ?>
            </div>
            <div class="presslearn-period month" title="<?php echo esc_attr('최근 30일 조회수'); ?>">
                <span><?php echo esc_html('30일'); ?></span> <?php echo esc_html(number_format($month_views)); ?>
            </div>
        </div>
    </div>
    <?php
}
add_action('manage_posts_custom_column', 'presslearn_analytics_column_content', 10, 2);
add_action('manage_pages_custom_column', 'presslearn_analytics_column_content', 10, 2);

function presslearn_analytics_column_sortable($columns) {
    $columns['presslearn_analytics'] = 'presslearn_analytics';
    return $columns;
}
add_filter('manage_edit-post_sortable_columns', 'presslearn_analytics_column_sortable');
add_filter('manage_edit-page_sortable_columns', 'presslearn_analytics_column_sortable');

function presslearn_analytics_column_orderby($query) {
    if (!is_admin()) {
        return;
    }

    $orderby = $query->get('orderby');
    
    if ($orderby == 'presslearn_analytics') {
        global $wpdb;
        $table_pageviews = $wpdb->prefix . 'presslearn_pageviews';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_pageviews'") != $table_pageviews) {
            return;
        }
        
        $query->set('meta_key', '_presslearn_post_views');
        $query->set('orderby', 'meta_value_num');
    }
}
add_action('pre_get_posts', 'presslearn_analytics_column_orderby');

function presslearn_schedule_post_views_update() {
    if (!wp_next_scheduled('presslearn_daily_post_views_update')) {
        wp_schedule_event(strtotime('today 00:00:00'), 'daily', 'presslearn_daily_post_views_update');
    }
}
add_action('wp', 'presslearn_schedule_post_views_update');

function presslearn_add_analytics_column_styles() {
    wp_register_style('presslearn-analytics-column-css', false);
    wp_enqueue_style('presslearn-analytics-column-css');
    
    $analytics_column_css = "
    .presslearn-analytics-stats {
        display: block;
        line-height: 1.5;
        text-align: left;
    }

    .presslearn-stats-row {
        display: flex;
        align-items: center;
        margin-bottom: 5px
    }

    .presslearn-label {
        font-size: 13px;
        color: #666;
        margin-right: 5px;
    }

    .presslearn-total {
        font-size: 16px;
        font-weight: bold;
        color: #2196F3;
        display: flex;
        align-items: center;
    }

    .presslearn-total .dashicons {
        margin-right: 3px;
    }

    .presslearn-unit {
        font-size: 12px;
        font-weight: normal;
        color: #666;
        margin-left: 1px;
    }

    .presslearn-stats-periods {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        flex-flow: column;
    }

    .presslearn-period {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 11px;
        padding: 2px 6px;
        border-radius: 5px;
        white-space: nowrap;
    }

    .presslearn-period .dashicons {
        margin-right: 2px;
    }

    .presslearn-period.today {
        background-color: #E8F5E9;
        color: #4CAF50;
    }

    .presslearn-period.week {
        background-color: #E3F2FD;
        color: #2196F3;
    }

    .presslearn-period.month {
        background-color: #EDE7F6;
        color: #673AB7;
    }

    .column-presslearn_analytics {
        width: 165px;
    }
    ";
    
    wp_add_inline_style('presslearn-analytics-column-css', $analytics_column_css);
}
add_action('admin_head', 'presslearn_add_analytics_column_styles');

function presslearn_update_post_views() {
    global $wpdb;
    $table_pageviews = $wpdb->prefix . 'presslearn_pageviews';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_pageviews'") != $table_pageviews) {
        return;
    }
    
    $args = array(
        'post_type' => array('post', 'page'),
        'posts_per_page' => -1,
        'post_status' => 'publish'
    );
    $posts = get_posts($args);
    
    foreach ($posts as $post) {
        $permalink = get_permalink($post->ID);
        
        $views = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_pageviews WHERE url = %s",
            $permalink
        ));
        
        update_post_meta($post->ID, '_presslearn_post_views', $views);
    }
}
add_action('presslearn_daily_post_views_update', 'presslearn_update_post_views');

register_activation_hook(__FILE__, 'presslearn_update_post_views');

function presslearn_button_animation_styles() {
    if (!function_exists('presslearn_plugin') || !presslearn_plugin()->is_plugin_activated()) {
        return;
    }
    
    $quick_button_enabled = get_option('presslearn_quick_button_enabled', 'no');
    if ($quick_button_enabled !== 'yes') {
        return;
    }
    
    wp_register_style('presslearn-button-animation-css', false);
    wp_enqueue_style('presslearn-button-animation-css');
    
    $animation_styles = '
        .presslearn-button {
            transition: all 0.3s ease;
        }
        
        @keyframes presslearn-pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .presslearn-button-animation-pulse:hover {
            animation: presslearn-pulse 1s infinite;
        }
        
        .presslearn-button-animation-zoom:hover {
            transform: scale(1.1);
        }
        
        .presslearn-button-animation-fade:hover {
            opacity: 0.8;
        }
        
        @keyframes presslearn-shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .presslearn-button-animation-shake:hover {
            animation: presslearn-shake 0.5s;
        }';
    
    wp_add_inline_style('presslearn-button-animation-css', $animation_styles);
    
    wp_register_script('presslearn-button-animation-js', false, array(), PRESSLEARN_PLUGIN_VERSION, true);
    wp_enqueue_script('presslearn-button-animation-js');
    
    $animation_script = '
        document.addEventListener("DOMContentLoaded", function() {
            const buttons = document.querySelectorAll(".presslearn-button");
            
            buttons.forEach(function(button) {
                const originalColor = button.style.backgroundColor;
                const hoverColor = button.getAttribute("data-hover-color");
                
                if (hoverColor && hoverColor !== originalColor) {
                    button.addEventListener("mouseenter", function() {
                        this.style.backgroundColor = hoverColor;
                    });
                    
                    button.addEventListener("mouseleave", function() {
                        this.style.backgroundColor = originalColor;
                    });
                }
            });
        });';
    
    wp_add_inline_script('presslearn-button-animation-js', $animation_script);
}
add_action('wp_body_open', 'presslearn_button_animation_styles');

function presslearn_button_animation_admin_styles() {
    if (!function_exists('presslearn_plugin') || !presslearn_plugin()->is_plugin_activated()) {
        return;
    }
    
    $quick_button_enabled = get_option('presslearn_quick_button_enabled', 'no');
    if ($quick_button_enabled !== 'yes') {
        return;
    }
    
    wp_register_style('presslearn-button-animation-admin-css', false);
    wp_enqueue_style('presslearn-button-animation-admin-css');
    
    $admin_animation_styles = '
        .editor-styles-wrapper .presslearn-button {
            transition: all 0.3s ease;
        }
        
        @keyframes presslearn-pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .editor-styles-wrapper .presslearn-button-animation-pulse:hover {
            animation: presslearn-pulse 1s infinite;
        }
        
        .editor-styles-wrapper .presslearn-button-animation-zoom:hover {
            transform: scale(1.1);
        }
        
        .editor-styles-wrapper .presslearn-button-animation-fade:hover {
            opacity: 0.8;
        }
        
        @keyframes presslearn-shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .editor-styles-wrapper .presslearn-button-animation-shake:hover {
            animation: presslearn-shake 0.5s;
        }';
    
    wp_add_inline_style('presslearn-button-animation-admin-css', $admin_animation_styles);
}
add_action('admin_enqueue_scripts', 'presslearn_button_animation_admin_styles');

function presslearn_delete_analytics_data() {
    if (!current_user_can('manage_options')) {
        wp_die('권한이 없습니다.');
    }
    
    check_ajax_referer('presslearn_delete_analytics_data_nonce', 'nonce');
    
    global $wpdb;
    $table_pageviews = $wpdb->prefix . 'presslearn_pageviews';
    $table_visitors = $wpdb->prefix . 'presslearn_visitors';
    $table_referrers = $wpdb->prefix . 'presslearn_referrers';
    
    $tables_exist = true;
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_pageviews'") != $table_pageviews) {
        $tables_exist = false;
    }
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_visitors'") != $table_visitors) {
        $tables_exist = false;
    }
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_referrers'") != $table_referrers) {
        $tables_exist = false;
    }
    
    if (!$tables_exist) {
        wp_send_json_error(array('message' => '테이블이 존재하지 않습니다.'));
        return;
    }
    
    $wpdb->query("TRUNCATE TABLE $table_pageviews");
    $wpdb->query("TRUNCATE TABLE $table_visitors");
    $wpdb->query("TRUNCATE TABLE $table_referrers");
    
    $wpdb->query("UPDATE $wpdb->posts SET presslearn_post_views = 0 WHERE presslearn_post_views > 0");
    
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_alpack_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_alpack_%'");
    
    wp_cache_flush();
    
    wp_send_json_success(array('message' => '모든 통계 데이터와 캐시가 성공적으로 삭제되었습니다.'));
}

add_action('wp_ajax_presslearn_dismiss_update_notice', 'presslearn_dismiss_update_notice');

function presslearn_dismiss_update_notice() {
    check_ajax_referer('presslearn_dismiss_notice', 'nonce');
    
    if (current_user_can('manage_options')) {
        delete_transient('presslearn_plugin_updated_notice');
        wp_send_json_success();
    } else {
        wp_send_json_error('권한이 없습니다.');
    }
    
    wp_die();
}

add_action('wp_head', 'presslearn_output_header_code', 999);
add_action('wp_body_open', 'presslearn_output_body_open_code');
add_action('wp_footer', 'presslearn_output_before_closing_body_code', 1);
add_action('wp_footer', 'presslearn_output_footer_code', 999);

/**
 * Output header code in <head> section
 * 
 * Security Note: Code is intentionally output without escaping as this is 
 * an admin-only feature for inserting HTML/CSS/JS code. Only users with
 * 'manage_options' capability can save/output code.
 */
function presslearn_output_header_code() {
    if (get_option('presslearn_header_footer_enabled', 'no') !== 'yes') {
        return;
    }
    
    $header_code = get_option('presslearn_header_code', '');
    if (!empty($header_code)) {
        if (current_user_can('manage_options') || !is_admin()) {
            echo "\n    <!-- PressLearn Header Code -->\n";
            echo wp_unslash($header_code) . "\n";
            echo "    <!-- /PressLearn Header Code -->\n\n";
        }
    }
}

function presslearn_output_body_open_code() {
    if (get_option('presslearn_header_footer_enabled', 'no') !== 'yes') {
        return;
    }
    
    $body_open_code = get_option('presslearn_body_open_code', '');
    if (!empty($body_open_code)) {
        if (current_user_can('manage_options') || !is_admin()) {
            echo "\n<!-- PressLearn Body Open Code -->\n";
            echo wp_unslash($body_open_code) . "\n";
            echo "<!-- /PressLearn Body Open Code -->\n\n";
        }
    }
}

function presslearn_output_before_closing_body_code() {
    if (get_option('presslearn_header_footer_enabled', 'no') !== 'yes') {
        return;
    }
    
    $before_closing_code = get_option('presslearn_before_closing_body_code', '');
    if (!empty($before_closing_code)) {
        if (current_user_can('manage_options') || !is_admin()) {
            echo "\n<!-- PressLearn Before Closing Body Code -->\n";
            echo wp_unslash($before_closing_code) . "\n";
            echo "<!-- /PressLearn Before Closing Body Code -->\n\n";
        }
    }
}

function presslearn_output_footer_code() {
    if (get_option('presslearn_header_footer_enabled', 'no') !== 'yes') {
        return;
    }
    
    $footer_code = get_option('presslearn_footer_code', '');
    if (!empty($footer_code)) {
        if (current_user_can('manage_options') || !is_admin()) {
            echo "\n<!-- PressLearn Footer Code -->\n";
            echo wp_unslash($footer_code) . "\n";
            echo "<!-- /PressLearn Footer Code -->\n\n";
        }
    }
/* --- ALPACK AI Extension 추가 코드 시작 --- */

// 1. 경로 및 URL 상수 정의 (기존에 없다면 추가)
if (!defined('PL_AI_PATH')) define('PL_AI_PATH', plugin_dir_path(__FILE__));
if (!defined('PL_AI_URL')) define('PL_AI_URL', plugin_dir_url(__FILE__));

// 2. 외부 로직 파일 로드 (includes 폴더 내 파일들)
require_once PL_AI_PATH . 'includes/admin-settings.php';
require_once PL_AI_PATH . 'includes/ai-metabox.php';

// 3. 사이드바 메뉴 등록 (ALPACK 스타일 설정 페이지로 이동)
add_action('admin_menu', function() {
    add_menu_page(
        'ALPACK AI 설정',       // 페이지 제목
        'ALPACK AI',           // 사이드바 메뉴 이름
        'manage_options',       // 권한
        'presslearn-settings',   // 슬러그 (설정 페이지 링크)
        'pl_ai_render_settings_page', // admin-settings.php에 정의된 콜백 함수
        'dashicons-superhero',  // 아이콘
        30                      // 메뉴 위치
    );
});

// 4. 필요한 CSS 및 JS 파일 로드 (assets 폴더)
add_action('admin_enqueue_scripts', function($hook) {
    // 글 편집 화면과 설정 페이지에서만 스크립트 실행
    if (!in_array($hook, ['post.php', 'post-new.php', 'toplevel_page_presslearn-settings'])) {
        return;
    }

    // CSS 파일 로드
    wp_enqueue_style('pl-ai-style', PL_AI_URL . 'assets/style.css', [], '2.7.0');

    // JS 파일 로드 및 데이터 전달
    wp_enqueue_script('pl-ai-script', PL_AI_URL . 'assets/script.js', ['jquery'], '2.7.0', true);
    
    // JS에서 사용할 동적 변수 (워커 주소 등) 설정
    wp_localize_script('pl-ai-script', 'plAiData', [
        'ajaxurl'   => admin_url('admin-ajax.php'),
        'workerUrl' => 'https://wpautoblogpostai.jiji15899.workers.dev', // 사용자님 워커 주소
        'nonce'     => wp_create_nonce('pl_ai_nonce')
    ]);
});

/* --- ALPACK AI Extension 추가 코드 끝 --- */

