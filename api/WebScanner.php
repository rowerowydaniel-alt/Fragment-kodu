<?php

class WebScanner {
    private $url;

    public function __construct($url) {
        $this->url = $url;
    }

    private function fetchHtml() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HEADER, true); // Include headers in the output
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("Curl error: $error");
        }

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);
        $html = substr($response, $headerSize);
        
        curl_close($ch);

        if ($httpCode >= 400) {
            throw new Exception("Failed to fetch URL (HTTP Code: $httpCode)");
        }

        return [$html, $httpCode, $headers];
    }

    public function testHtmlElements() {
        list($html, $code) = $this->fetchHtml();
        $results = [];
        
        $tags = ['h1', 'h2', 'p', 'button', 'a', 'img', 'input', 'textarea', 'select'];
        foreach ($tags as $tag) {
            $count = preg_match_all("/<$tag/i", $html);
            $results[] = "$tag count: $count";
        }

        return "HTML Elements found: " . implode(', ', $results);
    }

    public function testBrokenLinks() {
        list($html) = $this->fetchHtml();
        preg_match_all('/href=["\'](https?:\/\/[^"\']+)["\']/i', $html, $matches);
        $links = array_unique($matches[1]);
        
        $brokenLinks = [];
        $totalCount = count($links);
        
        $linksToTest = array_slice($links, 0, 5);
        $checked = 0;

        foreach ($linksToTest as $link) {
            $ch = curl_init($link);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code >= 400) {
                $brokenLinks[] = $link;
            }
            $checked++;
        }

        $result = "Checked $checked links. Broken: " . count($brokenLinks);
        if (!empty($brokenLinks)) {
            $result .= " URLs: " . implode(', ', $brokenLinks);
        }
        return $result;
    }

    public function testSeoTags() {
        list($html) = $this->fetchHtml();
        $results = [];

        if (preg_match('/<title>(.*?)<\/title>/i', $html, $matches)) {
            $results[] = "Title: " . trim($matches[1]);
        } else {
            $results[] = "Title: Missing";
        }

        if (preg_match('/<meta name="description" content="(.*?)"/i', $html, $matches)) {
            $results[] = "Description: " . trim($matches[1]);
        } else {
            $results[] = "Description: Missing";
        }

        return "SEO: " . implode(' | ', $results);
    }

    public function testSecurityHeaders() {
        list(, , $headers) = $this->fetchHtml();

        $importantHeaders = [
            'X-Frame-Options',
            'X-Content-Type-Options',
            'Content-Security-Policy',
            'Strict-Transport-Security'
        ];

        $found = [];
        foreach ($importantHeaders as $header) {
            if (stripos($headers, $header) !== false) {
                $found[] = $header;
            }
        }

        if (empty($found)) {
            return "No standard security headers found.";
        }

        return "Security Headers present: " . implode(', ', $found);
    }
}
