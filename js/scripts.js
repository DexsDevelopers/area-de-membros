/**
 * HELMER ACADEMY - JavaScript Avançado
 * Versão 2.0 - Com Loading States, Notificações Push e Mobile Optimization
 */

// ===== GLOBAL STATE =====
const AppState = {
    loading: false,
    notifications: [],
    user: null,
    isOnline: navigator.onLine
};

// ===== LOADING STATES =====
class LoadingManager {
    static show(message = 'Carregando...', type = 'overlay') {
        const loadingHTML = `
            <div class="loading-overlay" id="loading-overlay">
                <div class="loading-content">
                    <div class="loading-spinner"></div>
                    <p class="mt-4 text-white">${message}</p>
                </div>
            </div>
        `;
        
        if (type === 'overlay') {
            document.body.insertAdjacentHTML('beforeend', loadingHTML);
        } else if (type === 'button') {
            const button = event.target;
            button.classList.add('btn-loading');
            button.disabled = true;
            button.dataset.originalText = button.textContent;
            button.textContent = message;
        }
    }
    
    static hide(type = 'overlay') {
        if (type === 'overlay') {
            const overlay = document.getElementById('loading-overlay');
            if (overlay) overlay.remove();
        } else if (type === 'button') {
            const button = event.target;
            button.classList.remove('btn-loading');
            button.disabled = false;
            if (button.dataset.originalText) {
                button.textContent = button.dataset.originalText;
            }
        }
    }
    
    static showSkeleton(container, count = 3) {
        const skeletonHTML = `
            <div class="mobile-skeleton-card">
                <div class="mobile-skeleton mobile-skeleton-image"></div>
                <div class="mobile-skeleton mobile-skeleton-text"></div>
                <div class="mobile-skeleton mobile-skeleton-text"></div>
                <div class="mobile-skeleton mobile-skeleton-text" style="width: 60%;"></div>
            </div>
        `.repeat(count);
        
        container.innerHTML = skeletonHTML;
    }
}

// ===== NOTIFICATION SYSTEM =====
class NotificationManager {
    constructor() {
        this.container = this.createContainer();
        this.setupServiceWorker();
    }
    
    createContainer() {
        let container = document.getElementById('notification-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notification-container';
            container.className = 'notification-container';
            document.body.appendChild(container);
        }
        return container;
    }
    
    show(message, type = 'info', duration = 5000, options = {}) {
        const notification = this.createNotification(message, type, options);
        this.container.appendChild(notification);
        
        // Animar entrada
        setTimeout(() => notification.classList.add('show'), 100);
        
        // Auto remover
        if (duration > 0) {
            setTimeout(() => this.remove(notification), duration);
        }
        
        // Notificação push se suportado
        if (type === 'push' && 'Notification' in window) {
            this.showPushNotification(message, options);
        }
        
        return notification;
    }
    
    createNotification(message, type, options) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        
        const icon = this.getIcon(type);
        const title = options.title || this.getTitle(type);
        
        notification.innerHTML = `
            <div class="notification-header">
                <div class="flex items-center">
                    <i class="${icon} mr-2"></i>
                    <span class="notification-title">${title}</span>
                </div>
                <button class="notification-close" onclick="NotificationManager.remove(this.parentElement.parentElement)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="notification-message">${message}</div>
            <div class="notification-progress"></div>
        `;
        
        return notification;
    }
    
    getIcon(type) {
        const icons = {
            success: 'fas fa-check-circle text-green-400',
            error: 'fas fa-exclamation-circle text-red-400',
            warning: 'fas fa-exclamation-triangle text-yellow-400',
            info: 'fas fa-info-circle text-blue-400',
            push: 'fas fa-bell text-purple-400'
        };
        return icons[type] || icons.info;
    }
    
    getTitle(type) {
        const titles = {
            success: 'Sucesso',
            error: 'Erro',
            warning: 'Atenção',
            info: 'Informação',
            push: 'Nova Notificação'
        };
        return titles[type] || 'Notificação';
    }
    
    remove(notification) {
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }
    
    setupServiceWorker() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => {
                    console.log('Service Worker registrado:', registration);
                    this.requestNotificationPermission();
                })
                .catch(error => console.log('Erro ao registrar Service Worker:', error));
        }
    }
    
    requestNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission().then(permission => {
                console.log('Permissão de notificação:', permission);
            });
        }
    }
    
    showPushNotification(message, options = {}) {
        if (Notification.permission === 'granted') {
            const notification = new Notification(options.title || 'Helmer Academy', {
                body: message,
                icon: '/icons/icon-192x192.png',
                badge: '/icons/icon-72x72.png',
                tag: options.tag || 'helmer-notification',
                requireInteraction: options.persistent || false
            });
            
            notification.onclick = () => {
                window.focus();
                notification.close();
            };
        }
    }
}

// ===== MOBILE OPTIMIZATIONS =====
class MobileOptimizer {
    constructor() {
        this.setupTouchGestures();
        this.setupPullToRefresh();
        this.setupMobileNavigation();
        this.setupSafeAreas();
    }
    
    setupTouchGestures() {
        // Swipe para navegação
        let startX, startY, endX, endY;
        
        document.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
        });
        
        document.addEventListener('touchend', (e) => {
            endX = e.changedTouches[0].clientX;
            endY = e.changedTouches[0].clientY;
            
            const diffX = startX - endX;
            const diffY = startY - endY;
            
            // Swipe horizontal
            if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
                if (diffX > 0) {
                    this.handleSwipeLeft();
                } else {
                    this.handleSwipeRight();
                }
            }
        });
    }
    
    handleSwipeLeft() {
        // Implementar navegação para direita
        console.log('Swipe left detected');
    }
    
    handleSwipeRight() {
        // Implementar navegação para esquerda
        console.log('Swipe right detected');
    }
    
    setupPullToRefresh() {
        let startY = 0;
        let currentY = 0;
        let isPulling = false;
        
        document.addEventListener('touchstart', (e) => {
            if (window.scrollY === 0) {
                startY = e.touches[0].clientY;
            }
        });
        
        document.addEventListener('touchmove', (e) => {
            if (window.scrollY === 0) {
                currentY = e.touches[0].clientY;
                const pullDistance = currentY - startY;
                
                if (pullDistance > 0 && pullDistance < 100) {
                    isPulling = true;
                    this.showPullIndicator(pullDistance);
                }
            }
        });
        
        document.addEventListener('touchend', () => {
            if (isPulling && currentY - startY > 80) {
                this.handlePullToRefresh();
            }
            this.hidePullIndicator();
            isPulling = false;
        });
    }
    
    showPullIndicator(distance) {
        let indicator = document.getElementById('pull-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'pull-indicator';
            indicator.className = 'pull-indicator';
            indicator.innerHTML = '<i class="fas fa-arrow-down"></i>';
            document.body.appendChild(indicator);
        }
        
        if (distance > 60) {
            indicator.classList.add('active');
            indicator.innerHTML = '<i class="fas fa-refresh"></i>';
        }
    }
    
    hidePullIndicator() {
        const indicator = document.getElementById('pull-indicator');
        if (indicator) {
            indicator.classList.remove('active');
            setTimeout(() => indicator.remove(), 300);
        }
    }
    
    handlePullToRefresh() {
        LoadingManager.show('Atualizando...');
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }
    
    setupMobileNavigation() {
        // Navegação bottom para mobile
        if (window.innerWidth <= 768) {
            this.createMobileNavigation();
        }
    }
    
    createMobileNavigation() {
        const navHTML = `
            <div class="mobile-nav">
                <a href="index.php" class="mobile-nav-item">
                    <i class="fas fa-home"></i>
                    <span>Início</span>
                </a>
                <a href="cursos.php" class="mobile-nav-item">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Cursos</span>
                </a>
                <a href="produtos.php" class="mobile-nav-item">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Produtos</span>
                </a>
                <a href="chat.php" class="mobile-nav-item">
                    <i class="fas fa-comments"></i>
                    <span>Chat</span>
                </a>
                <a href="favoritos.php" class="mobile-nav-item">
                    <i class="fas fa-heart"></i>
                    <span>Favoritos</span>
                </a>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', navHTML);
    }
    
    setupSafeAreas() {
        // Aplicar safe areas para dispositivos com notch
        const meta = document.createElement('meta');
        meta.name = 'viewport';
        meta.content = 'width=device-width, initial-scale=1.0, viewport-fit=cover';
        document.head.appendChild(meta);
    }
}

// ===== ENHANCED FUNCTIONS =====

// Menu mobile melhorado
function abrirMenu() {
    const menu = document.getElementById('menu');
    const overlay = document.getElementById('menu-overlay');
    
    menu.classList.remove('-translate-x-full');
    if (overlay) overlay.classList.add('active');
    
    // Adicionar classe para prevenir scroll
    document.body.classList.add('overflow-hidden');
}

function fecharMenu() {
    const menu = document.getElementById('menu');
    const overlay = document.getElementById('menu-overlay');
    
    menu.classList.add('-translate-x-full');
    if (overlay) overlay.classList.remove('active');
    
    // Remover classe para permitir scroll
    document.body.classList.remove('overflow-hidden');
}

// Scroll reveal melhorado
const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('show');
            observer.unobserve(entry.target);
        }
    });
}, { 
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
});

document.querySelectorAll('.fade-in, .scroll-reveal').forEach(el => {
    observer.observe(el);
});

// Busca e filtro melhorados
const search = document.getElementById('search');
const filter = document.getElementById('filter');
const cursos = document.querySelectorAll('.curso, .swiper-slide');

if (search && filter && cursos.length) {
    search.addEventListener('input', debounce(aplicarFiltros, 300));
    filter.addEventListener('change', aplicarFiltros);
}

function aplicarFiltros() {
    const texto = search.value.toLowerCase();
    const tipo = filter.value;
    
    cursos.forEach(card => {
        const correspondeTexto = card.textContent.toLowerCase().includes(texto);
        const correspondeTipo = tipo === 'all' || card.dataset.tipo === tipo;
        
        if (correspondeTexto && correspondeTipo) {
            card.classList.remove('swiper-slide-hidden');
        } else {
            card.classList.add('swiper-slide-hidden');
        }
    });
}

// ===== UTILITY FUNCTIONS =====

// Debounce para otimizar performance
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Throttle para scroll events
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar managers
    window.NotificationManager = new NotificationManager();
    window.MobileOptimizer = new MobileOptimizer();
    
    // Configurar event listeners
    setupEventListeners();
    
    // Verificar conectividade
    setupConnectivityCheck();
    
    // Configurar PWA
    setupPWA();
});

function setupEventListeners() {
    // Fechar menu ao clicar no overlay
    document.addEventListener('click', (e) => {
        if (e.target.id === 'menu-overlay') {
            fecharMenu();
        }
    });
    
    // Fechar menu ao pressionar ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            fecharMenu();
        }
    });
}

function setupConnectivityCheck() {
    window.addEventListener('online', () => {
        AppState.isOnline = true;
        NotificationManager.show('Conexão restaurada!', 'success');
    });
    
    window.addEventListener('offline', () => {
        AppState.isOnline = false;
        NotificationManager.show('Conexão perdida. Algumas funcionalidades podem não funcionar.', 'warning');
    });
}

function setupPWA() {
    // Registrar Service Worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => {
                    console.log('Service Worker registrado:', registration.scope);
                })
                .catch(error => {
                    console.log('Erro ao registrar Service Worker:', error);
                });
        });
    }
    
    // Instalar PWA
    let deferredPrompt;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        showInstallButton();
    });
}

function showInstallButton() {
    const installButton = document.createElement('button');
    installButton.innerHTML = '<i class="fas fa-download mr-2"></i>Instalar App';
    installButton.className = 'fixed bottom-20 right-5 bg-blue-600 hover:bg-blue-700 text-white p-3 rounded-full shadow-lg transition transform hover:scale-110 z-50';
    
    installButton.addEventListener('click', async () => {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            console.log(`Instalação ${outcome}`);
            deferredPrompt = null;
            installButton.remove();
        }
    });
    
    document.body.appendChild(installButton);
    
    // Remove o botão após 10 segundos
    setTimeout(() => {
        if (installButton.parentNode) {
            installButton.remove();
        }
    }, 10000);
}
  