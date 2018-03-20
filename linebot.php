<?php
//Line bot to report timestamp to google sheet

date_default_timezone_set('Asia/Taipei');
if (!function_exists('hash_equals')) {
    defined('USE_MB_STRING') or define('USE_MB_STRING', function_exists('mb_strlen'));

    function hash_equals($knownString, $userString)
    {
        $strlen = function ($string) {
            if (USE_MB_STRING) {
                return mb_strlen($string, '8bit');
            }

            return strlen($string);
        };

        // Compare string lengths
        if (($length = $strlen($knownString)) !== $strlen($userString)) {
            return false;
        }

        $diff = 0;

        // Calculate differences
        for ($i = 0; $i < $length; $i++) {
            $diff |= ord($knownString[$i]) ^ ord($userString[$i]);
        }
                return $diff === 0;
    }
}



define("LINE_MESSAGING_API_CHANNEL_SECRET", '<SECRET>');
define("LINE_MESSAGING_API_CHANNEL_TOKEN", '<TOKEN>');


require __DIR__."/vendor/autoload.php";
$bot = new \LINE\LINEBot(
	    new \LINE\LINEBot\HTTPClient\CurlHTTPClient(LINE_MESSAGING_API_CHANNEL_TOKEN),
		    ['channelSecret' => LINE_MESSAGING_API_CHANNEL_SECRET]
		);
$signature = $_SERVER["HTTP_".\LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE];
$body = file_get_contents("php://input");
$events = $bot->parseEventRequest($body, $signature);
foreach ($events as $event) {
	if ($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage) {
		$reply_token = $event->getReplyToken();
		$text = $event->getText();
		//$userId = $event->getUserId();
		$displayName = "";
		// Get user profile
		/*$res = $bot->getProfile($userId);
		if ($res->isSucceeded()) {
			$profile = $res->getJSONDecodedBody();
			$displayName = $profile['displayName'];
		}*/

		// replace ， to ,
		$text = str_replace('，', ',', $text);
		if (substr( strtolower($text), 0, 3 ) === "tar") {
			//$resp = $bot->replyText($reply_token, 'substr_count($text, ',')');
			
			if (substr_count($text, ',') < 2) {
				$resp = $bot->replyText($reply_token, '格式錯誤喔, 格式:tar,隊伍,關卡(1-1),到達/離開,時間(option)');
			} else {
				$datas = explode(',', $text);
				if (sizeof($datas) < 4) {
					$resp = $bot->replyText($reply_token, '格式錯誤喔, 格式:tar,隊伍,關卡(1-1),到達/離開,時間(option)');
					return;
				}
				if (empty($datas[4])) {
					$datas[4] = date('G:i:s');
				}
				sendToGoogleForm($datas[1], $datas[2], $datas[3], @$datas[4], $displayName);
				$resp = $bot->replyText($reply_token, $displayName . '儲存成功。隊伍(' . $datas[1] . ') ' . $datas[3] . ' ' . $datas[2] . ' 時間:' . @$datas[4]) ;
			}
		} else {
		//	$resp = $bot->replyText($reply_token, '不要一直'.$text);
		}
	}
}


function sendToGoogleForm($team, $stage, $remark, $time, $recordBy) {
	$headers = array();
	$headers[] = 'Content-Type: application/x-www-form-urlencoded';

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://docs.google.com/forms/d/e/1FAIpQLSe3NsxX0gWhF1M1KGUy1HKaJdQkowag5UZ87cRQIsIvVPVwkQ/formResponse");
	curl_setopt($ch, CURLOPT_POST, true); // 啟用POST
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	$postFields = array(
		"entry.12453275" => $team,
		"entry.1960546971" => $stage,
		"entry.1232234427" => $remark,
		"entry.706994772"=> $time,
		"entry.375153974" => $recordBy
	);

	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
	$content = curl_exec($ch);
	curl_close($ch);
	return $content;
}
