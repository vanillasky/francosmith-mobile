<?php

include '../_header.php';

if (!$session->m_no) {
	exit;
}

if ($_SESSION['attendance']) {
	$attendanceNo = $_SESSION['attendance'];
}
else if ($_POST['attendanceNo']) {
	$attendanceNo = $_POST['attendanceNo'];
}
else {
	$attendanceNo = null;
}

if (!$attendanceNo) {
	exit;
}
unset($_SESSION['attendance']);

$query = $db->_query_print('SELECT * FROM gd_attendance WHERE attendance_no=[i] LIMIT 1', $attendanceNo);
$attendance = $db->fetch($query, true);
$attendance['start_date'] = str_replace('-', '.', $attendance['start_date']);
$attendance['end_date'] = str_replace('-', '.', $attendance['end_date']);

$query = $db->_query_print('SELECT * FROM gd_attendance_check WHERE attendance_no=[i] AND member_no=[i] LIMIT 1', $attendanceNo, $session->m_no);
$attendanceCheck = $db->fetch($query, true);
$attendance['check_date_all'] = explode(',', $attendanceCheck['check_date_all']);

$timestamp = time();
$year = $_POST['year'] ? sprintf('%02s', (int)$_POST['year']) : date('Y', $timestamp);
$month = $_POST['month'] ? sprintf('%02s', (int)$_POST['month']) : date('m', $timestamp);
$weekday = date('w', $timestamp);
$weekdayList = array('SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT');

$calendar = array();
$checkedDateList = array();

$_timestamp = strtotime($year.$month.'01');
$_week = 0;
$calendar[$_week] = array();
for ($_weekday = 0; $_weekday < (int)date('w', $_timestamp); $_weekday++) {
	$calendar[$_week][$_weekday] = null;
}
for ($_month = $month, $_day = '01'; $_month == date('m', $_timestamp); $_timestamp = strtotime('+1 day', $_timestamp), $_day = date('d', $_timestamp)) {
	$calendar[$_week][date('w', $_timestamp)] = $_day;
	if (in_array($year.'-'.$_month.'-'.$_day, $attendance['check_date_all'])) $checkedDateList[$_day] = true;
	if (date('w', $_timestamp) == 6) {
		$_week++;
		$calendar[$_week] = array();
	}
}
for ($_weekday = (int)date('w', $_timestamp); $_weekday < 7; $_weekday++) {
	$calendar[$_week][$_weekday] = null;
}

exit(json_encode(array(
    'attendance' => iconv_recursive('EUC-KR', 'UTF-8', $attendance),
    'attendanceNo' => $attendanceNo,
    'calendar' => array(
	'current' => date('Ymd'),
        'year' => $year,
	'month' => $month,
        'weekday' => $weekday,
        'dateList' => $calendar,
	'checkedDateList' => $checkedDateList,
    ),
)));

?>