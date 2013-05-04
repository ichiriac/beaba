<?php
/**
 * This file is distributed under the MIT Open Source
 * License. See README.MD for details.
 * @author Ioan CHIRIAC
 */

defined('CAN_DEBUG') or define('CAN_DEBUG', false);
/**
 * Handling forp profiler bootstrap
 */
if (
    CAN_DEBUG && function_exists('forp_start')
) {
    forp_start();
    register_shutdown_function(
        /**
         * @ProfileAlias("Shutdown Function")
         */
        function() {
            if (defined('PROFILE_DISABLED')) return;
            if ( PHP_SAPI !== 'cli' ) {
                echo '<script src="'.ASSETS_URL.'/core/forp-ui/js/forp.min.js"></script>';
                echo '<script>(new forp.Controller()).setStack(';
                echo json_encode(forp_dump());
                echo ').run();</script>';
            } else {
                forp_print();
            }
        }
    );
}

/**
 * Autoload handler
 */
spl_autoload_register(
    /**
     * @ProfileGroup("core")
     * @ProfileCaption("#1")
     * @ProfileAlias("Autoload")
     */
    function($class) {
        $location = explode('\\', $class, 2);
        switch( $location[0] ) {
            case 'app':
                include APP_PATH . '/' . strtr($location[1], '\\', '/') . '.php';
                break;
            case 'beaba':
                include BEABA_PATH . '/' . strtr($location[1], '\\', '/') . '.php';
                break;
        }
        return class_exists( $class, false );
    }
);

// sets default defines
defined('BEABA_PATH') OR define('BEABA_PATH', __DIR__);
defined('BEABA_APP') OR define('BEABA_APP',
    ($path = getenv('BEABA_APP')) ?
    $path : realpath(BEABA_PATH . '/../apps')
);
defined('APP_NAME') OR define('APP_NAME', 'default');
defined('APP_PATH') OR define('APP_PATH',
    BEABA_APP . '/' . APP_NAME
);
defined('ASSETS_URL') OR define('ASSETS_URL',
    ($url = getenv('ASSETS_URL')) ?
    $url : ''
);
// Gets the default language
defined('DEFAULT_LANG') OR define('DEFAULT_LANG',
    ($lang = getenv('DEFAULT_LANG')) ?
    $lang : 'en'
);
// include the core build file
define('BEABA_BUILD_CORE', false);
// include the application build file
define('BEABA_BUILD_APP', false);

/**
 * An array merging helper
 * @ProfileGroup("core")
 * @params array $original
 * @params array $additionnal
 * @params boolean $prepend
 * @return array
 */
function merge_array( $original, $additionnal, $prepend = false )
{
    if ( empty($additionnal) ) return $original;
    if ( $prepend ) {
        if ( empty($original) ) return $additionnal;
        foreach($original as $key => $value) {
            if ( is_numeric( $key ) ) {
                $additionnal[] = $value;
            } else {
                if ( !empty($additionnal[$key]) ) {
                    $additionnal[$key] = (
                        is_array($additionnal[$key])
                        && is_array($value) ?
                        merge_array($value, $additionnal[$key], true) :
                        $additionnal[$key]
                    );
                } else {
                    $additionnal[$key] = $value;
                }
            }
        }
        return $additionnal;
    } else {
        foreach($additionnal as $key => $value) {
            if ( is_numeric( $key ) ) {
                $original[] = $value;
            } else {
                $original[$key] = (
                    !empty($original[$key])
                    && is_array($value)
                    && is_array($original[$key]) ?
                    merge_array($original[$key], $value, false) : $value
                );
            }
        }
        return $original;
    }
}

/**
 * Function: sanitize
 * Returns a sanitized string, typically for URLs.
 *
 * Parameters:
 *     $string - The string to sanitize.
 *     $lowercase - Force the string to lowercase?
 *     $alnum - If set to *true*, will remove all non-alphanumeric characters.
 */
function sanitize($string, $lowercase = true, $alnum = false) {
    $string = trim(
        strtr(
            strip_tags(
                str_replace(
                    array('`', '^', '\''), null,
                    iconv(
                        'UTF-8',
                        'US-ASCII//TRANSLIT//IGNORE',
                        strtr($string, '\'', ' ')
                    )
                )
            ),
            '~`!@?#$%§^&*()_=+[]{}\\/|;:,"\'<>.',
            '                                  '
        )
    );
    if ($alnum) $string = preg_replace('/[^a-zA-Z0-9]/', ' ', $string);
    if ($lowercase) $string = strtolower($string);
    return preg_replace('/\s+/', '-', $string);
}