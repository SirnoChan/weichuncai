#删除自言自语
if(isset($_POST['del_tku_sub'])) {
    check_admin_referer('wcc_options');

    if(empty($_POST['del_tku'])) {
		$msg = __('请先选择一个要删除的项目!', 'weichuncai');
	}else {
		foreach($talkself_user['says'] as $k=>$v) {
			if(in_array($k, $_POST['del_tku'])) {
				unset($talkself_user['says'][$k]);
				unset($talkself_user['face'][$k]);
			}
		}
		update_option('sm-wcc-talkself_user', $talkself_user);
		$msg = __('设置已保存!', 'weichuncai');
	}
}

#新增自言自语
if(isset($_POST['talkself_user_sub'])) {
    check_admin_referer('wcc_options');

	if( count($talkself_user['says']) < 50 ) {
		$talkself_user['says'][] = $_POST['talkself_user']['says'];
		$talkself_user['face'][] = $_POST['talkself_user']['face'];
		update_option('sm-wcc-talkself_user', $talkself_user);
		$msg = __('设置已保存!', 'weichuncai');
	}else{
		$msg = __('设置未保存! 你的自言自语设置有点多了哦，删除一些吧.', 'weichuncai');
	}
}

if(isset($_POST['editchuncai'])){
    check_admin_referer('wcc_options');

	$wcc = get_option('sm-weichuncai');
	$wcc['defaultccs'] = $_POST['defaultccs'];
	update_option('sm-weichuncai', $wcc);
	$msg = __('春菜更新成功!', 'weichuncai');
}

$wcc = get_option('sm-weichuncai');
if(isset($_POST['subnotice'])){
    check_admin_referer('wcc_options');

	$wcc = get_option('sm-weichuncai');
	$wcc['notice'] = 	$_POST['notice'];
	$wcc['adminname'] = 	$_POST['adminname'];
	$wcc['isnotice'] = 	$_POST['isnotice'];
    if( $_POST['ques']) {
        foreach( $_POST['ques'] as $k=>$v ) {
            if( trim($v)=='' ) {
                unset($_POST['ques'][$k]);
                unset($_POST['ans'][$k]);
            }
        }
    }
	$wcc['ques'] =		$_POST['ques'];
	$wcc['ans'] =		$_POST['ans'];
	$wccnew = isset($_POST['wccnew']) ? $_POST['wccnew'] : '';
	if($wccnew != ''){
		$wcc['lifetime'][$wccnew] =	time();
		$wcc['ccs'][] = $wccnew;
	}
	update_option('sm-weichuncai', $wcc);
	$msg = __('设置已保存!', 'weichuncai');
}

if(isset($_GET['del'])){
    check_admin_referer('wcc_del_'.$_GET['ccsid']);

	$id = $_GET['ccsid'];
	$preg = '/userdefccs_/i';
	if(preg_match($preg, $id) ) {
		$id = str_replace( 'userdefccs_', '', $id );
		unset($wcc['userdefccs'][$id]);
		unset($wcc['lifetime'][$id]);
		update_option('sm-weichuncai', $wcc);
	}else{
		$pic = get_pic_path($wcc['ccs'][$id]);
		foreach($pic as $k=>$v){
			if(file_exists($v)){@unlink($v);}
		}
		$dir = dirname(__FILE__).'/skin/'.$wcc['ccs'][$id].'/';
		@rmdir($dir);
		unset($wcc['lifetime'][$wcc['ccs'][$id]]);
		unset($wcc['ccs'][$id]);
		update_option('sm-weichuncai', $wcc);
		echo '<script>window.location.href="?page=weichuncai/sm-options.php";</script>';
	}
}
if(isset($_POST['additional'])){
    check_admin_referer('wcc_options');

	$wcc['foods'] = $_POST['foods'];
	$wcc['eatsay'] = $_POST['eatsay'];
	update_option('sm-weichuncai', $wcc);
	$msg = __('附加设置更新成功!', 'weichuncai');
}

#音乐播放列表设置（新增/删除）
if(isset($_POST['wcc_music_sub'])){
    check_admin_referer('wcc_music');

	$wcc = get_option('sm-weichuncai');
	$wcc['music'] = array();
	if(!empty($_POST['wcc_music_name']) && is_array($_POST['wcc_music_name'])) {
		foreach($_POST['wcc_music_name'] as $k => $name) {
			$url = isset($_POST['wcc_music_url'][$k]) ? trim($_POST['wcc_music_url'][$k]) : '';
			$name = trim($name);
			if($name !== '' && $url !== '') {
				$wcc['music'][] = array('name' => $name, 'url' => $url);
			}
		}
	}
	update_option('sm-weichuncai', $wcc);
	$msg = __('音乐列表已保存!', 'weichuncai');
}

#添加春菜页面
if( isset($_GET['cp']) && $_GET['cp'] == 1 && isset($_POST['new_userdef_ccs_sub'])) {
    check_admin_referer('wcc_newccs');

	$wcc['userdefccs'][$_POST['userdefccs']] = array('name'=>$_POST['userdefccs'],
	'face'=>$_POST['face'],
	);
	$wcc['lifetime'][$_POST['userdefccs']] = time();;
	update_option('sm-weichuncai', $wcc);
	$msg = __('添加伪春菜成功!', 'weichuncai');
}

if(isset($msg)){
	echo '<div id="message" class="updated fade"><p>'.$msg.'</p></div>';
}
