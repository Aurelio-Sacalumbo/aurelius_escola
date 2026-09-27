const CACHE_NAME = "aurelius-cache-v2"; // <-- Mude aqui para forçar a limpeza
const ASSETS = [
  "Principal.html",
  "estudante.html",
  "professor.html",
  "Lista.html",
  "recuperar.html"
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
self.addEventListener("fetch", (event) => {
  // ATENÇÃO: Nunca guarda em cache ficheiros PHP para não congelar os dados do banco
  if (event.request.url.includes('.php')) {
    return; 
  }

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