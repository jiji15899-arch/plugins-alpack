<?php
if (!defined('ABSPATH')) exit;

add_action('add_meta_boxes', function() {
    add_meta_box('pl_ai_metabox', '⚡ PressLearn AI Masterpiece', 'pl_ai_render_metabox', ['post', 'page'], 'side', 'high');
});

function pl_ai_render_metabox($post) {
    // 로직과 디자인을 분리: 템플릿 파일을 불러옴
    include PL_AI_PATH . 'templates/metabox-template.php';
}

// 이미지 처리 Ajax (동일)
add_action('wp_ajax_pl_upload_image', function() {
    $data = base64_decode($_POST['base64']);
    $file = wp_upload_bits('ai_thumb_'.time().'.jpg', null, $data);
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $id = media_handle_sideload(['name'=>basename($file['file']), 'tmp_name'=>$file['file']], $_POST['post_id']);
    if(!is_wp_error($id)) set_post_thumbnail($_POST['post_id'], $id);
    wp_send_json_success();
});
