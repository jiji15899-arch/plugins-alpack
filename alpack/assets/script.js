jQuery(document).ready(function($) {
    let startTime;
    let timerInterval;

    $('#pl_generate_btn').click(async function() {
        const topic = $('#pl_topic').val();
        const lang = $('#pl_lang_select').val();
        const mode = $('#pl_mode_select').val();
        if(!topic) return alert("주제를 입력하세요.");

        // UI 초기화
        $(this).prop('disabled', true).text("생성 중...");
        $('#pl_progress_wrap').show();
        $('#pl_analysis_report').hide();
        startTime = Date.now();
        
        timerInterval = setInterval(() => {
            const elapsed = (Date.now() - startTime) / 1000;
            $('#pl_timer_val').text(elapsed.toFixed(1) + "s");
            if(elapsed < 60) $('#pl_bar_fill').css('width', (elapsed/60*100) + '%');
        }, 100);

        try {
            // 1. 텍스트 생성 (국가별 컨텍스트 자동 포함)
            const textRes = await fetch(plAiData.workerUrl, {
                method: 'POST',
                body: JSON.stringify({
                    type: "text", lang: lang, mode: mode,
                    prompt: `주제: ${topic}, 국가: ${lang}, 모드: ${mode}. 1500자 이상 작성. JSON: {"title":"..","body":"..","seo":98,"ads":92,"rev":85}`
                })
            });
            const textData = await textRes.json();
            const content = JSON.parse(textData.result.match(/\{[\s\S]*\}/)[0]);

            // 2. 이미지 생성
            const imgRes = await fetch(plAiData.workerUrl, {
                method: 'POST',
                body: JSON.stringify({ type: "image", prompt: `High quality thumbnail for ${topic}` })
            });
            const imgData = await imgRes.json();

            // 3. 에디터 반영
            if (wp.data) {
                wp.data.dispatch('core/editor').editPost({ title: content.title });
                wp.data.dispatch('core/block-editor').resetBlocks(wp.blocks.parse(content.body));
                if(imgData.image) {
                    $.post(plAiData.ajaxurl, { 
                        action: 'pl_upload_image', 
                        base64: imgData.image, 
                        post_id: $('#post_ID').val() 
                    });
                }
            }

            // 4. 분석 결과 반영
            $('#pl_s_seo').text(content.seo + '%');
            $('#pl_s_ads').text(content.ads + '%');
            $('#pl_s_rev').text(content.rev + '%');
            $('#pl_analysis_report').fadeIn();
            $('#pl_bar_fill').css('width', '100%');

        } catch(e) {
            alert("오류 발생: " + e.message);
        } finally {
            clearInterval(timerInterval);
            $(this).prop('disabled', false).text("포스팅 생성 시작");
        }
    });
});
