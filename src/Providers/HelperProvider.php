<?php

namespace Vlinde\Helper\Providers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use stdClass;

class Helper extends ServiceProvider
{
    public static $DOW_KEYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    // copied from identifier function
    public static $UNWANTED_KEYWORDS = [
        'restaurant',
        'Restaurant',
        'RESTAURANT',
        'ristorante',
        'Ristorante',
        'RISTORANTE',
        'restaurante',
        'Restaurante',
        'RESTAURANTE',
        'Ресторант',
        'ресторант',
        'РЕСТОРАНТ',
        'hotel',
        'Hotel',
        'HOTEL',
        'motel',
        'Motel',
        'MOTEL',
    ];
    public static $UNWANTED_CHARS = [
        "[",
        "^",
        "ø",
        "£",
        "$",
        "%",
        "&",
        "*",
        "(",
        ")",
        "}",
        "{",
        "@",
        "#",
        "~",
        ">",
        "<",
        "|",
        "=",
        "_",
        "+",
        "¬",
        "]",
        "-",
        "&",
        '🅫',
        "�",
        '�',
        '/',
        '\\',
        "\n",
        "\r",
        "\t",
    ];

    public static $UNWANTED_PUNCTUATION = [
        ",",
        ".",
        "?",
        "!",
        ":",
        ";"
    ];

    // use this for now
    public static $CURRENCY_CODES = [
        "£" => 'GBP',
        "$" => 'USD',
        "lei" => 'RON',
        "LEI" => 'RON',
        "€" => 'EUR',
        "zł" => 'PLN',
        "Fr." => 'CHF',
        "₫" => 'VND',
        'лв.' => 'BGN',
        'lv.' => 'BGN',
        "aud" => 'AUD' // modifiy this in the countries array

    ];

    public static $MEASURE_UNITS = [
        "l",
        "ml",
        "g",
        "mg",
        "kg",
        'cc',
        'cl',
        'cl.',
        'c l.',
        'c l'
    ];

    public static function fixPairedValues($params)
    {
        if (count($params['array']) % 2 != 0) {
            unset($params['array'][0]);
        }

        if (isset($params['unsetfirst'])) {
            unset($params['array'][0]);
        }

        $array = array_values($params['array']);
        $clean = isset($params['clean']) ? true : false;
        $slugify = isset($params['slug']) ? false : true;
        $clean_type = isset($params['clean_type']) ? $params['clean_type'] : 'value';

        $fixedArray = [];

        if (is_array($array) && !empty($array)) {
            foreach ($array as $key => $item) {
                if (is_object($item)) {
                    $object_key = key((array)$item);
                    if ($key % 2 == 0 && isset($array[$key + 1])) {
                        if ($slugify == true) {
                            $item->$object_key = str_slug($item->$object_key);
                        }
                        $fixedArray[$item->$object_key] = $clean == true
                            ? self::cleanStr([
                                'string' => $array[$key + 1]->$object_key,
                                'type' => $clean_type,
                            ]) : $array[$key + 1]->$object_key;
                    }
                } else {
                    if ($slugify == true) {
                        $item = str_slug($item);
                    }
                    if ($key % 2 == 0 && isset($array[$key + 1])) {
                        $fixedArray[str_slug($item)] = $clean == true
                            ? self::cleanStr([
                                'string' => $array[$key + 1],
                                'type' => $clean_type,
                            ]) : $array[$key + 1];
                    }
                }
            }
        }

        return $fixedArray;
    }

    public static function cleanStr($params)
    {
        $string = $params['string'];
        $type = isset($params['type']) ? $params['type'] : 'value';
        if (!isset($params['html']) ? $html = false : $html = true) {
            //
        }

        switch ($type) {
            case 'attr':
                if ($html) {
                    $string = mb_convert_encoding(pack('H*', $string[1]), 'UTF-8', 'UCS-2BE');
                }
                $string = str_replace(["\n", "\r", "\t", '&nbsp;'], '',
                    $string);
                $string = preg_replace('!\s+!', ' ', $string);
                $string = trim($string);
                break;

            case 'value':
                $string = strip_tags($string, '<br>');
                $string = str_replace("\n", ' ', $string);
                if ($html) {
                    $string = mb_convert_encoding(pack('H*', $string[1]), 'UTF-8', 'UCS-2BE');
                }
                $string = str_replace(["\n", "\r", "\t", '&nbsp;'], '', $string);
                $string = preg_replace('!\s+!', ' ', $string);
                $string = trim($string);
                $string = str_replace('<br> ', '<br>', $string);
                $string = str_replace(' <br>', '<br>', $string);
                break;

            case 'array':
                foreach ($string as $key => $single) {
                    $single = strip_tags($single, '<br>');
                    $single = str_replace("\n", ' ', $single);
                    if ($html) {
                        $string = htmlspecialchars($string, ENT_QUOTES, "UTF-8");
                    }
                    $single = str_replace(["\n", "\r", "\t", '&nbsp;'], '',
                        $single);
                    $single = preg_replace('!\s+!', ' ', $single);
                    $single = str_replace('<br> ', '<br>', $single);
                    $single = str_replace(' <br>', '<br>', $single);
                    $string[$key] = $single;
                }
                unset($single);

                break;
        }

        return $string;
    }

    public static function fixOneValueArray($params)
    {
        $array = $params['array'];
        $clean = isset($params['clean']) ? true : false;
        $clean_type = isset($params['clean_type']) ? $params['clean_type'] : 'value';

        $fixedArray = [];
        if (is_array($array) && !empty($array)) {
            foreach ($array as $key => $item) {

                if (is_object($item)) {
                    $object_key = key((array)$item);
                    $fixedArray[] = $clean ? self::cleanStr([
                        'string' => $array[$key]->$object_key,
                        'type' => $clean_type,
                    ]) : $array[$key]->$object_key;
                } else {
                    $fixedArray[] = $clean ? self::cleanStr([
                        'string' => $array[$key],
                        'type' => $clean_type,
                    ]) : $array[$key];
                }
            }
        }

        return $fixedArray;
    }

    public static function unsetValueFromArray($params)
    {
        $array = $params['array'];
        $attribute = $params['attribute'];

        $fixedArray = [];

        if (is_array($array) && !empty($array)) {
            foreach ($array as $key => $item) {
                if (is_object($item)) {
                    unset($item->$attribute);
                } else {
                    unset($item[$attribute]);
                }
                $fixedArray[] = $item;
            }
        }

        return $fixedArray;
    }

    public static function readData($request)
    {
        $params = new stdClass();
        $params->project = isset($request->project)
        && !empty($request->project) ? $request->project : false;
        $params->source = isset($request->source) && !empty($request->source)
            ? $request->source : false;
        $provider = self::getProvider(ucfirst($params->project),
            ucfirst($params->source));
        $params->element = isset($request->element)
        && !empty($request->element) ? $request->element : 1;
        $params->from = isset($request->from) && !empty($request->from)
            ? $request->from : false;
        $params->to = isset($request->to) && !empty($request->to)
            ? $request->to : false;
        $params->chunk = isset($request->chunk) && !empty($request->chunk)
            ? $request->chunk : false;
        $url_details = self::getUrl($provider::urls(), $params);
        $url_type = isset($url_details['type']) ? $url_details['type'] : false;

        $params->json_url = $url_details['url'];
        $url_content = file_get_contents($params->json_url);
        $json = json_decode($url_content);

        if ($json == null) {
            die('Json format is wrong!');
        }
        if (isset($url_details['direct_elements'])
            && $url_details['direct_elements']
        ) {
            $elements_collection = collect($json);
        } else {
            $elements_collection = collect($json->rows)->pluck('doc');
        }

        if ($url_type == 'file') {
            if (isset($params->from) && $params->from !== false
                && isset($params->to)
                && $params->to !== false
            ) {
                $elements = $elements_collection->splice($params->from,
                    ($params->to - $params->from));
            } elseif (isset($params->from) && $params->from !== false) {
                $elements = $elements_collection->splice($params->from);
            } elseif (isset($params->to) && $params->to !== false) {
                $elements = $elements_collection->take($params->to);
            } else {
                $elements = $elements_collection;
            }
        } else {
            $elements = $elements_collection;
        }

        return $elements;

    }

    public static function getProvider($project, $source)
    {
        $provider = 'app' . DIRECTORY_SEPARATOR . 'Providers' . DIRECTORY_SEPARATOR
            . 'Projects' . DIRECTORY_SEPARATOR . ucfirst($project)
            . DIRECTORY_SEPARATOR . ucfirst($source);
        $provider_path = base_path($provider . '.php');
//        dd($provider_path);
        if (!\File::exists($provider_path)) {
            die('The combination between source and project not found!');
        } else {
            $provider = ucfirst(str_replace(DIRECTORY_SEPARATOR, '\\', $provider));
            return $provider;
        }
    }

    public static function getUrl($urls, $request)
    {
        $limit = '';
        if (isset($request->from) && $request->from != 0) {
            $limit .= '&skip=' . $request->from;
        }
        if (isset($request->to) && $request->to != 0 && isset($request->from)) {
            $limit .= '&limit=' . ((int)$request->to - (int)$request->from);
        } elseif (isset($request->to) && $request->to != 0) {
            $limit .= '&limit=' . ((int)$request->to);
        }
        if (strpos($limit, "-")) {
            $limit = str_replace('-', '', $limit);
        }

        if (array_key_exists($request->element, $urls)) {
            $url_details = $urls[$request->element];
            if ($url_details['limit']) {
                $url_details['url'] .= $limit;
            }

            return $url_details;
        }

        return false;
    }

    public static function readStatistics($request)
    {
        $params = new stdClass();
        $params->project = isset($request->project) && !empty($request->project) ? $request->project : false;
        $params->source = isset($request->source) && !empty($request->source) ? $request->source : false;
        $provider = self::getProvider(ucfirst($params->project), ucfirst($params->source));
        $params->element = isset($request->element) && !empty($request->element) ? $request->element : 1;
        $params->json_url = self::getUrl($provider::urls(), $params);

        if ($params->json_url['limit']) {

            $exploded_statistics_link = explode('/', $params->json_url['url']);
            $link = '';
            foreach ($exploded_statistics_link as $key => $item) {
                if ($key <= 3) {
                    $link .= $key <= 2 ? $item . '/' : $item;
                }
            }

            $params->statistics_url = $link;
            $url_content = @file_get_contents($params->statistics_url);
            $json = json_decode($url_content);

            if ($json == null) {
                return 'Json format is wrong!';
            }
            $elements = collect($json);

            return $elements;
        }

        return false;

    }

    public static function getSources($project)
    {
        $provider_location = 'app' . DIRECTORY_SEPARATOR . 'Providers'
            . DIRECTORY_SEPARATOR . 'Projects' . DIRECTORY_SEPARATOR
            . ucfirst($project);

        $project_path = base_path($provider_location);

        $directories = \File::allFiles($project_path);

        $projects = [];
        $project_main_function = ucfirst($project) . 'Provider.php';

        $info = [];
        foreach ($directories as $directory) {
            $link_target = $directory->getPathName();
            $exploded_link_target = explode(DIRECTORY_SEPARATOR, $link_target);
            $count_elements_from_link_target = count($exploded_link_target);

            $source = str_replace('.php', '', $exploded_link_target[$count_elements_from_link_target - 1]);
            $project = $exploded_link_target[$count_elements_from_link_target - 2];

            $provider = self::getProvider($project, $source);

            if ($directory->getFilename() == $project_main_function) {
                $info = method_exists($provider, 'info') ? $provider::info() : [];

                continue;
            }

            $projects[$project][$source] = method_exists($provider, 'urls') ? $provider::urls() : [];

            $projects[$project][$source]['demo_urls'] = method_exists($provider, 'demo_urls') ? $provider::demo_urls() : [];
        }

        $projects['info'] = $info;

        return $projects;
    }

    public static function getProjects()
    {
        $projects = [];

        $provider_location = 'app' . DIRECTORY_SEPARATOR . 'Providers' . DIRECTORY_SEPARATOR . 'Projects';

        $project_path = base_path($provider_location);

        $directories = \File::directories($project_path);

        foreach ($directories as $directory) {
            $project_parts = explode(DIRECTORY_SEPARATOR, $directory);
            $project = $project_parts[(count($project_parts) - 1)];

            $provider = self::getProvider($project, $project . 'Provider');

            $projects[] = [
                'key' => str_slug($project),
                'name' => $project,
                'status' => property_exists($provider, 'status') ? $provider::$status : 'unknown',
                'version' => property_exists($provider, 'version') ? $provider::$version : 'unknown'
            ];

        }

        return $projects;
    }

    /**
     * Dump the passed variables and end the script.
     *
     * @param mixed
     *
     * @return void
     */
    public static function dd()
    {
        array_map(function ($x) {
            (new Dumper)->dump($x);
        }, func_get_args());
    }

    public static function getEmailAddresses($string)
    {

        $emails = [];
        $string = str_replace('[at]', '@', $string);
        $string = str_replace('[ at ]', '@', $string);
        $string = str_replace(' [at] ', '@', $string);
        $string = str_replace(' [ at ] ', '@', $string);
        $string = str_replace(' @ ', '@', $string);
        $string = str_replace("\r\n", ' ', $string);
        $string = str_replace("\n", ' ', $string);

        foreach (preg_split('/ /', $string) as $token) {
            $email = filter_var($token, FILTER_VALIDATE_EMAIL);
            if ($email !== false) {
                $emails[] = $email;
            }
        }

        return $emails;
    }

    public static function getPhoneNumber($string)
    {
        $to_remove = ['-', ' ', '/', '(', ')', '.'];
        $phone_numbers = [];
        foreach ($to_remove as $remover) {
            $string = str_replace($remover, '', $string);
            $string = str_replace(' ' . $remover, '', $string);
            $string = str_replace($remover . '', '', $string);
            $string = str_replace(' ' . $remover . ' ', '', $string);
        }
        preg_match_all('!\d+!', $string, $numbers);
        foreach ($numbers[0] as $single_number) {
            if (strlen($single_number) >= 8 && strlen($single_number) <= 14) {
                $number_final[] = $single_number;
            }
        }
        if (empty($number_final)) {
            $number_final = "NOT_FOUND";
        }

        return $number_final;

    }

    public static function getGermanZip($string)
    {
        preg_match_all('!\d+!', $string, $matches);

        foreach ($matches[0] as $single_number) {
            if (strlen($single_number) == 5) {
                $zip[] = $single_number;
            }
        }

        return $zip;
    }

    public static function cleanArray($params)
    {
        $array = $params['array'];
        $type = isset($params['type']) ? $params['type'] : 'value';
        $keep_key = isset($params['keep_key']) ? $params['keep_key'] : false;
        $fixedArray = [];

        if (is_array($array) && !empty($array)) {
            foreach ($array as $key => $item) {
                if (is_object($item)) {
                    $object_key = key((array)$item);
                    if ($keep_key) {
                        $fixedArray[$object_key] = self::cleanStr([
                            'string' => $array[$key]->$object_key,
                            'type' => $type,
                        ]);
                    } else {
                        $fixedArray[]
                            = self::cleanStr([
                            'string' => $array[$key]->$object_key,
                            'type' => $type,
                        ]);
                    }
                } else {
                    if ($keep_key) {
                        $fixedArray[$key]
                            = self::cleanStr([
                            'string' => $array[$key],
                            'type' => $type,
                        ]);
                    } else {
                        $fixedArray[]
                            = self::cleanStr([
                            'string' => $array[$key],
                            'type' => $type,
                        ]);
                    }
                }
            }
        }

        return json_decode(json_encode($fixedArray));

    }

    public static function makeList($request, $file, $link, $numbers, $replace, $limit_key)
    {
        $params = new stdClass();
        $params->from = isset($request->from) && !empty($request->from) ? $request->from : false;
        $params->to = isset($request->to) && !empty($request->to) ? $request->to : false;

        $file = url(Storage::url('projects/' . $request->project . '/' . $request->source . '/' . $file));
        $file_content = @file_get_contents($file);
        $contents = collect(explode("\r\n", $file_content));


        if (isset($params->from) && $params->from !== false && isset($params->to) && $params->to !== false) {
            $elements = $contents->splice($params->from,
                ($params->to - $params->from));
        } elseif (isset($params->from) && $params->from !== false) {
            $elements = $contents->splice($params->from);
        } elseif (isset($params->to) && $params->to !== false) {
            $elements = $contents->take($params->to);
        } else {
            $elements = $contents;
        }

        // NUMBERS GENERATOR -- not optimazed for only from
        if ($numbers != false) {
            if ($params->to != false) {
                if (($params->from == false) ? $params->from = "1"
                    : $params->from = $params->from + 1
                ) {
                    ;
                }
                if (($params->to < $numbers) ? $elements = range($params->from,
                    $params->to) : die('Only max: ' . $numbers)
                ) {
                    ;
                }
            } else {
                $elements = range(1, $numbers);
            }
        }

        $links = [];
        if (count($contents) > 1) {
            foreach ($elements as $key => $element) {

                $element = explode(',', $element);
                preg_match_all("/\{[0-9]}/", $link, $vars);
                $vars = collect($vars[0])->unique();
                $link_var = $link;

                foreach ($vars as $var) {
                    $var = str_replace(['{', '}'], ['', ''], $var);
                    if (!isset($element[$var])) {
                        $element[$var] = "";
                    }
                    if ($limit_key) {
                        if (strlen($element[$var]) > $limit_key) {
                            $limit_key_true = "dd";
                        }
                    }
                    $link_var = str_replace('{' . $var . '}', $element[$var], $link_var);
                    if ($replace) {
                        $link_var = str_replace(array_keys($replace), array_values($replace), $link_var);
                    }
                }

                if (isset($limit_key_true)) {
                    unset($limit_key_true);
                    unset($element[$key]);
                    continue;
                }
                $links[] = $link_var;
            }
        }

        return $links;
    }

    public static function bing_images($clean_elem)
    {
        $image_r = str_replace('murl&quot;:&quot;', '', $clean_elem);
        $image_r = explode('&quot;,&quot;turl', $image_r)[0];

        return $image_r;
    }

    public static function google_images($clean_elem)
    {

        $clean_elem = explode('&imgrefurl=', $clean_elem)[0];
        $clean_elem = str_replace('/imgres?imgurl=', '', $clean_elem);
        $clean_elem = urldecode($clean_elem);

        return $clean_elem;
    }

    public static function download_convert_images($soure, $name, $path, $size, $color)
    {
        // Create Slug with _
        $name_number = self::getAfter('_', $name);
        $name_number_underscore = "_" . $name_number;
        $name_for_slug = str_replace($name_number_underscore, '', $name);
        $name = str_slug($name_for_slug);
        $name = $name . "" . $name_number_underscore;

        $location = storage_path() . '/app/public/project_images/' . $path . '/cache/' . $name . '.jpg';

        $url = $soure;
        ini_set('gd.jpeg_ignore_warning', 1);

        $allowed_images = ['jpg', 'png', 'gif'];

        $file_get_image = [
            'http' => [
                'header' => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36',
                'proxy' => getProxyIp(),
                'request_fulluri' => true,
            ],
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
            ],
        ];

        if (@getimagesize($url) && in_array(pathinfo($url, PATHINFO_EXTENSION), $allowed_images)) {
            if (!@copy($url, $location)) {
                copy($url, $location, stream_context_create($file_get_image));
            }
            if (@getimagesize($location)) {
                $img = Image::make($location);
                $location = str_replace('cache', '', $location);

                $size = explode('x', $size);
                $img->fit($size[0], $size[1]);

                if ($color != "-") {
                    $color = explode('x', $color);
                    $img->colorize($color[0], $color[1], $color[2]);
                }

                // try to be undetectable
                $img->brightness(-7);
                $size0 = $size[0] + 13;
                $size1 = $size[1] + 13;
                $img->resize($size0, $size1);
                $img->sharpen(5);
                $sizestretch = $size0 + 3;
                $sizestretch1 = $size1 + 1;
                $img->resize($sizestretch, $sizestretch1);
                $img->brightness(-3);
                $img->colorize('03', '03', '03');

                $img->save($location);
            }
        }
    }

    public static function getAfter($delimiter, $string)
    {
        $exploded = explode($delimiter, $string);
        if (isset($exploded[1]) && !empty($exploded[1])) {
            return $exploded[1];
        }

        return $exploded[0];
    }

    public static function translator($to_transalte, $element)
    {
        foreach ($to_transalte as $translate) {
            if (strpos($translate, ' # ') !== false) {
                $subarray = self::getBefore(' # ', $translate);
                $new_value = self::getAfter(' = ', $translate);
                $elementbtw = self::getValueBetweenStrings(' # ', " = ", $translate)[0];
                if (isset($element->$subarray[$elementbtw])) {
                    $element->$subarray[$new_value] = $element->$subarray[$elementbtw];
                    unset($element->$subarray[$elementbtw]);
                }
            } else {
                $new_value = self::getAfter(' = ', $translate);
                $elementbtw = self::getBefore(" = ", $translate);
                if (isset($element->$elementbtw)) {
                    $element->$new_value = $element->$elementbtw;
                    unset($element->$elementbtw);
                }
            }
        }

        return $element;
    }

    public static function getBefore($delimiter, $string)
    {
        if (!is_array($string)) {
            $exploded = explode($delimiter, $string)[0];
        } else {
            foreach ($string as $single) {
                $exploded[] = explode($delimiter, $single)[0];
            }
        }

        if (empty($exploded)) {
            $exploded = null;
        }

        return $exploded;
    }

    public static function getValueBetweenStrings($startDelimiter, $endDelimiter, $str)
    {
        $contents = [];
        $startDelimiterLength = strlen($startDelimiter);
        $endDelimiterLength = strlen($endDelimiter);
        $startFrom = $contentStart = $contentEnd = 0;
        while (false !== ($contentStart = strpos($str, $startDelimiter,
                $startFrom))) {
            $contentStart += $startDelimiterLength;
            $contentEnd = strpos($str, $endDelimiter, $contentStart);
            if (false === $contentEnd) {
                break;
            }
            $contents[] = substr($str, $contentStart,
                $contentEnd - $contentStart);
            $startFrom = $contentEnd + $endDelimiterLength;
        }
        if (!strpos($str, $startDelimiter)) {
            return $str;
        } else {
            return $contents;
        }
    }

    public static function unsetter($elementvalue, $element)
    {
        foreach ($elementvalue as $value) {
            if (strpos($value, ' # ') !== false) {
                $subarray = self::getBefore(' # ', $value);
                $array_value = self::getAfter(' # ', $value);
                if (is_object($element)) {
                    unset($element->$subarray[$array_value]);
                } else {
                    unset($element[$subarray[$array_value]]);
                }
            } else {
                if (is_object($element)) {
                    if (empty($element->$value) or is_null($element->$value)) {
                        unset($element->$value);
                    }
                    if (isset($element->$value)) {
                        unset($element->$value);
                    }
                } else {
                    if (isset($element[$value]) || is_null($element[$value])) {
                        unset($element[$value]);
                    }
                }
            }
        }

        return $element;
    }

    public static function iftoarray($elementvalue, $element)
    {
        foreach ($elementvalue as $value) {
            if (isset($element->$value)) {
                if (!is_array($element->$value)) {
                    $element->$value = array($element->$value);
                }
            }
        }

        return $element;
    }

    public static function getPrices($string)
    {
        $money_symbols = ['€', 'EURO', 'euro', 'Euro', 'Eur.', 'Lei', 'RON'];

        if (strpos($string, "€")) {
            $string = "\n" . $string . "\n";
            $string = str_replace("\n", "###", $string);
            $match = self::getBetweenSame($string, "#", "3");
            foreach ($match as $line) {
                if (strpos($line, "€")) {
                    $line = str_replace('###', '', $line);

                    preg_match_all('!\d+!', $line, $price);
                    if (strlen($price[0][0]) >= 2
                        && strlen($price[0][0]) <= 4
                    ) {
                        $price_final = $price[0][0];
                    } else {
                        $price_final = "NO_PRICE";
                    }

                    $text = str_replace($price_final, '', $line);

                    $prices[] = $price_final . "€ ~~~ " . $text;
                }
            }

            return $prices;
        }

    }

    public static function getBetweenSame($string, $delmiter, $howmany)
    {
        // That function is needed by getPrices
        $find = '/\####{33}([^####]*)\####{33}/';
        $find = str_replace('33', $howmany, $find);
        $find = str_replace('####', $delmiter, $find);
        preg_match_all($find, $string, $match);

        return $match[0];
    }

    public static function replacer($to_replace, $string_replacer)
    {
        foreach ($to_replace as $res_title) {
            if (strpos($string_replacer, $res_title) !== false) {
                $final = str_replace($res_title, '', $string_replacer);

                return $final;
            } else {
                $final = $string_replacer;
            }

        }

        return $final;
    }

    public static function element_remover($elements, $key, $to_remove, $string_remover)
    {
        foreach ($to_remove as $rem_title) {
            $rem_title = mb_strtolower($rem_title);
            $string_remover = mb_strtolower($string_remover);
            if (strpos($string_remover, $rem_title) !== false) {
                unset($elements[$key]);
            } else {
                return $string_remover;
            }
        }
        unset($rem_title);


    }

    public static function htmlSplitter($classname, $el_menu)
    {
        libxml_use_internal_errors(true);
        $dom = new \DomDocument();
        $el_menu = "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">" . $el_menu;
        $dom->loadHTML($el_menu);
        $finder = new \DomXPath($dom);
        $nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");

        return $nodes;
    }

    public static function strsToArrays($input, $elements)
    {
        foreach ($elements as $element => $delimiter) {
            if (isset($input[$element])) {
                $input[$element] = array_filter(array_map('trim', explode($delimiter, $input[$element])));
            } else {
                $input[$element] = [];
            }
        }

        return $input;
    }

    public static function concatenateArrays($input, $array_name, $arrays)
    {
        $concatenated_array = [];
        foreach ($arrays as $array) {
            if (isset($input[$array])) {
                if (is_array($input[$array])) {
                    $concatenated_array = array_merge($concatenated_array, $input[$array]);
                } else {
                    $concatenated_array[] = $input[$array];
                }
                unset($input[$array]);
            }
        }
        $input[$array_name] = $concatenated_array;

        return $input;
    }

    public static function generateOpenHours($open_hours, $posible_openhours, $replaces)
    {
        $open_hours_exit = [];
        foreach ($open_hours as $key => $open_hour) {
            foreach ($replaces as $find => $replace) {
                $open_hour = str_replace($find, $replace, $open_hour);
            }
            $open_hour_key = false;
            foreach ($posible_openhours as $pkey => $posible_openhour) {
                if (strpos($open_hour, $posible_openhour) !== false) {
                    $open_hour = str_replace($posible_openhour, '', $open_hour);
                    $open_hour_key = $pkey;
                }
            }
            if ($open_hour_key) {
                $open_hours_exit[$open_hour_key] = $open_hour;
            }
            unset($open_hour);
        }

        return $open_hours_exit;
    }

    public static function changeArrayValues($input, $values)
    {
        return isset($values[$input]) ? $values[$input] : null; //TODO need to be checked again if works everywhere
    }

    public static function priceToFloat($price)
    {
        // convert "," to "."
        $price = str_replace(',', '.', $price);
        // remove everything except numbers and dot "."
        $price = preg_replace("/[^0-9\.]/", "", $price);
        // remove all seperators from first part and keep the end
        $price = str_replace('.', '', substr($price, 0, -3)) . substr($price, -3);

        return $price;
    }

    public static function getArrayFromStr($params)
    {
        $attributes_array = isset($params['attr_array']) ? $params['attr_array'] : false;
        $text_array = isset($params['text_array']) ? $params['text_array'] : false;
        if (!$attributes_array || !$text_array) {
            return false;
        }

        $result = [];
        foreach ($text_array as $text) {
            foreach ($attributes_array as $key => $attribute) {
                $text_key = key((array)$text);
                $text_val = $text->$text_key;
                $key_find = $key . "\n";
                $slug = $attribute;
                if (strpos($text_val, $key_find) !== false) {
                    $text_val = str_replace($key_find, '', $text_val);
                    $text_val = Helper::cleanStr([
                        'string' => $text_val,
                        'type' => 'value',
                    ]);
                    $result[$slug] = $text_val;
                }
            }
        }

        return $result;
    }

    public static function replaceInStrings($params)
    {
        $replace_array = isset($params['replace']) ? $params['replace'] : false;
        $replace_in_array = isset($params['replace_in']) ? $params['replace_in'] : false;

        foreach ($replace_in_array as $key => $replace_in) {
            foreach ($replace_array as $replace_key => $replace) {
                if (strpos(" " . $replace_in . " ", $replace_key) === 0) {
                    $replace_in = str_replace($replace_key, $replace, " " . $replace_in . " ");
                }
            }
            $replace_in = trim($replace_in);
            if (is_object($replace_in)) {
                $object_key = key((array)$replace_in);
                $replace_in_array->$object_key = $replace_in;
            } else {
                $replace_in_array[$key] = $replace_in;
            }
        }

        return array_filter(array_map('trim', $replace_in_array));
    }

    public static function phone_cleaner($params)
    {
        if (empty($params['phones'])) {
            return null;
        }

        foreach ($params['phones'] as $phone) {

            $trans = [
                "-" => "",
                " " => "",
                "/" => "",
                "(" => "",
                ")" => "",
                "." => "",
            ];

            $phone = strtr($phone, $trans);

            if(empty($phone)) {
                continue;
            }

            if (!empty($params['country_isocode']) || !empty($params['country_name'])) {

                if (isset($params['country_isocode'])) {
                    $phone_prefix = Helper::info_countries(['isocode' => $params['country_isocode']]);
                } else {
                    $phone_prefix = Helper::info_countries(['name' => strtolower($params['country_name'])]);
                }

                if ($phone_prefix != 'COUNTRY_NOT_IN_LIST' && $phone_prefix != null) {
                    $phone_prefix = $phone_prefix['prefix'];

                    $count_prefix = strlen($phone_prefix);

                    // if phone doesn't have a prefix
                    if (substr($phone, 0, $count_prefix) != $phone_prefix) {
                        // remove one zero from begine if exists
                        if (substr($phone, 0, 1) == "0") {
                            $phone = substr_replace($phone, '', 0, 1);
                        }

                        // remove double zero from begine if exists
                        if (substr($phone, 0, 2) == "00") {
                            $phone = substr_replace($phone, '', 0, 2);
                        }

                        // remove + form phone if exists
                        if ((strpos($phone, '+') !== false)) {
                            $phone = $phone = str_replace("+", "", $phone);
                        }

                        // add + to phone if prefix without + exists
                        $new_phone_prefix = str_replace('+', '', $phone_prefix);

                        if (substr($phone, 0, ($count_prefix - 1)) == $new_phone_prefix) {

                            $phone = '+' . $phone;

                        } else {
                            // add prefix
                            $phone = $phone_prefix . "" . $phone;
                        }

                        // remove nonumeric char except +
                        $phone = preg_replace("/[^0-9+]/", "", $phone);
                    }

                    if($phone == $phone_prefix) {
                        continue;
                    }

                    $phone = str_replace($phone_prefix, '(' . $phone_prefix . ')', $phone);

                    unset($phone_prefix);
                }

            }

            $phones[] = $phone;

        }

        if (empty($phones)) {
            return null;
        }

        return $phones;
    }

    public static function isocode($country)
    {
        $ic = self::info_countries(['name' => strtolower($country)]);
        return $ic !== null ? $ic['isocode'] : '';
    }

    public static function info_countries($params)
    {
        $countries = array(
            array(
                'domain' => 'de',
                'isocode' => 'DE',
                'curreny' => 'EUR',
                'name' => 'Germany',
                'zip' => '5',
                'prefix' => '+49',
            ),
            array(
                'domain' => 'co.uk',
                'isocode' => 'GB',
                'curreny' => 'GBP',
                'name' => 'United Kingdom',
                'prefix' => '+44',
                'zip' => 'GB', // A[A]N[A/N] -- Done
            ),
            array(
                'domain' => 'ro',
                'isocode' => 'RO',
                'curreny' => 'RON',
                'name' => 'Romania',
                'prefix' => '+40',
                'zip' => '6',
            ),
            array(
                'domain' => 'com.au',
                'isocode' => 'AU',
                'curreny' => 'AUD',
                'name' => 'Australia',
                'prefix' => '+61',
                'zip' => '4',
            ),
            array(
                'domain' => 'at',
                'isocode' => 'AT',
                'curreny' => 'EUR',
                'name' => 'Austria',
                'prefix' => '+43',
                'zip' => '4',
            ),
            array(
                'domain' => 'fr',
                'isocode' => 'FR',
                'curreny' => 'EUR',
                'name' => 'France',
                'prefix' => '+33',
                'zip' => '5',
            ),
            array(
                'domain' => 'es',
                'isocode' => 'ES',
                'curreny' => 'EUR',
                'name' => 'Spain',
                'prefix' => '+34',
                'zip' => '5',
            ),
            array(
                'domain' => 'hk',
                'isocode' => 'HK',
                'curreny' => 'HKD',
                'name' => 'Hong Kong',
                'prefix' => '+852',
                'zip' => 'HK',
            ),
            array(
                'domain' => 'com',
                'isocode' => 'US',
                'curreny' => 'USD',
                'name' => 'United States',
                'prefix' => '+1',
                'zip' => 'US', // NNNNN, NNNNN-NNN -- Done
            ),
            array(
                'domain' => 'ca',
                'isocode' => 'CA',
                'curreny' => 'CAD',
                'name' => 'Canada',
                'prefix' => '+1',
                'zip' => 'CA', // ANA NAN
            ),
            array(
                'domain' => 'co',
                'isocode' => 'CO',
                'curreny' => 'COP',
                'name' => 'Colombia',
                'prefix' => '+57',
                'zip' => '6',
            ),
            array(
                'domain' => 'in',
                'isocode' => 'IN',
                'curreny' => 'INR',
                'name' => 'India',
                'prefix' => '+91',
                'zip' => 'IN', //  NNNNNN, NNN NNN
            ),
            array(
                'domain' => 'ph',
                'isocode' => 'PH',
                'curreny' => 'PHP',
                'name' => 'Philippines',
                'prefix' => '+1',
                'zip' => '4',
            ),
            array(
                'domain' => 'ng',
                'isocode' => 'NG',
                'curreny' => 'NGN',
                'name' => 'Nigeria',
                'prefix' => '+63',
                'zip' => '6',
            ),
            array(
                'domain' => 'ch',
                'isocode' => 'CH',
                'curreny' => 'CHF',
                'name' => 'Switzerland',
                'prefix' => '+41',
                'zip' => '4',
            ),
            array(
                'domain' => 'com.ar',
                'isocode' => 'AR',
                'curreny' => 'ARS',
                'name' => 'Argentina',
                'prefix' => '+54',
                'zip' => '4',
            ),
            array(
                'domain' => 'com.mx',
                'isocode' => 'MX',
                'curreny' => 'MXN',
                'name' => 'Mexico',
                'prefix' => '+52',
                'zip' => '5',
            ),
            array(
                'domain' => 'com.pe',
                'isocode' => 'PE',
                'curreny' => 'PEN',
                'name' => 'Peru',
                'prefix' => '+51',
                'zip' => 'PE', // NNNNN CC, NNNN
            ),
            array(
                'domain' => 'com.br',
                'isocode' => 'BR',
                'curreny' => 'BRL',
                'name' => 'Brazil',
                'prefix' => '+55',
                'zip' => 'BR', // NNNNN, NNNNN-NNN
            ),
            array(
                'domain' => 'pt',
                'isocode' => 'PT',
                'curreny' => 'EUR',
                'name' => 'Portugal',
                'prefix' => '+351',
                'zip' => 'PT', // NNNN-NNN
            ),
            [
                'domain' => 'cn',
                'isocode' => 'CN',
                'curreny' => 'CNY',
                'name' => 'China',
                'prefix' => '+86',
                'zip' => '6',
            ],
            [
                'domain' => 'it',
                'isocode' => 'IT',
                'curreny' => 'EUR',
                'name' => 'Italy',
                'prefix' => '+39',
                'zip' => 'RM',
            ],
        );

        $country_info = null;
        if (isset($params['domain'])) {
            $domain = $params['domain'];
        }
        if (isset($params['isocode'])) {
            $get_isocode = $params['isocode'];
        }
        if (isset($params['name'])) {
            $name = $params['name'];
        }

        if (isset($domain)) {
            foreach ($countries as $country) {
                if (strpos($domain, "." . $country['domain'] . "/") !== false) {
                    $country_info = $country;
                }
            }
        } elseif (isset($get_isocode)) {
            foreach ($countries as $country) {
                if ($country['isocode'] == $get_isocode) {
                    $country_info = $country;
                }
            }
        } elseif (isset($name)) {
            foreach ($countries as $country) {
                if (strpos($name, strtolower($country['name'])) !== false) {
                    $country_info = $country;
                }
            }
        }

        return $country_info;
    }

    public static function identifier($params)
    {
        if (isset($params['name']) or isset($params['address'])) {

            $address = $params['address'];

            $to_clean = [
                '-restaurant-' => '',
                '-ristorante-' => '',
                '-restaurante-' => '',
                '-hotel-' => '',
                '-motel-' => '',
                'pizza-' => '',
                '-pizzeria-' => '',
                'shop-' => '',
                '-market-' => '',
                'und-' => '',
                '-and-' => '',
                '-&-' => '',
                '-the-' => '',
                '-la-' => '',
                '-el-' => '',
                '-de-' => '',
            ];

            $name = "-" . str_slug($params['name']) . "-";
            $name = str_replace(array_keys($to_clean), array_values($to_clean), $name);
            $name = trim($name);

            $zip = self::info_countries(['isocode' => $params['country']])['zip'];

            if ($params['zip'] !== false) {
                $zip = $params['zip'];
            } elseif (ctype_digit($zip)) {
                preg_match('/[0-9]{' . $zip . '}/', $address, $zip);
                if (isset($zip[0])) {
                    $zip = $zip[0];
                } else {
                    $zip = $address;
                }
            } else {
                $zip = filter_var($address, FILTER_SANITIZE_NUMBER_INT);
                $zip = str_replace('-', '', $zip);
                if (strlen($zip) > 4) {
                    $to_clean_address = [
                        '-str-' => '',
                        '-straße-' => '',
                        '-street-' => '',
                    ];
                    $zip = "-" . str_slug($zip) . "-";
                    $zip = str_replace(array_keys($to_clean_address),
                        array_values($to_clean_address), $zip);
                } else {
                    $zip = $address;
                }

            }

            return str_slug($params['country'] . "-" . $zip . "-" . $name);
        }
        return '';
    }

    public static function createRating($params)
    {
        if (isset($params['array'])) {

            if (isset($params['reverse'])) {
                $rating = array_reverse($params['array']);
            } else {
                $rating = $params['array'];
            }
            $rating = Helper::fixPairedValues([
                'array' => $rating,
                'slug' => false,
            ]);

            foreach ($rating as $key => $rate) {
                $rating_count = $rate;
                $rating_count = filter_var($rating_count, FILTER_SANITIZE_NUMBER_INT);
                $rating_text = $key;
                $rating_c[] = array(
                    'text' => $rating_text,
                    'stars' => $rating_count,
                );
                unset($rating_count);
                unset($rating_text);
            }
            unset($rating);
            if (!empty($rating_c)) {
                return $rating_c;
            }
        }
    }

    public static function transformSubArrayInArray($items)
    {
        $new_array = [];
        foreach ($items as $item) {
            foreach ($item as $key => $details) {
                if (isset($new_array[$key]) && is_array($details)) {
                    $second_array = $new_array[$key];
                    if (!is_array($second_array)) {
                        $details[] = $second_array;
                    } else {
                        $details = array_merge($details, $second_array);
                    }
                    $new_array[$key] = array_unique($details);
                } else {
                    $new_array[$key] = $details;
                }
            }
        }

        return $new_array;
    }

    public static function multiexplode($delimiters, $string)
    {
        $ready = str_replace($delimiters, $delimiters[0], $string);
        $launch = explode($delimiters[0], $ready);

        return $launch;
    }

    public static function validateOpenHoursArrayFormat($open_hours_arr)
    {

//  openhours": {
//    "wednesday": Array[1][
//      Array[2][
//        "11:30",
//        "15:00"
//      ]
//    ],
//    "thursday": Array[1][
//      Array[2][
//        "11:30",
//        "15:00"
//      ]
//    ],
//    "friday": Array[1][
//      Array[2][
//        "11:30",
//        "15:00"
//      ]
//    ],
//    "saturday": Array[1][
//      Array[2][
//        "12:00",
//        "16:00"
//      ]
//    ]
//  },\\
        if (!isset($open_hours_arr) || !is_array($open_hours_arr) || sizeof($open_hours_arr) === 0 || sizeof($open_hours_arr) > 7) {
            // not valid
            return [];
        } else {
            try {

                foreach (array_keys($open_hours_arr) as $day) {
//                dd($open_hours_arr[$day]);
                    if (!in_array($day, self::$DOW_KEYS)) {
                        // invalid key
                        return [];
                    }
                    if (!is_array($open_hours_arr[$day]) && !sizeof($open_hours_arr[$day]) > 0) {
                        // value of that key is not valid or emtpy
                        return [];
                    }
                    foreach ($open_hours_arr[$day] as $interval) {
                        if (sizeof($interval) !== 2) {
                            return [];
                        }
                        $end = floatval($interval[1]);
                        $start = floatval($interval[0]);
                        if ($start < 0 || $start > 24 || $end < 0 || $end > 24) {//$start > $end
                            return [];
                        }
                    }
                }
            } catch (\Exception $e) {
                return [];
            }
        }
        // valid
        return $open_hours_arr;

    }

    public static function validateIsoCode($isocode)
    {
        return in_array(strtoupper($isocode), ZipCode::getAvailableCountries()) ? strtoupper($isocode) : null;
    }

    public static function validateCoordinates($coords_string)
    {
        $res = $coords_string;
        try {
            $coords = explode(':', $coords_string);
            $res = implode(':', [floatval($coords[0]), floatval($coords[1])]);
        } catch (\Exception $e) {
            $res = null;
        } finally {
            return $res;
        }
    }


    public static function normalize_string_2($string)
    {
        $string = 'áéíóú';
        echo preg_replace('/[\x{0300}-\x{036f}]/u', "", Normalizer::normalize($string, Normalizer::FORM_D));
//aeiou
    }

    public static function words_cleaner($string,
                                         $arrays,
                                         $delimiter = ' ',
                                         $decode_html = true,
                                         $normalize = false,
                                         $remove_numeric_words = true,
                                         $remove_numeric_units = true,
                                         $avoid_empty_result = true,
                                         $force_fix = true,
                                         $sensitive = false,
                                         $removeUnwanted = false
    )
    {
        if (empty($string)) {
            return null;
        }

        if ($normalize) {
            $string = self::normalize_string($string);
        }
        if ($decode_html) {
            $string = html_entity_decode($string);
        }
        if ($force_fix) {
            $string = Encoding::toUTF8($string);
        }

        foreach ($arrays as $arr) {
            $string = $sensitive ? str_replace($arr, '', $string) : str_ireplace($arr, '', $string);
        }

        $string = trim(preg_replace('/\s+/', ' ', $string));

        $words = explode($delimiter, $string);
        $i = 0;

        while ($i < sizeof($words)) {
            if (!empty($words[$i])) {
                if ($remove_numeric_words && (is_numeric($words[$i]) || preg_match('/[0-9]/', $words[$i])) > 0) {
                    $chars_to_remove = 1;
                    if ($remove_numeric_units && $i + 1 < sizeof($words) && in_array(strtolower($words[$i + 1]), self::$MEASURE_UNITS)) {
                        $chars_to_remove = 2;
                    }
                    array_splice($words, $i, $chars_to_remove);
                    continue;
                }
            }

            try {
                if ($removeUnwanted && (in_array($words[$i][0], Helper::$UNWANTED_CHARS) || in_array($words[$i][0], Helper::$UNWANTED_PUNCTUATION))) {
                    $words[$i] = substr($words[$i], 0, strlen($words[$i]) - 1);

                    if (empty($words[$i])) {
                        unset($words[$i]);
                    }
                }

                $words[$i] = trim($words[$i]);
            } catch (\Exception $e) {

            }

            $i++;
        }
        $res = implode($delimiter, $words);

        try {
            if ($removeUnwanted && (in_array($res[-1], Helper::$UNWANTED_CHARS) || in_array($res[-1], Helper::$UNWANTED_PUNCTUATION))) {
                $res = substr($res, 0, strlen($res) - 1);
            }
        } catch (\Exception $e) {

        }

        if ($avoid_empty_result) {
            return isset($res) && !empty($res) ? $res : null;
        } else {
            return $res;
        }
    }

    public static function normalize_string($string)
    {
        // from wordpress https://core.trac.wordpress.org/browser/trunk/src/wp-includes/formatting.php#L1127
//        $string Text that might have accent characters
//        @return string Filtered string with replaced "nice" characters.
        if (!preg_match('/[\x80-\xff]/', $string)) {
            return $string;
        }

        if (self::seems_utf8($string)) {
            $chars = array(
                // Decompositions for Latin-1 Supplement.
                'ª' => 'a',
                'º' => 'o',
                'À' => 'A',
                'Á' => 'A',
                'Â' => 'A',
                'Ã' => 'A',
                'Ä' => 'A',
                'Å' => 'A',
                'Æ' => 'AE',
                'Ç' => 'C',
                'È' => 'E',
                'É' => 'E',
                'Ê' => 'E',
                'Ë' => 'E',
                'Ì' => 'I',
                'Í' => 'I',
                'Î' => 'I',
                'Ï' => 'I',
                'Ð' => 'D',
                'Ñ' => 'N',
                'Ò' => 'O',
                'Ó' => 'O',
                'Ô' => 'O',
                'Õ' => 'O',
                'Ö' => 'O',
                'Ù' => 'U',
                'Ú' => 'U',
                'Û' => 'U',
                'Ü' => 'U',
                'Ý' => 'Y',
                'Þ' => 'TH',
                'ß' => 's',
                'à' => 'a',
                'á' => 'a',
                'â' => 'a',
                'ã' => 'a',
                'ä' => 'a',
                'å' => 'a',
                'æ' => 'ae',
                'ç' => 'c',
                'è' => 'e',
                'é' => 'e',
                'ê' => 'e',
                'ë' => 'e',
                'ì' => 'i',
                'í' => 'i',
                'î' => 'i',
                'ï' => 'i',
                'ð' => 'd',
                'ñ' => 'n',
                'ò' => 'o',
                'ó' => 'o',
                'ô' => 'o',
                'õ' => 'o',
                'ö' => 'o',
                'ø' => 'o',
                'ù' => 'u',
                'ú' => 'u',
                'û' => 'u',
                'ü' => 'u',
                'ý' => 'y',
                'þ' => 'th',
                'ÿ' => 'y',
                'Ø' => 'O',
                // Decompositions for Latin Extended-A.
                'Ā' => 'A',
                'ā' => 'a',
                'Ă' => 'A',
                'ă' => 'a',
                'Ą' => 'A',
                'ą' => 'a',
                'Ć' => 'C',
                'ć' => 'c',
                'Ĉ' => 'C',
                'ĉ' => 'c',
                'Ċ' => 'C',
                'ċ' => 'c',
                'Č' => 'C',
                'č' => 'c',
                'Ď' => 'D',
                'ď' => 'd',
                'Đ' => 'D',
                'đ' => 'd',
                'Ē' => 'E',
                'ē' => 'e',
                'Ĕ' => 'E',
                'ĕ' => 'e',
                'Ė' => 'E',
                'ė' => 'e',
                'Ę' => 'E',
                'ę' => 'e',
                'Ě' => 'E',
                'ě' => 'e',
                'Ĝ' => 'G',
                'ĝ' => 'g',
                'Ğ' => 'G',
                'ğ' => 'g',
                'Ġ' => 'G',
                'ġ' => 'g',
                'Ģ' => 'G',
                'ģ' => 'g',
                'Ĥ' => 'H',
                'ĥ' => 'h',
                'Ħ' => 'H',
                'ħ' => 'h',
                'Ĩ' => 'I',
                'ĩ' => 'i',
                'Ī' => 'I',
                'ī' => 'i',
                'Ĭ' => 'I',
                'ĭ' => 'i',
                'Į' => 'I',
                'į' => 'i',
                'İ' => 'I',
                'ı' => 'i',
                'Ĳ' => 'IJ',
                'ĳ' => 'ij',
                'Ĵ' => 'J',
                'ĵ' => 'j',
                'Ķ' => 'K',
                'ķ' => 'k',
                'ĸ' => 'k',
                'Ĺ' => 'L',
                'ĺ' => 'l',
                'Ļ' => 'L',
                'ļ' => 'l',
                'Ľ' => 'L',
                'ľ' => 'l',
                'Ŀ' => 'L',
                'ŀ' => 'l',
                'Ł' => 'L',
                'ł' => 'l',
                'Ń' => 'N',
                'ń' => 'n',
                'Ņ' => 'N',
                'ņ' => 'n',
                'Ň' => 'N',
                'ň' => 'n',
                'ŉ' => 'n',
                'Ŋ' => 'N',
                'ŋ' => 'n',
                'Ō' => 'O',
                'ō' => 'o',
                'Ŏ' => 'O',
                'ŏ' => 'o',
                'Ő' => 'O',
                'ő' => 'o',
                'Œ' => 'OE',
                'œ' => 'oe',
                'Ŕ' => 'R',
                'ŕ' => 'r',
                'Ŗ' => 'R',
                'ŗ' => 'r',
                'Ř' => 'R',
                'ř' => 'r',
                'Ś' => 'S',
                'ś' => 's',
                'Ŝ' => 'S',
                'ŝ' => 's',
                'Ş' => 'S',
                'ş' => 's',
                'Š' => 'S',
                'š' => 's',
                'Ţ' => 'T',
                'ţ' => 't',
                'Ť' => 'T',
                'ť' => 't',
                'Ŧ' => 'T',
                'ŧ' => 't',
                'Ũ' => 'U',
                'ũ' => 'u',
                'Ū' => 'U',
                'ū' => 'u',
                'Ŭ' => 'U',
                'ŭ' => 'u',
                'Ů' => 'U',
                'ů' => 'u',
                'Ű' => 'U',
                'ű' => 'u',
                'Ų' => 'U',
                'ų' => 'u',
                'Ŵ' => 'W',
                'ŵ' => 'w',
                'Ŷ' => 'Y',
                'ŷ' => 'y',
                'Ÿ' => 'Y',
                'Ź' => 'Z',
                'ź' => 'z',
                'Ż' => 'Z',
                'ż' => 'z',
                'Ž' => 'Z',
                'ž' => 'z',
                'ſ' => 's',
                // Decompositions for Latin Extended-B.
                'Ș' => 'S',
                'ș' => 's',
                'Ț' => 'T',
                'ț' => 't',
                // Euro sign.
                '€' => 'E',
                // GBP (Pound) sign.
                '£' => '',
                // Vowels with diacritic (Vietnamese).
                // Unmarked.
                'Ơ' => 'O',
                'ơ' => 'o',
                'Ư' => 'U',
                'ư' => 'u',
                // Grave accent.
                'Ầ' => 'A',
                'ầ' => 'a',
                'Ằ' => 'A',
                'ằ' => 'a',
                'Ề' => 'E',
                'ề' => 'e',
                'Ồ' => 'O',
                'ồ' => 'o',
                'Ờ' => 'O',
                'ờ' => 'o',
                'Ừ' => 'U',
                'ừ' => 'u',
                'Ỳ' => 'Y',
                'ỳ' => 'y',
                // Hook.
                'Ả' => 'A',
                'ả' => 'a',
                'Ẩ' => 'A',
                'ẩ' => 'a',
                'Ẳ' => 'A',
                'ẳ' => 'a',
                'Ẻ' => 'E',
                'ẻ' => 'e',
                'Ể' => 'E',
                'ể' => 'e',
                'Ỉ' => 'I',
                'ỉ' => 'i',
                'Ỏ' => 'O',
                'ỏ' => 'o',
                'Ổ' => 'O',
                'ổ' => 'o',
                'Ở' => 'O',
                'ở' => 'o',
                'Ủ' => 'U',
                'ủ' => 'u',
                'Ử' => 'U',
                'ử' => 'u',
                'Ỷ' => 'Y',
                'ỷ' => 'y',
                // Tilde.
                'Ẫ' => 'A',
                'ẫ' => 'a',
                'Ẵ' => 'A',
                'ẵ' => 'a',
                'Ẽ' => 'E',
                'ẽ' => 'e',
                'Ễ' => 'E',
                'ễ' => 'e',
                'Ỗ' => 'O',
                'ỗ' => 'o',
                'Ỡ' => 'O',
                'ỡ' => 'o',
                'Ữ' => 'U',
                'ữ' => 'u',
                'Ỹ' => 'Y',
                'ỹ' => 'y',
                // Acute accent.
                'Ấ' => 'A',
                'ấ' => 'a',
                'Ắ' => 'A',
                'ắ' => 'a',
                'Ế' => 'E',
                'ế' => 'e',
                'Ố' => 'O',
                'ố' => 'o',
                'Ớ' => 'O',
                'ớ' => 'o',
                'Ứ' => 'U',
                'ứ' => 'u',
                // Dot below.
                'Ạ' => 'A',
                'ạ' => 'a',
                'Ậ' => 'A',
                'ậ' => 'a',
                'Ặ' => 'A',
                'ặ' => 'a',
                'Ẹ' => 'E',
                'ẹ' => 'e',
                'Ệ' => 'E',
                'ệ' => 'e',
                'Ị' => 'I',
                'ị' => 'i',
                'Ọ' => 'O',
                'ọ' => 'o',
                'Ộ' => 'O',
                'ộ' => 'o',
                'Ợ' => 'O',
                'ợ' => 'o',
                'Ụ' => 'U',
                'ụ' => 'u',
                'Ự' => 'U',
                'ự' => 'u',
                'Ỵ' => 'Y',
                'ỵ' => 'y',
                // Vowels with diacritic (Chinese, Hanyu Pinyin).
                'ɑ' => 'a',
                // Macron.
                'Ǖ' => 'U',
                'ǖ' => 'u',
                // Acute accent.
                'Ǘ' => 'U',
                'ǘ' => 'u',
                // Caron.
                'Ǎ' => 'A',
                'ǎ' => 'a',
                'Ǐ' => 'I',
                'ǐ' => 'i',
                'Ǒ' => 'O',
                'ǒ' => 'o',
                'Ǔ' => 'U',
                'ǔ' => 'u',
                'Ǚ' => 'U',
                'ǚ' => 'u',
                // Grave accent.
                'Ǜ' => 'U',
                'ǜ' => 'u',
            );

            $chars['Ä'] = 'Ae';
            $chars['ä'] = 'ae';
            $chars['Ö'] = 'Oe';
            $chars['ö'] = 'oe';
            $chars['Ü'] = 'Ue';
            $chars['ü'] = 'ue';
            $chars['ß'] = 'ss';
            $chars['Æ'] = 'Ae';
            $chars['æ'] = 'ae';
            $chars['Ø'] = 'Oe';
            $chars['ø'] = 'oe';
            $chars['Å'] = 'Aa';
            $chars['å'] = 'aa';
            $chars['l·l'] = 'll';
            $chars['Đ'] = 'DJ';
            $chars['đ'] = 'dj';

            // Used for locale-specific rules.
//            $locale = get_locale();
//
//            if ( 'de_DE' == $locale || 'de_DE_formal' == $locale || 'de_CH' == $locale || 'de_CH_informal' == $locale ) {
//                $chars['Ä'] = 'Ae';
//                $chars['ä'] = 'ae';
//                $chars['Ö'] = 'Oe';
//                $chars['ö'] = 'oe';
//                $chars['Ü'] = 'Ue';
//                $chars['ü'] = 'ue';
//                $chars['ß'] = 'ss';
//            } elseif ( 'da_DK' === $locale ) {
//                $chars['Æ'] = 'Ae';
//                $chars['æ'] = 'ae';
//                $chars['Ø'] = 'Oe';
//                $chars['ø'] = 'oe';
//                $chars['Å'] = 'Aa';
//                $chars['å'] = 'aa';
//            } elseif ( 'ca' === $locale ) {
//                $chars['l·l'] = 'll';
//            } elseif ( 'sr_RS' === $locale || 'bs_BA' === $locale ) {
//                $chars['Đ'] = 'DJ';
//                $chars['đ'] = 'dj';
//            }

            $string = strtr($string, $chars);
        } else {
            $chars = array();
            // Assume ISO-8859-1 if not UTF-8.
            $chars['in'] = "\x80\x83\x8a\x8e\x9a\x9e"
                . "\x9f\xa2\xa5\xb5\xc0\xc1\xc2"
                . "\xc3\xc4\xc5\xc7\xc8\xc9\xca"
                . "\xcb\xcc\xcd\xce\xcf\xd1\xd2"
                . "\xd3\xd4\xd5\xd6\xd8\xd9\xda"
                . "\xdb\xdc\xdd\xe0\xe1\xe2\xe3"
                . "\xe4\xe5\xe7\xe8\xe9\xea\xeb"
                . "\xec\xed\xee\xef\xf1\xf2\xf3"
                . "\xf4\xf5\xf6\xf8\xf9\xfa\xfb"
                . "\xfc\xfd\xff";

            $chars['out'] = 'EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy';

            $string = strtr($string, $chars['in'], $chars['out']);
            $double_chars = array();
            $double_chars['in'] = array("\x8c", "\x9c", "\xc6", "\xd0", "\xde", "\xdf", "\xe6", "\xf0", "\xfe");
            $double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
            $string = str_replace($double_chars['in'], $double_chars['out'], $string);
        }

        return $string;
    }

    public static function seems_utf8($str)
    {
        self::mbstring_binary_safe_encoding();
        $length = strlen($str);
        self::reset_mbstring_encoding();
        for ($i = 0; $i < $length; $i++) {
            $c = ord($str[$i]);
            if ($c < 0x80) {
                $n = 0; // 0bbbbbbb
            } elseif (($c & 0xE0) == 0xC0) {
                $n = 1; // 110bbbbb
            } elseif (($c & 0xF0) == 0xE0) {
                $n = 2; // 1110bbbb
            } elseif (($c & 0xF8) == 0xF0) {
                $n = 3; // 11110bbb
            } elseif (($c & 0xFC) == 0xF8) {
                $n = 4; // 111110bb
            } elseif (($c & 0xFE) == 0xFC) {
                $n = 5; // 1111110b
            } else {
                return false; // Does not match any model.
            }
            for ($j = 0; $j < $n; $j++) { // n bytes matching 10bbbbbb follow ?
                if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80)) {
                    return false;
                }
            }
        }
        return true;
    }

    public static function mbstring_binary_safe_encoding($reset = false)
    {
        static $encodings = array();
        static $overloaded = null;

        if (is_null($overloaded)) {
            $overloaded = function_exists('mb_internal_encoding') && (ini_get('mbstring.func_overload') & 2);
        }

        if (false === $overloaded) {
            return;
        }

        if (!$reset) {
            $encoding = mb_internal_encoding();
            array_push($encodings, $encoding);
            mb_internal_encoding('ISO-8859-1');
        }

        if ($reset && $encodings) {
            $encoding = array_pop($encodings);
            mb_internal_encoding($encoding);
        }
    }

    public static function reset_mbstring_encoding()
    {
        self::mbstring_binary_safe_encoding(true);
    }

    public static function does_string_contains_numbers($string)
    {
        return is_numeric($string) || (preg_match('/[0-9]/', $string) > 0);
    }

    public static function get_currency_code_from_symbol($symbol)
    {
        $res = null;
        try {
            $symbol = trim($symbol);
            if (in_array($symbol, array_keys(self::$CURRENCY_CODES))) {
                $res = self::$CURRENCY_CODES[$symbol];
            } else if (in_array($symbol, array_values(self::$CURRENCY_CODES))) {
                $i = array_search($symbol, array_values(self::$CURRENCY_CODES));
                if ($i === false) {
                    $res = null;
                } else {
                    $res = array_values(self::$CURRENCY_CODES)[$i];
                }
            } else {
                $res = null;
            }
        } catch (\Exception $e) {

        } finally {
            return $res;
        }

    }

    public static function get_image_ai_tags($urls)
    {
        try {
            $client = new Client();
            $q = 'http://server7.vlindedns.com:5000/tags?imgs=' . $urls . '&token=1Hribxp6A16hFPnWyeeO';
//            $q = 'http://127.0.0.1:5000/tags?imgs=' . $urls . '&token=1Hribxp6A16hFPnWyeeO&clean=false';
            $client->request('GET', $q);
            $resp = $client->getResponse();
            if ($resp->getStatusCode() !== 200) {
                return null;
            }
            $tags = [];
            $res = json_decode($resp->getContent());
            foreach ($res as $obj) {
                $aux = [];
                if (isset($obj->results)) {
                    foreach ($obj->results as $tag => $prob) {
                        if ($prob > 0) {
                            array_push($aux, $tag);
                        }
                    }
                }
                array_push($tags, $aux);
            }
            return $tags;
        } catch (\Exception $e) {
            return null;
//            dd($e->getMessage());
        }

    }

    public static function set_images_ai_tags($result)
    {
        // call api
        if (empty($result)) {
            return $result;
        }
        $urls = '';
        $aux = 0;
        foreach ($result as $item => $value) {
            $urls = $urls . urlencode($value['url']) . ',';
            $aux++;
            if ($aux > 1) {
            }
        }

        $urls = substr($urls, 0, strlen($urls) - 1);
//        dd($urls);
        $ai_tags = Helper::get_image_ai_tags($urls);
        if ($ai_tags === null) {
            return $result;
        }
//            dd($ai_tags);
        for ($i = 0; $i < sizeof($result); $i++) {
            try {
                $result[$i]['images_ai'] = $ai_tags[$i];
            } catch (\Exception $e) {
                break;
            }

        }
        return $result;
    }

    public static function removeFromEndOfString($string, $remove, $strict = true, $trim = true)
    {
        if ($trim) {
            $string = trim($string);
        }

        if ($strict) {
            $string = preg_replace("/\\" . $remove . "$/", "", $string);
        } else {
            $string = rtrim($string, $remove);
        }

        return trim($string);
    }

}