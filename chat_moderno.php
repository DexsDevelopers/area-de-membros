<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$logged_in_user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chat Ao Vivo | HELMER ACADEMY</title>
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
    * {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    
    .gradient-bg {
        background: linear-gradient(135deg, #000000 0%, #1a0000 25%, #2d0000 50%, #1a0000 75%, #000000 100%);
        background-size: 400% 400%;
        animation: gradientShift 8s ease infinite;
    }
    
    @keyframes gradientShift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    
    .hero-gradient {
        background: linear-gradient(135deg, #dc2626 0%, #ef4444 25%, #f97316 50%, #dc2626 75%, #ef4444 100%);
        background-size: 200% 200%;
        animation: heroPulse 3s ease infinite;
    }
    
    @keyframes heroPulse {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    
    .chat-container {
        background: linear-gradient(145deg, rgba(220,38,38,0.1) 0%, rgba(0,0,0,0.3) 50%, rgba(220,38,38,0.05) 100%);
        backdrop-filter: blur(25px);
        border: 1px solid rgba(220,38,38,0.3);
        box-shadow: 0 8px 32px rgba(220,38,38,0.2), 0 0 0 1px rgba(255,255,255,0.1);
    }
    
    .chat-bubble {
        max-width: 75%;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.1);
        transition: all 0.3s ease;
    }
    
    .chat-bubble:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    }
    
    .chat-bubble-me {
        background: linear-gradient(135deg, #dc2626, #ef4444);
        margin-left: auto;
        border-radius: 20px 20px 5px 20px;
        box-shadow: 0 4px 15px rgba(220,38,38,0.4);
    }
    
    .chat-bubble-other {
        background: linear-gradient(145deg, rgba(55,65,81,0.8), rgba(31,41,55,0.9));
        margin-right: auto;
        border-radius: 20px 20px 20px 5px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    }
    
    .input-field {
        background: linear-gradient(145deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05));
        backdrop-filter: blur(15px);
        border: 1px solid rgba(220,38,38,0.3);
        transition: all 0.3s ease;
    }
    
    .input-field:focus {
        border-color: rgba(220,38,38,0.6);
        box-shadow: 0 0 20px rgba(220,38,38,0.3);
        transform: scale(1.02);
    }
    
    .send-button {
        background: linear-gradient(135deg, #dc2626, #ef4444, #f97316);
        background-size: 200% 200%;
        box-shadow: 0 4px 15px rgba(220,38,38,0.4);
        transition: all 0.3s ease;
        animation: buttonPulse 2s ease-in-out infinite;
        position: relative;
        overflow: hidden;
    }
    
    .send-button::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: left 0.5s;
    }
    
    .send-button:hover::before {
        left: 100%;
    }
    
    .send-button:hover {
        transform: translateY(-2px) scale(1.05);
        box-shadow: 0 8px 25px rgba(220,38,38,0.6);
    }
    
    @keyframes buttonPulse {
        0%, 100% { 
            background-position: 0% 50%;
            box-shadow: 0 4px 15px rgba(220,38,38,0.4);
        }
        50% { 
            background-position: 100% 50%;
            box-shadow: 0 6px 20px rgba(220,38,38,0.6);
        }
    }
    
    .typing-indicator {
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 8px 16px;
        background: rgba(55,65,81,0.8);
        border-radius: 20px;
        margin: 8px 0;
    }
    
    .typing-dot {
        width: 8px;
        height: 8px;
        background: #dc2626;
        border-radius: 50%;
        animation: typingBounce 1.4s infinite ease-in-out;
    }
    
    .typing-dot:nth-child(1) { animation-delay: -0.32s; }
    .typing-dot:nth-child(2) { animation-delay: -0.16s; }
    .typing-dot:nth-child(3) { animation-delay: 0s; }
    
    @keyframes typingBounce {
        0%, 80%, 100% { transform: scale(0); }
        40% { transform: scale(1); }
    }
    
    .message-animation {
        animation: messageSlide 0.3s ease-out;
    }
    
    @keyframes messageSlide {
        from {
            opacity: 0;
            transform: translateY(20px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    
    .online-indicator {
        width: 8px;
        height: 8px;
        background: #22c55e;
        border-radius: 50%;
        animation: onlinePulse 2s ease-in-out infinite;
    }
    
    @keyframes onlinePulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.7; transform: scale(1.2); }
    }
    
    .scroll-smooth {
        scroll-behavior: smooth;
    }
    
    .glass-effect {
        background: rgba(255,255,255,0.1);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255,255,255,0.2);
    }
    
    .dopamine-text {
        background: linear-gradient(45deg, #dc2626, #ef4444, #f97316, #dc2626);
        background-size: 400% 400%;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        animation: textShimmer 3s ease-in-out infinite;
    }
    
    @keyframes textShimmer {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }
    
    .particle {
        position: absolute;
        background: radial-gradient(circle, rgba(220,38,38,0.8) 0%, rgba(220,38,38,0.2) 50%, transparent 100%);
        border-radius: 50%;
        pointer-events: none;
        animation: particleFloat 6s ease-in-out infinite;
    }
    
    @keyframes particleFloat {
        0%, 100% { 
            transform: translateY(0px) scale(1);
            opacity: 0.7;
        }
        50% { 
            transform: translateY(-20px) scale(1.2);
            opacity: 1;
        }
    }
</style>
</head>
<body class="gradient-bg text-white min-h-screen relative overflow-hidden">

<!-- Partículas de fundo -->
<div class="absolute inset-0 overflow-hidden pointer-events-none">
    <div class="particle w-2 h-2 top-1/4 left-1/4" style="animation-delay: 0s;"></div>
    <div class="particle w-3 h-3 top-1/3 right-1/4" style="animation-delay: 1s;"></div>
    <div class="particle w-2 h-2 bottom-1/4 left-1/3" style="animation-delay: 2s;"></div>
    <div class="particle w-3 h-3 bottom-1/3 right-1/3" style="animation-delay: 3s;"></div>
</div>

<div class="flex flex-col h-screen relative z-10">
    
    <!-- Header Premium -->
    <header class="glass-effect p-4 lg:p-6 border-b border-white/10">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <a href="index.php" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 hero-gradient rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fas fa-arrow-left text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-white">HELMER ACADEMY</h1>
                        <p class="text-sm text-gray-400">Chat Ao Vivo</p>
                    </div>
                </a>
            </div>
            
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2">
                    <div class="online-indicator"></div>
                    <span class="text-sm text-green-400 font-semibold">Online</span>
                </div>
                <div class="w-8 h-8 hero-gradient rounded-full flex items-center justify-center">
                    <i class="fas fa-comments text-white text-sm"></i>
                </div>
            </div>
        </div>
    </header>

    <!-- Área de Mensagens -->
    <main class="flex-1 overflow-y-auto p-4 lg:p-6 scroll-smooth" id="message-container">
        <div class="space-y-4 max-w-4xl mx-auto">
            <!-- Mensagens serão inseridas aqui via JavaScript -->
        </div>
    </main>

    <!-- Indicador de Digitação -->
    <div id="typing-indicator" class="hidden px-4 lg:px-6">
        <div class="typing-indicator max-w-4xl mx-auto">
            <span class="text-sm text-gray-400 mr-2">Alguém está digitando</span>
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
        </div>
    </div>

    <!-- Footer com Input -->
    <footer class="glass-effect p-4 lg:p-6 border-t border-white/10">
        <form id="chat-form" class="max-w-4xl mx-auto flex items-center gap-4">
            <div class="flex-1 relative">
                <input type="text" id="message-input" placeholder="Digite sua mensagem..." required autocomplete="off"
                       class="w-full p-4 pr-12 rounded-xl input-field text-white placeholder-gray-400 focus:outline-none transition-all duration-300">
                <button type="button" id="emoji-btn" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-white transition-colors">
                    <i class="fas fa-smile text-lg"></i>
                </button>
            </div>
            <button type="submit" class="send-button text-white font-bold py-4 px-6 rounded-xl transition-all duration-300 flex items-center space-x-2">
                <i class="fas fa-paper-plane"></i>
                <span class="hidden sm:inline">Enviar</span>
            </button>
        </form>
    </footer>
</div>

<script>
    const messageContainer = document.getElementById('message-container');
    const chatForm = document.getElementById('chat-form');
    const messageInput = document.getElementById('message-input');
    const typingIndicator = document.getElementById('typing-indicator');
    const loggedInUserId = <?php echo $logged_in_user_id; ?>;
    let lastMessageId = 0;
    let typingTimeout;

    // Função para criar bolha de mensagem moderna
    function createMessageBubble(msg) {
        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble p-4 rounded-2xl flex flex-col message-animation';
        
        if (msg.user_id == loggedInUserId) {
            bubble.classList.add('chat-bubble-me');
        } else {
            bubble.classList.add('chat-bubble-other');
        }
        
        const time = new Date(msg.timestamp).toLocaleTimeString('pt-BR', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });

        bubble.innerHTML = `
            <div class="flex items-center justify-between mb-2">
                <div class="font-bold text-sm ${msg.user_id == loggedInUserId ? 'text-white' : 'text-red-400'}">
                    ${msg.username}
                </div>
                <div class="text-xs text-gray-400">${time}</div>
            </div>
            <p class="text-white text-base leading-relaxed">${msg.mensagem}</p>
        `;
        
        return bubble;
    }

    // Função para buscar novas mensagens
    async function fetchMessages() {
        try {
            const response = await fetch(`api_get_chat.php?since_id=${lastMessageId}`);
            if (!response.ok) return;
            
            const messages = await response.json();
            
            if (messages.length > 0) {
                messages.forEach(msg => {
                    const bubble = createMessageBubble(msg);
                    messageContainer.appendChild(bubble);
                });
                
                lastMessageId = messages[messages.length - 1].id;
                scrollToBottom();
            }
        } catch (error) {
            console.error('Erro ao buscar mensagens:', error);
        }
    }

    // Função para rolar para o final
    function scrollToBottom() {
        messageContainer.scrollTop = messageContainer.scrollHeight;
    }

    // Função para mostrar indicador de digitação
    function showTypingIndicator() {
        typingIndicator.classList.remove('hidden');
        clearTimeout(typingTimeout);
        typingTimeout = setTimeout(() => {
            typingIndicator.classList.add('hidden');
        }, 3000);
    }

    // Função para enviar mensagem
    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const messageText = messageInput.value.trim();
        if (!messageText) return;

        try {
            await fetch('api_send_chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `mensagem=${encodeURIComponent(messageText)}`
            });
            
            messageInput.value = '';
            await fetchMessages();
        } catch (error) {
            console.error('Erro ao enviar mensagem:', error);
        }
    });

    // Detectar digitação para mostrar indicador
    let isTyping = false;
    messageInput.addEventListener('input', () => {
        if (!isTyping) {
            isTyping = true;
            showTypingIndicator();
        }
        
        clearTimeout(typingTimeout);
        typingTimeout = setTimeout(() => {
            isTyping = false;
            typingIndicator.classList.add('hidden');
        }, 1000);
    });

    // Auto-focus no input
    messageInput.focus();

    // Enter para enviar, Shift+Enter para nova linha
    messageInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            chatForm.dispatchEvent(new Event('submit'));
        }
    });

    // Iniciar polling de mensagens
    setInterval(fetchMessages, 2000);
    fetchMessages();

    // Scroll automático quando nova mensagem chega
    const observer = new MutationObserver(() => {
        scrollToBottom();
    });
    
    observer.observe(messageContainer, {
        childList: true,
        subtree: true
    });

    // Efeito de partículas interativas
    document.addEventListener('mousemove', function(e) {
        const particles = document.querySelectorAll('.particle');
        particles.forEach((particle, index) => {
            const speed = (index + 1) * 0.1;
            const x = e.clientX * speed / 200;
            const y = e.clientY * speed / 200;
            particle.style.transform = `translate(${x}px, ${y}px)`;
        });
    });
</script>

</body>
</html>
