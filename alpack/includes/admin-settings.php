<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function() {
    add_menu_page('ALPACK AI 설정', 'ALPACK AI', 'manage_options', 'presslearn-settings', 'pl_ai_render_settings_page', 'dashicons-admin-generic');
});

function pl_ai_render_settings_page() {
    if (isset($_POST['pl_save'])) {
        update_option('pl_ai_base_lang', $_POST['pl_base_lang']);
        echo '<div class="updated"><p>설정이 저장되었습니다.</p></div>';
    }
    
    // 디자인 파일 호출
    include PL_AI_PATH . 'templates/admin-page-template.php';
}
