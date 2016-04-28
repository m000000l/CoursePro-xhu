<?php

require_once 'dataProvider.php';
require_once 'response.php';

class DataHandler {

	private $dataProvider = null;

	function __construct() {

		$this -> dataProvider = new DataProvider();

	}

	function cookie() {

		echo $this -> dataProvider -> get_cookie();

	}

	function login() {

		$this -> user = $_POST['user'];
		$result = $this -> dataProvider -> login($this -> user, $_POST['psw'], $_POST['captcha']);

		if (strripos($result, 'ERROR') > 0) {

			Response::json(400, '系统正忙');

		} else if (strripos($result, '欢迎您') > 0) {

			preg_match_all('/<span id="xhxm">(.*?)<\/span>/', $result, $matches);
			$this -> name = str_replace('同学', '', $matches[1][0]);

			Response::json(200, $this -> name, $this -> get_all_data());

		} else {

			$pattern = "/<script language='javascript' defer>alert\('(.*?)'\).*<\/script>/is";
			preg_match_all($pattern, $result, $matches);

			Response::json(400, $matches[1][0]);

		}

	}

	private function timetable_formatter($table) {
		$list = array(
			'mon' => array(
				'1,2' => '',
				'3,4' => '',
				'5,6' => '',
				'7,8' => ''
			),
			'tues' => array(
				'1,2' => '',
				'3,4' => '',
				'5,6' => '',
				'7,8' => ''
			),
			'wed' => array(
				'1,2' => '',
				'3,4' => '',
				'5,6' => '',
				'7,8' => ''
			),
			'thur' => array(
				'1,2' => '',
				'3,4' => '',
				'5,6' => '',
				'7,8' => ''
			),
			'fri' => array(
				'1,2' => '',
				'3,4' => '',
				'5,6' => '',
				'7,8' => ''
			),
			'sat' => array(
				'1,2' => '',
				'3,4' => '',
				'5,6' => '',
				'7,8' => ''
			),
			'sun' => array(
				'1,2' => '',
				'3,4' => '',
				'5,6' => '',
				'7,8' => ''
			)
		);

		$week = array(
			"mon" => "周一",
			"tues" => "周二",
			"wed" => "周三",
			"thur" => "周四",
			"fri" => "周五",
			"sat" => "周六",
			"sun" => "周日"
		);

		$order = array('1,2', '3,4', '5,6', '7,8', '9,10', '9,10,11');

		foreach ($table as $key => $course) {

			foreach ($week as $key => $weekDay) {
				$pos = strpos($course, $weekDay);
				if ($pos) {
					$weekArrayDay = $key;
					foreach ($order as $key => $orderClass) {
						$pos = strpos($course, $orderClass);
						if ($pos) {
							$weekArrayOrder = $orderClass;
							if ($orderClass != '9,10') {
								break;
							}
						}
					}
					break;
				}
			}

			$list[$weekArrayDay][$weekArrayOrder] = $course;

		}

		return $list;
	}

	function timetable() {
		$result = $this -> dataProvider -> get_timetable($this -> user, $this -> name, '2014-2015', '1');

		preg_match_all('/<table id="Table1"[\w\W]*?>([\w\W]*?)<\/table>/', $result, $out);
		$timetable = $out[0][0];

		preg_match_all('/<td [\w\W]*?>([\w\W]*?)<\/td>/', $timetable, $out);
		$td = $out[1];
		$length = count($td);

		//获得课程列表
		for ($i = 0; $i < $length; $i++) {
			$td[$i] = str_replace("<br>", "\n", $td[$i]);
			if (!preg_match_all("/{(.*)}/", $td[$i], $matches)) {
				unset($td[$i]);
			}
		}

		return $this -> timetable_formatter($td);
	}

	private function get_course_data($result, $save_key = null) {
		preg_match_all('/<tr[\w\W]*?>([\w\W]*?)<\/tr>/', $result, $out_tr);
		$tr = $out_tr[1];
		$tr_length = count($tr);
		for ($i = 1; $i < $tr_length; $i++) {
			preg_match_all('/<td>([\w\W]*?)<\/td>/', $tr[$i], $out_td);
			$tr[$i] = $out_td[1];
		}

		unset($tr[0]);

		if (!!$save_key) {
			for ($j = 1; $j < $tr_length; $j++) {
				$td = $tr[$j];
				$td_length = count($td);

				for ($k = 0; $k < $td_length; $k++) {
					if (!in_array($k, $save_key)) {
						unset($td[$k]);
					}
				}
				$tr[$j] = array_values($td);
			}
		}

		return $tr;
	}

	function getScore() {
		$result = $this -> dataProvider -> get_score($_GET['user'], $_GET['name']);
		preg_match_all('/<table class="datelist"[\w\W]*?>([\w\W]*?)<\/table>/', $result, $original_data);
		$save_key = array('0', '1', '3', '6', '7', '8');

		$response['status'] = 'success';
		$response['data']['msg'] = $this -> get_course_data($original_data[0][0], $save_key);
		echo json_encode($response, JSON_UNESCAPED_UNICODE);
	}

	function getFailedCourse() {
		$result = $this -> dataProvider -> get_failed_course($_GET['user'], $_GET['name']);
		preg_match_all('/<table class="datelist"[\w\W]*?>([\w\W]*?)<\/table>/', $result, $original_data);
		$save_key = array('0', '1', '2', '3', '4');

		$response['status'] = 'success';
		$response['data']['msg'] = $this -> get_course_data($original_data[0][0], $save_key);
		echo json_encode($response, JSON_UNESCAPED_UNICODE);
	}

	function getExam() {
		$result = $this -> dataProvider -> get_exam($_GET['user'], $_GET['name']);
		preg_match_all('/<table class="datelist"[\w\W]*?>([\w\W]*?)<\/table>/', $result, $original_data);
		$save_key = array('1', '2', '3', '4', '5', '6');

		$response['status'] = 'success';
		$response['data']['msg'] = $this -> get_course_data($original_data[0][0], $save_key);
		echo json_encode($response, JSON_UNESCAPED_UNICODE);
	}

	function getTrainPlan() {
		$result = $this -> dataProvider -> get_train_plan($_GET['user'], $_GET['name']);
		preg_match_all('/<div id="divDataGrid4" class="divPadding">([\w\W]*?)<\/div>/', $result, $original_data);

		$train_plan = $this -> get_course_data($original_data[0][0]);
		$total_credit = 0;

		foreach ($train_plan as $index => $item) {
			$credit = floatval($item[1]);
			if (!$credit) {
				unset($train_plan[$index]);
			}
			$total_credit += $credit;
		}

		$train_plan = array_values($train_plan);
		$response['status'] = 'success';
		$response['data']['msg'] = $train_plan;
		$response['data']['totalCredit'] = $total_credit;
		echo json_encode($response, JSON_UNESCAPED_UNICODE);
	}

	function getCredits() {
		$result = $this -> dataProvider -> get_credits($_GET['user'], $_GET['name']);
		preg_match_all('/<table class="datelist"[\w\W]*?>([\w\W]*?)<\/table>/', $result, $original_data);
		$save_key = array('2', '3');
		$credits = $this -> get_course_data($original_data[0][0], $save_key);

		$credits_length = count($credits);
		for ($i = 0; $i < $credits_length; $i++) {
			$item = $credits[$i];
		}

		$response['status'] = 'success';
		$response['data']['msg'] = $credits;
		echo json_encode($response, JSON_UNESCAPED_UNICODE);
	}

	function get_all_data() {

		$result = array(
			'timetable' => $this -> timetable()
		);

		return $result;

	}

}