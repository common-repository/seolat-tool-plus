<?php defined('ABSPATH') || exit;

/*
 * Class that handles all admin settings
 */

class SimpleWpMapOptions {
    private $posted = '';
    private $error = '';
    private $cantDl = false;
    private $activePage = '';
    private $homeUrl;

    // Constructor: sets homeUrl with trailing slash
    public function __construct () {
        $this->homeUrl = $this->sanitizeUrl(get_home_url() . (substr(get_home_url(), -1) === '/' ? '' : '/'));
    }

    // Returns form submit url for the plugin directory
    public function getSubmitUrl () {
        return 'admin.php?page=sl-sitemap';
    }

    // Returns a sitemap url
    public function sitemapUrl ($format) {
        return sprintf('%ssitemap.%s', $this->homeUrl, $format);
    }

    // Get the url to the plugin dir
    public function pluginUrl () {
        return plugins_url() . '/seolat-tool-plus/plugin/img/';
    }

    // Returns default order option
    public function getDefaultOrder () {
        return array('home' => array('title' => 'Home', 'i' => 1), 'posts' => array('title' => 'Posts', 'i' => 2), 'pages' => array('title' => 'Pages', 'i' => 3), 'other' => array('title' => 'Other', 'i' => 4), 'categories' => array('title' => 'Categories', 'i' => 5), 'tags' => array('title' => 'Tags', 'i' => 6), 'authors' => array('title' => 'Authors', 'i' => 7));
    }

    // Updates the settings/options
    public function setOptions ($otherUrls, $blockUrls, $attrLink, $categories, $tags, $authors, $orderArray, $activePage, $lastUpdated) {
        @date_default_timezone_set(get_option('timezone_string'));
        update_option('simple_wp_other_urls', $this->addUrls($otherUrls, get_option('simple_wp_other_urls')));
        update_option('simple_wp_block_urls', $this->addUrls($blockUrls));
        update_option('simple_wp_attr_link', $attrLink);
        update_option('simple_wp_disp_categories', $categories);
        update_option('simple_wp_disp_tags', $tags);
        update_option('simple_wp_disp_authors', $authors);
        update_option('simple_wp_last_updated', $this->sanitHtml($lastUpdated));

        if ($this->checkOrder($orderArray) && uasort($orderArray, array($this, 'sortArr'))) { // sort the array here
            update_option('simple_wp_disp_sitemap_order', $orderArray);
        }

        $this->activePage = $this->sanitHtml($activePage);
    }

    // Returns the options as strings to be displayed in textareas, checkbox values and orderarray (to do: refactor this messy function)
    public function getOptions ($val) {
        if (preg_match("/^simple_wp_(other_urls|block_urls)$/", $val)) {
            $val = get_option($val);
        } elseif (preg_match("/^simple_wp_(attr_link|disp_categories|disp_tags|disp_authors)$/", $val)) {
            return get_option($val) ? 'checked' : ''; // return checkbox checked values right here and dont bother with the loop below
        } elseif ($val === 'simple_wp_disp_sitemap_order' && ($orderArray = get_option($val))) {
            return $this->checkOrder($orderArray);
        } elseif ($val === 'simple_wp_last_updated') {
            return $this->sanitHtml(get_option($val));
        } else {
            $val = null;
        }

        $str = '';
        if (!$this->isNullOrWhiteSpace($val)) {
            foreach ($val as $sArr) {
                $str .= $this->sanitizeUrl($sArr['url']) . "\n";
            }
        }
        return trim($str);
    }

    // Checks if string is empty (or an array: fix)
    public function isNullOrWhiteSpace ($word) {
        if (is_array($word)) {
            return false;
        }
        return ($word === null || $word === false || trim($word) === '');
    }

    // Sanitizes urls with esc_url and trims
    public function sanitizeUrl ($url) {
        return esc_url(trim($url));
    }

    // Escapes and trims html
    public function sanitHtml ($html) {
        return stripslashes(esc_html(trim($html)));
    }

    // Checks if orderArray is valid
    public function checkOrder ($orderArray) {
        if (is_array($orderArray)) {
            foreach ($orderArray as $title => $arr) {
                if (!is_array($arr) || !preg_match("/^[1-7]{1}$/", $arr['i']) || (!($orderArray[$title]['title'] = $this->sanitHtml($arr['title'])))) {
                    return false;
                }
            }
            return $orderArray;
        }
        return false;
    }

    // Adds new urls to the sitemaps
    public function addUrls ($urls, $oldUrls=null) {
        $arr = array();

        if (!$this->isNullOrWhiteSpace($urls)) {
            $urls = explode("\n", $urls);

            foreach ($urls as $u){
                if (!$this->isNullOrWhiteSpace($u)) {
                    $u = $this->sanitizeUrl($u);
                    $b = false;
                    if ($oldUrls && is_array($oldUrls)) {
                        foreach ($oldUrls as $o) {
                            if ($o['url'] === $u && !$b) {
                                $arr[] = $o;
                                $b = true;
                            }
                        }
                    }
                    if (!$b && strlen($u) < 500) {
                        $arr[] = array('url' => $u, 'date' => time());
                    }
                }
            }
        }
        return !empty($arr) ? $arr : '';
    }

    // Upgrades the plugin to premium
    public function upgradePlugin ($code) {
        $this->posted = $this->sanitHtml(strip_tags($code));
        $this->activePage = 'sitemap-premium';
        $url = 'https://www.webbjocke.com/downloads/update/'; // make sure it's https

        update_option('simple_wp_premium_code', $this->posted);

        try {
            if (!$this->posted) {
                throw new Exception('Please enter a code before submitting');
            }
            if (!class_exists('ZipArchive')) {
                $this->cantDl = true;
                throw new Exception('Your server doesn\'t support ZipArchive');
            }

            $res = wp_remote_post($url, array(
                'body' => array(
                    'action' => 'verify',
                    'object' => 'simple-wp-sitemap-premium',
                    'code' => $this->posted
                )
            ));

            if (is_wp_error($res) || $res['response']['code'] !== 200) {
                throw new Exception('Could not connect to server. Try again later');
            }
            if (!$res['body'] || trim($res['body']) === '' || $res['body'] === 'Invalid Code') {
                throw new Exception('Invalid Code');
            }
            if ($res['body'] === 'Failed') {
                throw new Exception('Failed to download. Try again later');
            }

            $dir = plugin_dir_path(__FILE__);
            $file = sprintf('%slupload.zip', $dir);

            $fp = fopen($file, 'w');
            fwrite($fp, $res['body']);
            fclose($fp);

            $zip = new ZipArchive();

            if (!file_exists($file)) {
                $this->cantDl = true;
                throw new Exception('Couldn\'t find the zip file, try again later');
            }
            if ($zip->open($file) !== true) {
                $this->cantDl = true;
                throw new Exception('Could not open file on the filesystem');
            }
            if (!$zip->extractTo($dir)) {
                $this->cantDl = true;
                throw new Exception('Failed to unpack files');
            }

            $zip->close();
            unlink($file);
            $this->redirect();

        } catch (Exception $ex) {
            $this->error = $ex->getMessage();
        }
    }

    // Get method for posted
    public function getPosted () {
        return $this->posted;
    }

    // Get active page in admin menu (returns empty string as default)
    public function getPage () {
        return $this->activePage;
    }

    // Get method for error text (empty string on default)
    public function getError () {
        if ($this->error) {
            return $this->sanitHtml($this->error) . ($this->cantDl ? '<p style="black">You might have to manually download and install the upgrade. Do it at <a href="https://www.webbjocke.com/downloads/manual-download/">webbjocke.com/downloads/manual-download</a></p>' : '');
        }
        return '';
    }

    // Sort function for "uasort"
    public function sortArr ($a, $b) {
        return $a['i'] - $b['i'];
    }

    // Deletes old or current sitemap files and updates order options
    public function migrateFromOld () {
        if (function_exists('get_home_path')) {
            $path = sprintf('%s%ssitemap.', get_home_path(), (substr(get_home_path(), -1) === '/' ? '' : '/'));
            try {
                foreach (array('xml', 'html') as $file) {
                    if (file_exists($path . $file)) {
                        unlink($path . $file);
                    }
                }
            } catch (Exception $ex) {
                return;
            }
        }

        if ($order = get_option('simple_wp_disp_sitemap_order')) {
            foreach ($order as $key => $val) {
                if (is_array($val)) { // It's ok
                    break;
                }
                $order[lcfirst($key)] = array('title' => $key, 'i' => $val);
                unset($order[$key]);
            }
        } else {
            $order = $this->getDefaultOrder();
        }
        update_option('simple_wp_disp_sitemap_order', $order);
        return $order;
    }

    // Redirect function on successful upgrade to premium
    public function redirect () { ?>
        <h1>Successfully upgraded to SEOLAT Tool Plus Premium!</h1>
        <p><strong>Get ready!</strong></p>
        <p>Redirecting in: <span id="redirectUrl">7</span> seconds</p>
        <script>
            var p = document.getElementById("redirectUrl"), time = 7;
            var inter = setInterval(function () {
                p.textContent = --time;
                if (time <= 0) {
                    clearInterval(inter);
                    location.href="<?php echo $this->getSubmitUrl(); ?>";
                }
            }, 1000);
        </script>
        <?php exit;
    }
}
