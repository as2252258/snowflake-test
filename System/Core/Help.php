<?php


namespace Snowflake\Core;


/**
 * Class Help
 * @package Snowflake\Snowflake\Core
 */
class Help
{

	/**
	 * @param array $data
	 * @return string
	 */
	public static function toXml(array $data)
	{
		$xml = "<xml>";
		foreach ($data as $key => $val) {
			if (is_numeric($val)) {
				$xml .= "<" . $key . ">" . $val . "</" . $key . ">";
			} else {
				$xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
			}
		}
		$xml .= "</xml>";
		return $xml;
	}


	/**
	 * @param $xml
	 * @return array|mixed
	 */
	public static function toArray($xml)
	{
		if (empty($xml)) {
			return [];
		} else if (is_array($xml)) {
			return $xml;
		}
		if (!Xml::isXml($xml)) {
			$xml = JSON::decode($xml);
		}
		return $xml;

		/*        $matchQuote = '/(<\?xml.*?\?>)?<([a-zA-Z_]+)>(<([a-zA-Z_]+)><!.*?><\/\4>)+<\/\2>/';*/
//        if (!preg_match($matchQuote, $xml)) {
//            return self::jsonToArray($xml);
//        }
//        try {
//            $data = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
//            if ($data !== false) {
//                $data = json_decode(json_encode($data), TRUE);
//            } else {
//                $data = $xml;
//            }
//        } catch (\Exception $exception) {
//            $data = $xml;
//        } finally {
//            return $data;
//        }
	}


	public static function jsonToArray($xml)
	{
		$_xml = json_decode($xml, true);
		if (is_null($_xml)) {
			return $xml;
		}
		return $_xml;
	}

	/**
	 * @param $xml
	 * @return mixed
	 */
	public static function xmlToArray($xml)
	{
		if (is_array($xml)) {
			return $xml;
		}
		if (($data = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)) !== false) {
			return json_decode(json_encode($data), TRUE);
		}
		if (!is_null($json = json_decode($xml, TRUE))) {
			return $json;
		}
		return $xml;
	}

	/**
	 * @param $parameter
	 * @return array|false|string
	 * @throws \Exception
	 */
	public static function toString($parameter)
	{
		if (!is_string($parameter)) {
			$parameter = ArrayAccess::toArray($parameter);
			if (is_array($parameter)) {
				$parameter = JSON::encode($parameter);
			}
		}
		return $parameter;
	}

	/**
	 * @param mixed $json
	 * @return false|mixed|string
	 */
	public static function toJson($json)
	{
		if (is_object($json)) {
			$json = get_object_vars($json);
		}
		if (is_array($json)) {
			return json_encode($json, JSON_UNESCAPED_UNICODE);
		}
		$matchQuote = '/(<\?xml.*?\?>)?<([a-zA-Z_]+)>(<([a-zA-Z_]+)><!.*?><\/\4>)+<\/\2>/';
		if (preg_match($matchQuote, $json)) {
			$json = self::xmlToArray($json);
		} else {
			$json = json_decode($json, true);
		}
		if (!is_array($json)) {
			$json = [];
		}
		return json_encode($json, JSON_UNESCAPED_UNICODE);
	}

	/**
	 * @param int $length
	 * @return string
	 *
	 * 随机字符串
	 */
	public static function random($length = 20)
	{
		$res = [];
		$str = 'abcdefghijklmnopqrstuvwxyz';
		$str .= strtoupper($str) . '1234567890';
		for ($i = 0; $i < $length; $i++) {
			$rand = substr($str, rand(0, strlen($str) - 2), 1);
			if (empty($rand)) {
				$rand = substr($str, strlen($str) - 3, 1);
			}
			array_push($res, $rand);
		}

		return implode($res);
	}

	/**
	 * @param array $array
	 * @param $key
	 * @param $type
	 * @return string
	 */
	public static function sign(array $array, $key, $type)
	{
		ksort($array, SORT_ASC);
		$string = [];
		foreach ($array as $hashKey => $val) {
			if (empty($val)) {
				continue;
			}
			$string[] = $hashKey . '=' . $val;
		}
		$string[] = 'key=' . $key;
		$string = implode('&', $string);
		if ($type == 'MD5') {
			return strtoupper(md5($string));
		} else {
			return hash('sha256', $string);
		}
	}

}
