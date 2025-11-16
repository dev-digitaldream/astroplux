<?php
// Script de synchronisation Grav vers Astro
header('Content-Type: application/json');

// Configuration
$gravPath = __DIR__;
$astroContentPath = __DIR__ . '/content';

try {
    // Charger Grav
    require __DIR__ . '/vendor/autoload.php';
    use Grav\Common\Grav;
    
    $grav = Grav::instance();
    $pages = $grav['pages'];
    
    // Créer les dossiers Astro s'ils n'existent pas
    if (!is_dir($astroContentPath . '/blog')) {
        mkdir($astroContentPath . '/blog', 0755, true);
    }
    if (!is_dir($astroContentPath . '/pages')) {
        mkdir($astroContentPath . '/pages', 0755, true);
    }
    
    // Synchroniser les articles du blog
    $blog = $pages->find('/blog');
    $syncedPosts = 0;
    
    if ($blog) {
        foreach ($blog->children() as $page) {
            $header = $page->header();
            $slug = $page->slug();
            
            // Créer le frontmatter Astro
            $frontmatter = "---\n";
            $frontmatter .= "title: " . json_encode($page->title()) . "\n";
            $frontmatter .= "description: " . json_encode($header->summary ?? $header->description ?? '') . "\n";
            $frontmatter .= "date: " . ($page->date() ? date('Y-m-d', $page->date()) : date('Y-m-d')) . "\n";
            $frontmatter .= "category: blog\n";
            
            if (isset($header->taxonomy['tag']) && is_array($header->taxonomy['tag'])) {
                $frontmatter .= "tags: " . json_encode($header->taxonomy['tag']) . "\n";
            }
            
            $frontmatter .= "---\n\n";
            $frontmatter .= $page->content();
            
            // Écrire le fichier Astro
            file_put_contents($astroContentPath . '/blog/' . $slug . '.md', $frontmatter);
            $syncedPosts++;
        }
    }
    
    // Synchroniser les pages statiques
    $syncedPages = 0;
    foreach ($pages->root()->children() as $page) {
        if ($page->route() === '/blog') continue;
        
        $slug = $page->slug();
        $header = $page->header();
        
        $frontmatter = "---\n";
        $frontmatter .= "title: " . json_encode($page->title()) . "\n";
        $frontmatter .= "description: " . json_encode($header->summary ?? $header->description ?? '') . "\n";
        $frontmatter .= "---\n\n";
        $frontmatter .= $page->content();
        
        file_put_contents($astroContentPath . '/pages/' . $slug . '.md', $frontmatter);
        $syncedPages++;
    }
    
    echo json_encode([
        'success' => true,
        'synced_posts' => $syncedPosts,
        'synced_pages' => $syncedPages,
        'message' => "Synchronisation réussie : $syncedPosts articles et $syncedPages pages"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
