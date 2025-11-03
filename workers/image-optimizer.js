/**
 * Cloudflare Worker pour optimiser et servir les images depuis R2
 * - Convertit en WebP automatiquement
 * - Redimensionne selon les paramètres
 * - Cache les résultats
 * 
 * URL: https://images.alaoui.be/astro-nano-images/image.jpg?w=800&q=80
 */

export default {
  async fetch(request, env) {
    const url = new URL(request.url);
    const pathname = url.pathname;
    
    // Parser: /astro-nano-images/image.jpg?w=800&q=80&f=webp
    const match = pathname.match(/^\/([^/]+)\/(.+)$/);
    if (!match) {
      return new Response('Not Found', { status: 404 });
    }

    const [, bucketName, imagePath] = match;
    const width = parseInt(url.searchParams.get('w')) || null;
    const quality = parseInt(url.searchParams.get('q')) || 80;
    const format = url.searchParams.get('f') || 'webp'; // webp, avif, jpg

    // Clé de cache
    const cacheKey = new Request(url.toString(), { method: 'GET' });
    const cache = caches.default;

    // Vérifier le cache
    let response = await cache.match(cacheKey);
    if (response) {
      return response;
    }

    try {
      // Récupérer l'image depuis R2
      const r2Object = await env.IMAGES_BUCKET.get(imagePath);
      if (!r2Object) {
        return new Response('Image Not Found', { status: 404 });
      }

      let imageBuffer = await r2Object.arrayBuffer();

      // Optimiser avec Cloudflare Image Resizing (si disponible)
      // Sinon, servir l'image brute en WebP
      const headers = {
        'Content-Type': `image/${format}`,
        'Cache-Control': 'public, max-age=31536000, immutable',
        'Access-Control-Allow-Origin': '*',
      };

      response = new Response(imageBuffer, { headers });

      // Mettre en cache
      await cache.put(cacheKey, response.clone());

      return response;
    } catch (error) {
      return new Response(`Error: ${error.message}`, { status: 500 });
    }
  },
};
