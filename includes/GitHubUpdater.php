<?php

declare(strict_types=1);

namespace Beenacle\ElementorTaxonomyLoop;

/**
 * Shared GitHub-Releases self-updater for beenacle plugins. The file is IDENTICAL
 * across every repo except this `namespace` line — which is per-plugin so two
 * beenacle plugins active on one site can't collide on the class name. The plugin
 * isn't on wordpress.org, so this hooks WordPress's native update machinery and
 * points it at github.com/{owner}/{repo}/releases; every site then sees and installs
 * new versions from Dashboard → Updates like any other plugin.
 *
 * Usage (in the main plugin file):
 *   require_once __DIR__ . '/includes/GitHubUpdater.php';
 *   ( new \Beenacle\ElementorTaxonomyLoop\GitHubUpdater( __FILE__, 'beenacle', 'my-repo' ) )->register();
 *
 * Self-configuring: version, name, description and author are read from the plugin
 * header; icons are used only if assets/icon-*.png exist. Public Releases API only
 * (no token), so a public repo needs no secrets.
 */
final class GitHubUpdater
{
    private const API = 'https://api.github.com/repos/%s/%s/releases/latest';
    private const TTL = 21600; // 6 * HOUR_IN_SECONDS

    private string $plugin_file;
    private string $basename;
    private string $slug;
    private string $owner;
    private string $repo;
    private string $version;
    private string $cache_key;
    /** @var array<string,string>|null */
    private ?array $headers = null;

    public function __construct(string $plugin_file, string $owner, string $repo)
    {
        $this->plugin_file = $plugin_file;
        $this->basename    = plugin_basename($plugin_file);
        $dir               = dirname($this->basename);
        $this->slug        = $dir !== '.' ? $dir : basename($this->basename, '.php');
        $this->owner       = $owner;
        $this->repo        = $repo;
        $this->version     = ltrim($this->header('version', '0.0.0'), 'vV');
        $this->cache_key   = 'bncl_upd_' . md5($owner . '/' . $repo);
    }

    public function register(): void
    {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'inject_update']);
        add_filter('plugins_api', [$this, 'details'], 10, 3);
        add_filter('upgrader_source_selection', [$this, 'fix_source_dir'], 10, 4);
        add_action('upgrader_process_complete', [$this, 'flush_cache'], 10, 2);
    }

    /** Plugin icon URLs — only those that actually ship in assets/. */
    private function icons(): array
    {
        $base  = plugin_dir_path($this->plugin_file) . 'assets/';
        $icons = [];
        if (file_exists($base . 'icon.svg')) {
            $icons['svg'] = plugins_url('assets/icon.svg', $this->plugin_file);
        }
        if (file_exists($base . 'icon-128x128.png')) {
            $icons['1x'] = plugins_url('assets/icon-128x128.png', $this->plugin_file);
        }
        if (file_exists($base . 'icon-256x256.png')) {
            $icons['2x']      = plugins_url('assets/icon-256x256.png', $this->plugin_file);
            $icons['default'] = $icons['2x'];
        }
        return $icons;
    }

    /**
     * @param mixed $transient
     * @return mixed
     */
    public function inject_update($transient)
    {
        if (!is_object($transient)) {
            return $transient;
        }
        $release = $this->get_release();
        if ($release === null) {
            return $transient;
        }

        $payload = (object) [
            'slug'         => $this->slug,
            'plugin'       => $this->basename,
            'new_version'  => $release['version'],
            'url'          => $release['html_url'],
            'package'      => $release['package'],
            'icons'        => $this->icons(),
            'banners'      => [],
            'tested'       => $release['tested'],
            'requires'     => $release['requires'],
            'requires_php' => $release['requires_php'],
        ];

        if (version_compare($release['version'], $this->version, '>')) {
            $transient->response[$this->basename] = $payload;
        } else {
            $transient->no_update[$this->basename] = $payload;
        }
        return $transient;
    }

    /**
     * @param mixed  $result
     * @param string $action
     * @param object $args
     * @return mixed
     */
    public function details($result, $action, $args)
    {
        if ($action !== 'plugin_information' || !isset($args->slug) || $args->slug !== $this->slug) {
            return $result;
        }
        $release = $this->get_release();
        if ($release === null) {
            return $result;
        }

        $author = $this->header('author', 'Beenacle');
        $uri    = $this->header('author_uri', 'https://beenacle.com');

        return (object) [
            'name'          => $this->header('name', $this->slug),
            'slug'          => $this->slug,
            'version'       => $release['version'],
            'author'        => $uri !== '' ? '<a href="' . esc_url($uri) . '">' . esc_html($author) . '</a>' : esc_html($author),
            'homepage'      => $release['html_url'],
            'download_link' => $release['package'],
            'icons'         => $this->icons(),
            'requires'      => $release['requires'],
            'requires_php'  => $release['requires_php'],
            'tested'        => $release['tested'],
            'last_updated'  => $release['date'],
            'sections'      => [
                'description' => $this->header('description', ''),
                'changelog'   => $release['changelog'],
            ],
        ];
    }

    /**
     * GitHub's source zipball extracts to "{repo}-{tag}/"; a built release asset
     * extracts to "{slug}/". Force the installed folder to the slug so WordPress
     * doesn't orphan the update under a renamed directory.
     *
     * @param string $source
     * @param string $remote_source
     * @param object $upgrader
     * @param array  $args
     * @return string|\WP_Error
     */
    public function fix_source_dir($source, $remote_source, $upgrader, $args = [])
    {
        if (!isset($args['plugin']) || $args['plugin'] !== $this->basename) {
            return $source;
        }
        global $wp_filesystem;
        $desired = trailingslashit($remote_source) . $this->slug;
        if (untrailingslashit($source) === $desired) {
            return $source;
        }
        if ($wp_filesystem instanceof \WP_Filesystem_Base && $wp_filesystem->move($source, $desired, true)) {
            return trailingslashit($desired);
        }
        return $source;
    }

    /**
     * @param object $upgrader
     * @param array  $extra
     */
    public function flush_cache($upgrader, $extra): void
    {
        if (is_array($extra) && ($extra['type'] ?? '') === 'plugin') {
            delete_transient($this->cache_key);
        }
    }

    /**
     * @return array{version:string,package:string,html_url:string,date:string,changelog:string,requires:string,requires_php:string,tested:string}|null
     */
    private function get_release(): ?array
    {
        $cached = get_transient($this->cache_key);
        if (is_array($cached)) {
            return isset($cached['__error']) ? null : $cached;
        }
        $data = $this->fetch_release();
        if ($data === null) {
            set_transient($this->cache_key, ['__error' => true], 1800);
            return null;
        }
        set_transient($this->cache_key, $data, self::TTL);
        return $data;
    }

    /** @return array<string,mixed>|null */
    private function fetch_release(): ?array
    {
        $url      = sprintf(self::API, rawurlencode($this->owner), rawurlencode($this->repo));
        $response = wp_remote_get($url, [
            'timeout' => 10,
            'headers' => [
                'Accept'     => 'application/vnd.github+json',
                'User-Agent' => 'Beenacle-Plugin-Updater/' . $this->version,
            ],
        ]);
        if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
            return null;
        }
        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        if (!is_array($body) || empty($body['tag_name'])) {
            return null;
        }

        $package = '';
        foreach (($body['assets'] ?? []) as $asset) {
            if (!empty($asset['name']) && str_ends_with((string) $asset['name'], '.zip')) {
                $package = (string) $asset['browser_download_url'];
                break;
            }
        }
        if ($package === '') {
            $package = (string) ($body['zipball_url'] ?? '');
        }
        if ($package === '') {
            return null;
        }

        return [
            'version'      => ltrim((string) $body['tag_name'], 'vV'),
            'package'      => $package,
            'html_url'     => (string) ($body['html_url'] ?? ''),
            'date'         => (string) ($body['published_at'] ?? ''),
            'changelog'    => $this->render_changelog((string) ($body['body'] ?? '')),
            'requires'     => $this->header('requires', ''),
            'requires_php' => $this->header('requires_php', ''),
            'tested'       => $this->header('tested', ''),
        ];
    }

    /** Read a header field from the installed plugin file (cached for the request). */
    private function header(string $key, string $default): string
    {
        if ($this->headers === null) {
            $this->headers = get_file_data($this->plugin_file, [
                'name'         => 'Plugin Name',
                'version'      => 'Version',
                'description'  => 'Description',
                'author'       => 'Author',
                'author_uri'   => 'Author URI',
                'requires'     => 'Requires at least',
                'requires_php' => 'Requires PHP',
                'tested'       => 'Tested up to',
            ]);
        }
        $value = isset($this->headers[$key]) ? trim((string) $this->headers[$key]) : '';
        return $value !== '' ? $value : $default;
    }

    /** Render a GitHub release body (Markdown) into safe HTML for the details modal. */
    private function render_changelog(string $markdown): string
    {
        $markdown = trim($markdown);
        if ($markdown === '') {
            return 'See the release notes on GitHub.';
        }
        $html = esc_html($markdown);
        $html = preg_replace('/^#{1,6}\s*(.+)$/m', '<h4>$1</h4>', $html) ?? $html;
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html) ?? $html;
        $html = preg_replace('/^\s*[-*]\s+(.+)$/m', '<li>$1</li>', $html) ?? $html;
        $html = preg_replace('/(?:<li>.*<\/li>\s*)+/s', '<ul>$0</ul>', $html) ?? $html;
        return wpautop($html);
    }
}
