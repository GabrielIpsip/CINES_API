<?php


namespace App\Utils;


use App\Common\Enum\Encoding;
use App\Common\Enum\Lang;
use Exception;

class StringTools
{
    /**
     * Convert string boolean to bool.
     * @param string $str String to evaluate.
     * @return bool True if string contains 'true' else false.
     */
    public static function stringToBool(string $str): bool
    {
        return strtolower($str) === 'true';
    }

    /**
     * Convert string boolean to int.
     * @param string|null $str String to evaluate.
     * @return int|string|null 1 if string contains true, 0 if contains false, else return string.
     */
    public static function stringBooltoInt(?string $str)
    {
        if (strtolower($str) === 'true')
        {
            return 1;
        }
        else if (strtolower($str) === 'false')
        {
            return 0;
        }
        else
        {
            return $str;
        }
    }

    /**
     * Remove special characters in string.
     * @param string $str String to clean.
     * @return string String cleaned.
     */
    public static function cleanString(string $str): string
    {
        return preg_replace("/([^a-zA-Z0-9_])/", "", $str);
    }

    /**
     * To get keywords contained in the initial string.
     * @param string $str String that contains keywords.
     * @return array Array that contains keywords.
     */
    public static function strToSearchKeywords(string $str): array
    {
        return preg_split("/[^a-z0-9àâçéèêëîïôûùüÿñæœ]/i", $str);
    }

    /**
     * Split string between mathematical symbols.
     * @param string $str String to split
     * @return array Elements between symbols.
     */
    public static function splitOperator(string $str): array
    {
        $operators = preg_split("/\+|\/|-|\*/", $str);
        foreach ($operators as &$operator)
        {
            $operator = self::cleanString($operator);
        }
        return $operators;
    }

    /**
     * Check if string is AVG function.
     * @param string $str String to evaluate.
     * @return bool True if is AVG function, else false.
     */
    public static function isAvg(string $str): bool
    {
        return preg_match("/^avg\([a-zA-Z0-9_, ]*\)$/i", $str);
    }

    /**
     * Check if string is SUM function.
     * @param string $str String to evaluate.
     * @return bool True if is SUM function, else false.
     */
    public static function isSum(string $str): bool
    {
        return preg_match("/^sum\([a-zA-Z0-9_, ]*\)$/i", $str);
    }

    /**
     * Check if string is a function.
     * @param string $str String to evaluate.
     * @return bool True if is a function, else false.
     */
    public static function isFunction(string $str): bool
    {
        return StringTools::isAvg($str) || StringTools::isSum($str);
    }

    /**
     * Get operator in function.
     * @param string $str String function.
     * @return array Array that contains operators in function.
     */
    public static function getOperatorFunction(string $str): array
    {
        $formula = StringTools::functionToAddition($str);
        return StringTools::splitOperator($formula);
    }

    /**
     * Convert function to addition.
     * @param string $str String to convert.
     * @return string Addition corresponding to function.
     */
    public static function functionToAddition(string $str): string
    {
        $formula = preg_replace('/[() ]|^sum\(|^avg\(/', '', $str);
        return str_replace(',', '+', $formula);
    }

    /**
     * Split string to comma.
     * @param string $str String to split.
     * @return array Result of split.
     */
    public static function commaSplit(string $str): array
    {
        $result = explode(',', $str);
        return array_filter($result);
    }

    /**
     * Check if all parenthesis are closed.
     * @param string $str String to check.
     * @return bool True if all parenthesis are closed, else false.
     */
    public static function areAllClosedParenthesis(string $str): bool
    {
        return mb_substr_count($str, '(') === mb_substr_count($str, ')');
    }

    /**
     * Check if parenthesis place are correct.
     * @param string $str String to check.
     * @return bool True if is correct, else false.
     */
    public static function correctParenthesis(string $str): bool
    {
        return !preg_match('/[^+\-*\/]\(|[+\-*\/]\)/', $str);
    }

    /**
     * Replace white characters like space by underscore.
     * @param string $str String to replace.
     * @return string String without white characters, replaced by underscore.
     */
    public static function replaceWhiteCharByUnderscore(string $str): string
    {
        return preg_replace('/\s/', '_', $str);
    }

    /**
     * Replace char which could make buggy in the names of the files by underscore.
     * @param string $str String to sanitize.
     * @return string String with underscore in place of buggy char.
     */
    public static function sanitizeFileName(string $str): string
    {
        return str_replace(['"', '\\', '/', '../'], '_', $str);
    }

    /**
     * Encode UTF-8 string to another encoding.
     * @param string $str String to encode.
     * @param string $encoding String encoded.
     */
    public static function encodeString(string &$str, string $encoding)
    {
        $str = iconv(Encoding::UTF8, "$encoding//IGNORE", $str);
    }

    /**
     * Encode UTF-8 string to another encoding.
     * @param string $str String to encode.
     * @param string $encoding String encoded.
     * @return false|string String encoded.
     */
    public static function getEncodedString(string $str, string $encoding)
    {
        return iconv(Encoding::UTF8, "$encoding//IGNORE", $str);
    }

    /**
     * Replace float separator to another locale.
     * @param string $str String to replace.
     * @param string $lang Use Lang enum to get available languages.
     * @return string|string[]|null Number string with new separator.
     */
    public static function numberToLocale(string $str, string $lang): string
    {
        switch ($lang)
        {
            case Lang::fr:
                return preg_replace('/\./', ',', $str);
            case Lang::en:
                return preg_replace('/,/', '.', $str);
        }
        return $str;
    }

    /**
     * Get separator for parameter in url.
     * @param string $url Url which the parameter will be added.
     * @return string Parameter separator to use.
     */
    public static function getUrlParameterSeparator(string $url): string
    {
        $charPos = strpos($url, '?');

        if (!$charPos)
        {
            return '?';
        }
        else
        {
            return '&';
        }
    }

    /**
     * Generate random token.
     * @param int $size Size of token.
     * @return string Token generated.
     * @throws Exception Error to generate token.
     */
    public static function generateToken(int $size): string
    {
        $bytes = random_bytes($size);
        return bin2hex($bytes);
    }

    /**
     * Get params in list http params.
     * @param string $queryString Mist of http params. (ex: token=xxx&toto=test)
     * @return array Array with name params like key and value params like value.
     */
    public static function getRequestFromQueryStringRequest(string $queryString): array
    {
        $params = explode('&', $queryString);
        $paramsArray = [];
        foreach ($params as $param) {
            $param = explode('=', $param);
            $paramsArray[$param[0]] = $param[1];
        }
        return $paramsArray;
    }

    /**
     * Check if regex is like option1|option2|option3...
     * @param string $regex
     * @return bool
     */
    public static function regexIsOptionType(string $regex): bool {
        return preg_match('/(\^\(([a-z0-9_àâçéèêëîïôûùüÿñæœ]+[|)]+)+\$)/i', $regex) ||
            preg_match('/([a-z0-9_àâçéèêëîïôûùüÿñæœ]+\|?)+/i', $regex);
    }
}
