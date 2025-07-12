<?php
/*	FarsiWeb - Persian Date and Time Functions
    Copyright (C) 2000-2015 FarsiWeb.info
    This library is free software; you can redistribute it and/or
    modify it under the terms of the GNU Lesser General Public
    License as published by the Free Software Foundation; either
    version 2.1 of the License, or (at your option) any later version.
    This library is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
    Lesser General Public License for more details.
    You should have received a copy of the GNU Lesser General Public
    License along with this library; if not, write to the Free Software
    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
*/
function jdf($format, $timestamp = '', $none = '', $time_zone = 'Asia/Tehran', $tr_num = 'fa')
{

	$T_sec = 0;/* <= T_sec */

	if ($time_zone != 'local') date_default_timezone_set(($time_zone === '') ? 'Asia/Tehran' : $time_zone);
	$ts = $T_sec + (($timestamp === '') ? time() : tr_num($timestamp));
	$date = explode('_', date('H_i_j_n_O_P_s_w_Y', $ts));
	list($j_y, $j_m, $j_d) = gregorian_to_jalali($date[8], $date[3], $date[2]);
	$doy = ($j_m < 7) ? (($j_m - 1) * 31) + $j_d - 1 : (($j_m - 7) * 30) + $j_d + 185;
	$kab = (((($date[8] - 1969) % 4) == 0) and !(($date[8] - 1969) % 100 == 0) and (($date[8] - 1969) % 400 != 0)) ? 1 : 0;
	$sy = (($date[8] - 1) % 4) == 0;
	$jdoy = ($kab) ? 366 : 365;
	$j_week = ($date[6] < 6) ? $date[6] + 1 : 0;
	$p_week = array('یک شنبه', 'دوشنبه', 'سه شنبه', 'چهارشنبه', 'پنج شنبه', 'جمعه', 'شنبه');
	$p_month = array('فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند');
	$j_y_if = ($j_y > 1300 and $j_y < 1500) ? 1 : 0;
	$j_m_if = ($j_m > 0 and $j_m < 13) ? 1 : 0;
	$j_d_if = ($j_d > 0 and $j_d < 32) ? 1 : 0;
	$format = str_replace(array('Mo', 'Month', 'D', 'W', 'Y', 'yy', 'y', 'm', 'n', 'd', 'j', 'w', 'S', 'g', 'G', 'h', 'H', 'i', 's', 't', 'a', 'A', 'z', 'O', 'P', 'B', 'L'), array($date[3], $p_month[$j_m - 1], $j_d, $p_week[$j_week], $j_y, substr($j_y, 2, 4), substr($j_y, 2, 4), ($j_m > 9) ? $j_m : '0' . $j_m, $j_m, ($j_d > 9) ? $j_d : '0' . $j_d, $j_d, $j_week, 'ام', ($date[0] > 12) ? $date[0] - 12 : $date[0], $date[0], ($date[0] > 12) ? $date[0] - 12 : $date[0], $date[0], $date[1], $date[7], ($j_m != 12) ? (31 - (int)($j_m / 6.5)) : ($kab ? 30 : 29), ($date[0] < 12) ? 'ق.ظ' : 'ب.ظ', ($date[0] < 12) ? 'قبل از ظهر' : 'بعد از ظهر', $doy, $date[4], $date[5], (int)(($ts % 86400) + 3600) / 3.6, $sy), $format);
	if ($tr_num != 'en') {
		$format = str_replace(array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9'), array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'), $format);
	}
	return ($none === '') ? $format : str_replace(array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9), array('', '', '', '', '', '', '', '', '', ''), $format);
}
function gregorian_to_jalali($g_y, $g_m, $g_d, $mod = '')
{
	$g_d_m = array(0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334);
	if ($g_y > 1600) {
		$jy = 979;
		$g_y -= 1600;
	} else {
		$jy = 0;
		$g_y -= 621;
	}
	$gy2 = ($g_m > 2) ? ($g_y + 1) : $g_y;
	$days = (365 * $g_y) + ((int)(($gy2 + 3) / 4)) - ((int)(($gy2 + 99) / 100)) + ((int)(($gy2 + 399) / 400)) - 80 + $g_d + $g_d_m[$g_m - 1];
	$jy += 33 * ((int)($days / 12053));
	$days %= 12053;
	$jy += 4 * ((int)($days / 1461));
	$days %= 1461;
	$jy += (int)(($days - 1) / 365);
	if ($days > 365) $days = ($days - 1) % 365;
	if ($days < 186) {
		$jm = 1 + (int)($days / 31);
		$jd = 1 + ($days % 31);
	} else {
		$jm = 7 + (int)(($days - 186) / 30);
		$jd = 1 + (($days - 186) % 30);
	}
	return ($mod === '') ? array($jy, $jm, $jd) : $jy . $mod . $jm . $mod . $jd;
}
function tr_num($str, $mod = 'en', $mf = '٫')
{
	$num_a = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.');
	$key_a = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', $mf);
	return ($mod == 'fa') ? str_replace($num_a, $key_a, $str) : str_replace($key_a, $num_a, $str);
}
?>
