<?php if ( ! defined('TS_HEADER')) exit('No se permite el acceso directo al script');
/**
 * Controlador AJAX
 *
 * @name    ajax.posts.php
 * @author  Miguel92 & PHPost.es
*/
/**********************************\

*	(VARIABLES POR DEFAULT)		*

\*********************************/

	// NIVELES DE ACCESO Y PLANTILLAS DE CADA ACCI�N
	$files = array(
      'posts-genbus' => array('n' => 2, 'p' => 'genbus'),
		'posts-preview' => array('n' => 2, 'p' => 'preview'),
		'posts-borrar' =>  array('n' => 2, 'p' => ''),
		'posts-admin-borrar' =>  array('n' => 2, 'p' => ''),
		'posts-votar' =>  array('n' => 2, 'p' => ''),
		'posts-last-comentarios' =>  array('n' => 0, 'p' => 'last-comentarios'),
		//
		'posts-destacados' => array('n' => 0, 'p' => 'destacados'),
		'posts-recientes' => array('n' => 0, 'p' => 'destacados'),
	);

/**********************************\

* (VARIABLES LOCALES ESTE ARCHIVO)	*

\*********************************/

	// REDEFINIR VARIABLES
	$tsPage = 'php_files/p.posts.'.$files[$action]['p'];
	$tsLevel = $files[$action]['n'];
	$tsAjax = empty($files[$action]['p']) ? 1 : 0;

/**********************************\

*	(INSTRUCCIONES DE CODIGO)		*

\*********************************/
	
	// DEPENDE EL NIVEL
	$tsLevelMsg = $tsCore->setLevel($tsLevel, true);
	if($tsLevelMsg != 1) { echo '0: '.$tsLevelMsg['mensaje']; die();}
	// CLASE
	require('../class/c.posts.php');
	$tsPosts = new tsPosts();
	// CODIGO
	switch($action){
		case 'posts-genbus':
			//<--
                $do = isset($_GET['do']) ? htmlspecialchars($_GET['do']) : '';
                $q = $tsCore->setSecure($_POST['q']);
                //
                if($do == 'search'){
                    $smarty->assign("tsPosts",$tsPosts->simiPosts($q));   
                }elseif($do == 'generador'){
                    $tags = $tsPosts->genTags($q);
                    $smarty->assign("tsTags",$tags);
                }
                //
                $smarty->assign("tsDo",$do);
			//-->
		break;
		case 'posts-preview':
			//<--
				$smarty->assign("tsPreview",$tsPosts->getPreview());
			//-->
		break;
		case 'posts-borrar':
			//<--
				echo $tsPosts->deletePost();
			//-->
		break;
		case 'posts-admin-borrar':
			//<--
				echo $tsPosts->deleteAdminPost();
			//-->
		break;
		case 'posts-votar':
			//<--
				echo $tsPosts->votarPost();
			//-->
		break;
		case 'posts-last-comentarios':
			//<--
			    $smarty->assign("tsComments",$tsPosts->getLastComentarios());
			//-->
		break;
		case 'posts-destacados':
		case 'posts-recientes':	
			$fijado = ($action === 'posts-destacados') ? true : false;
			$tsLastPosts = $tsPosts->getLastPosts('', $fijado);
    		$smarty->assign("tsPosts", $tsLastPosts['data']);
        	$smarty->assign("tsPages", $tsLastPosts['pages']);
		break;
	}