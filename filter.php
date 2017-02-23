<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Main filter class
 */
class filter_garavot extends moodle_text_filter {
    private static $csk;
    private static $watermark;
    public function filter($text, array $options = array()) {
        self::$csk = get_config('filter_garavot', 'csk');
        self::$watermark = get_config('filter_garavot', 'watermark');
        if (strpos($text, '[garavot ') === false) {
            return $text;
        }
        return preg_replace_callback( '/\[garavot\s+([A-Za-z0-9\=\s\"\']+)\]/' , function($matches) {
            if (is_null(self::$csk) || self::$csk === "") {
                return "Plugin not set properly. Please enter API key.";
            }
            $attrs = array();
            $regex = '/\b([a-zA-Z0-9]+)\=[\"\']*([A-Za-z0-9]+)[\"\']*\b/';
            $output = preg_replace_callback( $regex , function($matches) use(&$attrs) {
                $attrs[$matches[1]] = $matches[2];
            } , $matches[1]);
            $params = array(
                'video' => $attrs['id'],
            );
            $height = (isset($attrs['height'])) ? $attrs['height'] : 480;
            $width = (isset($attrs['width'])) ? $attrs['width'] : 720;
            if (substr($height, -2) !== 'px') {
                $height .= 'px';
            }
            if (substr($width, -2) !== 'px') {
                $width .= 'px';
            }

            $anno = [];
            if (!function_exists("eval_date")) {
                function eval_date($matches) {
                    return current_time($matches[1]);
                }
            }
            if (!empty(self::$watermark)) {
                global $USER;
                $annotatecode = self::$watermark;
                if ( isset($USER) && !is_null($USER) ) {
                    $fullname = $USER->firstname . ' ' . $USER->middlename . ' ' . $USER->lastname;
                    $annotatecode = str_replace('{name}', $fullname . ' ', $annotatecode);
                    $annotatecode = str_replace('{email}', $USER->email , $annotatecode);
                    $annotatecode = str_replace('{username}', $USER->username , $annotatecode);
                    $annotatecode = str_replace('{id}', $USER->id , $annotatecode);
                }
                $annotatecode = str_replace('{ip}', $_SERVER['REMOTE_ADDR'] , $annotatecode);
                $annotatecode = preg_replace_callback('/\{date\.([^\}]+)\}/', "eval_date" , $annotatecode);
                if (!isset($attrs['no_annotate'])) {
                    $anno = array("annotate" => $annotatecode);
                }
            }
            $otp = $this->garavot_send("otp", $params, $anno);
            if (is_null(json_decode($otp))) {
                return "Video playback can not be authenticated.";
            }
            $otp = json_decode($otp)->otp;
            $output = <<<EOF
<div id="garavot$otp" style="height:$height;width:$width;max-width:100%;"></div>
    <script>
    (function(v,i,d,e,o){v[o]=v[o]||{}; v[o].add = v[o].add || function V(a){ (v[o].d=v[o].d||[]).push(a);};
    if(!v[o].l) { v[o].l=1*new Date(); a=i.createElement(d), m=i.getElementsByTagName(d)[0];
    a.async=1; a.src=e; m.parentNode.insertBefore(a,m);}
    })(window,document,'script','//de122v0opjemw.cloudfront.net/garavot.js','garavot');
    garavot.add({
        o: "$otp",
    });
</script>
EOF;
            return $output;
        }, $text );
    }
    private function garavot_send($action, $params, $posts = []) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);

        $getdata = http_build_query($params);
        curl_setopt($curl, CURLOPT_POST, true);
        $posts["clientSecretKey"] = self::$csk;
        $postdata = http_build_query($posts, null, '&');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        $url = "https://api.garavot.com/v2/$action/?$getdata";
        curl_setopt($curl, CURLOPT_URL, $url);
        $html = curl_exec($curl);
        if (!$html) {
            echo curl_error($curl);
        }
        curl_close($curl);
        return $html;
    }
}
