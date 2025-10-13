<?php
session_start();
if (!isset($_SESSION['user_id'])) { // Usar user_id é mais seguro
    header("Location: login.php");
    exit();
}
// Guarda o ID do usuário logado para o JavaScript usar
$logged_in_user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Chat Ao Vivo | HELMER ACADEMY</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    /* Estilo para as "bolhas" de mensagem */
    .chat-bubble { max-width: 75%; }
    .chat-bubble-me { background-color: #dc2626; /* Red-600 */ margin-left: auto; }
    .chat-bubble-other { background-color: #374151; /* Gray-700 */ margin-right: auto; }
</style>
</head>
<body class="bg-black text-gray-300 font-sans flex flex-col h-screen">

<header class="bg-gray-800/80 backdrop-blur-lg p-4 text-center border-b border-gray-700">
    <a href="index.php" class="text-xl font-bold text-white tracking-widest">HELMER ACADEMY | CHAT AO VIVO</a>
</header>

<main class="flex-1 overflow-y-auto p-4 md:p-6">
    <div id="message-container" class="space-y-4">
        </div>
</main>

<footer class="p-4 bg-gray-900 border-t border-gray-700">
    <form id="chat-form" class="flex items-center gap-4">
        <input type="text" id="message-input" placeholder="Digite sua mensagem..." required autocomplete="off"
               class="flex-1 p-3 rounded-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-red-500 transition">
        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg transition">
            Enviar
        </button>
    </form>
</footer>

<script>
    const messageContainer = document.getElementById('message-container');
    const chatForm = document.getElementById('chat-form');
    const messageInput = document.getElementById('message-input');
    const loggedInUserId = <?php echo $logged_in_user_id; ?>;
    let lastMessageId = 0; // Para buscar apenas mensagens novas

    // Função para buscar novas mensagens
    async function fetchMessages() {
        try {
            const response = await fetch(`api_get_chat.php?since_id=${lastMessageId}`);
            if (!response.ok) return; // Se não houver resposta, para.
            
            const messages = await response.json();
            
            if (messages.length > 0) {
                messages.forEach(msg => {
                    const bubble = document.createElement('div');
                    bubble.className = 'chat-bubble p-3 rounded-lg flex flex-col';
                    
                    // Adiciona classes diferentes para "eu" e "outros"
                    if (msg.user_id == loggedInUserId) {
                        bubble.classList.add('chat-bubble-me');
                    } else {
                        bubble.classList.add('chat-bubble-other');
                    }
                    
                    // Formata a hora
                    const time = new Date(msg.timestamp).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

                    bubble.innerHTML = `
                        <div class="font-bold text-sm ${msg.user_id == loggedInUserId ? 'text-white' : 'text-red-400'}">${msg.username}</div>
                        <p class="text-white text-base">${msg.mensagem}</p>
                        <div class="text-xs text-gray-400 text-right mt-1">${time}</div>
                    `;
                    messageContainer.appendChild(bubble);
                });
                
                // Atualiza o ID da última mensagem e rola para o final
                lastMessageId = messages[messages.length - 1].id;
                messageContainer.scrollTop = messageContainer.scrollHeight;
            }
        } catch (error) {
            console.error('Erro ao buscar mensagens:', error);
        }
    }

    // Função para enviar uma nova mensagem
    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const messageText = messageInput.value;
        if (!messageText) return;

        try {
            await fetch('api_send_chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `mensagem=${encodeURIComponent(messageText)}`
            });
            
            messageInput.value = ''; // Limpa o campo
            await fetchMessages(); // Busca as mensagens imediatamente após enviar
        } catch (error) {
            console.error('Erro ao enviar mensagem:', error);
        }
    });

    // Inicia o "Polling": busca novas mensagens a cada 3 segundos
    setInterval(fetchMessages, 3000);
    
    // Busca as mensagens iniciais ao carregar a página
    fetchMessages();
</script>

</body>
</html>