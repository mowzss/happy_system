<?php
declare (strict_types=1);

namespace app\common\util;

class BaiduTongjiUtil
{
    private string $VERSION = "wap-0-0.2";

    private int $VISIT_DURATION = 1800;

    private int $VISITOR_MAX_AGE = 31536000;

    private array $SEARCH_ENGINE_LIST = [
        ["1", "baidu.com", "word|wd"],
        ["2", "google.com", "q"],
        ["4", "sogou.com", "query"],
        ["6", "search.yahoo.com", "p"],
        ["7", "yahoo.cn", "q"],
        ["8", "soso.com", "w"],
        ["11", "youdao.com", "q"],
        ["12", "gougou.com", "search"],
        ["13", "bing.com", "q"],
        ["14", "so.com", "q"],
        ["14", "so.360.cn", "q"],
        ["15", "jike.com", "q"],
        ["16", "qihoo.com", "kw"],
        ["17", "etao.com", "q"],
        ["18", "soku.com", "keyword"],
    ];

    private string $siteId = "";
    private string $searchEngine = "";
    private string $searchWord = "";

    private string $visitUrl = "";
    private int $eventType = 0;
    private string $eventProperty = "";

    /**
     * @param string $url
     * @param string $key
     * @return mixed|null
     */
    private function getQueryValue(string $url, string $key): mixed
    {
        preg_match("/(^|&|\\?|#)(" . $key . ")=([^&#]*)(&|$|#)/", $url, $matches);

        return count($matches) > 0 ? $matches[3] : null;
    }

    /**
     * @param string $path
     * @param string $referer
     * @param int $currentPageVisitTime
     * @param int $lastPageVisitTime
     * @return int
     */
    private function getSourceType(string $path, string $referer, int $currentPageVisitTime, int $lastPageVisitTime): int
    {
        $parsedPath = parse_url($path);
        $parsedReferer = parse_url($referer);
        if (is_null($referer) || (!is_null($parsedPath) && !is_null($parsedReferer) && $parsedPath["host"] === $parsedReferer["host"])) {
            return ($currentPageVisitTime - $lastPageVisitTime > $this->VISIT_DURATION) ? 1 : 4;
        }
        $sel = $this->SEARCH_ENGINE_LIST;
        foreach ($sel as $i => $iValue) {
            if (preg_match("/" . $iValue[1] . "/", $parsedReferer["host"])) {
                $this->searchWord = $this->getQueryValue($referer, $iValue[2]);
                if (!is_null($this->searchWord) || $iValue[0] === "2" || $iValue[0] === "14" || $iValue[0] === "17") {
                    $this->searchEngine = $sel[$i][0];

                    return 2;
                }
            }
        }

        return 3;
    }

    /**
     * @param string $text
     * @return string
     */
    private function replaceSpecialChars(string $text): string
    {
        $text = str_replace(["'", "*", "!"], ["'0", "'1", "'2"], $text);

        return str_replace("%27", "'", urlencode($text));
    }

    /**
     * @return string
     */
    private function getPixelUrl(): string
    {
        $path = (isset($_SERVER["HTTPS"]) && ($_SERVER["HTTPS"] === "on") ? 'https://' : 'http://') .
            $_SERVER['SERVER_NAME'] .
            (($_SERVER["SERVER_PORT"] === '80') ? '' : ':' . $_SERVER["SERVER_PORT"]) .
            $_SERVER['REQUEST_URI'];

        $referer = $_SERVER['HTTP_REFERER'];

        $currentPageVisitTime = time();

        $lastPageVisitTime = (int)$_COOKIE["Hm_lpvt_" . $this->siteId];

        $lastVisitTime = $_COOKIE["Hm_lvt_" . $this->siteId];

        $sourceType = $this->getSourceType($path, $referer, $currentPageVisitTime, $lastPageVisitTime);
        $isNewVisit = ($sourceType == 4) ? 0 : 1;

        setCookie("Hm_lpvt_" . $this->siteId, $currentPageVisitTime, 0, "/");
        setCookie("Hm_lvt_" . $this->siteId, $currentPageVisitTime, time() + $this->VISITOR_MAX_AGE, "/");

        $pixelUrl = "http://hm.baidu.com/hm.gif" .
            "?si=" . $this->siteId .
            "&et=" . $this->eventType .
            ($this->eventProperty !== "" ? "&ep=" . $this->eventProperty : "") .
            "&nv=" . $isNewVisit .
            "&st=" . $sourceType .
            ($this->searchEngine !== "" ? "&se=" . $this->searchEngine : "") .
            ($this->searchWord !== "" ? "&sw=" . urlencode($this->searchWord) : "") .
            (!is_null($lastVisitTime) ? "&lt=" . $lastVisitTime : "") .
            (!is_null($referer) ? "&su=" . urlencode($referer) : "") .
            ($this->visitUrl !== "" ? "&u=" . urlencode($this->visitUrl) : "") .
            "&v=" . $this->VERSION .
            "&rnd=" . rand(10e8, 10e9);

        return htmlspecialchars($pixelUrl);
    }

    /**
     * _HMT constructor.
     * @param string $siteId
     */
    public function __construct(string $siteId)
    {
        $this->siteId = $siteId;
    }

    /**
     *
     * @param string $siteId
     */
    public function setAccount(string $siteId): void
    {
        $this->siteId = $siteId;
    }

    /**
     * @param string|null $url
     * @return string
     */
    public function trackPageView(string $url = null): string
    {
        $this->eventType = 0;
        $this->eventProperty = "";
        if (!is_null($url) && strpos($url, "/") === 0) {
            $this->visitUrl = (isset($_SERVER["HTTPS"]) && ($_SERVER["HTTPS"] === "on") ? 'https://' : 'http://') .
                $_SERVER['SERVER_NAME'] .
                (($_SERVER["SERVER_PORT"] === '80') ? '' : ':' . $_SERVER["SERVER_PORT"]) .
                $url;
        } else {
            $this->visitUrl = "";
        }

        return $this->getPixelUrl();
    }

    /**
     * @param string $category
     * @param string $action
     * @param string|null $opt_label
     * @param string|null $opt_value
     * @return string
     */
    public function trackEvent(string $category, string $action, string $opt_label = null, string $opt_value = null): string
    {
        $this->eventType = 4;
        $this->eventProperty = $this->replaceSpecialChars($category) .
            "*" . $this->replaceSpecialChars($action) .
            (!is_null($opt_label) ? "*" . $this->replaceSpecialChars($opt_label) : "") .
            (!is_null($opt_value) ? "*" . $this->replaceSpecialChars($opt_value) : "");
        $this->visitUrl = "";

        return $this->getPixelUrl();
    }
}
