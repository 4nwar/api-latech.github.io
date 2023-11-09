<?php

require_once '../vendor/autoload.php';

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;

$client = new HttpBrowser();
$crawler = new Crawler();