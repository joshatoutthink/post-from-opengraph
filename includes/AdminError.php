<?php

namespace OpengraphPost;

use WP_Error;

class AdminError {
    const key = __NAMESPACE__ . ":error";
    public function __construct() {
    }

    public static function error(string $message): void {
        error_log($message);
        error_log(print_r("creating error:\n---\n$message", true));
        set_transient(self::key, $message);
    }

    public static function display(): void {
        $error_msg = get_transient(self::key);
        if ($error_msg) {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p>' . esc_html($error_msg) . '</p>';
            echo '</div>';
        }
    }

    public static function hasError(): bool {
        return get_transient(self::key) !== false;
    }
    public static function wpError(): WP_Error {
        return new WP_Error(self::key, get_transient(self::key));
    }

    public static function clear(): void {
        delete_transient(self::key);
    }
}
