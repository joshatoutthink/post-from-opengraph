<?php
namespace OpengraphPost\updatePlugin;

class UpdatePlugin {
    public function __construct(
        public object $config,
    ) {
    }
    const LICENSE_KEY = "OTU_token";

    public function transientUpdate(mixed $transient) {

        if (empty($transient->checked)) {
            return $transient;
        }

        $remote = wp_remote_get(
            $this->config->infoUrl,
            array(
                'timeout' => 10,
                'headers' => array(
                    'Accept' => 'application/json',
                    'sslverify' => false,
                )
            )
        );

        if (
            is_wp_error($remote)
            || 200 !== wp_remote_retrieve_response_code($remote)
            || empty(wp_remote_retrieve_body($remote))
        ) {
            return $transient;
        }

        $remote = json_decode(wp_remote_retrieve_body($remote));
        // your installed plugin version should be on the line below! You can obtain it dynamically of course 
        if (
            $remote
            && version_compare($this->config->version, $remote->version, '<')
            && version_compare($remote->requires, get_bloginfo('version'), '<')
            && version_compare($remote->requires_php, PHP_VERSION, '<')
        ) {

            $res = new \stdClass();
            $res->slug = $remote->slug;
            $res->plugin = $this->config->pluginBasename;
            $res->new_version = $remote->version;
            $res->tested = $remote->tested;
            $res->package = $remote->download_url;
            $res->download_link = $remote->download_url;


            $transient->response[$res->plugin] = $res;
        }
        return $transient;
    }

    public function pluginInfo(mixed $res, $action, $args) {

        if ('plugin_information' !== $action) {
            return $res;
        }

        // do nothing if it is not our plugin
        if ($this->config->pluginSlug !== $args->slug) {
            return $res;
        }

        // info.json is the file with the actual plugin information on your server
        $remote = wp_remote_get(
            $this->config->infoUrl,
            array(
                'sslverify' => false,
                'timeout' => 10,
                'headers' => array(
                    'Accept' => 'application/json'
                )
            )
        );


        // do nothing if we don't get the correct response from the server
        if (
            is_wp_error($remote)
            || 200 !== wp_remote_retrieve_response_code($remote)
            || empty(wp_remote_retrieve_body($remote))
        ) {
            return $res;
        }

        $remote = json_decode(wp_remote_retrieve_body($remote));

        $res = new \stdClass();
        $res->name = $remote->name;
        $res->slug = $remote->slug;
        $res->version = $remote->version;
        $res->requires = $remote->requires;
        $res->requires_php = $remote->requires_php;
        $res->sections = array(
            'description' => $remote->sections->description,
            'changelog' => $remote->sections->changelog
            // you can add your custom sections (tabs) here
        );
        $res->download_link = $remote->download_url;
        // in case you want the screenshots tab, use the following HTML format for its content:
        // <ol><li><a href="IMG_URL" target="_blank"><img src="IMG_URL" alt="CAPTION" /></a><p>CAPTION</p></li></ol>
        if (! empty($remote->sections->screenshots)) {
            $res->sections['screenshots'] = $remote->sections->screenshots;
        }

        return $res;
    }

    public function getAuth(): string {
        return "Basic " . get_option(self::LICENSE_KEY);
    }

    /** 
     * Adds auth and some settings for testing in dev
     *
     * @param array<string,mixed> $args
     * @param string $url
     * @return array<string, false>|mixed  
     **/
    public function modifyRequests(array $args, string $url) {
        if (wp_get_environment_type() == "development") {
            $args["sslverify"] = false;
            $args['reject_unsafe_urls'] = false;
        }
        if (strpos($url, "wp-json/ot-update") !== false) {
            $args["headers"]["Authorization"] = $this->getAuth();
        }

        return $args;
    }
}
