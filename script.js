// 从本地存储加载聊天记录
function loadChatFromCache() {
    console.log('开始加载缓存的聊天记录');
    const cachedHistory = localStorage.getItem('chatHistory');
    console.log('缓存内容:', cachedHistory);
    if (cachedHistory) {
        const history = JSON.parse(cachedHistory);
        const messagesDiv = document.getElementById('chat-messages');
        const welcomeMessage = document.getElementById('welcome-message');
        
        if (history.length > 0 && welcomeMessage) {
            welcomeMessage.remove();
        }
        
        history.forEach(record => {
            console.log('加载记录:', record);
            appendMessage(record.user_query, 'user');
            appendMessage(record.ai_response, 'ai');
        });
        
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }
}

// 保存聊天记录到本地存储
function saveChatToCache(userQuery, aiResponse) {
    let history = [];
    const cachedHistory = localStorage.getItem('chatHistory');
    
    if (cachedHistory) {
        history = JSON.parse(cachedHistory);
    }
    
    // 检查是否存在相同的问题
    const isDuplicate = history.some(record => record.user_query === userQuery);
    
    // 如果不是重复问题，才添加到历史记录
    if (!isDuplicate) {
        history.push({
            user_query: userQuery,
            ai_response: aiResponse,
            timestamp: new Date().toISOString()
        });
        
        localStorage.setItem('chatHistory', JSON.stringify(history));
    }
}

// 确保DOM完全加载后再加载历史记录
document.addEventListener('DOMContentLoaded', function() {
    loadChatFromCache();  // 从缓存加载
    // 自动聚焦到输入框
    const userInput = document.getElementById('user-input');
    userInput.focus();
});

let currentEventSource = null;  // 添加全局变量来跟踪当前的 EventSource

function sendMessage() {
    const userInput = document.getElementById('user-input');
    const message = userInput.value.trim();
    const sendButton = document.querySelector('.send-button');
    
    // 如果当前正在接收响应，则停止输出
    if (currentEventSource) {
        currentEventSource.close();
        currentEventSource = null;
        // 恢复发送按钮状态
        sendButton.innerHTML = `
            <svg class="send-icon" t="1737640059149" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="6951">
                <path d="M748.032 927.0272a60.7232 60.7232 0 0 1-40.96-15.9744L558.08 775.68a9.8816 9.8816 0 0 0-7.3728-2.6112 10.24 10.24 0 0 0-7.0144 3.584l-59.6992 71.2704a61.44 61.44 0 0 1-106.7008-25.9584l-42.6496-190.5152a10.24 10.24 0 0 0-6.6048-7.3216l-193.28-63.488a61.44 61.44 0 0 1-8.96-112.64l646.8608-333.7728a61.44 61.44 0 0 1 89.1392 58.9824L809.2672 870.4a60.7744 60.7744 0 0 1-38.4 52.2752 61.44 61.44 0 0 1-22.8352 4.352zM551.4752 721.92a60.5696 60.5696 0 0 1 40.96 15.9232l148.9408 135.3728a10.24 10.24 0 0 0 16.6912-6.656l52.6848-697.1904a10.24 10.24 0 0 0-14.592-9.6768L149.2992 493.568a10.24 10.24 0 0 0 1.4336 18.432l193.28 63.488a61.44 61.44 0 0 1 40.6016 44.8l42.6496 190.5152a10.24 10.24 0 0 0 17.4592 4.2496l59.6992-71.2704a60.672 60.672 0 0 1 42.8032-21.76zM137.5232 471.04z" fill="currentColor" p-id="6952"></path>
                <path d="M464.0768 719.6672a25.6 25.6 0 0 1-25.6-23.6032l-6.656-84.8384a76.2368 76.2368 0 0 1 19.968-57.4976l217.4976-235.1104a25.6 25.6 0 1 1 37.6832 34.6624L489.472 588.4928a24.9856 24.9856 0 0 0-6.5024 18.7392l6.656 84.8384a25.6 25.6 0 0 1-23.7056 27.5456z" fill="currentColor" p-id="6953"></path>
            </svg>`;
        sendButton.title = "发送消息";
        return;
    }

    if (!message) {
        alert('请输入消息');
        return;
    }

    // 检查是否是重复问题（本地缓存）
    const cachedHistory = localStorage.getItem('chatHistory');
    if (cachedHistory) {
        const history = JSON.parse(cachedHistory);
        const existingRecord = history.find(record => record.user_query === message);
        
        if (existingRecord) {
            // 如果是重复问题，直接显示历史回答
            appendMessage(message, 'user');
            appendMessage(existingRecord.ai_response, 'ai');
            userInput.value = '';
            return;
        }
    }

    // 显示用户消息
    appendMessage(message, 'user');
    userInput.value = '';

    // 更改发送按钮为停止按钮
    sendButton.innerHTML = `
        <svg class="send-icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg">
            <path d="M512 0C229.2 0 0 229.2 0 512s229.2 512 512 512 512-229.2 512-512S794.8 0 512 0z m0 981.3C252.9 981.3 42.7 771.1 42.7 512S252.9 42.7 512 42.7 981.3 252.9 981.3 512 771.1 981.3 512 981.3z" fill="currentColor"></path>
            <path d="M320 320h384v384H320z" fill="currentColor"></path>
        </svg>`;
    sendButton.title = "停止生成";

    // 创建AI消息容器
    const aiMessageDiv = document.createElement('div');
    aiMessageDiv.className = 'message ai-message';
    document.getElementById('chat-messages').appendChild(aiMessageDiv);

    // 添加加载动画
    const loadingDots = document.createElement('div');
    loadingDots.className = 'loading-dots';
    loadingDots.innerHTML = '<span>.</span><span>.</span><span>.</span>';
    aiMessageDiv.appendChild(loadingDots);

    // 使用EventSource接收流式响应
    currentEventSource = new EventSource(`chat.php?message=${encodeURIComponent(message)}`);
    let aiResponse = '';

    currentEventSource.onmessage = function(event) {
        try {
            // 记录原始数据到调试窗口
            addDebugInfo(event.data);
            
            // 检查数据是否以"data:"开头
            let jsonStr = event.data;
            if (jsonStr.startsWith('data:')) {
                jsonStr = jsonStr.slice(5).trim();
            }
            
            // 解析JSON
            const data = JSON.parse(jsonStr);
            
            if (data.error) {
                aiMessageDiv.textContent = `错误: ${data.error}`;
                aiMessageDiv.className = 'message error-message';
                currentEventSource.close();
                return;
            }

            // 处理流式响应
            if (data.choices && data.choices[0].delta && data.choices[0].delta.content) {
                const newContent = data.choices[0].delta.content;
                
                // 移除loading动画
                if (loadingDots && loadingDots.parentNode === aiMessageDiv) {
                    aiMessageDiv.removeChild(loadingDots);
                }
                
                // 追加新内容
                aiResponse += newContent;
                
                // 格式化并显示内容
                const formattedContent = formatAIResponse(aiResponse);
                
                // 如果aiMessageDiv还没有内容容器，创建一个
                if (!aiMessageDiv.querySelector('.ai-content')) {
                    const contentElement = document.createElement('div');
                    contentElement.className = 'ai-content formatted';
                    aiMessageDiv.appendChild(contentElement);
                }
                
                // 更新内容，使用平滑过渡
                const contentElement = aiMessageDiv.querySelector('.ai-content');
                contentElement.style.transition = 'opacity 0.3s ease';
                contentElement.style.opacity = '0';
                
                setTimeout(() => {
                    contentElement.innerHTML = formattedContent;
                    contentElement.style.opacity = '1';
                }, 50);
                
                // 平滑滚动到底部
                const messagesDiv = document.getElementById('chat-messages');
                messagesDiv.scrollTo({
                    top: messagesDiv.scrollHeight,
                    behavior: 'smooth'
                });
            }

            if (data.choices && data.choices[0].finish_reason === "stop") {
                currentEventSource.close();
                currentEventSource = null;
                // 恢复发送按钮状态
                sendButton.innerHTML = `
                    <svg class="send-icon" t="1737640059149" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="6951">
                        <path d="M748.032 927.0272a60.7232 60.7232 0 0 1-40.96-15.9744L558.08 775.68a9.8816 9.8816 0 0 0-7.3728-2.6112 10.24 10.24 0 0 0-7.0144 3.584l-59.6992 71.2704a61.44 61.44 0 0 1-106.7008-25.9584l-42.6496-190.5152a10.24 10.24 0 0 0-6.6048-7.3216l-193.28-63.488a61.44 61.44 0 0 1-8.96-112.64l646.8608-333.7728a61.44 61.44 0 0 1 89.1392 58.9824L809.2672 870.4a60.7744 60.7744 0 0 1-38.4 52.2752 61.44 61.44 0 0 1-22.8352 4.352zM551.4752 721.92a60.5696 60.5696 0 0 1 40.96 15.9232l148.9408 135.3728a10.24 10.24 0 0 0 16.6912-6.656l52.6848-697.1904a10.24 10.24 0 0 0-14.592-9.6768L149.2992 493.568a10.24 10.24 0 0 0 1.4336 18.432l193.28 63.488a61.44 61.44 0 0 1 40.6016 44.8l42.6496 190.5152a10.24 10.24 0 0 0 17.4592 4.2496l59.6992-71.2704a60.672 60.672 0 0 1 42.8032-21.76zM137.5232 471.04z" fill="currentColor" p-id="6952"></path>
                        <path d="M464.0768 719.6672a25.6 25.6 0 0 1-25.6-23.6032l-6.656-84.8384a76.2368 76.2368 0 0 1 19.968-57.4976l217.4976-235.1104a25.6 25.6 0 1 1 37.6832 34.6624L489.472 588.4928a24.9856 24.9856 0 0 0-6.5024 18.7392l6.656 84.8384a25.6 25.6 0 0 1-23.7056 27.5456z" fill="currentColor" p-id="6953"></path>
                    </svg>`;
                sendButton.title = "发送消息";
                // 保存到本地缓存
                saveChatToCache(message, aiResponse);
            }
        } catch (error) {
            console.log('JSON解析失败，原始数据:', event.data);
            if (event.data && typeof event.data === 'string') {
                aiResponse += event.data;
                const formattedContent = formatAIResponse(aiResponse);
                aiMessageDiv.innerHTML = formattedContent;
            }
        }
    };

    eventSource.onerror = function(error) {
        currentEventSource.close();
        if (aiResponse === '') {
            aiMessageDiv.textContent = '发生错误，请稍后重试';
            aiMessageDiv.className = 'message error-message';
        }
    };

    // 添加格式化函数
    function formatAIResponse(content) {
        if (!content) return '';
        
        // 首先处理代码块
        content = content.replace(/```([\s\S]*?)```/g, function(match, code) {
            return `<pre class="code-block"><code>${code}</code></pre>`;
        });
        
        // 处理标题
        content = content.replace(/### (.*?)(?:\n|$)/g, '<h3 class="ai-title">$1</h3>\n');
        
        // 处理加粗文本
        content = content.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        
        // 处理段落，保持自然的换行
        const paragraphs = content.split(/\n\s*\n/);
        let formattedContent = '';
        
        paragraphs.forEach(para => {
            para = para.trim();
            if (!para) return;
            
            // 移除段落末尾的破折号
            para = para.replace(/---\s*$/, '');
            
            if (para.startsWith('<h3') || para.startsWith('<pre')) {
                formattedContent += para;  // 标题和代码块直接添加
            } else {
                // 处理段落内的换行，保持自然段落格式
                const lines = para.split('\n').map(line => line.trim()).filter(line => line);
                formattedContent += `<p>${lines.join('<br>')}</p>`;
            }
        });
        
        return formattedContent;
    }

    // 移除原有的 done 事件监听器，因为我们现在在流式传输时就进行格式化
    currentEventSource.addEventListener('done', function() {
        // 可以在这里添加完成后的其他处理逻辑
        console.log('Stream completed');
    });
}

function appendMessage(content, type) {
    const messagesDiv = document.getElementById('chat-messages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}-message`;
    if (type === 'user') {
        messageDiv.textContent = content;
    } else {
        // AI 回答需要保持格式
        const contentElement = document.createElement('div');
        contentElement.className = 'ai-content formatted';
        contentElement.innerHTML = formatAIResponse(content);
        messageDiv.appendChild(contentElement);
    }
    messagesDiv.appendChild(messageDiv);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

// 调试窗口拖拽功能
const debugWindow = document.getElementById('debug-window');
const debugHeader = debugWindow.querySelector('.debug-header');
let isDragging = false;
let currentX;
let currentY;
let initialX;
let initialY;
let xOffset = 0;
let yOffset = 0;

debugHeader.addEventListener('mousedown', startDragging);
document.addEventListener('mousemove', drag);
document.addEventListener('mouseup', stopDragging);

function startDragging(e) {
    initialX = e.clientX - xOffset;
    initialY = e.clientY - yOffset;
    
    if (e.target === debugHeader) {
        isDragging = true;
    }
}

function drag(e) {
    if (isDragging) {
        e.preventDefault();
        currentX = e.clientX - initialX;
        currentY = e.clientY - initialY;
        xOffset = currentX;
        yOffset = currentY;
        
        setTranslate(currentX, currentY, debugWindow);
    }
}

function stopDragging() {
    isDragging = false;
}

function setTranslate(xPos, yPos, el) {
    el.style.transform = `translate(${xPos}px, ${yPos}px)`;
}

// 透明度控制
const opacityControl = document.getElementById('opacity-control');
opacityControl.addEventListener('input', (e) => {
    debugWindow.style.backgroundColor = `rgba(30, 30, 30, ${e.target.value})`;
    debugWindow.querySelector('.debug-header').style.backgroundColor = `rgba(45, 45, 45, ${e.target.value})`;
});

// 显示/隐藏控制
function toggleDebug() {
    const btn = document.querySelector('.debug-toggle');
    if (debugWindow.style.display === 'none') {
        debugWindow.style.display = 'flex';
        debugWindow.style.opacity = '1';
        debugWindow.style.pointerEvents = 'auto';
        btn.textContent = '隐藏';
    } else {
        debugWindow.style.opacity = '0';
        debugWindow.style.pointerEvents = 'none';
        debugWindow.style.display = 'none';
        btn.textContent = '显示';
    }
}

// 添加快捷键控制
document.addEventListener('keydown', function(e) {
    // F12 键打开/关闭调试窗口
    if (e.key === 'F12') {
        e.preventDefault(); // 阻止默认的开发者工具
        toggleDebug();
    }
});

// 清除内容
function clearDebug() {
    document.getElementById('debug-content').innerHTML = '';
}

// 添加调试信息
function addDebugInfo(data) {
    try {
        const debugContent = document.getElementById('debug-content');
        if (!debugContent) return; // 如果找不到调试窗口则静默返回
        
        const debugItem = document.createElement('div');
        debugItem.className = 'debug-item';
        
        const timestamp = document.createElement('div');
        timestamp.className = 'debug-timestamp';
        timestamp.textContent = new Date().toLocaleTimeString();
        
        const content = document.createElement('pre');
        content.className = 'debug-content-item';
        
        try {
            const jsonData = typeof data === 'string' ? JSON.parse(data) : data;
            content.textContent = JSON.stringify(jsonData, null, 2);
        } catch (e) {
            content.textContent = data;
        }
        
        debugItem.appendChild(timestamp);
        debugItem.appendChild(content);
        debugContent.appendChild(debugItem);
        debugContent.scrollTop = debugContent.scrollHeight;
    } catch (error) {
        console.log('Debug info error:', error);
    }
}

// 清空聊天历史
async function clearHistory() {
    if (!confirm('确定要清空当前显示的聊天记录吗？')) {
        return;
    }
    
    // 清空本地存储
    localStorage.removeItem('chatHistory');

    // 清空前端显示
    const messagesDiv = document.getElementById('chat-messages');
    messagesDiv.innerHTML = `
        <div class="welcome-message" id="welcome-message">
            <h2>欢迎使用 AI 智能助手</h2>
            <p>您可以问我任何问题，我会尽力为您解答。</p>
        </div>
    `;
}

// 格式化 AI 响应内容
function formatAIResponse(content) {
    if (!content) return '';

    // 首先处理代码块
    content = content.replace(/```([\s\S]*?)```/g, function(match, code) {
        return `<pre class="code-block"><code>${code}</code></pre>`;
    });
    
    // 处理标题
    content = content.replace(/### (.*?)(?:\n|$)/g, '<h3 class="ai-title">$1</h3>\n');
    
    // 处理加粗文本
    content = content.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    
    // 处理段落，保持自然的换行
    const paragraphs = content.split(/\n\s*\n/);
    let formattedContent = '';
    
    paragraphs.forEach(para => {
        para = para.trim();
        if (!para) return;
        
        // 移除段落末尾的破折号
        para = para.replace(/---\s*$/, '');
        
        if (para.startsWith('<h3') || para.startsWith('<pre')) {
            formattedContent += para;  // 标题和代码块直接添加
        } else {
            // 处理段落内的换行，保持自然段落格式
            const lines = para.split('\n').map(line => line.trim()).filter(line => line);
            formattedContent += `<p>${lines.join('<br>')}</p>`;
        }
    });
    
    return formattedContent;
} 