<?php
require dirname(__DIR__) . '/includes/init.php';
require dirname(__DIR__) . '/includes/sitio.php';

header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach (web_sitemap() as $row) {
    echo '  <url><loc>' . e(absolute_url(web_public($row[0]))) . '</loc><lastmod>' . e($row[1]) . '</lastmod></url>' . "\n";
}
echo '</urlset>';
