// ⚡ ATUALIZAÇÃO DE CACHE DA ACADEMIA AURÉLIUS
const CACHE_NAME = 'aurelius-cache-v7'; // Incrementado para v7 para limpar instâncias antigas

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
  self.skipWaiting();
});

// 🔄 2. ATIVAÇÃO - Limpa caches antigos do ecossistema
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
    }).then(() => self.clients.claim())
  );
});

// 📡 3. INTERCEÇÃO DE REDE (FETCH) - INTELIGENTE E BLINDADA
self.addEventListener('fetch', (event) => {
  const url = event.request.url;

  // 🌟 REGRA IMPERIAL EXPANDIDA: Protege os scripts dinâmicos de faturamento e consulta para nunca congelarem dados
  if (
    url.includes('chat_academico.php') || 
    url.includes('buscar_notas.php') || 
    url.includes('lancar_nota.php') || 
    url.includes('buscar_livros.php') || 
    url.includes('guardar_livro.php') || 
    url.includes('obter_pauta_prof.php') ||
    url.includes('obter_turmas_publicas.php') ||
    url.includes('unitel.php') ||
    url.includes('faturamento.php')
  ) {
    event.respondWith(fetch(event.request));
    return;
  }

  // 📦 REGRA PARA FICHEIROS ESTÁTICOS
  event.respondWith(
    caches.match(event.request).then((cachedResponse) => {
      if (cachedResponse) {
        return cachedResponse;
      }
      
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
      // 🎯 CORREÇÃO CRÍTICA: Corresponde exatamente à string registada na lista ASSETS
      return caches.match("./Principal.html");
    })
  );
});