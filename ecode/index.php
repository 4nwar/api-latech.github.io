<?php

require_once '../vendor/autoload.php';

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;

$client = new HttpBrowser();
$crawler = new Crawler();

// Mendapatkan nomor ecode dari parameter "ecode" (jika ada)
$ecodeToFind = isset($_GET['ecode']) ? $_GET['ecode'] : '';

// Jika parameter "ecode" tidak kosong, cari ecode dengan nomor yang cocok
if (!empty($ecodeToFind)) {
    $ecodeData = scrapeEcodeData(); // Panggil fungsi untuk mendapatkan semua data ecode

    $foundEcodes = [];

    // Loop melalui semua data ecode untuk mencari yang cocok
    foreach ($ecodeData as $ecode) {
        if (preg_match("/$ecodeToFind/i", $ecode['id'])) {
            $foundEcodes[] = $ecode;
        }
    }

    if (!empty($foundEcodes)) {
        $jsonData = json_encode($foundEcodes);
    } else {
        $jsonData = json_encode(['error' => 'Ecode not found']);
    }
} else {
    // Jika parameter "ecode" kosong, kembalikan semua data ecode
    $ecodeData = scrapeEcodeData(); // Panggil fungsi untuk mendapatkan semua data ecode
    $jsonData = json_encode($ecodeData);
}

// Output JSON data
header('Content-Type: application/json');
echo $jsonData;

// Fungsi untuk melakukan scraping semua data ecode
function scrapeEcodeData()
{
    $client = new HttpBrowser();
    $crawler = new Crawler();

    // Get all ecodes
    $client->request('GET', 'https://ecodehalalcheck.com/ ');
    $html = $client->getResponse()->getContent();
    $crawler->addHtmlContent($html);

    $ecodeContainers = $crawler->filter('.p-8.rounded-lg.shadow-lg.text-white');

    $ecodeData = [];

    // Iterate over ecodes containers and extract data
    foreach ($ecodeContainers as $ecodeContainer) {
        $ecode = [];
        $ecodeCrawler = new Crawler($ecodeContainer);

        // Get ecode & status
        $ecodeNode = $ecodeCrawler->filter('h2.text-xl.font-bold')->first();
        if ($ecodeNode->count() > 0) {
            $data = $ecodeNode->text();
            list($id, $status) = explode(" - ", $data);

            $ecode['id'] = $id;
            $ecode['status'] = $status;
        }

        // Get ecode name
        $nameNode = $ecodeCrawler->filter('p.mt-2.font-light')->first();
        if ($nameNode->count() > 0) {
            $ecode['name'] = $nameNode->text();
        }

        // Get ecode categori & detail
        $detailName = $ecodeCrawler->filter('p.mt-4.font-light')->first();
        if ($detailName->count() > 0) {
            $split = $detailName->text();
            list($category, $detail) = explode(':', $split);
            // Ambil dari 'data-lazy-src', jika tidak ada, ambil dari 'src'
            $ecode['category'] = $category;
            $ecode['detail'] = $detail;
        }

        $ecodeData[] = $ecode;
    }

    return $ecodeData;
}