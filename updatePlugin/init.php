<?php

namespace OpengraphPost\updatePlugin;
require_once "UpdatePlugin.php";
//configure
function newUpdater():UpdatePlugin {
    $config = new \stdClass;
    $config->infoUrl = "https://outthinkplugins.kinsta.cloud/wp-json/ot-update/v1/plugin-info/post-from-opengraph";
    $config->version = OpengraphPost_VERSION;
    $config->pluginBasename = plugin_basename(OpengraphPost_FILE);
    $config->pluginSlug = basename(dirname(OpengraphPost_FILE));
    return new UpdatePlugin($config);
}

// use
add_filter('plugins_api', fn($res, $action, $args) => newUpdater()->pluginInfo($res, $action, $args), 10, 3);
add_filter('site_transient_update_plugins', fn($transient) => newUpdater()->transientUpdate($transient), 10, 1);
add_filter('http_request_args', fn($args, $url) => newUpdater()->modifyRequests($args, $url), 999, 2);
