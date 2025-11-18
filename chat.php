<?php
session_start();
include 'db.php'; 
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}
$currentUser = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<title>Foodie Chat</title>

<style>
* {
  box-sizing: border-box;
}

html, body {
  height: 100%;
  margin: 0;
  overflow: hidden;
}

body {
  font-family: 'Poppins', sans-serif;
  background: linear-gradient(135deg, #fff8f0 0%, #ffe8d6 100%);
  display: flex;
  align-items: stretch;
  justify-content: flex-start;
  padding: 0;
  gap: 0;
  flex-direction: row-reverse;
}

/* Dashboard Button */
a.dashboard-btn {
  position: fixed;
  top: 20px;
  left: 20px;
  background: white;
  padding: 10px 16px;
  border-radius: 50px;
  box-shadow: 0 4px 12px rgba(255, 112, 67, 0.2);
  text-decoration: none;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
  color: #ff7043;
  transition: all 0.3s ease;
  z-index: 100;
}

a.dashboard-btn:hover {
  background: #ff7043;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(255, 112, 67, 0.3);
}

/* Header */
.chat-header {
  width: 350px;
  padding: 40px 30px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: flex-start;
  animation: fadeInLeft 0.6s ease;
  background: linear-gradient(135deg, #fff8f0 0%, #ffe8d6 100%);
}

h1 {
  color: #FEA116;
  margin: 0 0 12px 0;
  font-size: 35px;
  font-weight: 700;
  text-shadow: 0 2px 4px rgba(255, 112, 67, 0.1);
  text-align: left;
  display: flex;
  align-items: center;
  gap: 10px;
}

h1 img.logo {
  width: 50px;
  height: 50px;
  object-fit: contain;
}

.subtitle {
  color: #666;
  margin: 0;
  font-size: 1rem;
  font-weight: 400;
  text-align: left;
  line-height: 1.6;
}

/* Chat Container */
.chat-container {
  flex: 1;
  background: white;
  border-radius: 0;
  box-shadow: 4px 0 20px rgba(255, 112, 67, 0.1);
  border-left: 3px solid #ff7043;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  animation: fadeInRight 0.6s ease;
  height: 100vh;
  max-width: calc(100% - 350px);
}

/* Chat Box */
#chat {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  background: linear-gradient(to bottom, #fafafa 0%, #ffffff 100%);
}

#chat::-webkit-scrollbar {
  width: 6px;
}

#chat::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

#chat::-webkit-scrollbar-thumb {
  background: #ff7043;
  border-radius: 10px;
}

#chat::-webkit-scrollbar-thumb:hover {
  background: #f4511e;
}

/* Messages */
.msg {
  padding: 12px 16px;
  border-radius: 16px;
  max-width: 75%;
  word-wrap: break-word;
  font-size: 0.9rem;
  line-height: 1.4;
  position: relative;
  animation: messageSlide 0.3s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.user {
  background: linear-gradient(135deg, #ff8a65 0%, #ff7043 100%);
  color: white;
  align-self: flex-end;
  border-bottom-right-radius: 4px;
}

.other {
  background: #f5f5f5;
  align-self: flex-start;
  border-bottom-left-radius: 4px;
}

.msg strong {
  font-weight: 600;
  font-size: 0.85rem;
  opacity: 0.9;
}

.timestamp {
  display: block;
  font-size: 0.7rem;
  opacity: 0.7;
  margin-top: 4px;
  text-align: right;
}

.msg.new {
  animation: messageNew 0.5s ease;
}

/* Message content wrapper */
.msg-content-wrapper {
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

.msg.user .msg-content-wrapper {
  justify-content: flex-end;
  text-align: right;
}

.msg-text-content {
  flex: 1;
}

/* Profile picture */
.msg-profile-pic {
  width: 35px;
  height: 35px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
  border: 2px solid #ff7043;
}

/* Reply Preview inside messages */
.reply-preview {
  background: rgba(255, 255, 255, 0.3);
  border-left: 3px solid rgba(255, 255, 255, 0.5);
  padding: 6px 8px;
  margin-bottom: 6px;
  font-size: 0.75rem;
  border-radius: 6px;
  opacity: 0.9;
}

.other .reply-preview {
  background: rgba(0, 0, 0, 0.05);
  border-left-color: #ccc;
}

/* Reply link in chat */
.msg a.reply-link {
  font-size: 0.72rem;
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  display: inline-block;
  margin-top: 4px;
  font-weight: 500;
  transition: opacity 0.2s;
}

.other a.reply-link {
  color: #ff7043;
}

.msg a.reply-link:hover {
  opacity: 1;
  text-decoration: underline;
}

/* Input Area */
.input-wrapper {
  padding: 16px 20px;
  background: white;
  border-top: 1px solid #f0f0f0;
  flex-shrink: 0;
}

/* Reply Indicator */
#reply-indicator {
  background: linear-gradient(to right, #ffe0b2, #ffcc80);
  border-left: 4px solid #ff7043;
  padding: 8px 12px;
  margin-bottom: 10px;
  border-radius: 8px;
  font-size: 0.8rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  animation: slideDown 0.3s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
}

#reply-indicator button {
  background: none;
  border: none;
  color: #ff7043;
  font-weight: bold;
  cursor: pointer;
  font-size: 1.2rem;
  padding: 0;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: background 0.2s;
}

#reply-indicator button:hover {
  background: rgba(255, 112, 67, 0.1);
}

#input-area {
  display: flex;
  gap: 10px;
  align-items: center;
}

input {
  flex: 1;
  padding: 12px 16px;
  border: 2px solid #ffe0b2;
  border-radius: 25px;
  font-size: 0.9rem;
  font-family: 'Poppins', sans-serif;
  transition: all 0.3s ease;
  background: #fafafa;
}

input:focus {
  outline: none;
  border-color: #ff7043;
  background: white;
  box-shadow: 0 0 0 3px rgba(255, 112, 67, 0.1);
}

button {
  background: linear-gradient(135deg, #ff8a65 0%, #ff7043 100%);
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 25px;
  cursor: pointer;
  font-weight: 600;
  font-family: 'Poppins', sans-serif;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(255, 112, 67, 0.3);
  display: flex;
  align-items: center;
  gap: 6px;
}

button:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(255, 112, 67, 0.4);
}

button:active {
  transform: translateY(0);
}

/* Animations */
@keyframes fadeInRight {
  from {
    opacity: 0;
    transform: translateX(20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes fadeInLeft {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes messageSlide {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes messageNew {
  0% {
    background-color: #c8e6c9;
  }
  100% {
    background-color: inherit;
  }
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #999;
}

.empty-state .material-icons {
  font-size: 64px;
  color: #ff7043;
  opacity: 0.3;
  margin-bottom: 16px;
}

.empty-state p {
  font-size: 0.9rem;
  color: #999;
}

/* Mobile Responsive */
@media (max-width: 768px) {
  body {
    flex-direction: column;
    padding: 0;
    overflow: hidden;
  }

  .chat-header {
    width: 100%;
    padding: 65px 18px 12px 18px;
    text-align: center;
    align-items: center;
    order: -1;
    flex-shrink: 0;
  }

  h1 {
    font-size: 1.4rem;
    text-align: center;
    justify-content: center;
  }

  h1 img.logo {
    width: 36px;
    height: 36px;
  }

  .subtitle {
    font-size: 0.8rem;
    text-align: center;
  }

  .chat-container {
    border-radius: 0;
    box-shadow: none;
    max-width: 100%;
    order: 1;
    border-left: none;
    border-top: none;
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
  }

  #chat {
    padding: 12px;
    gap: 10px;
  }

  .msg {
    max-width: 85%;
    padding: 9px 12px;
    font-size: 0.8rem;
  }

  .msg-profile-pic {
    width: 30px;
    height: 30px;
  }

  .input-wrapper {
    padding: 10px 12px;
  }

  input {
    padding: 9px 12px;
    font-size: 0.8rem;
  }

  button {
    padding: 9px 18px;
    font-size: 0.8rem;
  }

  a.dashboard-btn {
    top: 8px;
    left: 8px;
    padding: 7px 12px;
    font-size: 0.8rem;
  }

  #reply-indicator {
    font-size: 0.7rem;
    padding: 5px 8px;
  }
}

@media (max-width: 480px) {
  .chat-header {
    padding: 55px 12px 10px 12px;
  }

  h1 {
    font-size: 1.2rem;
  }

  h1 img.logo {
    width: 32px;
    height: 32px;
  }

  .subtitle {
    font-size: 0.75rem;
  }

  #chat {
    padding: 10px;
    gap: 8px;
  }

  .msg {
    max-width: 90%;
    padding: 8px 10px;
    font-size: 0.75rem;
  }

  .msg-profile-pic {
    width: 28px;
    height: 28px;
  }

  .msg strong {
    font-size: 0.8rem;
  }

  .timestamp {
    font-size: 0.65rem;
  }

  input {
    padding: 8px 10px;
    font-size: 0.75rem;
  }

  button {
    padding: 8px 10px;
    font-size: 0.75rem;
    min-width: 65px;
    justify-content: center;
  }

  button span:last-child {
    display: none;
  }

  .material-icons {
    font-size: 18px;
  }

  a.dashboard-btn {
    padding: 5px 10px;
    font-size: 0.75rem;
  }

  #reply-indicator {
    font-size: 0.7rem;
    padding: 5px 8px;
    margin-bottom: 6px;
  }

  .reply-preview {
    font-size: 0.7rem;
    padding: 4px 6px;
  }
}
</style>
</head>

<body>

<!-- Dashboard Button -->
<a href="dashboard.php" class="dashboard-btn">
  <span class="material-icons">home</span>
  <span>Home</span>
</a>

<!-- Chat Container (LEFT) -->
<div class="chat-container">
  <div id="chat">
    <div class="empty-state">
      <span class="material-icons">chat_bubble_outline</span>
      <p>No messages yet. Start the conversation!</p>
    </div>
  </div>

  <div class="input-wrapper">
    <div id="input-area">
      <input id="user-input" type="text" placeholder="Type a message..." autocomplete="off" />
      <button onclick="sendMessage()">
        <span class="material-icons" style="font-size: 18px;">send</span>
        <span>Send</span>
      </button>
    </div>
  </div>
</div>

<!-- Header (RIGHT) -->
<div class="chat-header">
  <h1><img src="img/logo_new.png" alt="Logo" class="logo">Foodie Chat</h1>
  <p class="subtitle">Chat with other food lovers and share tips!</p>
</div>

<script>
const chat = document.getElementById('chat');
const input = document.getElementById('user-input');
const currentUser = '<?= $currentUser ?>';
let lastMessageId = 0;
let replyTo = null;

// Show reply indicator
function showReplyIndicator(msg) {
  const existing = document.getElementById('reply-indicator');
  if (existing) existing.remove();

  const div = document.createElement('div');
  div.id = 'reply-indicator';
  div.innerHTML = `
    <span>↪ Replying to <strong>${escapeHtml(msg.username)}</strong>: ${escapeHtml(msg.message.slice(0, 30))}${msg.message.length > 30 ? '...' : ''}</span>
    <button>×</button>
  `;
  div.querySelector('button').onclick = () => {
    replyTo = null;
    div.remove();
  };
  document.querySelector('.input-wrapper').insertBefore(div, document.getElementById('input-area'));
  replyTo = msg;
  input.focus();
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// Render messages (optimized to prevent flickering)
function renderMessages(messages) {
  const isNearBottom = chat.scrollHeight - chat.scrollTop - chat.clientHeight < 50;
  
  if (messages.length === 0) {
    if (!chat.querySelector('.empty-state')) {
      chat.innerHTML = `
        <div class="empty-state">
          <span class="material-icons">chat_bubble_outline</span>
          <p>No messages yet. Start the conversation!</p>
        </div>
      `;
    }
    return;
  }

  // Remove empty state if it exists
  const emptyState = chat.querySelector('.empty-state');
  if (emptyState) {
    emptyState.remove();
  }

  // Only add new messages instead of re-rendering everything
  messages.forEach(msg => {
    // Check if message already exists
    const existingMsg = chat.querySelector(`[data-msg-id="${msg.id}"]`);
    if (existingMsg) return;

    const div = document.createElement('div');
    div.className = 'msg ' + (msg.username === currentUser ? 'user' : 'other');
    div.setAttribute('data-msg-id', msg.id);
    
    if (msg.id > lastMessageId) {
      div.classList.add('new');
      lastMessageId = msg.id;
    }

    let replyHtml = '';
    if (msg.reply_to_message && msg.reply_to_user) {
      replyHtml = `
        <div class="reply-preview">
          ↪ <strong>${escapeHtml(msg.reply_to_user)}:</strong> ${escapeHtml(msg.reply_to_message.slice(0, 50))}${msg.reply_to_message.length > 50 ? '...' : ''}
        </div>
      `;
    }

    const timestamp = new Date(msg.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    if (msg.username === currentUser) {
      // Current user message (no profile picture, no username)
      div.innerHTML = `
        <div class="msg-content-wrapper">
          <div class="msg-text-content">
            ${replyHtml}
            <div>${escapeHtml(msg.message)}</div>
            <span class="timestamp">${timestamp}</span>
          </div>
        </div>
      `;
    } else {
      // Other users message (with profile picture)
      const profilePic = msg.profile_picture || 'img/no_profile.png';
      div.innerHTML = `
        <div class="msg-content-wrapper">
          <img src="${escapeHtml(profilePic)}" 
               alt="${escapeHtml(msg.username)}" 
               class="msg-profile-pic"
               onerror="this.src='img/no_profile.png'">
          <div class="msg-text-content">
            ${replyHtml}
            <div><strong>${escapeHtml(msg.username)}:</strong> ${escapeHtml(msg.message)}</div>
            <span class="timestamp">${timestamp}</span>
          </div>
        </div>
      `;
    }

    const replyBtn = document.createElement('a');
    replyBtn.href = "#";
    replyBtn.textContent = "Reply";
    replyBtn.className = "reply-link";
    replyBtn.onclick = (e) => {
      e.preventDefault();
      showReplyIndicator(msg);
    };
    div.querySelector('.msg-text-content').appendChild(replyBtn);
    
    chat.appendChild(div);
  });

  if (isNearBottom) {
    chat.scrollTop = chat.scrollHeight;
  }
}

// Send message
async function sendMessage() {
  const text = input.value.trim();
  if (!text) return;

  const payload = { message: text };
  if (replyTo) payload.reply_to_id = replyTo.id;

  input.value = '';
  const tempReplyTo = replyTo;
  replyTo = null;

  const indicator = document.getElementById('reply-indicator');
  if (indicator) indicator.remove();

  try {
    await fetch('send_message.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(payload)
    });
    fetchMessages();
  } catch (error) {
    console.error('Error sending message:', error);
    // Restore reply state on error
    if (tempReplyTo) {
      replyTo = tempReplyTo;
      showReplyIndicator(tempReplyTo);
    }
  }
}

// Fetch and update
async function fetchMessages() {
  try {
    const res = await fetch('get_messages.php');
    if (!res.ok) throw new Error('Failed to fetch messages');
    const messages = await res.json();
    renderMessages(messages);
  } catch (error) {
    console.error('Error fetching messages:', error);
  }
}

// Initial fetch and polling
fetchMessages();
setInterval(fetchMessages, 2000);

// Send on Enter
input.addEventListener('keypress', e => {
  if (e.key === 'Enter') {
    e.preventDefault();
    sendMessage();
  }
});
</script>
</body>
</html>