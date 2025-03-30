<!DOCTYPE html>
<html lang="zh-CN">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dai</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
</head>

<body>
  <div class="island">
    <img class="island-img" src="ai.svg" alt="灵动岛图片">
    <span class="island-text">AI生成，不代表官方立场</span>
    <div class="island-lines">
      <div class="island-line"></div>
      <div class="island-line"></div>
      <div class="island-line"></div>
    </div>
  </div>

  <div class="chat-container">
    <div class="chat-header">
      Dai-接入多家大模型
      <div class="model-select-container">
        <select id="model-select">
          <option value="ollama/qwq:latest">QWQ-本地</option>
          <option value="ollama/deepseek-r1:32b">DeepSeek-R1-32B-本地</option>
        </select>
      </div>
    </div>
    <div class="chat-body"></div>
    <form class="chat-input">
      <input type="text" id="message" placeholder="输入您的消息...">
      <button type="submit"><i class="fas fa-paper-plane"></i></button>
      <button type="button" id="search-toggle" class="search-button" title="联网搜索">
        <i class="fas fa-search"></i>
      </button>
      <div class="clear-button">
        <button type="button">清除</button>
      </div>
    </form>
  </div>

  <div class="loading-indicator">
    <i class="fas fa-spinner fa-spin"></i>
  </div>

  <script>
    $(document).ready(function () {
      $('.chat-container').show();

      const chatHistoryKey = 'chatHistory';
      const chatHistory = JSON.parse(localStorage.getItem(chatHistoryKey)) || [];
      const userLimitsKey = 'userLimits';
      const userLimits = JSON.parse(localStorage.getItem(userLimitsKey)) || { count: 0, lastTime: 0 };

      let chatContext = [];
      let originalMessages = {};

      loadChatHistory(chatHistory);
      const welcomeMessage = `<div class="chat-message bot"><div class="bot-info"><img class="bot-avatar" src="ai.svg" alt="AI头像"><span class="bot-name">Dai</span></div><div class="message-content">您好！我是Dai，请随时提问</div></div>`;
      $('.chat-body').append(welcomeMessage);

      $('form').on('submit', function (e) {
        e.preventDefault();
        const messageInput = $('#message');
        const message = messageInput.val().trim();

        if (!message) {
          alert('请输入消息');
          return;
        }

        const messageId = Date.now();
        originalMessages[messageId] = message;

        const userMessage = createUserMessage(message, messageId);
        $('.chat-body').append(userMessage);
        processMessage(message, messageId);
        messageInput.val('');
      });

      function createUserMessage(message, messageId) {
        return $(`
          <div class="chat-message user" id="msg-${messageId}" data-id="${messageId}">
            <div class="message-content">${escapeHtml(message)}</div>
            <div class="message-actions">
              <button class="action-button rewrite-btn" data-id="${messageId}">
                <i class="fas fa-edit"></i> 重写
              </button>
            </div>
          </div>
        `);
      }

      function createBotMessage(response, messageId) {
        // 提取思考过程
        let finalResponse = response;
        let thinkContent = '';
        
        const thinkMatch = response.match(/<think>([\s\S]*?)<\/think>/);
        if (thinkMatch) {
          thinkContent = thinkMatch[1].trim();
          finalResponse = response.replace(/<think>[\s\S]*?<\/think>/, '').trim();
        }
        
        const htmlContent = marked.parse(finalResponse);
        const messageElement = $(`
          <div class="chat-message bot" data-original="${escapeHtml(response)}" data-id="${messageId}">
            <div class="bot-info">
              <img class="bot-avatar" src="ai.svg" alt="AI头像">
              <span class="bot-name">Dai</span>
            </div>
            <div class="message-content">${finalResponse}</div>
            ${thinkContent ? `
              <div class="think-toggle">
                <i class="fas fa-chevron-down"></i>
                显示思考过程
              </div>
              <div class="think-content">${marked.parse(thinkContent)}</div>
            ` : ''}
            <div class="message-actions" style="display:none;">
              <button class="action-button regenerate-btn">
                <i class="fas fa-redo"></i> 重新生成
              </button>
              <button class="action-button copy-btn">
                <i class="far fa-copy"></i> 复制
              </button>
              <button class="action-button render-btn">
                <i class="fas fa-code"></i> Markdown渲染
              </button>
            </div>
            <div class="message-status"><i class="fas fa-check"></i> 已完成</div>
          </div>
        `);

        // 绑定思考过程切换事件
        messageElement.find('.think-toggle').on('click', function() {
          const $this = $(this);
          const $thinkContent = $this.next('.think-content');
          $thinkContent.slideToggle(200);
          $this.toggleClass('active');
          $this.html($this.hasClass('active') ? 
            '<i class="fas fa-chevron-up"></i>隐藏思考过程' : 
            '<i class="fas fa-chevron-down"></i>显示思考过程'
          );
        });

        return messageElement;
      }

      function processMessage(message, messageId, replaceId = null) {
        $('.loading-indicator').show();
        
        if (replaceId) {
          const messageToRemove = $(`.chat-message[data-id="${replaceId}"]`);
          const messageContent = messageToRemove.find('.message-content').text();
          messageToRemove.remove();
          
          // Remove both user and assistant messages from context
          chatContext = chatContext.filter(msg => 
            msg.content !== messageContent && 
            msg.content !== originalMessages[messageId]
          );
        }

        const formData = new FormData();
        formData.append('message', message);
        formData.append('model', $('#model-select').val());
        formData.append('context', JSON.stringify(chatContext));

        $.ajax({
          url: 'api.php',
          type: 'POST',
          data: formData,
          contentType: false,
          processData: false,
          success: function(response) {
            const botMessageId = Date.now();
            const botMessage = createBotMessage(response, botMessageId);
            
            if (replaceId) {
              $(`#msg-${messageId}`).after(botMessage);
            } else {
              $(`#msg-${messageId}`).after(botMessage);
            }

            typeWriterEffect(botMessage.find('.message-content'), () => {
              botMessage.find('.message-actions').show();
              botMessage.find('.message-status').show();
            });

            chatContext.push({ role: "user", content: message });
            chatContext.push({ role: "assistant", content: response });
            
            chatHistory.push({
              type: 'bot',
              content: response,
              id: botMessageId,
              rendered: false
            });
            localStorage.setItem(chatHistoryKey, JSON.stringify(chatHistory));
          },
          complete: function() {
            $('.loading-indicator').hide();
            $('.chat-body').scrollTop($('.chat-body')[0].scrollHeight);
          }
        });
      }

      function typeWriterEffect(element, callback) {
        const text = element.data('original') || element.text();
        element.text('').data('original', text);
        
        let i = 0;
        const interval = setInterval(() => {
          if (i < text.length) {
            element.append(text.charAt(i));
            i++;
          } else {
            clearInterval(interval);
            if (callback) callback();
          }
        }, 50);
      }

      $('.chat-body')
        .on('click', '.regenerate-btn', function() {
          const messageId = $(this).closest('.chat-message').data('id');
          const questionId = $(this).closest('.chat-message').prev('.user').data('id');
          processMessage(originalMessages[questionId], questionId, messageId);
        })
        .on('click', '.render-btn', function() {
          const message = $(this).closest('.chat-message');
          const content = message.find('.message-content');
          
          if (!message.data('rendered')) {
            content.html(marked.parse(content.text()));
            message.data('rendered', true);
            $(this).html('<i class="fas fa-undo"></i> 原始文本');
          } else {
            content.text(content.data('original'));
            message.data('rendered', false);
            $(this).html('<i class="fas fa-code"></i> Markdown渲染');
          }
        })
        .on('click', '.copy-btn', function() {
          const content = $(this).closest('.chat-message').find('.message-content');
          const text = content.data('original') || content.text();
          navigator.clipboard.writeText(text);
        });

      $('.chat-body').on('click', '.rewrite-btn', function() {
        const messageId = $(this).data('id');
        const original = originalMessages[messageId];
        
        const modal = $(`
          <div class="rewrite-modal">
            <h4>编辑消息</h4>
            <textarea class="rewrite-textarea" placeholder="请输入修改后的问题...">${original}</textarea>
            <div class="modal-actions">
              <button class="action-button cancel-btn">取消</button>
              <button class="action-button confirm-btn">发送修改</button>
            </div>
          </div>
        `);
        
        modal.hide().fadeIn(200);
        $('body').append(modal);

        modal.find('.cancel-btn').click(() => modal.fadeOut(200, () => modal.remove()));
        modal.find('.confirm-btn').click(() => {
          const newMessage = modal.find('textarea').val().trim();
          if(newMessage) {
            originalMessages[messageId] = newMessage;
            $(`#msg-${messageId} .message-content`).text(newMessage);
            
            const botMessage = $(`#msg-${messageId}`).next('.bot');
            if (botMessage.length) {
              processMessage(newMessage, messageId, botMessage.data('id'));
            }
            
            modal.fadeOut(200, () => modal.remove());
          }
        });
      });

      function escapeHtml(unsafe) {
        return unsafe
          .replace(/&/g, "&amp;")
          .replace(/</g, "&lt;")
          .replace(/>/g, "&gt;")
          .replace(/"/g, "&quot;")
          .replace(/'/g, "&#039;");
      }

      function loadChatHistory(history) {
        history.forEach(entry => {
          if(entry.type === 'user') {
            $('.chat-body').append(createUserMessage(entry.content, entry.id));
            originalMessages[entry.id] = entry.content;
          } else {
            $('.chat-body').append(createBotMessage(entry.content));
          }
        });
        $('.chat-body').scrollTop($('.chat-body')[0].scrollHeight);
      }

      $('.clear-button button').on('click', function () {
        $('.chat-body').empty();
        localStorage.removeItem(chatHistoryKey);
        localStorage.removeItem(userLimitsKey);
        chatContext = [];
        const welcomeMessage = `<div class="chat-message bot"><div class="bot-info"><img class="bot-avatar" src="ai.svg" alt="AI头像"><span class="bot-name">Dai</span></div><div class="message-content">您好！我是Dai，请随时提问</div></div>`;
        $('.chat-body').append(welcomeMessage);
        $('.chat-body').scrollTop($('.chat-body')[0].scrollHeight);
      });

      $(window).on('load', function() {
        loadChatHistory(chatHistory);
        $('.chat-body').scrollTop($('.chat-body')[0].scrollHeight);
      });

      // 联网搜索功能
      let isSearchEnabled = false;
      let searchResults = [];
      
      async function performWebSearch(query) {
        try {
          $('.loading-indicator').show();
          const response = await $.ajax({
            url: 'api.php',
            type: 'POST',
            data: {
              action: 'search',
              query: query
            }
          });
          
          searchResults = response.results;
          return response;
        } catch (error) {
          console.error('搜索失败:', error);
          throw error;
        } finally {
          $('.loading-indicator').hide();
        }
      }

      $('#search-toggle').on('click', function() {
        isSearchEnabled = !isSearchEnabled;
        $(this).toggleClass('active', isSearchEnabled);
        
        const status = isSearchEnabled ? '已开启' : '已关闭';
        const message = `联网搜索功能${status}`;
        
        const notification = $(`
          <div class="chat-message bot">
            <div class="bot-info">
              <img class="bot-avatar" src="ai.svg" alt="AI头像">
              <span class="bot-name">系统通知</span>
            </div>
            <div class="message-content">${message}</div>
          </div>
        `);
        
        $('.chat-body').append(notification);
        $('.chat-body').scrollTop($('.chat-body')[0].scrollHeight);
      });

      // 修改消息处理函数以包含搜索功能
      async function processMessage(message, messageId, replaceId = null) {
        $('.loading-indicator').show();
        
        if (replaceId) {
          const messageToRemove = $(`.chat-message[data-id="${replaceId}"]`);
          const messageContent = messageToRemove.find('.message-content').text();
          messageToRemove.remove();
          
          chatContext = chatContext.filter(msg => 
            msg.content !== messageContent && 
            msg.content !== originalMessages[messageId]
          );
        }

        const formData = new FormData();
        formData.append('message', message);
        formData.append('model', $('#model-select').val());
        formData.append('context', JSON.stringify(chatContext));
        
        if (isSearchEnabled) {
          try {
            const searchResponse = await performWebSearch(message);
            formData.append('search_results', JSON.stringify(searchResponse.results));
          } catch (error) {
            const errorMessage = $(`
              <div class="chat-message bot">
                <div class="bot-info">
                  <img class="bot-avatar" src="ai.svg" alt="AI头像">
                  <span class="bot-name">系统通知</span>
                </div>
                <div class="message-content">搜索失败，请稍后重试</div>
              </div>
            `);
            $('.chat-body').append(errorMessage);
          }
        }

        $.ajax({
          url: 'api.php',
          type: 'POST',
          data: formData,
          contentType: false,
          processData: false,
          success: function(response) {
            const botMessageId = Date.now();
            const botMessage = createBotMessage(response, botMessageId);
            
            if (replaceId) {
              $(`#msg-${messageId}`).after(botMessage);
            } else {
              $(`#msg-${messageId}`).after(botMessage);
            }

            typeWriterEffect(botMessage.find('.message-content'), () => {
              botMessage.find('.message-actions').show();
              botMessage.find('.message-status').show();
            });

            chatContext.push({ role: "user", content: message });
            chatContext.push({ role: "assistant", content: response });
            
            chatHistory.push({
              type: 'bot',
              content: response,
              id: botMessageId,
              rendered: false
            });
            localStorage.setItem(chatHistoryKey, JSON.stringify(chatHistory));
          },
          complete: function() {
            $('.loading-indicator').hide();
            $('.chat-body').scrollTop($('.chat-body')[0].scrollHeight);
          }
        });
      }
    });
  </script>
  <div class="footer">
    <a href="https://www.dduck.fun/posts/help-for-dai.html">使用文档</a> |
    <a href="https://github.com/ououduck/Dai">GitHub已开源</a> |
    <span>© 2025 D工作室 & Duck</span>
  </div>
</body>
</html>
