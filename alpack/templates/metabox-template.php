<div id="pl-ai-container">
    <div class="pl-field">
        <label>🎯 글 주제</label>
        <input type="text" id="pl_topic" placeholder="미국 정부지원금 종류">
    </div>

    <div class="pl-field">
        <label>🌍 언어 및 국가 설정</label>
        <select id="pl_lang_select">
            <option value="ko">한국 (대한민국)</option>
            <option value="en">USA (United States)</option>
            <option value="ja">Japan (日本)</option>
        </select>
    </div>

    <div class="pl-field">
        <label>📝 포스팅 모드</label>
        <select id="pl_mode_select">
            <option value="adsense">💎 애드센스 승인용 (1500자+)</option>
            <option value="subsidy">💰 정부지원금 (표 포함)</option>
            <option value="pasona">🔥 수익형 (PASONA)</option>
        </select>
    </div>

    <button type="button" id="pl_generate_btn" class="pl-btn-main">🚀 마스터피스 생성</button>

    <div id="pl_analysis_report" style="display:none;">
        <h4>📊 ALPACK AI 분석</h4>
        <div class="pl-score-row"><span>SEO 최적화</span><b id="pl_s_seo">0%</b></div>
        <div class="pl-score-row"><span>승인 예상도</span><b id="pl_s_ads">0%</b></div>
        <div class="pl-score-row"><span>수익률 지수</span><b id="pl_s_rev">0%</b></div>
    </div>

    <div id="pl_progress_wrap" style="display:none;">
        <div class="pl-progress-bar"><div id="pl_bar_fill"></div></div>
        <div id="pl_timer_val">00.0s</div>
    </div>
</div>
