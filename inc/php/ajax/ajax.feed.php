<?php if ( ! defined('TS_HEADER')) exit('No se permite el acceso directo al script');
/**
 * Controlador AJAX
 *
 * @name    ajax.feed.php
 * @author  Miguel92 & PHPost.es
*/

/**********************************\

* (VARIABLES LOCALES ESTE ARCHIVO)	*

\*********************************/

	// REDEFINIR VARIABLES
	$tsPage = 'php_files/p.live.'.$files[$action]['p'];
	$tsLevel = $files[$action]['n'];
	$tsAjax = empty($files[$action]['p']) ? 1 : 0;

/**********************************\

*	(INSTRUCCIONES DE CODIGO)		*

\*********************************/
	
	// DEPENDE EL NIVEL
	$tsLevelMsg = $tsCore->setLevel($tsLevel, true);
	if($tsLevelMsg != 1) { echo '0: '.$tsLevelMsg['mensaje']; die();}
	 //
	$code = [
      'title' => $tsCore->settings['titulo'],
      'url' => $tsCore->settings['url'],
      'version' => $tsCore->settings['version'],
      'admin' => $tsUser->nick,
      'id' => $tsUser->uid
   ];
	$key = base64_encode(serialize($code));
	$key .= '&verification=' . $tsCore->verification();
	$conexion = "https://zcode.newluckies.com/feed/index.php?key=$key&type=";
	// CODIGO
	switch($action){
		case 'feed-support':
			//<--- CONSULTAR ACTUALIZACIONES OFICIALES Y VERIFICAR VERSI�N ACTUAL DE ESTE SCRIPT
				$json = $tsCore->getUrlContent($conexion . 'support');
				echo $json;
			//--->
		break;
		case 'feed-version':
			/**
			 * Versi�n a 03 de septiembre de 2023 *
			 * PHPost Risus 1.3.0.005 *
			*/
			$time = time();
			$version_now = 'PHPost 1.3.5-24';
			$version_code = str_replace([' ', '.'], '_', strtolower($version_now));
			# ACTUALIZAR VERSI�N
			if($tsCore->settings['version'] != $version_now){
				db_exec([__FILE__, __LINE__], 'query', "UPDATE w_configuracion SET version = '$version_now', version_code = '$version_code' WHERE tscript_id = 1 LIMIT 1");
				db_exec([__FILE__, __LINE__], 'query', "UPDATE `w_stats` SET stats_time_upgrade = $time WHERE stats_no = 1 LIMIT 1");
			}
			//<---
			$json = $tsCore->getUrlContent($conexion . 'version');
			echo $json;
			//--->
		break;
		  default:
				die('0: Este archivo no existe.');
		  break;
	}