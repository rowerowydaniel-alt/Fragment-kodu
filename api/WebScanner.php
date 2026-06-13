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

    public function testPerformance() {
        $start = microtime(true);
        list(, $code) = $this->fetchHtml();
        $end = microtime(true);
        $duration = round($end - $start, 3);
        return "Response time: {$duration} seconds";
    }

    public function testAccessibility() {
        list($html) = $this->fetchHtml();
        $results = [];
        
        $images = preg_match_all('/<img[^>]+>/i', $html, $imgMatches);
        $missingAlt = 0;
        if ($images > 0) {
            foreach ($imgMatches[0] as $img) {
                if (stripos($img, 'alt=') === false) {
                    $missingAlt++;
                }
            }
        }
        $results[] = "Images: $images, Missing alt: $missingAlt";

        if (preg_match('/<meta name="viewport" content="[^"]*width=[^"]*scale=[^"]*"/i', $html)) {
            $results[] = "Viewport meta: Present";
        } else {
            $results[] = "Viewport meta: Missing";
        }

        return "Accessibility: " . implode(' | ', $results);
    }

    public function testMobileView() {
        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $html = curl_exec($ch);
        curl_close($ch);

        if (preg_match('/<meta name="viewport" content="[^"]*width=[^"]*"/i', $html)) {
            return "Mobile View: Viewport meta present";
        }
        return "Mobile View: Viewport meta missing";
    }

    public function testCookies() {
        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);
        curl_close($ch);

        preg_match_all('/Set-Cookie:\s*([^;]*)/i', $headers, $matches);
        $cookies = $matches[1] ?? [];

        if (empty($cookies)) {
            return "No cookies set by the server.";
        }

        return "Cookies found: " . count($cookies) . " (" . implode(', ', array_slice($cookies, 0, 3)) . (count($cookies) > 3 ? '...' : '') . ")";
    }

    public function testHttpsRedirect() {
        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_exec($ch);
        $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        if (strpos($effectiveUrl, 'https://') === 0) {
            return "HTTPS Redirect: Active ($effectiveUrl)";
        }
        return "HTTPS Redirect: Inactive (Currently using $effectiveUrl)";
    }

    public function testSitemap() {
        $urlParts = parse_url($this->url);
        $base = $urlParts['scheme'] . '://' . $urlParts['host'] . '/';
        $sitemaps = ['sitemap.xml', 'sitemap_index.xml'];
        $found = [];

        $ch = curl_init($base . 'robots.txt');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $robots = curl_exec($ch);
        curl_close($ch);

        if (preg_match('/Sitemap:\s*(https?:\/\/[^\s]+)/i', $robots, $matches)) {
            $found[] = "Found in robots.txt: " . trim($matches[1]);
        }

        foreach ($sitemaps as $sitemap) {
            $ch = curl_init($base . $sitemap);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code === 200) {
                $found[] = "Directly accessible: $sitemap";
            }
        }

        return empty($found) ? "No sitemap found." : "Sitemap: " . implode(' | ', array_unique($found));
    }

    public function testReadability() {
        list($html) = $this->fetchHtml();
        $hasH1 = preg_match('/<h1/i', $html);
        $pCount = preg_match_all('/<p/i', $html);
        
        $result = "H1 present: " . ($hasH1 ? 'Yes' : 'No');
        if ($pCount > 0) {
            $result .= " | Paragraphs found: $pCount";
        } else {
            $result .= " | No paragraphs found";
        }
        return $result;
    }

    public function testImageOptimization() {
        list($html) = $this->fetchHtml();
        $imgCount = preg_match_all('/<img/i', $html, $imgMatches);
        $missingAltCount = 0;

        if ($imgCount > 0) {
            foreach ($imgMatches[0] as $img) {
                if (stripos($img, 'alt=') === false) {
                    $missingAltCount++;
                }
            }
            $result = "Images: $imgCount, Missing alt: $missingAltCount";
            if ($missingAltCount > 0) {
                $result .= " (Needs improvement)";
            }
        } else {
            $result = "No images found.";
        }
        return $result;
    }

    public function testBrokenForms() {
        list($html) = $this->fetchHtml();
        $formCount = preg_match_all('/<form/i', $html);
        
        if ($formCount === 0) {
            return "No forms found.";
        }

        preg_match_all('/<form[^>]*>(.*?)<\/form>/is', $html, $formMatches);
        $brokenForms = 0;
        $totalForms = count($formMatches[1]);

        foreach ($formMatches[1] as $formContent) {
            if (preg_match('/<input|<textarea|<select/i', $formContent) === 0) {
                $brokenForms++;
            }
        }

        $result = "Forms found: $totalForms, Empty forms: $brokenForms";
        if ($brokenForms > 0) {
            $result .= " (Check empty forms)";
        }
        return $result;
    }

    public function testExternalScripts() {
        list($html) = $this->fetchHtml();
        $domain = parse_url($this->url, PHP_URL_HOST);
        
        preg_match_all('/<script[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $html, $matches);
        $externalScripts = 0;
        $scriptUrls = $matches[1] ?? [];

        foreach ($scriptUrls as $scriptUrl) {
            $scriptHost = parse_url($scriptUrl, PHP_URL_HOST);
            if ($scriptHost && $scriptHost !== $domain) {
                $externalScripts++;
            }
        }

        return "External scripts: $externalScripts out of " . count($scriptUrls) . " total scripts.";
    }
}