<?php

/**
 * [UCenter] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_COMSENZ')) {
	exit('Access Denied');
}

function show_msg($error_no, $error_msg = 'ok', $success = 1, $quit = TRUE) {
	if(VIEW_OFF) {
		$error_code = $success ? 0 : constant(strtoupper($error_no));
		$error_msg = empty($error_msg) ? $error_no : $error_msg;
		$error_msg = str_replace('"', '\"', $error_msg);
		$str = "<root>\n";
		$str .= "\t<error errorCode=\"$error_code\" errorMessage=\"$error_msg\" />\n";
		$str .= '</root>';
		send_mime_type_header();
		echo $str;
		exit;
	} else {
		show_header();
		global $step;

		$title = lang($error_no);
		$comment = lang($error_no.'_comment', false);
		$errormsg = '';

		if($error_msg) {
			if(!empty($error_msg)) {
				foreach((array)$error_msg as $k => $v) {
					if(is_numeric($k)) {
						$comment .= "<li><em class=\"red\">".lang($v).'</em></li>';
					}
				}
			}
		}

		if($step > 0) {
			echo "<div class=\"box warnbox\"><h3>$title</h3><ul>$comment</ul>";
		} else {
			echo "</div><div class=\"main\"><b>$title</b><ul style=\"line-height: 200%; margin-left: 30px;\">$comment</ul>";
		}

		if($quit) {
			echo '<br /><span class="red">'.lang('error_quit_msg').'</span><br /><br /><br />';
		}

		echo '<input type="button" class="btn oldbtn" onclick="history.back()" value="'.lang('click_to_back').'" />';

		echo '</div>';

		$quit && show_footer();
	}
}

function check_db($dbhost, $dbuser, $dbpw, $dbname, $tablepre) {
	if(!class_exists('mysqli')) {
		show_msg('mysqli_unsupport', '', 0);
	}

	mysqli_report(MYSQLI_REPORT_OFF);

	$link = new mysqli($dbhost, $dbuser, $dbpw);
	if(!$link || $link->connect_errno > 0) {
		$errno = $link->connect_errno;
		$error = $link->connect_error;
		if($errno == 1045) {
			show_msg('database_errno_1045', $error, 0);
		} elseif($errno == 2003) {
			show_msg('database_errno_2003', $error, 0);
		} else {
			show_msg('database_connect_error', $error, 0);
		}
	} else {
		if($query = ($link->query("SHOW TABLES FROM $dbname"))) {
			if(!$query) {
				return false;
			}
			while($row = ($query->fetch_row())) {
				if(preg_match("/^$tablepre/", $row[0])) {
					return false;
				}
			}
		}
	}
	return true;
}

function check_redis($redishost, $redisport, $redispw) {
	if(!extension_loaded('redis')) {
		show_msg('redis_unsupport', '', 0);
	}

	try {
		$link = new Redis();
		$connent = $link->connect($redishost, $redisport);
		if($redispw) {
			$link->auth($redispw);
		}
		$_tkey = 'ucinst_'.random(5);
		$_tval = time();
		$link->set($_tkey, $_tval);
		if($_tval != $link->get($_tkey)) {
			throw new Exception('Redis get 错误');
		}
		$link->del($_tkey);
		if($link->get($_tkey)) {
			throw new Exception('Redis del 错误');
		}
	} catch (Exception $e) {
		show_msg('redis_connect_error', $e->getMessage(), 0);
	}
	return true;
}

function dirfile_check(&$dirfile_items) {
	foreach($dirfile_items as $key => $item) {
		$item_path = $item['path'];
		if($item['type'] == 'dir') {
			if(!dir_writeable(ROOT_PATH.$item_path)) {
				if(is_dir(ROOT_PATH.$item_path)) {
					$dirfile_items[$key]['status'] = 0;
					$dirfile_items[$key]['current'] = '+r';
				} else {
					$dirfile_items[$key]['status'] = -1;
					$dirfile_items[$key]['current'] = 'nodir';
				}
			} else {
				$dirfile_items[$key]['status'] = 1;
				$dirfile_items[$key]['current'] = '+r+w';
			}
		} else {
			if(file_exists(ROOT_PATH.$item_path)) {
				if(is_writable(ROOT_PATH.$item_path)) {
					$dirfile_items[$key]['status'] = 1;
					$dirfile_items[$key]['current'] = '+r+w';
				} else {
					$dirfile_items[$key]['status'] = 0;
					$dirfile_items[$key]['current'] = '+r';
				}
			} else {
				if(dir_writeable(dirname(ROOT_PATH.$item_path))) {
					$dirfile_items[$key]['status'] = 1;
					$dirfile_items[$key]['current'] = '+r+w';
				} else {
					$dirfile_items[$key]['status'] = -1;
					$dirfile_items[$key]['current'] = 'nofile';
				}
			}
		}
	}
}

function env_check(&$env_items) {
	foreach($env_items as $key => $item) {
		if($key == 'php') {
			$env_items[$key]['current'] = PHP_VERSION;
		} elseif($key == 'attachmentupload') {
			$env_items[$key]['current'] = @ini_get('file_uploads') ? getmaxupload() : 'unknow';
		} elseif($key == 'gdversion') {
			$tmp = function_exists('gd_info') ? gd_info() : [];
			$env_items[$key]['current'] = empty($tmp['GD Version']) ? 'noext' : $tmp['GD Version'];
			unset($tmp);
		} elseif($key == 'diskspace') {
			if(function_exists('disk_free_space')) {
				$env_items[$key]['current'] = floor(disk_free_space(ROOT_PATH) / (1024 * 1024)).'M';
			} else {
				$env_items[$key]['current'] = 'unknow';
			}
		} elseif(isset($item['c'])) {
			$env_items[$key]['current'] = constant($item['c']);
		} elseif($key == 'opcache') {
			$opcache_data = function_exists('opcache_get_configuration') ? opcache_get_configuration() : [];
			$env_items[$key]['current'] = !empty($opcache_data['directives']['opcache.enable']) ? 'enable' : 'disable';
		} elseif($key == 'curl') {
			if(function_exists('curl_init') && function_exists('curl_version')) {
				$v = curl_version();
				$env_items[$key]['current'] = 'enable'.' '.$v['version'];
			} else {
				$env_items[$key]['current'] = 'disable';
			}
		} elseif(isset($item['f'])) {
			$env_items[$key]['current'] = function_exists($item['f']) ? 'enable' : 'disable';
		}

		$env_items[$key]['status'] = 1;
		if($item['r'] != 'notset' && strcmp($env_items[$key]['current'], $item['r']) < 0) {
			$env_items[$key]['status'] = 0;
		}
	}
}

function function_check($func_items) {
	foreach($func_items as $item) {
		function_exists($item) or show_msg('undefine_func', $item, 0);
	}
}

function show_env_result($env_items, $dirfile_items, $func_items) {

	$env_str = $file_str = $dir_str = $func_str = '';
	$error_code = 0;

	foreach($env_items as $key => $item) {
		if($key == 'php' && strcmp($item['current'], $item['r']) < 0) {
			show_msg('php_version_too_low', $item['current'], 0);
		}
		$status = 1;
		if($key == 'redis') {
			$status = extension_loaded('redis');
		}
		if($item['r'] != 'notset') {
			if(intval($item['current']) && intval($item['r'])) {
				if(intval($item['current']) < intval($item['r'])) {
					$status = 0;
					$error_code = ENV_CHECK_ERROR;
				}
			} else {
				if(strcmp($item['current'], $item['r']) < 0) {
					$status = 0;
					$error_code = ENV_CHECK_ERROR;
				}
			}
		}
		if(VIEW_OFF) {
			$env_str .= "\t\t<runCondition name=\"$key\" status=\"$status\" Require=\"{$item['r']}\" Best=\"{$item['b']}\" Current=\"{$item['current']}\"/>\n";
		} else {
			$env_str .= "<tr>\n";
			$env_str .= '<td>'.lang($key)."</td>\n";
			$env_str .= "<td class=\"padleft\">".lang($item['r'])."</td>\n";
			$env_str .= "<td class=\"padleft\">".lang($item['b'])."</td>\n";
			$env_str .= ($status ? "<td class=\"w pdleft1\">" : "<td class=\"nw pdleft1\">").$item['current']."</td>\n";
			$env_str .= "</tr>\n";
		}
	}

	foreach($dirfile_items as $key => $item) {
		$tagname = $item['type'] == 'file' ? 'File' : 'Dir';
		$variable = $item['type'].'_str';

		if(VIEW_OFF) {
			if($item['status'] == 0) {
				$error_code = ENV_CHECK_ERROR;
			}
			$$variable .= "\t\t\t<File name=\"{$item['path']}\" status=\"{$item['status']}\" requirePermisson=\"+r+w\" currentPermisson=\"{$item['current']}\" />\n";
		} else {
			$$variable .= "<tr>\n";
			$$variable .= "<td>{$item['path']}</td><td class=\"w pdleft1\">".lang('writeable')."</td>\n";
			if($item['status'] == 1) {
				$$variable .= "<td class=\"w pdleft1\">".lang('writeable')."</td>\n";
			} elseif($item['status'] == -1) {
				$error_code = ENV_CHECK_ERROR;
				$$variable .= "<td class=\"nw pdleft1\">".lang('nodir')."</td>\n";
			} else {
				$error_code = ENV_CHECK_ERROR;
				$$variable .= "<td class=\"nw pdleft1\">".lang('unwriteable')."</td>\n";
			}
			$$variable .= "</tr>\n";
		}
	}

	if(VIEW_OFF) {

		$str = "<root>\n";
		$str .= "\t<runConditions>\n";
		$str .= $env_str;
		$str .= "\t</runConditions>\n";
		$str .= "\t<FileDirs>\n";
		$str .= "\t\t<Dirs>\n";
		$str .= $dir_str;
		$str .= "\t\t</Dirs>\n";
		$str .= "\t\t<Files>\n";
		$str .= $file_str;
		$str .= "\t\t</Files>\n";
		$str .= "\t</FileDirs>\n";
		$str .= "\t<error errorCode=\"$error_code\" errorMessage=\"\" />\n";
		$str .= '</root>';
		send_mime_type_header();
		echo $str;
		exit;

	} else {

		show_header();

		echo "<div class=\"box\"><h2 class=\"title\">".lang('env_check')."</h2>\n";
		echo "<table class=\"tb\">\n";
		echo "<tr>\n";
		echo "\t<th>".lang('project')."</th>\n";
		echo "\t<th class=\"padleft\">".lang('ucenter_required')."</th>\n";
		echo "\t<th class=\"padleft\">".lang('ucenter_best')."</th>\n";
		echo "\t<th class=\"padleft\">".lang('curr_server')."</th>\n";
		echo "</tr>\n";
		echo $env_str;
		echo "</table></div>\n";

		echo "<div class=\"box\"><h2 class=\"title\">".lang('priv_check')."</h2>\n";
		echo "<table class=\"tb\">\n";
		echo "\t<tr>\n";
		echo "\t<th>".lang('step1_file')."</th>\n";
		echo "\t<th class=\"padleft\">".lang('step1_need_status')."</th>\n";
		echo "\t<th class=\"padleft\">".lang('step1_status')."</th>\n";
		echo "</tr>\n";
		echo $file_str;
		echo $dir_str;
		echo "</table></div>\n";

		foreach($func_items as $item) {
			$status = function_exists($item);
			$func_str .= "<tr>\n";
			$func_str .= "<td>$item()</td>\n";
			if($status) {
				$func_str .= "<td class=\"w pdleft1\">".lang('supportted')."</td>\n";
				$func_str .= "<td class=\"padleft\">".lang('none')."</td>\n";
			} else {
				$error_code = ENV_CHECK_ERROR;
				$func_str .= "<td class=\"nw pdleft1\">".lang('unsupportted')."</td>\n";
				$func_str .= "<td><font color=\"red\">".lang('advice_'.$item)."</font></td>\n";
			}
		}
		echo "<div class=\"box\"><h2 class=\"title\">".lang('func_depend')."</h2>\n";
		echo "<table class=\"tb\">\n";
		echo "<tr>\n";
		echo "\t<th>".lang('func_name')."</th>\n";
		echo "\t<th class=\"padleft\">".lang('check_result')."</th>\n";
		echo "\t<th class=\"padleft\">".lang('suggestion')."</th>\n";
		echo "</tr>\n";
		echo $func_str;
		echo "</table></div>\n";

		show_next_step(2, $error_code);

		show_footer();

	}

}

function show_next_step($step, $error_code) {
	echo "<form action=\"index.php\" method=\"get\">\n";
	echo "<input type=\"hidden\" name=\"step\" value=\"$step\" />";
	if(isset($GLOBALS['hidden'])) {
		echo $GLOBALS['hidden'];
	}
	if($error_code == 0) {
		$nextstep = "<input type=\"button\" class=\"btn oldbtn\" onclick=\"history.back();\" value=\"".lang('old_step')."\"><input type=\"submit\" class=\"btn\" value=\"".lang('new_step')."\">\n";
	} else {
		$nextstep = "<input type=\"button\" class=\"btn\" disabled=\"disabled\" value=\"".lang('not_continue')."\">\n";
	}
	echo "<div class=\"btnbox\"><div class=\"inputbox\">".$nextstep."</div></div>\n";
	echo "</form>\n";
}

function show_form($form_items, $error_msg) {

	global $step;

	if(empty($form_items) || !is_array($form_items)) {
		return;
	}

	show_header();
	show_setting('start');
	show_setting('hidden', 'step', $step);
	$is_first = 1;
	echo '<div id="form_items_'.$step.'">';
	foreach($form_items as $key => $items) {
		global ${'error_'.$key};
		if($is_first == 0) {
			echo '</div>';
		}

		if(!${'error_'.$key}) {
			show_tips('tips_'.$key);
		} else {
			show_error('tips_admin_config', ${'error_'.$key});
		}

		echo '<div class="box">';
		foreach($items as $k => $v) {
			global $$k;
			if(!empty($error_msg)) {
				$value = isset($_POST[$key][$k]) ? $_POST[$key][$k] : '';
			} else {
				if(isset($v['value']) && is_array($v['value'])) {
					if($v['value']['type'] == 'constant') {
						$value = defined($v['value']['var']) ? constant($v['value']['var']) : '';
					} elseif($v['value']['type'] == 'var') {
						$value = $GLOBALS[$v['value']['var']];
					} elseif($v['value']['type'] == 'string') {
						$value = $v['value']['var'];
					}
				} else {
					$value = '';
				}
			}
			if($v['type'] == 'checkbox') {
				$value = '1';
			}
			show_setting($k, $key.'['.$k.']', $value, $v['type'], isset($error_msg[$key][$k]) ? $key.'_'.$k.'_invalid' : '');
		}

		if($is_first) {
			$is_first = 0;
		}
	}
	echo '</div>';
	echo '</div>';
	echo '<div class="btnbox">';
	show_setting('', 'submitname', 'new_step', ($step == 2 ? 'submit|oldbtn' : 'submit'));
	echo '</div>';
	show_setting('end');
	show_footer();
}

function show_license() {
	global $self, $uchidden, $step;
	$next = $step + 1;
	if(VIEW_OFF) {

		show_msg('license_contents', lang('license'));

	} else {

		show_header();

		$license = str_replace('  ', '&nbsp; ', lang('license'));
		$lang_agreement_yes = lang('agreement_yes');
		$lang_agreement_no = lang('agreement_no');

		echo <<<EOT
</div>
<div class="main">
	<div class="licenseblock">$license</div>
	<div class="btnbox">
		<form method="get" autocomplete="off" action="index.php" class="inputbox">
		<input type="hidden" name="step" value="$next">
		<input type="button" class="btn oldbtn" name="exit" value="{$lang_agreement_no}"  onclick="window.close(); return false;">
		<input type="submit" class="btn" name="submit" value="{$lang_agreement_yes}">
		</form>
	</div>
EOT;

		show_footer();

	}
}

function createtable($sql) {
	$type = strtoupper(preg_replace('/^\s*CREATE TABLE\s+.+\s+\(.+?\).*(ENGINE|TYPE)\s*=\s*([a-z]+?).*$/isU', "\\2", $sql));
	$type = in_array($type, ['INNODB', 'MYISAM', 'HEAP', 'MEMORY']) ? $type : 'INNODB';
	return preg_replace('/^\s*(CREATE TABLE\s+.+\s+\(.+?\)).*$/isU', "\\1", $sql)." ENGINE=$type DEFAULT CHARSET=".DBCHARSET.(DBCHARSET === 'utf8mb4' ? ' COLLATE=utf8mb4_unicode_ci' : '');
}

function dir_writeable($dir) {
	$writeable = 0;
	if(!is_dir($dir)) {
		@mkdir($dir);
	}
	if(is_dir($dir)) {
		if($fp = @fopen("$dir/test.txt", 'w')) {
			@fclose($fp);
			@unlink("$dir/test.txt");
			$writeable = 1;
		} else {
			$writeable = 0;
		}
	}
	return $writeable;
}

function dir_clear($dir) {
	global $lang;
	showjsmessage($lang['clear_dir'].' '.str_replace(ROOT_PATH, '', $dir));
	$directory = dir($dir);
	while($entry = $directory->read()) {
		$filename = $dir.'/'.$entry;
		if(is_file($filename)) {
			@unlink($filename);
		}
	}
	$directory->close();
	@touch($dir.'/index.htm');
}

function show_header() {
	define('SHOW_HEADER', TRUE);
	global $step;
	$nostep = $step > 0 ? '' : ' nostep';
	$version = SOFT_VERSION;
	$release = SOFT_RELEASE;
	$install_lang = lang(INSTALL_LANG);
	$title = lang('title_install');
	$titlehtml = '
<svg width="127.78282px" height="22.5px" viewBox="0 0 127.78282 22.5" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
    <defs>
        <linearGradient x1="54.9591121%" y1="0%" x2="54.959052%" y2="100%" id="linearGradient-ose6cjh84e-1">
            <stop stop-color="#E8A833" offset="0%"></stop>
            <stop stop-color="#EBC874" offset="51.5905084%"></stop>
            <stop stop-color="#AE7222" offset="100%"></stop>
        </linearGradient>
    </defs>
    <g id="Discuz" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
        <g id="logo" fill="url(#linearGradient-ose6cjh84e-1)" fill-rule="nonzero">
            <path d="M8.98281976,3.2 L5.28281976,20.7 L7.98281976,20.7 C10.5828198,20.7 12.7828198,20.1 14.4828198,18.9 C16.1828198,17.7 17.6828198,14 18.1828198,11.9 C18.6828198,9.7 18.6828198,6 17.4828198,4.9 C16.2828198,3.7 14.2828198,3.2 11.5828198,3.2 L8.98281976,3.2 Z M3.88281976,1.7 L11.1828198,1.7 C13.2828198,1.7 15.0828198,1.8 16.5828198,2.1 C18.0828198,2.3 19.1828198,2.7 20.0828198,3.2 C21.6828198,4.1 22.7828198,5.3 23.3828198,6.8 C23.9828198,8.3 24.0828198,10 23.6828198,12 C23.1828198,14 22.3828198,15.8 21.1828198,17.2 C19.9828198,18.7 18.2828198,19.9 16.2828198,20.8 C15.0828198,21.3 13.6828198,21.7 12.0828198,22 C10.4828198,22.2 8.58281976,22.4 6.28281976,22.4 L2.28281976,22.4 C0.782819756,22.4 -0.317180244,21.2 0.0828197562,19.5 L3.88281976,1.7 Z M28.5828198,2.4 L33.6828198,2.4 C32.2828198,9.2 30.8828198,15.6 29.4828198,22.3 L26.5828198,22.3 C24.7828198,22.3 24.5828198,21.2 24.8828198,19.9 L28.5828198,2.4 Z M31.8828198,20.6 C34.2828198,20.6 39.9828198,20.7 42.6828198,20.7 C46.0828198,20.5 46.8828198,19.3 47.2828198,16.9 C47.5828198,15.5 44.9828198,14 42.2828198,13 C35.7828198,10.6 34.8828198,5.5 40.3828198,2.9 C42.4828198,1.9 44.8828198,1.6 47.8828198,1.6 C48.6828198,1.6 49.4828198,1.7 50.3828198,1.9 C51.3828198,2.1 52.6828198,2.3 54.3828198,2.8 L53.7828198,4.5 C50.5828198,4 48.9828198,3.6 46.4828198,3.6 C45.3828198,3.6 44.4828198,3.9 43.7828198,4.1 C40.9828198,5.2 42.3828198,8 44.7828198,9.3 C47.6828198,10.8 50.9828198,11.6 51.9828198,13.2 C52.5828198,14.2 53.0828198,15.2 52.7828198,16.6 C52.3828198,18.5 51.3828198,19.6 49.2828198,20.7 C47.1828198,21.8 44.5828198,22.4 41.3828198,22.4 L31.3828198,22.4 L31.8828198,20.6 L31.8828198,20.6 Z M80.6828198,3.6 L78.7828198,13.6 C78.3828198,15.5 77.8828198,17.5 78.5828198,18.3 C79.8828198,20.2 84.8828198,20 86.2828198,18.5 C87.4828198,17.3 88.3828198,15.3 88.7828198,13 L90.5828198,3.4 L95.9828198,3.4 L92.6828198,22 L87.7828198,22 L88.1828198,19.9 C86.0828198,21.7 83.6828198,22.4 80.0828198,22.4 C77.1828198,22.4 75.4828198,21.7 74.2828198,20.2 C73.0828198,18.7 72.7828198,16.6 73.2828198,13.8 L75.2828198,3.7 C73.8828198,3.5 71.0828198,3.6 69.8828198,3.6 C67.4828198,3.6 64.8828198,4.3 63.2828198,5.5 C58.5828198,8.8 57.4828198,20 66.9828198,20.3 C67.7828198,20.4 69.9828198,19.9 70.8828198,19.7 L71.0828198,19.7 L70.7828198,21.7 C70.2828198,21.8 68.5828198,22.2 68.2828198,22.3 C67.2828198,22.5 66.3828198,22.5 65.4828198,22.5 C61.4828198,22.5 58.4828198,21.6 56.4828198,19.6 C54.4828198,17.7 53.7828198,15.1 54.3828198,11.9 C54.8828198,8.9 56.6828198,6.4 59.4828198,4.5 C62.2828198,2.6 65.2828198,1.5 69.6828198,1.5 C73.2828198,1.5 75.3828198,1.5 78.5828198,1.6 C81.1828198,1.7 80.8828198,2.9 80.6828198,3.6 M98.6828198,3.2 L114.98282,3.2 C115.58282,5.4 114.88282,7.4 112.78282,9.2 L100.48282,20.5 L113.58282,20.5 L113.58282,22.4 L95.4828198,22.4 C95.6828198,19.5 95.7828198,18.5 98.4828198,15.9 L109.88282,5.3 L98.4828198,5.3 L98.6828198,3.2 L98.6828198,3.2 Z M122.78282,22.2 L121.18282,22.1 L121.08282,18.8 L122.98282,19 L124.58282,19.1 L124.28282,22.3 L122.78282,22.2 L122.78282,22.2 Z M124.38282,16.4 L121.68282,16.1 C121.78282,14.6 121.28282,3.5 121.18282,2.2 L121.08282,8.8817842e-16 C121.78282,8.8817842e-16 121.68282,8.8817842e-16 124.88282,0.4 C126.88282,0.6 127.18282,0.7 127.78282,0.7 L126.98282,3.5 C126.28282,5.7 124.58282,15.6 124.38282,16.4" id="形状"></path>
        </g>
    </g>
</svg>
'.lang('install_wizard');
	$charset = CHARSET;
	echo <<<EOT
<!DOCTYPE html>
<html>
<head>
<meta charset="$charset" />
<meta name="renderer" content="webkit" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<title>$title</title>
<link rel="stylesheet" href="style.css" type="text/css" media="all" />
<script type="text/javascript">
	function $(id) {
		return document.getElementById(id);
	}

	function showmessage(message) {
		$('notice').value += message + "\\r\\n";
	}
</script>
<meta content="Discuz! Team" name="Copyright" />
</head>
<div class="container{$nostep}">
	<div class="header">
		<h1>$title</h1>
		<span>V$version $install_lang $release</span>
EOT;

	$step > 0 && show_step($step);
}

function show_footer($quit = true) {

	$copy = lang('copyright');

	echo <<<EOT
		<div class="footer">$copy</div>
	</div>
</div>
</body>
</html>
EOT;
	$quit && exit();
}

function showjsmessage($message) {
	if(VIEW_OFF) return;
	echo '<script type="text/javascript">showmessage(\''.addslashes($message).' \');</script>'."\r\n";
	flush();
	ob_flush();
}

function random($length, $numeric = 0) {
	$seed = base_convert(md5(microtime().$_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
	$seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
	if($numeric) {
		$hash = '';
	} else {
		$hash = chr(rand(1, 26) + rand(0, 1) * 32 + 64);
		$length--;
	}
	$max = strlen($seed) - 1;
	for($i = 0; $i < $length; $i++) {
		$hash .= $seed[mt_rand(0, $max)];
	}
	return $hash;
}

function secrandom($length, $numeric = 0, $strong = false) {
	// Thank you @popcorner for your strong support for the enhanced security of the function.
	$chars = $numeric ? ['A', 'B', '+', '/', '='] : ['+', '/', '='];
	$num_find = str_split('CDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz');
	$num_repl = str_split('01234567890123456789012345678901234567890123456789');
	$isstrong = false;
	if(function_exists('random_bytes')) {
		$isstrong = true;
		$random_bytes = function($length) {
			return random_bytes($length);
		};
	} elseif(extension_loaded('mcrypt') && function_exists('mcrypt_create_iv')) {
		// for lower than PHP 7.0, Please Upgrade ASAP.
		$isstrong = true;
		$random_bytes = function($length) {
			$rand = mcrypt_create_iv($length);
			if($rand !== false && strlen($rand) === $length) {
				return $rand;
			} else {
				return false;
			}
		};
	} elseif(extension_loaded('openssl') && function_exists('openssl_random_pseudo_bytes')) {
		// for lower than PHP 7.0, Please Upgrade ASAP.
		// openssl_random_pseudo_bytes() does not appear to cryptographically secure
		// https://github.com/paragonie/random_compat/issues/5
		$isstrong = true;
		$random_bytes = function($length) {
			$rand = openssl_random_pseudo_bytes($length, $secure);
			if($secure === true) {
				return $rand;
			} else {
				return false;
			}
		};
	}
	if(!$isstrong) {
		return $strong ? false : random($length, $numeric);
	}
	$retry_times = 0;
	$return = '';
	while($retry_times < 128) {
		$getlen = $length - strlen($return); // 33% extra bytes
		$bytes = $random_bytes(max($getlen, 12));
		if($bytes === false) {
			return false;
		}
		$bytes = str_replace($chars, '', base64_encode($bytes));
		$return .= substr($bytes, 0, $getlen);
		if(strlen($return) == $length) {
			return $numeric ? str_replace($num_find, $num_repl, $return) : $return;
		}
		$retry_times++;
	}
}

function redirect($url) {

	echo '<script>'.
		"function redirect() {window.location.replace('$url');}\n".
		"setTimeout('redirect();', 0);\n".
		'</script>';
	exit();

}

function validate_ip($ip) {
	return filter_var($ip, FILTER_VALIDATE_IP) !== false;
}

function get_onlineip() {
	$onlineip = $_SERVER['REMOTE_ADDR'];
	if(isset($_SERVER['HTTP_CLIENT_IP']) && validate_ip($_SERVER['HTTP_CLIENT_IP'])) {
		$onlineip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		if(strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') > 0) {
			$exp = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
			$onlineip = validate_ip(trim($exp[0])) ? $exp[0] : $onlineip;
		} else {
			$onlineip = validate_ip($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $onlineip;
		}
	}
	return $onlineip;
}

function config_edit() {
	extract($GLOBALS, EXTR_SKIP);
	$ucsalt = '';
	$ucfounderpw = password_hash($ucfounderpw, PASSWORD_BCRYPT);
	$regdate = time();

	$ucauthkey = generate_key();
	$ucsiteid = generate_key();
	$ucmykey = generate_key();
	$config = "<?php \r\ndefine('UC_DBHOST', '$dbhost');\r\n";
	$config .= "define('UC_DBUSER', '$dbuser');\r\n";
	$config .= "define('UC_DBPW', '$dbpw');\r\n";
	$config .= "define('UC_DBNAME', '$dbname');\r\n";
	$config .= "define('UC_DBCHARSET', '".DBCHARSET."');\r\n";
	$config .= "define('UC_DBTABLEPRE', '$tablepre');\r\n";
	$config .= "define('UC_COOKIEPATH', '/');\r\n";
	$config .= "define('UC_COOKIEDOMAIN', '');\r\n";
	$config .= "define('UC_DBCONNECT', 0);\r\n";
	$config .= "define('UC_CHARSET', '".CHARSET."');\r\n";
	$config .= "define('UC_FOUNDERPW', '$ucfounderpw');\r\n";
	$config .= "define('UC_FOUNDERSALT', '$ucsalt');\r\n";
	$config .= "define('UC_KEY', '$ucauthkey');\r\n";
	$config .= "define('UC_SITEID', '$ucsiteid');\r\n";
	$config .= "define('UC_MYKEY', '$ucmykey');\r\n";
	$config .= "define('UC_DEBUG', false);\r\n";
	$config .= "define('UC_PPP', 20);\r\n";
	$config .= "define('UC_ONLYREMOTEADDR', 1);\r\n";
	$config .= "define('UC_IPGETTER', 'header');\r\n";
	$config .= "define('UC_REDIS_HOST', '$redishost');\r\n";
	$config .= "define('UC_REDIS_PORT', $redisport);\r\n";
	$config .= "define('UC_REDIS_CONNECT', 1);\r\n";
	$config .= "define('UC_REDIS_TIMEOUT', 0);\r\n";
	$config .= "define('UC_REDIS_PASS', '$redispw');\r\n";
	$config .= "define('UC_REDIS_DB', 0);\r\n";
	$config .= "define('UC_REDIS_KEYPREFIX', '$redispre');\r\n";
	$config .= "// define('UC_IPGETTER_HEADER', serialize(array('header' => 'HTTP_X_FORWARDED_FOR')));\r\n";

	file_put_contents(CONFIG, $config);
}

function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {

	// 动态密钥长度, 通过动态密钥可以让相同的 string 和 key 生成不同的密文, 提高安全性
	$ckey_length = 4;

	$key = md5($key ? $key : UC_KEY);
	// a参与加解密, b参与数据验证, c进行密文随机变换
	$keya = md5(substr($key, 0, 16));
	$keyb = md5(substr($key, 16, 16));
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

	// 参与运算的密钥组
	$cryptkey = $keya.md5($keya.$keyc);
	$key_length = strlen($cryptkey);

	// 前 10 位用于保存时间戳验证数据有效性, 10 - 26位保存 $keyb , 解密时通过其验证数据完整性
	// 如果是解码的话会从第 $ckey_length 位开始, 因为密文前 $ckey_length 位保存动态密匙以保证解密正确
	$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
	$string_length = strlen($string);

	$result = '';
	$box = range(0, 255);

	// 产生密钥簿
	$rndkey = [];
	for($i = 0; $i <= 255; $i++) {
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}

	// 打乱密钥簿, 增加随机性
	// 类似 AES 算法中的 SubBytes 步骤
	for($j = $i = 0; $i < 256; $i++) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}

	// 从密钥簿得出密钥进行异或，再转成字符
	for($a = $j = $i = 0; $i < $string_length; $i++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}

	if($operation == 'DECODE') {
		// 这里按照算法对数据进行验证, 保证数据有效性和完整性
		// $result 01 - 10 位是时间, 如果小于当前时间或为 0 则通过
		// $result 10 - 26 位是加密时的 $keyb , 需要和入参的 $keyb 做比对
		if(((int)substr($result, 0, 10) == 0 || (int)substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) === substr(md5(substr($result, 26).$keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return '';
		}
	} else {
		// 把动态密钥保存在密文里, 并用 base64 编码保证传输时不被破坏
		return $keyc.str_replace('=', '', base64_encode($result));
	}

}

function generate_key($length = 32) {
	$random = secrandom($length);
	$info = md5($_SERVER['SERVER_SOFTWARE'].$_SERVER['SERVER_NAME'].(isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '').(isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : '').$_SERVER['HTTP_USER_AGENT'].time());
	$return = '';
	for($i = 0; $i < $length; $i++) {
		$return .= $random[$i].$info[$i];
	}
	return $return;
}

function show_install() {
	if(VIEW_OFF) return;
	?>
	<script type="text/javascript">
		function showmessage(message) {
			document.getElementById('notice').value += message + "\r\n";
		}

		function initinput() {
			window.location = '<?php echo 'index.php?step='.($GLOBALS['step']);?>';
		}
	</script>
	<div>
		<div class="btnbox"><textarea name="notice" style="width: 80%;" readonly="readonly" id="notice"></textarea></div>
		<div class="btnbox">
			<input type="button" name="submit" value="<?php echo lang('install_in_processed'); ?>" disabled class="btn"  id="laststep" onclick="initinput()">
		</div>
	<?php
}

function runquery($sql) {
	global $lang, $tablepre, $db;

	if(!isset($sql) || empty($sql)) return;

	$sql = str_replace("\r", "\n", str_replace(' '.ORIG_TABLEPRE, ' '.$tablepre, $sql));
	$ret = [];
	$num = 0;
	foreach(explode(";\n", trim($sql)) as $query) {
		$ret[$num] = '';
		$queries = explode("\n", trim($query));
		foreach($queries as $query) {
			$ret[$num] .= (isset($query[0]) && $query[0] == '#') || (isset($query[1]) && $query[0].$query[1] == '--') ? '' : $query;
		}
		$num++;
	}
	unset($sql);

	foreach($ret as $query) {
		$query = trim($query);
		if($query) {

			if(substr($query, 0, 12) == 'CREATE TABLE') {
				$name = preg_replace('/CREATE TABLE ([a-z0-9_]+) .*/is', "\\1", $query);
				showjsmessage(lang('create_table').' '.$name.' ... '.lang('succeed'));
				$db->query(createtable($query));
			} else {
				$db->query($query);
			}

		}
	}

}

function charcovert($string) {
	return str_replace('\'', '\\\'', $string);
}

function insertconfig($s, $find, $replace) {
	if(preg_match($find, $s)) {
		$s = preg_replace($find, $replace, $s);
	} else {
		$s .= "\r\n".$replace;
	}
	return $s;
}

function getgpc($k, $t = 'GP') {
	$t = strtoupper($t);
	switch($t) {
		case 'GP' :
			isset($_POST[$k]) ? $var = &$_POST : $var = &$_GET;
			break;
		case 'G':
			$var = &$_GET;
			break;
		case 'P':
			$var = &$_POST;
			break;
		case 'C':
			$var = &$_COOKIE;
			break;
		case 'R':
			$var = &$_REQUEST;
			break;
	}
	return isset($var[$k]) ? $var[$k] : '';
}

function var_to_hidden($k, $v) {
	return "<input type=\"hidden\" name=\"$k\" value=\"$v\" />\n";
}

function fsocketopen($hostname, $port = 80, &$errno = null, &$errstr = null, $timeout = 15) {
	$fp = '';
	if(function_exists('fsockopen')) {
		$fp = @fsockopen($hostname, $port, $errno, $errstr, $timeout);
	} elseif(function_exists('pfsockopen')) {
		$fp = @pfsockopen($hostname, $port, $errno, $errstr, $timeout);
	} elseif(function_exists('stream_socket_client')) {
		$fp = @stream_socket_client($hostname.':'.$port, $errno, $errstr, $timeout);
	}
	return $fp;
}

function dfopen($url, $limit = 0, $post = '', $cookie = '', $bysocket = FALSE, $ip = '', $timeout = 15, $block = TRUE, $encodetype = 'URLENCODE', $allowcurl = TRUE) {
	$return = '';
	$matches = parse_url($url);
	$scheme = strtolower($matches['scheme']);
	$host = $matches['host'];
	$path = !empty($matches['path']) ? $matches['path'].(!empty($matches['query']) ? '?'.$matches['query'] : '') : '/';
	$port = !empty($matches['port']) ? $matches['port'] : ($scheme == 'https' ? 443 : 80);

	if(function_exists('curl_init') && function_exists('curl_exec') && $allowcurl) {
		$ch = curl_init();
		$ip && curl_setopt($ch, CURLOPT_HTTPHEADER, ['Host: '.$host]);
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		// 在提供 IP 地址的同时, 当请求主机名并非一个合法 IP 地址, 且 PHP 版本 >= 5.5.0 时, 使用 CURLOPT_RESOLVE 设置固定的 IP 地址与域名关系
		// 在不支持的 PHP 版本下, 继续采用原有不支持 SNI 的流程
		if(!empty($ip) && filter_var($ip, FILTER_VALIDATE_IP) && !filter_var($host, FILTER_VALIDATE_IP) && version_compare(PHP_VERSION, '5.5.0', 'ge')) {
			curl_setopt($ch, CURLOPT_RESOLVE, ["$host:$port:$ip"]);
			curl_setopt($ch, CURLOPT_URL, $scheme.'://'.$host.':'.$port.$path);
		} else {
			curl_setopt($ch, CURLOPT_URL, $scheme.'://'.($ip ? $ip : $host).':'.$port.$path);
		}
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if($post) {
			curl_setopt($ch, CURLOPT_POST, 1);
			if($encodetype == 'URLENCODE') {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
			} else {
				parse_str($post, $postarray);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postarray);
			}
		}
		if($cookie) {
			curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		}
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$data = curl_exec($ch);
		$status = curl_getinfo($ch);
		$errno = curl_errno($ch);
		curl_close($ch);
		if($errno || $status['http_code'] != 200) {
			return;
		} else {
			return !$limit ? $data : substr($data, 0, $limit);
		}
	}

	if($post) {
		$out = "POST $path HTTP/1.0\r\n";
		$header = "Accept: */*\r\n";
		$header .= "Accept-Language: zh-cn\r\n";
		if($allowcurl) {
			$encodetype = 'URLENCODE';
		}
		$boundary = $encodetype == 'URLENCODE' ? '' : '; boundary='.trim(substr(trim($post), 2, strpos(trim($post), "\n") - 2));
		$header .= $encodetype == 'URLENCODE' ? "Content-Type: application/x-www-form-urlencoded\r\n" : "Content-Type: multipart/form-data$boundary\r\n";
		$header .= "User-Agent: {$_SERVER['HTTP_USER_AGENT']}\r\n";
		$header .= "Host: $host:$port\r\n";
		$header .= 'Content-Length: '.strlen($post)."\r\n";
		$header .= "Connection: Close\r\n";
		$header .= "Cache-Control: no-cache\r\n";
		$header .= "Cookie: $cookie\r\n\r\n";
		$out .= $header.$post;
	} else {
		$out = "GET $path HTTP/1.0\r\n";
		$header = "Accept: */*\r\n";
		$header .= "Accept-Language: zh-cn\r\n";
		$header .= "User-Agent: {$_SERVER['HTTP_USER_AGENT']}\r\n";
		$header .= "Host: $host:$port\r\n";
		$header .= "Connection: Close\r\n";
		$header .= "Cookie: $cookie\r\n\r\n";
		$out .= $header;
	}

	$fpflag = 0;
	$context = [];
	if($scheme == 'https') {
		$context['ssl'] = [
			'verify_peer' => false,
			'verify_peer_name' => false,
			'peer_name' => $host
		];
		if(version_compare(PHP_VERSION, '5.6.0', '<')) {
			$context['ssl']['SNI_enabled'] = true;
			$context['ssl']['SNI_server_name'] = $host;
		}
	}
	if(ini_get('allow_url_fopen')) {
		$context['http'] = [
			'method' => $post ? 'POST' : 'GET',
			'header' => $header,
			'timeout' => $timeout
		];
		if($post) {
			$context['http']['content'] = $post;
		}
		$context = stream_context_create($context);
		$fp = @fopen($scheme.'://'.($ip ? $ip : $host).':'.$port.$path, 'b', false, $context);
		$fpflag = 1;
	} elseif(function_exists('stream_socket_client')) {
		$context = stream_context_create($context);
		$fp = @stream_socket_client(($scheme == 'https' ? 'ssl://' : '').($ip ? $ip : $host).':'.$port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
	} else {
		$fp = @fsocketopen(($scheme == 'https' ? 'ssl://' : '').($scheme == 'https' ? $host : ($ip ? $ip : $host)), $port, $errno, $errstr, $timeout);
	}

	if(!$fp) {
		return '';
	} else {
		stream_set_blocking($fp, $block);
		stream_set_timeout($fp, $timeout);
		if(!$fpflag) {
			@fwrite($fp, $out);
		}
		$status = stream_get_meta_data($fp);
		if(!$status['timed_out']) {
			while(!feof($fp) && !$fpflag) {
				if(($header = @fgets($fp)) && ($header == "\r\n" || $header == "\n")) {
					break;
				}
			}

			$stop = false;
			while(!feof($fp) && !$stop) {
				$data = fread($fp, ($limit == 0 || $limit > 8192 ? 8192 : $limit));
				$return .= $data;
				if($limit) {
					$limit -= strlen($data);
					$stop = $limit <= 0;
				}
			}
		}
		@fclose($fp);
		return $return;
	}
}

function check_env() {

	global $lang, $attachdir;

	$errors = ['quit' => false];
	$quit = false;

	if(!class_exists('mysqli')) {
		$errors[] = 'mysqli_unsupport';
		$quit = true;
	}

	if(!file_exists(DISCUZ_ROOT.'./config.inc.php')) {
		$errors[] = 'config_nonexistence';
		$quit = true;
	} elseif(!is_writeable(DISCUZ_ROOT.'./config.inc.php')) {
		$errors[] = 'config_unwriteable';
		$quit = true;
	}

	$checkdirarray = [
		'attach' => $attachdir,
		'forumdata' => './forumdata',
		'cache' => './forumdata/cache',
		'ftemplates' => './forumdata/templates',
		'threadcache' => './forumdata/threadcaches',
		'log' => './forumdata/logs',
		'uccache' => './uc_client/data/cache'
	];

	foreach($checkdirarray as $key => $dir) {
		if(!dir_writeable(DISCUZ_ROOT.$dir)) {
			$langkey = $key.'_unwriteable';
			$errors[] = $key.'_unwriteable';
			if($key != 'ftemplate') {
				$quit = TRUE;
			}
		}
	}

	$errors['quit'] = $quit;
	return $errors;

}

function show_error($type, $errors = '', $quit = false) {

	global $lang, $step;

	$title = lang($type);
	$comment = lang($type.'_comment', false);
	$errormsg = '';
	if($errors) {
		if(!empty($errors)) {
			foreach((array)$errors as $k => $v) {
				if(is_numeric($k)) {
					$comment .= "<li><em class=\"red\">".lang($v).'</em></li>';
				}
			}
		}
	}

	if($step > 0) {
		echo "<div class=\"desc\"><b>$title</b><ul>$comment</ul>";
	} else {
		echo "</div><div class=\"main\"><b>$title</b><ul style=\"line-height: 200%; margin-left: 30px;\">$comment</ul>";
	}

	if($quit) {
		echo '<br /><span class="red">'.$lang['error_quit_msg'].'</span><br /><br /><br /><br /><br /><br />';
	}

	echo '</div>';

	$quit && show_footer();
}

function show_tips($tip, $title = '', $comment = '', $style = 1) {
	global $lang;
	$title = empty($title) ? lang($tip) : $title;
	$comment = empty($comment) ? lang($tip.'_comment', FALSE) : $comment;
	if($style) {
		echo "<div class=\"desc\"><b>$title</b>";
	} else {
		echo "</div><div class=\"main\">$title<div class=\"desc1 marginbot\"><ul>";
	}
	$comment && print('<br>'.$comment);
	echo '</div>';
}

function show_setting($setname, $varname = '', $value = '', $type = 'text|password|checkbox', $error = '') {
	if($setname == 'start') {
		echo "<form method=\"post\" action=\"index.php\">\n";
		return;
	} elseif($setname == 'end') {
		echo "\n</form>\n";
		return;
	} elseif($setname == 'hidden') {
		echo "<input type=\"hidden\" name=\"$varname\" value=\"$value\">\n";
		return;
	}

	echo "\n".'<div class="inputbox'.($error ? ' red' : '').'">';
	if($type == 'text' || $type == 'password') {
		echo "\n".'<label class="tbopt" for="inst_'.$varname.'">'.(empty($setname) ? '' : lang($setname).':')."</label>\n";
		$value = dhtmlspecialchars($value);
		echo "<input type=\"$type\" id=\"inst_{$varname}\" name=\"$varname\" value=\"$value\" class=\"txt\">";
	} elseif(strpos($type, 'submit') !== FALSE) {
		if(strpos($type, 'oldbtn') !== FALSE) {
			echo "<input type=\"button\" name=\"oldbtn\" value=\"".lang('old_step')."\" class=\"btn oldbtn\" onclick=\"history.back();\">\n";
		}
		$value = empty($value) ? 'new_step' : $value;
		echo "<input type=\"submit\" name=\"$varname\" value=\"".lang($value)."\" class=\"btn\">\n";
	} elseif($type == 'checkbox') {
		if(!is_array($varname) && !is_array($value)) {
			echo "<input type=\"checkbox\" class=\"ckb\" id=\"$varname\" name=\"$varname\" value=\"1\"".($value ? 'checked="checked"' : '')."><label for=\"$varname\">".lang($setname.'_check_label')."</label>\n";
		}
	} else {
		echo $value;
	}

	if($error) {
		$comment = '<div class="comm red">'.(is_string($error) ? lang($error) : lang($setname.'_error')).'</div>';
	} else {
		$comment = lang($setname.'_comment', false);
		if($comment) {
			$comment = '<div class="comm">'.$comment.'</div>';
		}
	}
	echo "$comment\n</div>\n";
	return true;
}

function show_step($step) {

	global $method;

	$laststep = 3;
	$title = lang('step_'.$method.'_title');
	$comment = lang('step_'.$method.'_desc');
	$step_title_1 = lang('step_title_1');
	$step_title_2 = lang('step_title_2');
	$step_title_3 = lang('step_title_3');

	$stepclass = [];
	for($i = 1; $i <= $laststep; $i++) {
		$stepclass[$i] = $i == $step ? 'current' : ($i < $step ? '' : 'unactivated');
	}
	$stepclass[$laststep] .= ' last';

	echo <<<EOT
</div>
<div class="setup">
	<div>
		<div class="step step{$step}">
			<div class="stepnum">{$step}</div>
			<div>
				<h2>$title</h2>
				<p>$comment</p>
			</div>
		</div>
		<div class="stepstat">
			<div class="stepstattxt">
				<div class="$stepclass[1]">$step_title_1</div>
				<div class="$stepclass[2]">$step_title_2</div>
				<div class="$stepclass[3]">$step_title_3</div>
			</div>
			<div class="stepstatbg stepstat{$step}"></div>
		</div>
	</div>
</div>
<div class="main">
EOT;

}

function lang($lang_key, $force = true) {
	return isset($GLOBALS['lang'][$lang_key]) ? $GLOBALS['lang'][$lang_key] : ($force ? $lang_key : '');
}

function check_adminuser($username, $password, $email) {

	@include ROOT_PATH.'./config.inc.php';
	include ROOT_PATH.'./uc_client/client.php';
	$error = '';
	$uid = uc_user_register($username, $password, $email);
	if($uid == -1 || $uid == -2) {
		$error = 'admin_username_invalid';
	} elseif($uid == -4 || $uid == -5 || $uid == -6) {
		$error = 'admin_email_invalid';
	} elseif($uid == -3) {
		$ucresult = uc_user_login($username, $password);
		list($tmp['uid'], $tmp['username'], $tmp['password'], $tmp['email']) = uc_addslashes($ucresult);
		$ucresult = $tmp;
		if($ucresult['uid'] <= 0) {
			$error = 'admin_exist_password_error';
		} else {
			$uid = $ucresult['uid'];
			$email = $ucresult['email'];
			$password = $ucresult['password'];
		}
	}

	if(!$error && $uid > 0) {
		$password = md5($password);
		uc_user_addprotected($username, '');
	} else {
		$uid = 0;
		$error = empty($error) ? 'error_unknow_type' : $error;
	}
	return ['uid' => $uid, 'username' => $username, 'password' => $password, 'email' => $email, 'error' => $error];
}

function dhtmlspecialchars($string, $flags = null) {
	if(is_array($string)) {
		foreach($string as $key => $val) {
			$string[$key] = dhtmlspecialchars($val, $flags);
		}
	} else {
		if($flags === null) {
			$string = str_replace(['&', '"', '<', '>'], ['&amp;', '&quot;', '&lt;', '&gt;'], $string);
			if(strpos($string, '&amp;#') !== false) {
				$string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4}));)/', '&\\1', $string);
			}
		} else {
			if(PHP_VERSION < '5.4.0') {
				$string = htmlspecialchars($string, $flags);
			} else {
				if(strtolower(CHARSET) == 'utf-8') {
					$charset = 'UTF-8';
				} else {
					$charset = 'ISO-8859-1';
				}
				$string = htmlspecialchars($string, $flags, $charset);
			}
		}
	}
	return $string;
}

function send_mime_type_header($type = 'application/xml') {
	header('Content-Type: '.$type);
}

function getmaxupload() {
	$sizeconv = ['B' => 1, 'KB' => 1024, 'MB' => 1048576, 'GB' => 1073741824];
	$sizes = [];
	$sizes[] = ini_get('upload_max_filesize');
	$sizes[] = ini_get('post_max_size');
	$sizes[] = ini_get('memory_limit');
	if(intval($sizes[1]) === 0) {
		unset($sizes[1]);
	}
	if(intval($sizes[2]) === -1) {
		unset($sizes[2]);
	}
	$sizes = preg_replace_callback(
		'/^(\-?\d+)([KMG]?)$/i',
		function($arg) use ($sizeconv) {
			return (intval($arg[1]) * $sizeconv[strtoupper($arg[2]).'B']).'|'.strtoupper($arg[0]);
		},
		$sizes
	);
	natsort($sizes);
	$output = explode('|', current($sizes));
	if(!empty($output[1])) {
		return $output[1];
	} else {
		return ini_get('upload_max_filesize');
	}
}
