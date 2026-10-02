// ⚡ ATUALIZAÇÃO DE CACHE DA ACADEMIA AURÉLIUS
const CACHE_NAME = 'aurelius-cache-v6'; // 🌟 Incrementado para v6 para forçar a limpeza em todos os telemóveis e navegadores

// 🌟 Lista de ficheiros estáticos para funcionamento Offline
const ASSETS = [
  './',
  './estudante.html',
  './Principal.html',
  './professor.html',
  './lista.html',
  './manifest.json',
  './icone-192.png',
  './icone-512.png'
];

// 🔧 1. INSTALAÇÃO - Guarda os ficheiros estáticos em cache
self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(ASSETS))
  );
  self.skipWaiting(); // Força o Service Worker novo a assumir o controlo imediatamente
});

// 🔄 2. ATIVAÇÃO - Limpa de forma permanente todos os caches antigos do ecossistema
self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            console.log("🧹 A deitar fora cache antigo:", key);
            return caches.delete(key);
          }
        })
      );
    }).then(() => self.clients.claim()) // Assume o controlo das abas ativas na hora
  );
});

// 📡 3. INTERCEÇÃO DE REDE (FETCH) - INTELIGENTE E BLINDADA
self.addEventListener('fetch', (event) => {
  const url = event.request.url;

  // 🌟 REGRA IMPERIAL: Se for chamada de base de dados ou chat PHP, busca SEMPRE na rede viva e NUNCA salva no cache!
  if (
    url.includes('chat_academico.php') || 
    url.includes('buscar_notas.php') || 
    url.includes('lancar_nota.php') || 
    url.includes('buscar_livros.php') || 
    url.includes('guardar_livro.php') || 
    url.includes('obter_pauta_prof.php')
  ) {
    event.respondWith(fetch(event.request));
    return; // Encerra a interceção para este ficheiro dinâmico
  }

  // 📦 REGRA PARA FICHEIROS ESTÁTICOS: Tenta ler o Cache primeiro, se não achar, busca na rede
  event.respondWith(
    caches.match(event.request).then((cachedResponse) => {
      if (cachedResponse) {
        return cachedResponse; // Retorna a cópia do cache estável
      }
      
      return fetch(event.request).then((networkResponse) => {
        // Valida se a resposta da rede é legítima antes de guardar
        if (!networkResponse || networkResponse.status !== 200 || networkResponse.type !== 'basic') {
          return networkResponse;
        }
        
        // Clona a resposta para guardar uma cópia no cache de navegação rápida
        const responseToCache = networkResponse.clone();
        caches.open(CACHE_NAME).then((cache) => {
          cache.put(event.request, responseToCache);
        });
        
        return networkResponse;
      });
    }).catch(() => {
      // Se a rede falhar por completo e o ficheiro não estiver em cache, redireciona para a Home
      return caches.match("Principal.html");
    })
  );
});