// ⚡ ATUALIZAÇÃO DE CACHE DA ACADEMIA AURÉLIUS
const CACHE_NAME = 'aurelius-cache-v5'; // 🌟 Incrementado para v5 para forçar a limpeza em todos os telemóveis

// 🌟 CORREÇÃO: Nome unificado em MAIÚSCULAS para bater certo com a função install
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

// Instalação - Guarda os ficheiros estáticos em cache
self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(ASSETS))
  );
});

// Ativação - Limpa caches antigos ao atualizar o PWA
self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) return caches.delete(key);
        })
      );
    })
  );
});

// Interceção de Rede
self.addEventListener('fetch', event => {
  if (event.request.url.includes('chat_academico.php') || event.request.url.includes('buscar_notas.php')) {
      // Força buscar sempre na rede viva e nunca salvar no cache do Service Worker
      return event.respondWith(fetch(event.request));
  }
  // Restante do seu código do sw.js...
});

  event.respondWith(
    caches.match(event.request).then((cachedResponse) => {
      if (cachedResponse) return cachedResponse;
      
      return fetch(event.request).then((networkResponse) => {
        if (!networkResponse || networkResponse.status !== 200 || networkResponse.type !== 'basic') {
          return networkResponse;
        }
        
        const responseToCache = networkResponse.clone();
        caches.open(CACHE_NAME).then((cache) => {
          cache.put(event.request, responseToCache);
        });
        
        return networkResponse;
      });
    }).catch(() => {
      return caches.match("Principal.html");
    })
  );
});