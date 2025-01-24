<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI 智能助手</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header class="chat-header">
            <h1>
                    <svg class="header-icon" t="1737639729911" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="13937">
                        <path d="M960 737.4V472.8c1.2-106.3-84-193.4-190.3-194.6h-0.1l-226.8 1.4v-67.2c19.8-11.7 32.1-33 32.3-56 0.2-35.2-28-63.9-63.1-64.4-34.8 0-63 28.2-63 63.1v1.3c-0.1 23.1 12.2 44.5 32.2 56v65.8H254.4C148.5 280.3 63.8 366.9 64 472.8v264.6C62.8 843.7 148 930.8 254.3 932h513.9c106.4-0.5 192.3-87.2 191.8-193.6v-1z m-617.4-55.8c-41.8-2.3-73.7-38-71.5-79.8 2.1-38.6 32.9-69.3 71.5-71.5v-0.2c41.4 0.4 74.6 34.3 74.2 75.7 0 41.3-33 75-74.2 75.8z m366.8-7.1c-27.5 11.8-59.4 5-79.8-16.8-21.5-21.2-27.6-53.6-15.4-81.2 9.9-27.8 36.3-46.3 65.8-46.2v-0.1c41.4 0.4 74.6 34.3 74.2 75.7 0.3 29.8-17.4 56.8-44.8 68.6z" fill="#1a73e8" p-id="13938"></path>
                    </svg>
                AI 智能助手
                <button onclick="clearHistory()" class="clear-history-btn" title="清空当前显示的聊天记录" data-type="display">
                    <svg class="clear-icon" t="1737639805290" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="15124">
                        <path d="M912.96 176.64H128.32c-6.08 0-11.2-5.12-11.2-11.2v-76.8c0-5.44 3.84-9.92 9.28-11.2L368 32h274.24l272.32 45.44c5.44 0.96 9.28 5.76 9.28 11.2v76.48c0.32 6.4-4.8 11.52-10.88 11.52zM786.56 1011.84H254.72c-45.12 0-81.92-36.8-81.92-81.92V342.08c0-45.12 36.8-81.92 81.92-81.92h531.84c45.12 0 81.92 36.8 81.92 81.92v587.52a81.92 81.92 0 0 1-81.92 82.24z" fill="currentColor" p-id="15125"></path>
                    </svg>
                    清空记录
                </button>
            </h1>
        </header>
        
        <div class="chat-container">
            <div class="chat-messages" id="chat-messages">
                <div class="welcome-message" id="welcome-message">
                    <h2>欢迎使用 AI 智能助手</h2>
                    <p>您可以问我任何问题，我会尽力为您解答。</p>
                </div>
            </div>

            <div class="chat-input-container">
                <div class="input-wrapper">
                    <textarea id="user-input" placeholder="输入您的问题..." rows="1" onkeydown="if(event.keyCode === 13 && !event.shiftKey) { event.preventDefault(); sendMessage(); }"></textarea>
                </div>
                <button onclick="sendMessage()" class="send-button">
                    <svg class="send-icon" t="1737640059149" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="6951">
                        <path d="M748.032 927.0272a60.7232 60.7232 0 0 1-40.96-15.9744L558.08 775.68a9.8816 9.8816 0 0 0-7.3728-2.6112 10.24 10.24 0 0 0-7.0144 3.584l-59.6992 71.2704a61.44 61.44 0 0 1-106.7008-25.9584l-42.6496-190.5152a10.24 10.24 0 0 0-6.6048-7.3216l-193.28-63.488a61.44 61.44 0 0 1-8.96-112.64l646.8608-333.7728a61.44 61.44 0 0 1 89.1392 58.9824L809.2672 870.4a60.7744 60.7744 0 0 1-38.4 52.2752 61.44 61.44 0 0 1-22.8352 4.352zM551.4752 721.92a60.5696 60.5696 0 0 1 40.96 15.9232l148.9408 135.3728a10.24 10.24 0 0 0 16.6912-6.656l52.6848-697.1904a10.24 10.24 0 0 0-14.592-9.6768L149.2992 493.568a10.24 10.24 0 0 0 1.4336 18.432l193.28 63.488a61.44 61.44 0 0 1 40.6016 44.8l42.6496 190.5152a10.24 10.24 0 0 0 17.4592 4.2496l59.6992-71.2704a60.672 60.672 0 0 1 42.8032-21.76zM137.5232 471.04z" fill="currentColor" p-id="6952"></path>
                        <path d="M464.0768 719.6672a25.6 25.6 0 0 1-25.6-23.6032l-6.656-84.8384a76.2368 76.2368 0 0 1 19.968-57.4976l217.4976-235.1104a25.6 25.6 0 1 1 37.6832 34.6624L489.472 588.4928a24.9856 24.9856 0 0 0-6.5024 18.7392l6.656 84.8384a25.6 25.6 0 0 1-23.7056 27.5456z" fill="currentColor" p-id="6953"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- 添加浮动调试窗口 -->
    <div id="debug-window" class="debug-window" style="display: none;">
        <div class="debug-header">
            <div class="debug-controls">
                <input type="range" id="opacity-control" min="0.1" max="1" step="0.1" value="0.9">
                <button onclick="toggleDebug()" class="debug-toggle">隐藏</button>
            </div>
            <button onclick="clearDebug()" class="debug-clear">清除</button>
        </div>
        <div class="debug-content" id="debug-content"></div>
        <div class="resize-handle"></div>
    </div>

    <script src="script.js"></script>
</body>
</html> 