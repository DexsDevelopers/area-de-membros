/**
 * Service Worker para Helmer Academy
 * Funcionalidades offline e cache
 */

const CACHE_NAME = 'helmer-academy-v1.0.0';
const STATIC_CACHE = 'helmer-static-v1.0.0';
const DYNAMIC_CACHE = 'helmer-dynamic-v1.0.0';

// Arquivos estáticos para cache
const STATIC_FILES = [
    '/',
    '/index.php',
    '/login.php',
    '/busca_avancada.php',
    '/favoritos.php',
    '/chat.php',
    '/manifest.json',
    '/css/style.css',
    '/js/scripts.js',
    'https://cdn.tailwindcss.com',
    'https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js',
    'https://unpkg.com/swiper/swiper-bundle.min.css',
    'https://unpkg.com/swiper/swiper-bundle.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
];

// Instalação do Service Worker
self.addEventListener('install', event => {
    console.log('Service Worker: Instalando...');
    
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then(cache => {
                console.log('Service Worker: Cacheando arquivos estáticos');
                return cache.addAll(STATIC_FILES);
            })
            .then(() => {
                console.log('Service Worker: Instalação concluída');
                return self.skipWaiting();
            })
            .catch(error => {
                console.error('Service Worker: Erro na instalação', error);
            })
    );
});

// Ativação do Service Worker
self.addEventListener('activate', event => {
    console.log('Service Worker: Ativando...');
    
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        if (cacheName !== STATIC_CACHE && cacheName !== DYNAMIC_CACHE) {
                            console.log('Service Worker: Removendo cache antigo:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log('Service Worker: Ativação concluída');
                return self.clients.claim();
            })
    );
});

// Interceptação de requisições
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Estratégia de cache para diferentes tipos de recursos
    if (request.method === 'GET') {
        // Páginas HTML - Network First
        if (request.headers.get('accept').includes('text/html')) {
            event.respondWith(networkFirstStrategy(request));
        }
        // CSS, JS, Imagens - Cache First
        else if (isStaticAsset(request)) {
            event.respondWith(cacheFirstStrategy(request));
        }
        // APIs - Network First com fallback
        else if (url.pathname.includes('api_')) {
            event.respondWith(networkFirstStrategy(request));
        }
        // Outros recursos
        else {
            event.respondWith(staleWhileRevalidateStrategy(request));
        }
    }
});

// Estratégia: Network First (para páginas HTML e APIs)
async function networkFirstStrategy(request) {
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            const cache = await caches.open(DYNAMIC_CACHE);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.log('Service Worker: Rede indisponível, buscando no cache');
        const cachedResponse = await caches.match(request);
        
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Fallback para páginas offline
        if (request.headers.get('accept').includes('text/html')) {
            return caches.match('/offline.html') || new Response(
                getOfflinePage(),
                { headers: { 'Content-Type': 'text/html' } }
            );
        }
        
        throw error;
    }
}

// Estratégia: Cache First (para recursos estáticos)
async function cacheFirstStrategy(request) {
    const cachedResponse = await caches.match(request);
    
    if (cachedResponse) {
        return cachedResponse;
    }
    
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            const cache = await caches.open(STATIC_CACHE);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.log('Service Worker: Erro ao buscar recurso:', request.url);
        throw error;
    }
}

// Estratégia: Stale While Revalidate (para outros recursos)
async function staleWhileRevalidateStrategy(request) {
    const cache = await caches.open(DYNAMIC_CACHE);
    const cachedResponse = await cache.match(request);
    
    const fetchPromise = fetch(request).then(networkResponse => {
        if (networkResponse.ok) {
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    }).catch(() => cachedResponse);
    
    return cachedResponse || fetchPromise;
}

// Verifica se é um recurso estático
function isStaticAsset(request) {
    const url = new URL(request.url);
    const pathname = url.pathname;
    
    return pathname.endsWith('.css') ||
           pathname.endsWith('.js') ||
           pathname.endsWith('.png') ||
           pathname.endsWith('.jpg') ||
           pathname.endsWith('.jpeg') ||
           pathname.endsWith('.gif') ||
           pathname.endsWith('.webp') ||
           pathname.endsWith('.svg') ||
           pathname.endsWith('.woff') ||
           pathname.endsWith('.woff2') ||
           pathname.endsWith('.ttf');
}

// Página offline personalizada
function getOfflinePage() {
    return `
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline - Helmer Academy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .pulse-animation { animation: pulse 2s infinite; }
    </style>
</head>
<body class="bg-gradient-to-b from-black via-gray-900 to-black text-white font-sans flex items-center justify-center min-h-screen p-4">
    <div class="text-center max-w-md mx-auto">
        <div class="mb-8">
            <i class="fas fa-wifi text-6xl text-gray-600 mb-4 pulse-animation"></i>
            <h1 class="text-3xl font-bold text-red-500 mb-2">Sem Conexão</h1>
            <p class="text-gray-400 mb-6">Você está offline. Verifique sua conexão com a internet.</p>
        </div>
        
        <div class="space-y-4">
            <button onclick="window.location.reload()" 
                    class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg transition">
                <i class="fas fa-redo mr-2"></i>Tentar Novamente
            </button>
            
            <button onclick="window.history.back()" 
                    class="w-full bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded-lg transition">
                <i class="fas fa-arrow-left mr-2"></i>Voltar
            </button>
        </div>
        
        <div class="mt-8 text-sm text-gray-500">
            <p>Algumas funcionalidades podem estar limitadas offline.</p>
        </div>
    </div>
    
    <script>
        // Verifica conexão periodicamente
        setInterval(() => {
            if (navigator.onLine) {
                window.location.reload();
            }
        }, 5000);
        
        // Escuta mudanças de conectividade
        window.addEventListener('online', () => {
            window.location.reload();
        });
    </script>
</body>
</html>`;
}

// Notificações push melhoradas
self.addEventListener('push', event => {
    console.log('Service Worker: Push recebido');
    
    let notificationData = {
        title: 'Helmer Academy',
        body: 'Nova notificação da Helmer Academy',
        icon: '/icons/icon-192x192.png',
        badge: '/icons/badge-72x72.png',
        url: '/'
    };
    
    if (event.data) {
        try {
            const data = event.data.json();
            notificationData = { ...notificationData, ...data };
        } catch (e) {
            notificationData.body = event.data.text();
        }
    }
    
    const options = {
        body: notificationData.body,
        icon: notificationData.icon,
        badge: notificationData.badge,
        vibrate: [200, 100, 200],
        data: {
            url: notificationData.url,
            timestamp: Date.now()
        },
        requireInteraction: notificationData.persistent || false,
        tag: notificationData.tag || 'helmer-notification',
        actions: [
            {
                action: 'open',
                title: 'Abrir',
                icon: '/icons/open-96x96.png'
            },
            {
                action: 'dismiss',
                title: 'Dispensar',
                icon: '/icons/close-96x96.png'
            }
        ]
    };
    
    event.waitUntil(
        self.registration.showNotification(notificationData.title, options)
    );
});

// Clique em notificação melhorado
self.addEventListener('notificationclick', event => {
    console.log('Service Worker: Clique na notificação');
    
    event.notification.close();
    
    if (event.action === 'open' || !event.action) {
        event.waitUntil(
            clients.matchAll({ type: 'window' }).then(clientList => {
                // Verificar se já existe uma janela aberta
                for (const client of clientList) {
                    if (client.url === event.notification.data.url && 'focus' in client) {
                        return client.focus();
                    }
                }
                
                // Abrir nova janela se não existir
                if (clients.openWindow) {
                    return clients.openWindow(event.notification.data.url || '/');
                }
            })
        );
    } else if (event.action === 'dismiss') {
        // Apenas fechar a notificação
        console.log('Service Worker: Notificação dispensada');
    }
});

// Sincronização em background
self.addEventListener('sync', event => {
    console.log('Service Worker: Sincronização em background');
    
    if (event.tag === 'background-sync') {
        event.waitUntil(doBackgroundSync());
    }
});

async function doBackgroundSync() {
    // Implementar sincronização de dados offline
    console.log('Service Worker: Executando sincronização...');
}
