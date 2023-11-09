<?php

require_once '../../vendor/autoload.php';

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;

function scrapeCategory($categoryURL, $page)
{
    $client = new HttpBrowser();
    $categoryData = [];

    // Construct full URL by appending $categoryURL and page number
    $fullURL = 'https://halalmui.org/category/' . $categoryURL;

    if ($page > 1) {
        $fullURL .= '/page/' . $page . '/';
    }

    // Request the category page
    $client->request('GET', $fullURL);
    $html = $client->getResponse()->getContent();
    $crawler = new Crawler($html);

    // Get all containers for the category
    $categoryContainers = $crawler->filter('article.ast-full-width.ast-grid-common-col');

    // Iterate over category containers and extract data
    foreach ($categoryContainers as $categoryContainer) {
        $categoryItem = [];
        $categoryCrawler = new Crawler($categoryContainer);

        // Get article URL
        $urlNode = $categoryCrawler->filter('a.elementor-post__read-more')->first();
        if ($urlNode->count() > 0) {
            $categoryItem['url'] = $urlNode->attr('href');
        }

        // Get article Category
        // $categoryNode = $categoryURL;
        $categoryNode = trim($categoryURL, '/');
        $categoryNode = ucfirst($categoryNode);
        $categoryItem['category'] = $categoryNode;

        // Get article image
        $imgNode = $categoryCrawler->filter('a.elementor-post__thumbnail__link img')->first();
        if ($imgNode->count() > 0) {
            // Ambil dari 'data-lazy-src', jika tidak ada, ambil dari 'src'
            $categoryItem['img'] = $imgNode->attr('data-lazy-src') ?: $imgNode->attr('src');
        }else {
            $categoryItem['img'] = "https://halalmui.org/wp-content/uploads/2023/08/logo-lppommui-low-300x225.jpg";
        }

        // Get article title
        $titleNode = $categoryCrawler->filter('h3.elementor-post__title a')->first();
        if ($titleNode->count() > 0) {
            $categoryItem['title'] = $titleNode->text();
        }

        // $MaxpageNode = $crawler->filter('.elementor-pagination a.page-numbers')->eq(2);

        // if ($MaxpageNode->count() > 0) {
        //     // Ambil teks dan gunakan fungsi preg_match atau explode untuk mendapatkan angka dari teks tersebut
        //     $maxPageText = $MaxpageNode->text();
        //     preg_match('/(\d+)/', $maxPageText, $matches);
        //     $totalPages = (int) $matches[0];
        //     $categoryItem['page'] = $totalPages;
        // } else {
        //     // Set to 1 if there is no maxpage element
        //     $totalPages = 1;
        // }

        $categoryData[] = $categoryItem;
    }
    // Get total pages
    $MaxpageNode = $crawler->filter('.elementor-pagination a.page-numbers')->slice(-2);
    // $MaxpageNode = $crawler->filter('.page-numbers span.elementor-screen-only')->last();

    if ($MaxpageNode->count() > 0) {
        // Ambil teks dan gunakan fungsi preg_match atau explode untuk mendapatkan angka dari teks tersebut
        $maxPageText = $MaxpageNode->text();
        preg_match('/(\d+)/', $maxPageText, $matches);
        $totalPages = (int) $matches[0];
        // $categoryItem['page'] = $totalPages;
    } else {
        // Set to 1 if there is no maxpage element
        $totalPages = 1;
    }

    return ['total_page' => $totalPages, 'data' => $categoryData];
}

// Mendapatkan kategori yang ingin Anda scrape dari parameter URL
$categoryParam = isset($_GET['category']) ? $_GET['category'] : 'all';
$pageParam = isset($_GET['page']) ? (int) $_GET['page'] : 1;

// Inisialisasi totalPages
$totalPages = 5;

// Scrape data sesuai dengan kategori yang dipilih
if ($categoryParam == 'news') {
    $result = scrapeCategory('berita/', $pageParam);
    $categoryData = $result['data'];
    $totalPages = (int) $result['total_page'];
} elseif ($categoryParam == 'article') {
    $result = scrapeCategory('artikel-halal/', $pageParam);
    $categoryData = $result['data'];
    $totalPages = (int) $result['total_page'];
} elseif ($categoryParam == 'ukm') {
    $result = scrapeCategory('ruang-umk/', $pageParam);
    $categoryData = $result['data'];
    $totalPages = (int) $result['total_page'];
} else {
    // Jika 'all' atau kategori tidak valid, maka scrape semua kategori
    $newsData = scrapeCategory('berita/', $pageParam);
    $articleData = scrapeCategory('artikel-halal/', $pageParam);
    $ukmData = scrapeCategory('ruang-umk/', $pageParam);

    // Menghitung total halaman dari salah satu kategori (semua harus memiliki jumlah halaman yang sama)
    if (!empty($newsData)) {
        $totalPages = $newsData[0]['total_pages'];
    }

    // Combine data from all categories
    $categoryData = array_merge($newsData, $articleData, $ukmData);
}

// Get current page
$currentPage = $pageParam;

// Create response object
$response = [
    "data" => [
        "total_pages" => $totalPages,
        "current_page" => $currentPage,
        "datas" => $categoryData
    ]
];

// Convert response object to JSON
$jsonData = json_encode($response);

// Output JSON data
header('Content-Type: application/json');
echo $jsonData;