<?php
/*** 
TeamToy extenstion info block  
##name WorkHours 
##folder_name work_hours
##author StarRay
##email liangliang.pan@gmail.com
##reversion 1
##desp 为TODO添加估计工时数，剩余工时数，以及优先级
##update_url http://tt2net.sinaapp.com/?c=plugin&a=update_package&name=work_hours
##reverison_url http://tt2net.sinaapp.com/?c=plugin&a=latest_reversion&name=work_hours 
***/

if( !defined('IN') ) die('bad request');

if( !my_sql("SHOW COLUMNS FROM `work_hours`") )
{

	$sql = "CREATE TABLE IF NOT EXISTS `work_hours` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `plan_hours` int(11) NOT NULL DEFAULT  0, 
  `left_hours` int(11) NOT NULL DEFAULT  0, 
  PRIMARY KEY (`id`),
  KEY `tid` (`tid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8";

	run_sql( $sql );
}

$plugin_lang = array();

$plugin_lang['zh_cn'] = array
(
	'PL_WORK_HOURS_TITLE' => '工时估计',
	'PL_WORK_HOURS_PLAN_HOURS' => '估计需要时间',
	'PL_WORK_HOURS_LEFT_HOURS' => '剩余时间'
);

$plugin_lang['zh_tw'] = array
(
	'PL_WORK_HOURS_TITLE' => '工时估计',
	'PL_WORK_HOURS_PLAN_HOURS' => '估计需要时间',
	'PL_WORK_HOURS_LEFT_HOURS' => '剩余时间'
);

$plugin_lang['us_en'] = array
(
	'PL_WORK_HOURS_TITLE' => 'Work Hours',
	'PL_WORK_HOURS_PLAN_HOURS' => 'Plan Hours',
	'PL_WORK_HOURS_LEFT_HOURS' => 'Left Hours'
);

plugin_append_lang( $plugin_lang );

add_action( 'UI_TODO_DETAIL_COMMENTBOX_BEFORE' , 'work_hours_area' );
function work_hours_area($data)
{
	echo render_html( $data , dirname(__FILE__) . DS .'view' . DS . 'work_hours_area.tpl.html' );
	return $data;
}

   

add_action( 'UI_HEAD' , 'work_hours_js' );
function work_hours_js()
{
	echo '<script type="text/javascript" src="plugin/work_hours/app.js"></script>';
	echo '<link href="plugin/work_hours/app.css" rel="stylesheet">';
}

add_action( 'PLUGIN_WORKHOURS_UPDATE' , 'plugin_workhours_update' );
function plugin_workhours_update()
{
	$target = z(t(v('target')));
	if(  $target != 'plan_hours' ||  $target != 'left_hours' ) 
		return render( array( 'code' => 100002 , 'message' => 'bad args' ) , 'rest' );

	$tid = intval(v('tid'));
	if( $tid < 1 ) 
		return render( array( 'code' => 100002 , 'message' => 'bad args' ) , 'rest' );

	$hours = intval(v('hours'));
	if( $hours < 1 ) 
		return render( array( 'code' => 100002 , 'message' => 'bad args' ) , 'rest' );

	$params = array();
	$params['target'] = $target;
	$params['hours'] = $hours;
	$params['tid'] = $tid;

	if($content = send_request( 'workhours_update' ,  $params , token()  ))
	{
		$data = json_decode($content , 1);
		if( $data['err_code'] == 0 )
		{
			return render( array( 'code' => 0) , 'rest' );
		}
		else
			return render( array( 'code' => 100002 , 'message' => 'can not save data' ) , 'rest' );
		//return render( array( 'code' => 0 , 'data' => $data['data'] ) , 'rest' );
	}

	return render( array( 'code' => 100001 , 'message' => 'can not get api content' ) , 'rest' );
}


add_action( 'API_WORKHOURS_UPDATE' , 'api_workhours_update' );
function api_workhours_update()
{
	$target = z(t(v('target')));
	if( !not_empty($target) ) 
		return apiController::send_error(  LR_API_ARGS_ERROR , 'target CAN\'T EMPTY' );
	if($target != 'plan_hours' ||  $target != 'left_hours' ) 
		return render( array( 'code' => 100002 , 'message' => 'bad args' ) , 'rest' );
		
	$tid = intval(v('tid'));
	if( intval( $tid ) < 1 ) return apiController::send_error( LR_API_ARGS_ERROR , 'TID NOT EXISTS' );

	$hours = intval(v('hours'));
	if( intval( $hours ) < 1 ) return apiController::send_error( LR_API_ARGS_ERROR , 'hours NOT CORRECT' );

	// check user
	$tinfo = get_todo_info_by_id( $tid );
	if( (intval($tinfo['details']['is_public']) == 0) && (uid() != $tinfo['owner_uid']) )
		return apiController::send_error( LR_API_FORBIDDEN , 'ONLY PUBLIC TODO CAN UPDATE HOURS BY OTHERS' );


	//check hours record exists
	$hours_info = get_line( "SELECT * FROM `work_hours` WHERE `tid` = '" . $tid . "' LIMIT 1" );
	if($hours_info['uid'] == null){
		$sql = "INSERT INTO `work_hours` ( `tid` ,  `".$target."`, `uid` ) VALUES ( '" 
		. intval($tid) . "' , '" . $hours . "' , '" . intval(uid()) . "') ";

	}else{
		$sql = "UPDATE `work_hours` SET `".$target."` = " . intval($hours) 
		. " WHERE `tid` = " . $tid . " LIMIT 100";
	}

	run_sql( $sql );

	if( db_errno() != 0 )
		return apiController::send_error( LR_API_DB_ERROR , 'DATABASE ERROR ' . mysql_error() );
	else
		return apiController::send_result( get_line("SELECT * FROM `work_hours` WHERE `tid` = '" . intval($tid) . "' LIMIT 1" , db()) ); 

}


// 为user profile 接口追加工时数据
add_filter( 'API_TODO_DETAIL_OUTPUT_FILTER' , 'api_plan_hours_detail'  );
function api_plan_hours_detail($data)
{
	$tid = intval($data['tid']);
	$hours_info = get_line( "SELECT * FROM `work_hours` WHERE `tid` = '" . intval( $tid ) . "' LIMIT 1" );
	if(empty($hours_info)){
		$hours_info["plan_hours"] = 0;
		$hours_info["left_hours"] = 0;
	}
	$data['plan_hours'] = $hours_info["plan_hours"];
	$data['left_hours'] = $hours_info["left_hours"];
	
	return $data;
}




