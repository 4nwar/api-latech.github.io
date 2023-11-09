<?php

require_once '../../vendor/autoload.php';

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;

function scrapeAndFormatDetail($url)
{
    $client = new HttpBrowser();
    $crawler = new Crawler();

    // Construct full URL using the provided URL parameter
    $fullURL = $url;

    // Request the detail page
    $client->request('GET', $fullURL);
    $html = $client->getResponse()->getContent();
    $crawler->addHtmlContent($html);

    // Extract data from the detail page
    $detailData = [];

    // Extract title
    $titleNode = $crawler->filter('strong.pp-breadcrumbs-crumb')->first();
    if ($titleNode->count() > 0) {
        $detailData['title'] = $titleNode->text();
    }

    // Extract URL
    // $urlNode = $crawler->filter('h1.elementor-heading-title.elementor-size-default a')->first();
    // if ($urlNode->count() > 0) {
    //     $detailData['url'] = $urlNode->attr('href');
    // }

    // Extract image
    $imgNode = $crawler->filter('img.attachment-large')->first();
    if ($imgNode->count() > 0) {
        $detailData['img'] = $imgNode->attr('data-lazy-src') ?: $imgNode->attr('src');
    }

    // Extract writer
    $writerNode = $crawler->filter('span.elementor-icon-list-text')->first();
    if ($writerNode->count() > 0) {
        $detailData['writer'] = $writerNode->text();
    }

    // Extract date
    $dateNode = $crawler->filter('span.elementor-icon-list-text.elementor-post-info__item.elementor-post-info__item--type-date')->first();
    if ($dateNode->count() > 0) {
        $detailData['date'] = $dateNode->text();
    }

    // Extract content
    $contentNodes = $crawler->filter('[data-widget_type="theme-post-content.default"] div.elementor-widget-container > p');
    $content = [];
    foreach ($contentNodes as $contentNode) {
        // $content[] = $contentNode->textContent;
        $domContent = new DOMDocument();
        $domContent->appendChild($domContent->importNode($contentNode, true));
        $content[] = $domContent->saveHTML(); // Simpan sebagai teks HTML
    }
    $detailData['content'] = implode("\n", $content);

    // Menghapus karakter khusus seperti "\n" dan menggantinya dengan spasi
    // $detailData['content'] = str_replace("\n", '\n', $detailData['content']);

    // Menghapus spasi berlebihan dan trim teks
    $detailData['content'] = trim(preg_replace('/\s+/', ' ', $detailData['content']));

    // Mengganti karakter "&nbsp;" dengan spasi
    $detailData['content'] = str_replace('&nbsp;', ' ', $detailData['content']);

    return $detailData;
}

// Get the URL parameter
if (isset($_GET['url'])) {
    $url = $_GET['url'];

    // Scrape and format the detail based on the URL parameter
    $formattedDetailData = scrapeAndFormatDetail($url);

    // Wrap the JSON data in a "data" key
    $data = ["data" => $formattedDetailData];

    // Convert the data to JSON
    $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    // Output JSON data
    header('Content-Type: application/json');
    echo $jsonData;
} else {
    // URL parameter 'url' is missing
    echo 'URL parameter "url" is missing.';
}