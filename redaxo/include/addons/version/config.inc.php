<?php

/**
 * Version
 *
 * @author jan@kristinus.de
 *
 * @package redaxo4
 * @version $Id: config.inc.php,v 1.13 2008/03/26 21:06:37 kills Exp $
 */

$mypage = "version";
$REX['ADDON']['rxid'][$mypage] = '461';
// $REX['ADDON']['page'][$mypage] = $mypage;
// $REX['ADDON']['name'][$mypage] = 'Version';
// $REX['ADDON']['perm'][$mypage] = 'version[]';
$REX['ADDON']['version'][$mypage] = '0.2';
$REX['ADDON']['author'][$mypage] = 'Jan Kristinus';
$REX['ADDON']['supportpage'][$mypage] = 'forum.redaxo.de';

$REX['EXTPERM'][] = 'version[only_working_version]';
// $REX['EXTPERM'][] = 'version[admin]';

if($REX["REDAXO"])
{
	$I18N_A461 = new i18n($REX['LANG'], $REX['INCLUDE_PATH'].'/addons/'.$mypage.'/lang/');
}



// ***** an EPs andocken
rex_register_extension('ART_INIT', 'rex_version_initArticle');
function rex_version_initArticle($params)
{
	global $REX;

	$version = rex_request("rex_version","int");
	if($version == "")
		return;
		
	if(!isset($_SESSION))
		session_start();

	$REX["LOGIN"] = new rex_backend_login($REX['TABLE_PREFIX'] .'user');
	if ($REX['PSWFUNC'] != '')
	  $REX_LOGIN->setPasswordFunction($REX['PSWFUNC']);

	if ($REX["LOGIN"]->checkLogin() !== true)
		return;
	
	$REX["USER"] = &$REX["LOGIN"]->USER;

  $params["article"]->setSliceRevision($version);
	$params["article"]->getContentAsQuery();
	$params["article"]->setEval(TRUE);

}

rex_register_extension('PAGE_CONTENT_HEADER', 'rex_version_header');
function rex_version_header($params)
{

	global $REX,$I18N_A461;

  $return = "";

	$rex_version_article = $REX["LOGIN"]->getSessionVar("rex_version_article");
	if(!is_array($rex_version_article))$
		$rex_version_article = array();
	
	$func = rex_request("rex_version_func","string");
	switch($func)
	{
		case("work_on_live"):
			unset($rex_version_article[$params["article_id"]]);
			$params["slice_revision"] = 0;
		break;
		case("work_on_preview"):
			$rex_version_article[$params["article_id"]] = 1;
			$params["slice_revision"] = 1;
		break;
		case("copy_work_to_live"):
		  if($REX["USER"]->isValueOf("rights","version[only_working_version]"))
		  {
				require $REX['INCLUDE_PATH'].'/addons/version/functions/function_rex_copyrevisioncontent.inc.php';
				// rex_copyRevisionContent($article_id,$clang,$from_revision_id, $to_revision_id, $gc->getValue("id"),$delete_to_revision);
				rex_copyRevisionContent($params["article_id"],$params["clang"],1, 0, 0, TRUE);
		  	$return .= rex_info($I18N_A461->msg("version_info_working_version_to_live"));
		  }
		break;
		case("copy_live_to_work"):
			require $REX['INCLUDE_PATH'].'/addons/version/functions/function_rex_copyrevisioncontent.inc.php';
			// rex_copyRevisionContent($article_id,$clang,$from_revision_id, $to_revision_id, $gc->getValue("id"),$delete_to_revision);
			rex_copyRevisionContent($params["article_id"],$params["clang"],0, 1, 0, TRUE);
		  $return .= rex_info($I18N_A461->msg("version_info_live_version_to_working"));
		break;
	}
	
	$REX["LOGIN"]->setSessionVar("rex_version_article", $rex_version_article);

	if(isset($rex_version_article[$params["article_id"]]))
		$params["slice_revision"] = 1;

  if($REX["USER"]->isValueOf("rights","version[only_working_version]"))
		$params["slice_revision"] = 1;

	$cl_work = '';
	if($params["slice_revision"] == 1)
		$cl_work = ' class="active"';

	$cl_live = '';
	if($params["slice_revision"] == 0)
		$cl_live = ' class="active"';
		
	$link = 'index.php?page='.$params["page"].'&article_id='.$params["article_id"].'&clang='.$params["clang"];

	$return .= '
		<div id="rex-version-header">
				<div class="rex-version-header"><ul>
	';

  if($REX["USER"]->isValueOf("rights","version[only_working_version]"))
	{
		$return .= '<li'.$cl_work.'><a href="'.$link.'&rex_version_func=work_on_preview">'.$I18N_A461->msg("version_workingversion").'</a></li>';
		$return .= '<li><a href="'.$link.'&rex_version_func=copy_live_to_work">'.$I18N_A461->msg("version_copy_live_to_workingversion").'</a></li>';
	  $return .= '<li><a href="/'.rex_getUrl($params["article_id"],$params["clang"],array("rex_version"=>1)).'" target="_blank">'.$I18N_A461->msg("version_preview").'</a></li>';
	}else
	{
		$return .= '<li'.$cl_live.'><a href="'.$link.'&rex_version_func=work_on_live">'.$I18N_A461->msg("version_liveversion").'</a></li>';
		$return .= '<li'.$cl_work.'><a href="'.$link.'&rex_version_func=work_on_preview">'.$I18N_A461->msg("version_workingversion").'</a></li>';
		$return .= '<li><a href="'.$link.'&rex_version_func=copy_work_to_live">'.$I18N_A461->msg("version_working_to_live").'</a></li>';
		$return .= '<li><a href="'.$link.'&rex_version_func=copy_live_to_work">'.$I18N_A461->msg("version_copy_live_to_workingversion").'</a></li>';
	  $return .= '<li><a href="../'.rex_getUrl($params["article_id"],$params["clang"],array("rex_version"=>1)).'" target="_blank">'.$I18N_A461->msg("version_preview").'</a></li>';
	}


	$return .= '
			</ul></div>
			<div style="clear:both;"></div>

			<style>
#rex-version-header { background-color:#f0efeb; margin:0px; margin-bottom:10px;}
#rex-version-header ul{ margin:0px; margin-left:50px;}
#rex-version-header li { list-style:square; float:left; margin-left:10px; margin-right:15px; line-height:30px;}
#rex-version-header li.active a{ color:#000;}
			</style>
			
		</div>
	';
	
	return $return;
}