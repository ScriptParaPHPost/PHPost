<?php 
/**
 * Controlador
 *
 * @name    pages.php
 * @author  PHPost Team
*/
/**********************************\

*	(VARIABLES POR DEFAULT)		*

\*********************************/

	$tsPage = "pages";	// tsPage.tpl -> PLANTILLA PARA MOSTRAR CON ESTE ARCHIVO.

	$tsLevel = 0;		// NIVEL DE ACCESO A ESTA PAGINA. => VER FAQs

	$tsAjax = empty($_GET['ajax']) ? 0 : 1; // LA RESPUESTA SERA AJAX?
	
	$tsContinue = true;	// CONTINUAR EL SCRIPT
	
/*++++++++ = ++++++++*/

	require_once realpath('../../../') . DIRECTORY_SEPARATOR . "header.php";  // INCLUIR EL HEADER

	$tsTitle = $tsCore->settings['titulo'].' - '.$tsCore->settings['slogan']; 	// TITULO DE LA PAGINA ACTUAL

/*++++++++ = ++++++++*/

	// VERIFICAMOS EL NIVEL DE ACCSESO ANTES CONFIGURADO
	$tsLevelMsg = $tsCore->setLevel($tsLevel, true);
	if($tsLevelMsg != 1){	
		$tsPage = 'aviso';
		$tsAjax = 0;
		$smarty->assign("tsAviso",$tsLevelMsg);
		//
		$tsContinue = false;
	}
	//
	if($tsContinue){

/**********************************\

* (VARIABLES LOCALES ESTE ARCHIVO)	*

\*********************************/
    $action = $_GET['action'];
/**********************************\

*	(INSTRUCCIONES DE CODIGO)		*

\*********************************/
	
	$paginas = ['ayuda', 'chat', 'contact', 'protocolo', 'terminos-y-condiciones', 'privacidad', 'dmca', 'cookies-policy'];
	if(!in_array($action, $paginas)) {
		$tsCore->redirectTo($tsCore->settings['url']);
	}
    //
    $smarty->assign("tsAction",$action);
/**********************************\

* (AGREGAR DATOS GENERADOS | SMARTY) *

\*********************************/
	}

if(empty($tsAjax)) {	// SI LA PETICION SE HIZO POR AJAX DETENER EL SCRIPT Y NO MOSTRAR PLANTILLA, SI NO ENTONCES MOSTRARLA.

	$smarty->assign("tsTitle",$tsTitle);	// AGREGAR EL TITULO DE LA PAGINA ACTUAL

	/*++++++++ = ++++++++*/
	require_once TS_ROOT . "footer.php";
	/*++++++++ = ++++++++*/
}