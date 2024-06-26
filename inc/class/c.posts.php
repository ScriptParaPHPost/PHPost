<?php if ( ! defined('TS_HEADER')) exit('No se permite el acceso directo al script');
/**
 * Clase para el manejo de los posts
 *
 * @name    c.posts.php
 * @author  Miguel92 & PHPost.es
 */

class tsPosts {

	/** 
	 * isAdmod($fix, $add)
	 * @access public
	 * @param string
	 * @param string
	 * @return string
	*/
	private function isAdmod(string $fix = 'u.', string $add = '') {
		global $tsCore, $tsUser;
		//
		$isAdmod = ($tsUser->is_admod AND (int)$tsCore->settings['c_see_mod'] === 1) ? '' : "AND {$fix}user_activo = 1 AND {$fix}user_baneado = 0 $add";
		//
		return $isAdmod;
	}
	
	/** 
	 * isAdmodPost($fix, $add)
	 * @access public
	 * @param string
	 * @param string
	 * @return string
	*/
	private function isAdmodPost(string $fixu = 'u.', string $fixp = 'p.', string $add = '') {
      $tsCore = new tsCore;
      $tsUser = new tsUser;
      //
      $isAdmodPost = ($tsUser->is_admod && $tsCore->settings['c_see_mod'] == 1) ? " {$fixp}post_id > 0 " : " {$fixu}user_activo = 1 && {$fixu}user_baneado = 0 && {$fixp}post_status = 0";
      //
      return $isAdmodPost;
	}

	/**
	 * Acortador de post autom�tico 
	 * @author KMario19
	 * Formateado por
	 * @author Miguel92
	 * @link https://www.phpost.net/foro/topic/24984-mod-acortador-de-post-autom%C3%A1tico/
	*/
	public function short_url_post() {
		global $tsCore, $tsUser;
		# Obtenemos el nombre del post!
		$post = (int)$_GET['p'];
		# Adicionamos si es administrador o no! 
		$admod = self::isAdmod();
		# Buscamos el post en la base
		$q = db_exec('fetch_assoc', $search = db_exec([__FILE__, __LINE__], 'query', "SELECT p.post_id, p.post_title, p.post_category, p.post_user, u.user_name, c.* FROM p_posts AS p LEFT JOIN u_miembros AS u ON p.post_user = u.user_id LEFT JOIN p_categorias AS c ON p.post_category = c.cid WHERE p.post_id = $post AND p.post_status = 0 {$admod}"));
		# Si no existe redirecciomos a la p�gina posts
		if(!db_exec('num_rows', $search)){
			$tsCore->redirectTo($tsCore->settings['url'].'/posts/');
			die;
		}
		$tsCore->redirectTo("{$tsCore->settings['url']}/posts/{$q['c_seo']}/{$q['post_id']}/{$tsCore->setSEO($q['post_title'], '-')}.html");
	}

	/** 
	 * simiPosts($q, $like)
	 * @access public
	 * @param string
	 * @param bool
	 * @return array
	 */
	public function simiPosts(string $q = '', bool $like = true) {
		global $tsUser, $tsCore;
		// Es administrador o moderador?...
		$isAdmod = self::isAdmod();
		// Modo de busqueda
		$typeSearch = $like ? "p.post_title LIKE '%$q%'" : "MATCH(p.post_title) AGAINST('$q' IN BOOLEAN MODE)";
		// Buscamos posts con el t�tulo similar...
		$data = result_array(db_exec([__FILE__, __LINE__], 'query', "SELECT p.post_id, p.post_title, c.c_seo FROM p_posts AS p LEFT JOIN u_miembros AS u ON u.user_id = p.post_user LEFT JOIN p_categorias AS c ON c.cid = p.post_category WHERE p.post_status = 0 $isAdmod && $typeSearch ORDER BY RAND() DESC LIMIT 5"));
		//
		return $data;
	}

	/** 
	 * genTags($q)
	 * @access public
	 * @param string
	 * @return string
	*/
	public function genTags(string $q = ''){
		$texto = preg_replace('/ {2,}/si', " ", trim(preg_replace("/[^ A-Za-z0-9]/", "", $q)));
		$array = []; # Para iniciar el arreglo
		foreach (explode(' ', $texto) as $tag) { # Solo agregamos de m�s de 4 y menos de 12 letras
			# A�adimos cada palabra al array
			if(strlen($tag) >= 4 AND strlen($tag) <= 12) array_push($array, strtolower($tag));
		}
		return join(', ', $array);
	}

	/** 
	 * getPreview()
	 * @access public
	 * @return array
	 * En este caso solo se dejar� el cuerpo
	*/
	public function getPreview() {
		global $tsCore;
		//
		$cuerpo = $tsCore->setSecure($_POST['cuerpo'], true);
		$cuerpo = $tsCore->parseBadWords($cuerpo, true);
		$cuerpo = $tsCore->parseBBCode($cuerpo);
		return ['cuerpo' => $cuerpo];
	}

	/** 
	 * validTags()
	 * @access public
	 * @param string
	 * @return bool
	*/
	public function validTags(string $tags = ''){
		$tags = preg_replace('/[^A-Za-z0-9, ]/', '', trim($tags));
		if (empty($tags)) return false;
		$tagsArray = array_filter(explode(',', $tags), 'trim');
		if (safe_count($tagsArray) < 4) return false;
		foreach ($tagsArray as $tag) {
			if (empty($tag)) return false;
		}
		return true;
	}

	/**
	 * newEditPost($data, $type)
	 * @access private
	 * @param array
	 * @param string = new
	 * @return array
	*/
	private function newEditPost(array $data = [], string $type = 'new') {
		global $tsCore;
		$data = [
			'title' => $tsCore->parseBadWords($tsCore->setSecure($data['titulo'], true)),
			'body' => $tsCore->setSecure($data['cuerpo']),
			'tags' => $tsCore->parseBadWords($tsCore->setSecure($data['tags'], true)),
			'category' => (int)$data['categoria']
		];
		if($type === 'new') $data['date'] = time();
		return $data;
	}

	/** 
	 * newPost()
	 * @access public
	 * @return ID
	*/
	public function newPost() {
		global $tsCore, $tsUser, $tsMonitor, $tsActividad;
		//
		if($tsUser->is_admod || $tsUser->permisos['gopp']){
			// Avitando que se repita en nuevo post y editar post
			$postData = self::newEditPost($_POST, 'new');
			$d = db_exec('fetch_row', db_exec([__FILE__, __LINE__], 'query', "SELECT COUNT(post_id) AS few FROM `p_posts` WHERE post_body = '{$postData['body']}' LIMIT 1"));
			if($d[0]) die('No se puede agregar el post.');
			// VACIOS
			foreach($postData as $key => $val){
				$val = trim(preg_replace('/[^ A-Za-z0-9]/', '', $val));
				$val = str_replace(' ', '', $val);
				if(empty($val)) return 0;
			}
		  // TAGS
		  $tags = $this->validTags($postData['tags']);
		  if(empty($tags)) return 'Tienes que ingresar por lo menos <b>4</b> tags.';
		  // ESTOS PUEDEN IR VACIOS
			$keys = ['visitantes', 'smileys', 'private', 'block_comments', 'sponsored', 'sticky'];
			foreach ($keys as $key) {
				$postData[$key] = ($_POST[$key] === 'on') ? 1 : 0;
				if ($key === 'sponsored' || $key === 'sticky') {
					$postData[$key] = (!$tsUser->is_admod AND $tsUser->permisos['most'] != false) ? 0 : ($_POST[$key] === 'on' ? 1 : 0);
				}
			}
			// ANTIFLOOD
			$antiflood = 2;
			if((int)$tsUser->info['user_lastpost'] < (time() - $antiflood)) {
				// EXISTE LA CATEGORIA?
				$query = db_exec([__FILE__, __LINE__], 'query', "SELECT cid FROM p_categorias WHERE cid = {$postData['category']} LIMIT 1");
				if(db_exec('num_rows', $query) === 0) return 'La categor&iacute;a especificada no existe.';
				// Agregamos este item al array
				$postData['ip'] = $tsCore->validarIP();
				if(!filter_var($postData['ip'], FILTER_VALIDATE_IP)) die('0: Su ip no se pudo validar.');
				// Agregamos estos items al array
				$postData['user'] = $tsUser->uid;
				$postData['status'] = (!$tsUser->is_admod AND (int)$tsCore->settings['c_desapprove_post'] === 1) ? 3 : 0;
				// VERIFICAMOS LA RUTA DE GUARDADO DE LA IMAGEN Y OTROS ANTES DE ENVIAR EL FORMULARIO.
				$urlimage = $tsCore->covers_posts();
				$postData['portada'] = $urlimage;
				// INSERTAMOS
				if(insertInto([__FILE__, __LINE__], 'p_posts', $postData, 'post_')) {
					$postID = (int)db_exec('insert_id');
					$time = time();
					// Si est� oculto, lo creamos en el historial e.e
					if(!$tsUser->is_admod && ((int)$tsCore->settings['c_desapprove_post'] == 1 || $tsUser->permisos['gorpap'] == true)) db_exec(insertInto([__FILE__, __LINE__], 'w_historial', [`pofid` => $postID, `action` => 3, `type` => 1, `mod` => $tsUser->uid, `reason` => 'Revisi&oacute;n al publicar', `date` => $time, `mod_ip` => $postData['ip']]));
					// ESTAD�STICAS
					db_exec([__FILE__, __LINE__], 'query', "UPDATE `w_stats` SET `stats_posts` = stats_posts + 1 WHERE `stats_no` = 1");
					// ULTIMO POST
					db_exec([__FILE__, __LINE__], 'query', "UPDATE u_miembros SET user_lastpost = $time WHERE user_id = {$tsUser->uid}");
					// AGREGAR AL MONITOR DE LOS USUARIOS QUE ME SIGUEN
					$tsMonitor->setFollowNotificacion(5, 1, $tsUser->uid, $postID);
					// REGISTRAR MI ACTIVIDAD
					$tsActividad->setActividad(1, $postID);
					// SUBIR DE RANGO?
					$this->subirRango($tsUser->uid);
					//
					return $postID;
				} else return show_error('Error al ejecutar la consulta de la l&iacute;nea '.__LINE__.' de '.__FILE__.'.', 'db');
			} else return -1;
		} else return 'No tienes permiso para crear posts.';
	}

	/** 
	 * savePost()
	 * @access public
	 * @return ID
	*/
	public function savePost() {
		global $tsCore, $tsUser;
		// Buscamos el post por ID
		$post_id = (int)$_GET['pid'];
		$data = db_exec('fetch_assoc', db_exec([__FILE__, __LINE__], 'query', "SELECT post_user, post_sponsored, post_sticky, post_status FROM p_posts WHERE post_id = $post_id LIMIT 1"));
		//
		if((int)$data['post_status'] != 0 && !$tsUser->is_admod && !$tsUser->permisos['moedpo']) return 'El post no puede ser editado.';
		//
		$postData = self::newEditPost($_POST, 'edit');
		// VACIOS
		foreach($postData as $key => $val){
			$val = trim(preg_replace('/[^ A-Za-z0-9]/', '', $val));
			$val = str_replace(' ', '', $val);
			if(empty($val)) return 0;
		}
		// TAGS
		$tags = $this->validTags($postData['tags']);
		if(empty($tags)) return 'Tienes que ingresar por lo menos <b>4</b> tags.';
		// ESTOS PUEDEN IR VACIOS
		$keys = ['visitantes', 'smileys', 'private', 'block_comments', 'sponsored', 'sticky'];
		foreach ($keys as $key) {
			$postData[$key] = ($_POST[$key] === 'on') ? 1 : 0;
			if ($key === 'sponsored' || $key === 'sticky') {
				$postData[$key] = (!$tsUser->is_admod AND $tsUser->permisos['most'] != false) ? 0 : ($_POST[$key] === 'on' ? 1 : 0);
			}
		}
		if(!empty($_FILES["portada"]["name"])) {
			$postData["portada"] = $tsCore->covers_posts($postData["portada"]);
		}
		// ACTUALIZAMOS
		if((int)$tsUser->uid === (int)$data['post_user'] || !empty($tsUser->is_admod) || !empty($tsUser->permisos['moedpo'])) {
			if(db_exec([__FILE__, __LINE__], 'query', "UPDATE p_posts SET {$tsCore->getIUP($postData, 'post_')} WHERE post_id = $post_id")) {
				// Guardamos en el historial de moderaci�n
				if(($tsUser->is_admod || $tsUser->permisos['moedpo']) && $tsUser->uid != $data['post_user'] && $_POST['razon']) {
					include_once TS_CLASS . "c.moderacion.php";
					$tsMod = new tsMod();
					return $tsMod->setHistory('editar', 'post', [
						'post_id' => $post_id, 
						'title' => $postData['title'], 
						'autor' => $data['post_user'], 
						'razon' => $tsCore->setSecure($_POST['razon'])
					]);
				} else return 1;
			} else exit( show_error('Error al ejecutar la consulta de la l&iacute;nea '.__LINE__.' de '.__FILE__.'.', 'db') );
		}
	}

	/**
	 * setNP()
	 * @access public
	 * return redirecciona a post
	*/
	public function setNP() {
		global $tsUser, $tsCore;
		// Tipo de acci�n
		$action = $_GET['action'];
		// Es administrador, moderador o especial
		$isAdmod = self::isAdmod();
		$order = ($action == 'fortuitae') ? 'RAND() DESC' : 'p.post_id ' . ($action === 'prev' ? 'DESC' : 'ASC');
		if($action != 'fortuitae') {
			$pid = isset($_GET['id']) ? (int) $_GET['id'] : 1;
			$isAdmod .= ' AND p.post_id ' . ($action === 'prev' ? "< " : "> ") . $pid;
		}
		$query = db_exec([__FILE__, __LINE__], 'query', "SELECT p.post_id, p.post_user, p.post_category, p.post_title, u.user_name, c.c_nombre, c.c_seo FROM p_posts AS p LEFT JOIN u_miembros AS u ON p.post_user = u.user_id LEFT JOIN p_categorias AS c ON c.cid = p.post_category WHERE p.post_status = 0 $isAdmod ORDER BY $order LIMIT 1") or exit(show_error('Error al ejecutar la consulta de la l&iacute;nea '.__LINE__.' de '.__FILE__.'.', 'db'));
		if(!db_exec('num_rows', $query)) $tsCore->redirectTo($tsCore->settings['url'].'/posts/');
		$q = db_exec('fetch_assoc', $query);
		$tsCore->redirectTo("{$tsCore->settings['url']}/posts/{$q['c_seo']}/{$q['post_id']}/{$tsCore->setSEO($q['post_title'])}.html");
	}
	
	/**
	 * @access public
	 * @return array
	*/
	public function getCatData(){
		global $tsCore;
		// Obtenemo categor�a
		$category = $tsCore->setSecure($category);
		return db_exec('fetch_assoc', db_exec([__FILE__, __LINE__], 'query', "SELECT c_nombre, c_seo FROM p_categorias WHERE c_seo = '{$category}' LIMIT 1"));
	}
	/**
	 * @access public
	 * @param string
	 * @param bool
	 * @return arrat
	*/
	public function getLastPosts(string $category = NULL, bool $sticky = false) {
		global $tsCore, $tsUser;
		// TIPO DE POSTS A MOSTRAR
		$c_where = '';
		$p_where = '';
		if(!empty($category)) {
			$category = $tsCore->setSecure($category);
			// EXISTE LA CATEGORIA?
		 	$cid = (int)db_exec('fetch_assoc', db_exec([__FILE__, __LINE__], 'query', "SELECT cid FROM p_categorias WHERE c_seo = '$category' LIMIT 1"))['cid'];
		 	if($cid > 0) {
		 		$c_where = 'AND p.post_category = ' . $cid;
		 		$p_where = ' && post_category = ' . $cid;
		 	}
		}

		$s_where = 'AND p.post_sticky = ' . ($sticky ? 1 : 0);
		$s_order = 'p.post_' . ($sticky ? 'sponsored' : 'id');
		// TOTAL DE POSTS
		$isAdmodPost = self::isAdmodPost();
		$posts['total'] = db_exec('fetch_row', db_exec([__FILE__, __LINE__], 'query', "SELECT COUNT(p.post_id) AS total FROM p_posts AS p LEFT JOIN u_miembros AS u ON p.post_user = u.user_id WHERE $isAdmodPost $p_where $s_where"))[0];
		//
		$start = $sticky ? '0, 10' : $tsCore->setPageLimit($tsCore->settings['c_max_posts'],false,$posts['total']);
		$lastPosts['pages'] = $tsCore->system_pagination($posts['total'], $tsCore->settings['c_max_posts']);
		$isAdmod = self::isAdmod();
		$query = db_exec([__FILE__, __LINE__], 'query', "SELECT p.post_id, p.post_user, p.post_category, p.post_title, p.post_hits, p.post_portada, p.post_date, p.post_comments, p.post_puntos, p.post_private, p.post_sponsored, p.post_status, p.post_sticky, u.user_id, u.user_name, u.user_activo, u.user_baneado, c.c_nombre, c.c_seo, c.c_img FROM p_posts AS p LEFT JOIN u_miembros AS u ON p.post_user = u.user_id $isAdmod LEFT JOIN p_categorias AS c ON c.cid = p.post_category WHERE $isAdmodPost $c_where $s_where GROUP BY p.post_id ORDER BY $s_order DESC LIMIT $start");
		$lastPosts['data'] = result_array($query);
		foreach ($lastPosts['data'] as $pid => $post) {
			$lastPosts['data'][$pid]['post_portada'] = $tsCore->verifyUrl($post['post_portada'] ?? '');
			// Ya vio el post?
			$ipLike = "`ip` LIKE '{$_SERVER['REMOTE_ADDR']}'";
			$userIp = $tsUser->is_member ? "(`user` = {$tsUser->uid} OR $ipLike)" : $ipLike;
			$visto = db_exec('fetch_assoc', db_exec(array(__FILE__, __LINE__), 'query', "SELECT id FROM `w_visitas` WHERE `for` = {$post['post_id']} && `type` = 2 && $userIp LIMIT 1"));
			$lastPosts['data'][$pid]['visto'] = ($tsUser->is_member AND $visto !== 0);
		}
		//
		return $lastPosts;
	}
	/*
		getPost()
	*/
	public function getPost(){
		global $tsCore, $tsUser;
		//
		$time = time();
		$post_id = (int)$_GET['post_id'];
		if(empty($post_id)) return array('deleted','Oops! Este post no existe o fue eliminado.');
		// DAR MEDALLA
		$this->DarMedalla($post_id);
		// DATOS DEL POST
		$postData = db_exec('fetch_assoc', db_exec([__FILE__, __LINE__], 'query', "SELECT c.* ,m.*, u.user_id FROM `p_posts` AS c LEFT JOIN `u_miembros` AS u ON c.post_user = u.user_id LEFT JOIN `u_perfil` AS m ON c.post_user = m.user_id  WHERE `post_id` = $post_id {$this->isAdmod} LIMIT 1"));
		//
		if(empty($postData['post_id'])) {
			$tsDraft = db_exec('fetch_assoc', db_exec([__FILE__, __LINE__], 'query', "SELECT b_title FROM p_borradores WHERE b_post_id = $post_id LIMIT 1"));
			$text = (!empty($tsDraft['b_title'])) ? 'Este post no existe o fue eliminado.' : 'El post fue eliminado!';
			return ['deleted','Oops! ' . $text];
		} elseif($postData['post_status'] == 1 && (!$tsUser->is_admod && $tsUser->permisos['moacp'] == false)) return ['denunciado','Oops! El Post se encuentra en revisi&oacute;n por acumulaci&oacute;n de denuncias.'];
		elseif($postData['post_status'] == 2 && (!$tsUser->is_admod && $tsUser->permisos['morp'] == false)) return ['deleted','Oops! El post fue eliminado!'];
		elseif($postData['post_status'] == 3 && (!$tsUser->is_admod && $tsUser->permisos['mocp'] == false)) return ['denunciado','Oops! El Post se encuentra en revisi&oacute;n, a la espera de su publicaci&oacute;n.'];
		elseif(!empty($postData['post_private']) && empty($tsUser->is_member)) return ['privado', $postData['post_title']];
  
		//ESTAD�STICAS
		#if((int)$postData['post_cache'] > $time - ((int)$tsCore->settings['c_stats_cache'] * 60)) {
			// N�MERO DE COMENTARIOS
			$postData['post_comments'] = db_exec('fetch_row', db_exec([__FILE__, __LINE__], 'query', "SELECT COUNT(u.user_name) AS c FROM u_miembros AS u LEFT JOIN p_comentarios AS c ON u.user_id = c.c_user WHERE c.c_post_id = $post_id && c.c_status = 0 && u.user_activo = 1 && u.user_baneado = 0"))[0];
			// N�MERO DE SEGUIDORES
			$postData['post_seguidores'] = db_exec('fetch_row', db_exec([__FILE__, __LINE__], 'query', "SELECT COUNT(u.user_name) AS s FROM u_miembros AS u LEFT JOIN u_follows AS f ON u.user_id = f.f_user WHERE f.f_type = 2 && f.f_id = $post_id && u.user_activo = 1 && u.user_baneado = 0"))[0];
			// N�MERO DE SEGUIDORES
			$postData['post_shared'] = db_exec('fetch_row', db_exec([__FILE__, __LINE__], 'query', "SELECT COUNT(follow_id) AS m FROM u_follows WHERE f_type = 3 && f_id = $post_id"))[0];
			// N�MERO DE FAVORITOS
			$postData['post_favoritos'] = db_exec('fetch_row', db_exec([__FILE__, __LINE__], 'query', "SELECT COUNT(fav_id) AS f FROM p_favoritos WHERE fav_post_id = $post_id"))[0];
			// ACTUALIZAMOS
			$post = $tsCore->getIUP([
				'comments' => $postData['post_comments'],
				'seguidores' => $postData['post_seguidores'],
				'shared' => $postData['post_shared'],
				'favoritos' => $postData['post_favoritos'],
				'cache' => $time
			], 'post_');

		  //ACTUALIZAMOS LAS ESTAD�STICAS
		  db_exec([__FILE__, __LINE__], 'query', "UPDATE p_posts SET $post WHERE post_id = $post_id");
		#}
		// BLOQUEADO
		$postData['block'] = db_exec('num_rows', db_exec([__FILE__, __LINE__], 'query', "SELECT bid FROM u_bloqueos WHERE b_user = {$postData['post_user']} AND b_auser = {$tsUser->uid} LIMIT 1"));
		// FOLLOWS
		if($postData['post_seguidores'] > 0){
			$postData['follow'] = db_exec('fetch_row', db_exec([__FILE__, __LINE__], 'query', "SELECT COUNT(follow_id) AS f FROM u_follows WHERE f_id = {$postData['post_id']} AND f_user = {$tsUser->uid} AND f_type = 2"))[0];	
		}
		//VISITANTES RECIENTES
		if($postData['post_visitantes']) {
			$postData['visitas'] = result_array(db_exec([__FILE__, __LINE__], 'query', "SELECT v.*, u.user_id, u.user_name FROM w_visitas AS v LEFT JOIN u_miembros AS u ON v.user = u.user_id WHERE v.for = {$postData['post_id']} && v.type = 2 && v.user > 0 ORDER BY v.date DESC LIMIT 10"));
		}
		//PUNTOS
		if($postData['post_user'] == $tsUser->uid || $tsUser->is_admod){
			$postData['puntos'] = result_array(db_exec([__FILE__, __LINE__], 'query', "SELECT p.*, u.user_id, u.user_name FROM p_votos AS p LEFT JOIN u_miembros AS u ON p.tuser = u.user_id WHERE p.tid = {$postData['post_id']} && p.type = 1 ORDER BY p.cant DESC"));
		}
		// Portada
		$postData['post_portada'] = $tsCore->verifyUrl($postData['post_portada'] ?? '');
		// CATEGORIAS
		$postData['categoria'] = db_exec('fetch_assoc', db_exec([__FILE__, __LINE__], 'query', "SELECT c.c_nombre, c.c_seo FROM p_categorias AS c  WHERE c.cid = {$postData['post_category']}"));
		// BBCode
		$postData['post_body'] = $tsCore->parseBadWords($postData['post_smileys'] == 0  ? $tsCore->parseBBCode($postData['post_body']) : $tsCore->parseBBCode($postData['post_body'], 'firma'), true);
		$postData['user_firma'] = $tsCore->parseBadWords($tsCore->parseBBCodeFirma($postData['user_firma']),true);
		// Para el seo
		$postData['post_body_descripcion'] = $tsCore->truncate(strip_tags(preg_replace("/[\r\n|\n|\r]+/", " ", $postData['post_body'])), 230);
		// MEDALLAS
		$postData['medallas'] = result_array(db_exec([__FILE__, __LINE__], 'query', "SELECT m.*, a.* FROM w_medallas AS m LEFT JOIN w_medallas_assign AS a ON a.medal_id = m.medal_id WHERE a.medal_for = {$postData['post_id']} AND m.m_type = 2 ORDER BY a.medal_date"));
		$postData['m_total'] = safe_count($postData['medallas']);
		// TAGS
		$postData['post_tags'] = explode(",", $postData['post_tags']);
		$postData['n_tags'] = safe_count($postData['post_tags']) - 1;
		// NUEVA VISITA : FUNCION SIMPLE
		$likeip = "`ip` LIKE '{$_SERVER['REMOTE_ADDR']}'";
		$useriplike = $tsUser->is_member ? "(`user` = {$tsUser->uid} OR $likeip)" : $likeip;
		$visitado = db_exec('num_rows', db_exec([__FILE__, __LINE__], 'query', "SELECT id FROM `w_visitas` WHERE `for` = $post_id && `type` = 2 && $useriplike LIMIT 1"));
		if($tsUser->is_member && $visitado == 0) {
			db_exec([__FILE__, __LINE__], 'query', "INSERT INTO w_visitas (`user`, `for`, `type`, `date`, `ip`) VALUES ({$tsUser->uid}, $post_id, 2, $time, '{$_SERVER['REMOTE_ADDR']}')");
			db_exec([__FILE__, __LINE__], 'query', "UPDATE p_posts SET post_hits = post_hits + 1 WHERE post_id = $post_id AND post_user != {$tsUser->uid}");
		} else{
			db_exec([__FILE__, __LINE__], 'query', "UPDATE `w_visitas` SET `date` = $time, ip = '{$tsCore->getIP()}' WHERE `for` = $post_id && `type` = 2 && `user` = {$tsUser->uid} LIMIT 1");
		}
		if($tsCore->settings['c_hits_guest'] == 1 && !$tsUser->is_member && !$visitado) {
			db_exec([__FILE__, __LINE__], 'query', "INSERT INTO w_visitas (`user`, `for`, `type`, `date`, `ip`) VALUES ({$tsUser->uid}, $post_id, 2, $time, '{$_SERVER['REMOTE_ADDR']}')");
			db_exec([__FILE__, __LINE__], 'query', "UPDATE p_posts SET post_hits = post_hits + 1 WHERE post_id = $post_id");
		}
		// AGREGAMOS A VISITADOS... PORTAL
		if($tsCore->settings['c_allow_portal']){
			$data = db_exec('fetch_assoc', db_exec([__FILE__, __LINE__], 'query', "SELECT last_posts_visited FROM u_portal WHERE user_id = {$tsUser->uid} LIMIT 1"));
		
			$visited = safe_unserialize($data['last_posts_visited']);
			$total = safe_count($visited);
			if($total > 10) array_splice($visited, 0, 1);
			if(!in_array($postData['post_id'],$visited)) $visited = [...$visited ,$postData['post_id']];
			$visited = serialize($visited);
			db_exec([__FILE__, __LINE__], 'query', "UPDATE u_portal SET last_posts_visited = '$visited' WHERE user_id = {$tsUser->uid}");
		}
		//
		return $postData;
	}
	/*
		getSideData($array)
	*/
	public function getAutor(int $user_id = 0){
		global $tsUser, $tsCore;
		// DATOS DEL AUTOR
		$data = db_exec('fetch_assoc', db_exec([__FILE__, __LINE__], 'query', "SELECT u.user_id, u.user_name, u.user_rango, u.user_puntos, u.user_lastactive, u.user_registro, u.user_last_ip, u.user_activo, u.user_baneado, p.user_pais, p.user_sexo, p.user_firma FROM u_miembros AS u LEFT JOIN u_perfil AS p ON u.user_id = p.user_id WHERE u.user_id = $user_id LIMIT 1"));
		//
		$data['user_seguidores'] = db_exec('num_rows', db_exec([__FILE__, __LINE__], 'query', "SELECT follow_id FROM u_follows WHERE f_id = $user_id && f_type = 1"));
		$data['user_comentarios'] = db_exec('num_rows', db_exec([__FILE__, __LINE__], 'query', "SELECT cid FROM p_comentarios WHERE c_user = $user_id && c_status = 0"));
		$data['user_posts'] = db_exec('num_rows', db_exec([__FILE__, __LINE__], 'query', "SELECT post_id FROM p_posts WHERE post_user = $user_id && post_status = 0"));
		// RANGOS DE ESTE USUARIO
		$data['rango'] = db_exec('fetch_assoc', db_exec([__FILE__, __LINE__], 'query', "SELECT r_name, r_color, r_image FROM u_rangos WHERE rango_id = {$data['user_rango']} LIMIT 1"));
		// STATUS
		$is_online = (time() - ($tsCore->settings['c_last_active'] * 60));
		$is_inactive = (time() - (($tsCore->settings['c_last_active'] * 60) * 2)); // DOBLE DEL ONLINE
		if($data['user_lastactive'] > $is_online) 
			$data['status'] = ['t' => 'Usuario Online', 'css' => 'online'];
		elseif($data['user_lastactive'] > $is_inactive) 
			$data['status'] = ['t' => 'Usuario Inactivo', 'css' => 'inactive'];
		else 
			$data['status'] = ['t' => 'Usuario Offline', 'css' => 'offline'];
		// PAIS
		include_once TS_EXTRA . "datos.php"; // Fix 10/06/2013
		$data['pais'] = [
			'icon' => strtolower($data['user_pais'] ?? ''),
			'name' => $tsPaises[$data['user_pais']]
		];
		// FOLLOWS
		if($data['user_seguidores'] > 0){
			$query = db_exec([__FILE__, __LINE__], 'query', 'SELECT follow_id FROM u_follows WHERE f_id = \''.(int)$user_id.'\' AND f_user = \''.$tsUser->uid.'\' AND f_type = \'1\'');
			$data['follow'] = db_exec('num_rows', $query);
			
		}
		$data['user_avatar'][50] = $tsCore->getAvatar($user_id, 50);
		$data['user_avatar'][120] = $tsCore->getAvatar($user_id, 120);
		// RETURN
		return $data;
	}
	
	/*
		lalala
	*/
	function getPunteador(){
		global $tsUser, $tsCore;
		
		if($tsCore->settings['c_allow_points'] > 0) {
		$data['rango'] = $tsCore->settings['c_allow_points'];
		}elseif($tsCore->settings['c_allow_points'] == '-1') {
		$data['rango'] = $tsUser->info['user_puntosxdar']; 
		}else{
		$data['rango'] = $tsUser->permisos['gopfp'];
		  }
		return $data;
	}
	/*
		getEditPost()
	*/
	function getEditPost(){
		global $tsCore, $tsUser;
		//
		$pid = (int)$_GET['pid'];
		//
		$ford = db_exec('fetch_assoc', db_exec([__FILE__, __LINE__], 'query', "SELECT * FROM p_posts WHERE post_id = $pid LIMIT 1"));
		
		if(empty($ford['post_id'])){
				return 'El post elegido no existe.';
		} elseif($ford['post_status'] != '0' && $tsUser->is_admod == 0 && $tsUser->permisos['moedpo'] == false){
				return 'El post no puede ser editado.';
		} elseif(($tsUser->uid != $ford['post_user']) && $tsUser->is_admod == 0 && $tsUser->permisos['moedpo'] == false){
				return 'No puedes editar un post que no es tuyo.';
		}
		// PEQUE�O HACK
		foreach($ford as $key => $val){
			$iden = str_replace('post_', 'b_', $key);
			$data[$iden] = ($key == 'post_body') ? str_replace(['\n', '\r'], ["\n", ''], $val) : $val;
		}
		//
		return $data;
	}
	/*
		deletePost()
	*/
	function deletePost(){
		global $tsCore, $tsUser;
		//
		$post_id = $tsCore->setSecure($_POST['postid']);
		// ES SU POST EL Q INTENTA BORRAR?
		$query = db_exec([__FILE__, __LINE__], 'query', 'SELECT post_id, post_title, post_user, post_body, post_category FROM p_posts WHERE post_id = \''.(int)$post_id.'\' AND post_user = \''.$tsUser->uid.'\'');
		$data = db_exec('fetch_assoc', $query);
		
		  db_exec([__FILE__, __LINE__], 'query', 'UPDATE `w_stats` SET `stats_posts` = stats_posts - \'1\' WHERE `stats_no` = \'1\'');
		  db_exec([__FILE__, __LINE__], 'query', 'UPDATE `u_miembros` SET `user_posts` = user_posts - \'1\' WHERE `user_id` = \''.$data['post_user'].'\'');
		// ES MIO O SOY MODERADOR/ADMINISTRADOR...
		if(!empty($data['post_id']) || !empty($tsUser->is_admod)){
				// SI ES MIS POST LO BORRAMOS Y MANDAMOS A BORRADORES
			if(db_exec([__FILE__, __LINE__], 'query', 'DELETE FROM p_posts WHERE post_id = \''.(int)$post_id.'\'')) {
				if(db_exec([__FILE__, __LINE__], 'query', 'DELETE FROM p_comentarios WHERE c_post_id = \''.(int)$post_id.'\'')) {
						 if(db_exec([__FILE__, __LINE__], 'query', 'INSERT INTO `p_borradores` (b_user, b_date, b_title, b_body, b_tags, b_category, b_status, b_causa) VALUES (\''.$tsUser->uid.'\', \''.time().'\', \''.$tsCore->setSecure($data['post_title']).'\', \''.$tsCore->setSecure($data['post_body']).'\', \'\', \''.$data['post_category'].'\', \'2\', \'\')'))
						  return "1: El post fue eliminado satisfactoriamente.";  
					  }
			}else {
				 if(db_exec([__FILE__, __LINE__], 'query', 'UPDATE p_posts SET post_status = \'2\' WHERE post_id = \''.(int)$post_id.'\'')) return "1: El post se ha eliminado correctamente.";
			}
				
		} else return '0: Lo que intentas no est&aacute; permitido.';
	}
	
	function deleteAdminPost(){
		global $tsUser;
			  if($tsUser->is_admod == 1){
				 if(db_exec('num_rows', db_exec([__FILE__, __LINE__], 'query', 'SELECT post_id FROM p_posts WHERE post_id = \''.(int)$_POST['postid'].'\' AND post_status = \'2\''))){
				 if(db_exec([__FILE__, __LINE__], 'query', 'DELETE FROM p_posts WHERE post_id = \''.(int)$_POST['postid'].'\'')) {
				  if(db_exec([__FILE__, __LINE__], 'query', 'DELETE FROM p_comentarios WHERE c_post_id = \''.(int)$_POST['postid'].'\' ')){
						db_exec([__FILE__, __LINE__], 'query', 'UPDATE `w_stats` SET `stats_posts` = stats_posts - \'1\' WHERE `stats_no` = \'1\'');
				 return "1: El post se ha eliminado correctamente.";
					 }else return '0: Ha ocurrido un error eliminando comentarios del post.';
				}else return '0: Ha ocurrido un error eliminando el post.';
				 }else return '0: El post ya se encuentra eliminado';
			}else return '0: Para el carro chacho';
	}
	/*
		getRelated()
	*/
	function getRelated($tags){
		global $tsCore, $tsUser;
		// ES UN ARRAT AHORA A UNA CADENA
		if(is_array($tags)) $tags = implode(", ",$tags);
		else str_replace('-',', ',$tags);
		//
		$pid = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
		//
		$query = db_exec([__FILE__, __LINE__], 'query', "SELECT DISTINCT p.post_id, p.post_title, p.post_category, p.post_private, c.c_seo, c.c_img, u.user_id, u.user_name FROM p_posts AS p LEFT JOIN p_categorias AS c ON c.cid = p.post_category LEFT JOIN u_miembros AS u ON u.user_id = p.post_user WHERE MATCH (post_tags) AGAINST ('$tags' IN BOOLEAN MODE) AND p.post_status = 0 AND post_sticky = 0 AND p.post_id != $pid ORDER BY rand() LIMIT 0, 5");
		//
		$data = result_array($query);
		foreach($data as $pid => $post) {
			$data[$pid]['user_avatar'][50] = $tsCore->getAvatar($post['user_id'], 50);
			$data[$pid]['user_avatar'][120] = $tsCore->getAvatar($post['user_id'], 120);
		}

		//
		return $data;
	}
	/*
		getLastComentarios()
		: PARA EL PORTAL
	*/
	public function getLastComentarios() {
		global $tsUser, $tsCore;
		//
		$query = db_exec([__FILE__, __LINE__], 'query', 'SELECT cm.cid, cm.c_status, u.user_id, u.user_name, u.user_activo, u.user_baneado, p.post_id, p.post_title, p.post_status, c.c_seo FROM p_comentarios AS cm LEFT JOIN u_miembros AS u ON cm.c_user = u.user_id LEFT JOIN p_posts AS p ON p.post_id = cm.c_post_id LEFT JOIN p_categorias AS c ON c.cid = p.post_category '.($tsUser->is_admod && $tsCore->settings['c_see_mod'] == 1 ? '' : 'WHERE p.post_status = \'0\'  AND cm.c_status = \'0\' AND u.user_activo = \'1\' && u.user_baneado = \'0\'').' ORDER BY cid DESC LIMIT 10');
		if(!$query) exit( show_error('Error al ejecutar la consulta de la l&iacute;nea '.__LINE__.' de '.__FILE__.'.', 'db') );
		$data = result_array($query);
		
		//
		return $data;
	}
	/*
		getComentarios()
	*/
	public function getComentarios(int $post_id = 0) {
		global $tsCore, $tsUser;
		//
		$start = $tsCore->setPageLimit($tsCore->settings['c_max_com']);
		$cstatus = $tsUser->is_admod ? '' : "AND c_status = 0";
		$admod = $tsUser->is_admod ? '' : "$cstatus AND u.user_activo = 1 && u.user_baneado = 0";
		//
		$query = db_exec([__FILE__, __LINE__], 'query', "SELECT u.user_id, u.user_name, u.user_activo, u.user_baneado, c.* FROM u_miembros AS u LEFT JOIN p_comentarios AS c ON u.user_id = c.c_user WHERE c.c_post_id = $post_id $admod ORDER BY c.cid LIMIT $start");
		// COMENTARIOS TOTALES
		$return['num'] = db_exec('num_rows', db_exec([__FILE__, __LINE__], 'query', "SELECT cid FROM p_comentarios WHERE c_post_id = $post_id $cstatus"));
		//
		$comments = result_array($query);
		// PARSEAR EL BBCODE
		foreach($comments as $i => $comment){
			$return['data'][$i]['votado'] = 0;
			if($comment['c_votos'] != 0) {
				$return['data'][$i]['votado'] = db_exec('num_rows',db_exec([__FILE__, __LINE__], 'query', "SELECT voto_id FROM p_votos WHERE tid = {$comment['cid']} AND tuser = {$tsUser->uid} AND type = 2 LIMIT 1"));
			}
			// BLOQUEADO
			$return['block'] = db_exec('num_rows', db_exec([__FILE__, __LINE__], 'query', "SELECT bid, b_user, b_auser FROM `u_bloqueos` WHERE b_user = {$comment['c_user']} AND b_auser = {$tsUser->uid} LIMIT 1"));
			 //
			$return['data'][$i] = $comment;
			$return['data'][$i]['c_html'] = $tsCore->parseBadWords($tsCore->parseBBCode($return['data'][$i]['c_body']), true);
			$return['data'][$i]['c_avatar'] = [
				50 => $tsCore->getAvatar($comment['user_id'], 50),
				120 => $tsCore->getAvatar($comment['user_id'], 120)
			];
		}
		//
		return $return;
	}
	/*
		newComentario()
	*/
	function newComentario(){
		global $tsCore, $tsUser, $tsActividad;
		
		// NO MAS DE 1500 CARACTERES PUES NADIE COMENTA TANTO xD
		$comentario = substr($_POST['comentario'],0,1500);
		$post_id = ($_POST['postid']);
		/* DE QUIEN ES EL POST */
		$query = db_exec([__FILE__, __LINE__], 'query', 'SELECT post_user, post_block_comments FROM p_posts WHERE post_id = \''.(int)$post_id.'\' LIMIT 1');
		$data = db_exec('fetch_assoc', $query);
		
		  /* COMPROBACIONES */
		  $tsText = preg_replace('# +#',"",$comentario);
		  $tsText = str_replace("\n","",$tsText);
		  if($tsText == '') return '0: El campo <b>Comentario</b> es requerido para esta operaci&oacute;n';
		/*        ------       */
		$most_resp = $_POST['mostrar_resp'];
		$fecha = time();
		//
		  if($data['post_user']){
				if($data['post_block_comments'] != 1 || $data['post_user'] == $tsUser->uid || $tsUser->is_admod || $tsUser->permisos['mocepc']){
					 if(empty($tsUser->is_admod) && $tsUser->permisos['gopcp'] == false) return '0: No deber&iacute;as hacer estas pruebas.';
				// ANTI FLOOD
					 $tsCore->antiFlood();
				$_SERVER['REMOTE_ADDR'] = $_SERVER['X_FORWARDED_FOR'] ? $_SERVER['X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
					 if(!filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) { die('0: Su ip no se pudo validar.'); }
				if(db_exec([__FILE__, __LINE__], 'query', 'INSERT INTO `p_comentarios` (`c_post_id`, `c_user`, `c_date`, `c_body`, `c_ip`) VALUES (\''.(int)$post_id.'\', \''.$tsUser->uid.'\', \''.$fecha.'\', \''.$comentario.'\', \''.$_SERVER['REMOTE_ADDR'].'\')')) {
					$cid = db_exec('insert_id');
						  //SUMAMOS A LAS ESTAD�STICAS
						  db_exec([__FILE__, __LINE__], 'query', 'UPDATE w_stats SET stats_comments = stats_comments + 1 WHERE stats_no = \'1\'');
						  db_exec([__FILE__, __LINE__], 'query', 'UPDATE p_posts SET post_comments = post_comments +  1 WHERE post_id = \''.(int)$post_id.'\'');
						  db_exec([__FILE__, __LINE__], 'query', 'UPDATE u_miembros SET user_comentarios = user_comentarios + 1 WHERE user_id = \''.$tsUser->uid.'\'');
						  // NOTIFICAR SI FUE CITADO Y A LOS QUE SIGUEN ESTE POST, DUE�O
						  $this->quoteNoti($post_id, $data['post_user'], $cid, $comentario);
						  // ACTIVIDAD
						  $tsActividad->setActividad(5, $post_id);
					// array(comid, comhtml, combbc, fecha, autor_del_post)
					if(!empty($most_resp)) return array($cid, $tsCore->parseBadWords($tsCore->parseBBCode($comentario), true),$comentario, $fecha, $_POST['auser'], '', $_SERVER['REMOTE_ADDR']);
					 else return '1: Tu comentario fue agregado satisfactoriamente.';
				} else return '0: Ocurri&oacute; un error int&eacute;ntalo m&aacute;s tarde.';
				} else return '0: El post se encuentra cerrado y no se permiten comentarios.';
		  } else return '0: El post no existe.';
	}
	 /*
		  quoteNoti()
		  :: Avisa cuando citan los comentarios.
	 */
	 function quoteNoti($post_id, $post_user, $cid, $comentario){
		  global $tsCore, $tsUser, $tsMonitor;
		  $ids = array();
		  $total = 0;
		  //
		  preg_match_all("/\[quote=(.*?)\]/is",$comentario,$users);
		  //
		  if(!empty($users[1])) {
				foreach($users[1] as $user){
					 # DATOS
					 $udata = explode('|',$user);
					 $user = empty($udata[0]) ? $user : $udata[0];
				$lcid = empty($udata[1]) ? $cid : (int)$udata[1];
					 # COMPROBAR
					 if($user != $tsUser->nick){
						  $uid = $tsUser->getUserID($tsCore->setSecure($user));
						  if(!empty($uid) && $uid != $tsUser->uid && !in_array($uid, $ids)){
								$ids[] = $uid;
								$tsMonitor->setNotificacion(9, $uid, $tsUser->uid, $post_id, $lcid);
						  }
						  ++$total;
					 }
				}
		  }
		// AGREGAR AL MONITOR DEL DUE�O DEL POST SI NO FUE CITADO
		  if(!in_array($post_user, $ids)){
			 $tsMonitor->setNotificacion(2, $post_user, $tsUser->uid, $post_id);
		  }
		  // ENVIAR NOTIFICAIONES A LOS Q SIGUEN EL POST :D
		  // PERO NO A LOS QUE CITARON :)
		  $tsMonitor->setFollowNotificacion(7, 2, $tsUser->uid, $post_id, 0, $ids);
		  // 
		  return true;
	 }
	 /*
		  editComentario()
	 */
	 function editComentario(){
		  global $tsUser, $tsCore;
		  //
		  $cid = intval($_POST['cid']);
		  $comentario =  $tsCore->parseBadWords($tsCore->setSecure(substr($_POST['comentario'],0,1500), true));
		  /* COMPROBACIONES */
		  $tsText = preg_replace('# +#',"",$comentario);
		  $tsText = str_replace("\n","",$tsText);
		  if($tsText == '') return '0: El campo <b>Comentario</b> es requerido para esta operaci&oacute;n';
		  //
		$query = db_exec([__FILE__, __LINE__], 'query', 'SELECT c_user FROM p_comentarios WHERE cid = \''.$cid.'\' LIMIT 1');
		  $cuser = db_exec('fetch_assoc', $query);
		  
		  //
		  if($tsUser->is_admod || ($tsUser->uid == $cuser['c_user'] && $tsUser->permisos['goepc']) || $tsUser->permisos['moedcopo']){
				// ANTI FLOOD
				$tsCore->antiFlood();
			if(db_exec([__FILE__, __LINE__], 'query', 'UPDATE p_comentarios SET c_body = \''.$comentario.'\' WHERE cid = \''.(int)$cid.'\''))
					 return '1: El comentario fue editado.';
				else return '0: Ocurri&oacute; un error :(';
		  } else return '0: Hey, este comentario no es tuyo.';
	 }
	/* 
		delComentario()
	*/
	function delComentario(){
		global $tsCore, $tsUser;
		//
		$comid = (int)$_POST['comid'];
		$autor = (int)$_POST['autor'];
		$post_id = isset($_POST['postid']) ? (int)$_POST['postid'] : (int)$_POST['post_id'];
		// Cargamos los comentarios solamente del post acutual        
		if(!db_exec('num_rows', db_exec([__FILE__, __LINE__], 'query', "SELECT cid FROM p_comentarios WHERE cid = $comid"))) return '0: El comentario no existe';
		// Es mi post?...
		$is_mypost = db_exec('num_rows', db_exec([__FILE__, __LINE__], 'query', "SELECT post_id FROM p_posts WHERE post_id = $post_id AND post_user = {$tsUser->uid}"));
		// Es mi comentario?...
		$is_mycmt = db_exec('num_rows', db_exec([__FILE__, __LINE__], 'query', "SELECT cid FROM p_comentarios WHERE cid = $comid AND c_user = {$tsUser->uid}"));
		// SI ES....
		if(!empty($is_mypost) || (!empty($is_mycmt) && !empty($tsUser->permisos['godpc'])) || !empty($tsUser->is_admod) || !empty($tsUser->permisos['moecp'])){
			if(deleteID([__FILE__, __LINE__], 'p_comentarios', "cid = $comid AND c_user = $autor AND c_post_id = $post_id")) {
				// BORRAR LOS VOTOS
				deleteID([__FILE__, __LINE__], 'p_votos', "tid = $comid");
				// RESTAR EN LAS ESTAD�STICAS
				db_exec([__FILE__, __LINE__], 'query', "UPDATE w_stats SET stats_comments = stats_comments - 1 WHERE stats_no = 1");
				db_exec([__FILE__, __LINE__], 'query', "UPDATE p_posts SET post_comments = post_comments - 1 WHERE post_id = $post_id");
				db_exec([__FILE__, __LINE__], 'query', "UPDATE u_miembros SET user_comentarios = user_comentarios - 1 WHERE user_id = $autor");
				//
				return '1: Comentario borrado.';
			}else return '0: Ocurri&oacute; un error, intentalo m&aacute;s tarde.';
		} else return '0: No tienes permiso para hacer esto.';
	}
	
	/* 
		OcultarComentario()
	*/
	function OcultarComentario(){
		global $tsCore, $tsUser;
		//
		if($tsUser->is_admod || $tsUser->permisos['moaydcp']){
			//
			$comid = (int)$_POST['comid'];
			$autor = (int)$_POST['autor'];
			$data = db_exec('fetch_assoc', db_exec([__FILE__, __LINE__], 'query', "SELECT cid, c_user, c_post_id, c_status, user_id FROM p_comentarios LEFT JOIN u_miembros ON user_id = $autor WHERE cid = $comid"));
			
			db_exec([__FILE__, __LINE__], 'query', 'UPDATE w_stats SET stats_comments = stats_comments '.($data['c_status'] == 1 ? '+' : '-').' 1 WHERE stats_no = \'1\'');
			  db_exec([__FILE__, __LINE__], 'query', 'UPDATE p_posts SET post_comments = post_comments '.($data['c_status'] == 1 ? '+' : '-').' 1 WHERE post_id = \''.$data['c_post_id'].'\'');
			  db_exec([__FILE__, __LINE__], 'query', 'UPDATE u_miembros SET user_comentarios = user_comentarios '.($data['c_status'] == 1 ? '+' : '-').' 1 WHERE user_id = \''.$data['c_user'].'\'');
			// OCULTAMOS O MOSTRAMOS
			if(db_exec([__FILE__, __LINE__], 'query', 'UPDATE p_comentarios SET c_status = '.($data['c_status'] == 1 ? '\'0\'' : '\'1\'').' WHERE cid = \''.(int)$_POST['comid'].'\'')) {
			if($data['c_status'] == 1) return '2: El comentario fue habilitado.';
			else return '1: El comentario fue ocultado.';
			} else return 'Ocurri&oacute; un error';
		 } else return '0: No tienes permiso para hacer eso.';
		
	}
	/*
		votarComentario()
	*/
	function votarComentario(){
		global $tsCore, $tsUser, $tsMonitor, $tsActividad;
		  
		// VOTAR
		$cid = $tsCore->setSecure($_POST['cid']);
		$post_id = $tsCore->setSecure($_POST['postid']);
		$votoVal = ($_POST['voto'] == 1) ? 1 : 0;
		$voto = ($votoVal == 1) ? "+ 1" : "- 1";
		  //COMPROBAMOS PERMISOS
		  if(($votoVal == 1 && ($tsUser->is_admod || $tsUser->permisos['govpp'])) || ($votoVal == 0 && ($tsUser->is_admod || $tsUser->permisos['govpn'])) ){
		//
		$query = db_exec([__FILE__, __LINE__], 'query', 'SELECT c_user FROM p_comentarios WHERE cid = \''.(int)$cid.'\' LIMIT 1');
		$data = db_exec('fetch_assoc', $query);
		
		// ES MI COMENTARIO?
		$is_mypost = ($data['c_user'] == $tsUser->uid) ? true : false;
		// NO ES MI COMENTARIO, PUEDO VOTAR
		if(!$is_mypost){
			// YA LO VOTE?
			$query = db_exec([__FILE__, __LINE__], 'query', 'SELECT tid FROM p_votos WHERE tid = \''.(int)$cid.'\' AND tuser = \''.$tsUser->uid.'\' AND type = \'2\' LIMIT 1');
			$votado = db_exec('num_rows', $query);
			
			if(empty($votado)){
				// SUMAR VOTO
				db_exec([__FILE__, __LINE__], 'query', 'UPDATE p_comentarios SET c_votos = c_votos '.$voto.' WHERE cid = \''.(int)$cid.'\'');
				// INSERTAR EN TABLA
				if(db_exec([__FILE__, __LINE__], 'query', 'INSERT INTO p_votos (tid, tuser, type) VALUES (\''.(int)$cid.'\', \''.$tsUser->uid.'\', \'2\' ) ')){
					// SUMAR PUNTOS??
					if($votoVal == 1 && $tsCore->settings['c_allow_sump'] == 1) {
						 db_exec([__FILE__, __LINE__], 'query', 'UPDATE u_miembros SET user_puntos = user_puntos +1 WHERE user_id = \''.$data['c_user'].'\'');
								$this->subirRango($data['c_user']);
					}
					// AGREGAR AL MONITOR
					$tsMonitor->setNotificacion(8, $data['c_user'], $tsUser->uid, $post_id, $cid, $votoVal);
						  // ACTIVIDAD
						  $tsActividad->setActividad(6, $post_id, $votoVal);
				}
				//
				return '1: Gracias por tu voto';
			} return '0: Ya has votado este comentario';
		} else return '0: No puedes votar tu propio comentario';
		} else return '0: No tienes permiso para hacer eso.';
	}
	/*
		votarPost()
	*/
	function votarPost(){
		global $tsCore, $tsUser, $tsMonitor, $tsActividad;
		#GLOBALES
		
		if($tsUser->is_admod || $tsUser->permisos['godp']){
		
		  // Comprobamos que sean n�meros v�lidos.
		  if(!ctype_digit($_POST['puntos'])) { return '0: S&oacute;lo puedes votar con n&uacute;meros.'; }
		//Comprobamos si otro usuario ha votado un post con esta ip
		$_SERVER['REMOTE_ADDR'] = $_SERVER['X_FORWARDED_FOR'] ? $_SERVER['X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
		  if(!filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) { return '0: Su ip no se pudo validar.'; }
		if($tsUser->is_admod != 1){
		if(db_exec('num_rows', db_exec([__FILE__, __LINE__], 'query', 'SELECT user_id FROM u_miembros WHERE user_last_ip =  \''.$_SERVER['REMOTE_ADDR'].'\' AND user_id != \''.$tsUser->uid.'\'')) || db_exec('num_rows', db_exec([__FILE__, __LINE__], 'query', 'SELECT session_id FROM u_sessions WHERE session_ip =  \''.$tsCore->setSecure($_SERVER['REMOTE_ADDR']).'\' AND session_user_id != \''.$tsUser->uid.'\''))) return '0: Has usado otra cuenta anteriormente, deber&aacute;s contactar con la administraci&oacute;n.';
		}
		$post_id = intval($_POST['postid']);
		$puntos = intval($_POST['puntos']);
		  $puntos = abs($puntos); // Num�rico negativo se convierte a num�rico positivo		
		// SUMAR PUNTOS
		$query = db_exec([__FILE__, __LINE__], 'query', 'SELECT post_user FROM p_posts WHERE post_id = \''.(int)$post_id.'\' LIMIT 1');
		$data = db_exec('fetch_assoc', $query);
		
		// ES MI POST?
		$is_mypost = ($data['post_user'] == $tsUser->uid) ? true : false;
		// NO ES MI POST, PUEDO VOTAR
		if(!$is_mypost){
			// YA LO VOTE?
			$votado = db_exec('num_rows', db_exec([__FILE__, __LINE__], 'query', 'SELECT tid FROM p_votos WHERE tid = \''.(int)$post_id.'\' AND tuser = \''.$tsUser->uid.'\' AND type = \'1\' LIMIT 1'));
			if(empty($votado)){
			
				// COMPROBAMOS LOS PUNTOS QUE PODEMOS DAR
		  if($tsCore->settings['c_allow_points'] > 0) {
		  $max_points = $tsCore->settings['c_allow_points'];
		}elseif($tsCore->settings['c_allow_points'] == '-1') { //TRUCO, podr�s dar todos los puntos que tengas disponibles
		$max_points = $tsUser->info['user_puntosxdar']; 
		}elseif($tsCore->settings['c_allow_points'] == '-2') { //TRUCO, podr�s dar todos los puntos que quieras (sin abusar ��), se restar�n igual, si tienes puesto mantener puntos, estar�s debiendo puntos durante una temporada.
		$max_points = 999999999;
		  }else{
		$max_points = $tsUser->permisos['gopfp'];
		}
				  // TENGO SUFICIENTES PUNTOS
				if($tsUser->info['user_puntosxdar'] >= $puntos){
					 if($puntos > 0) { // Votar sin dar puntos? No, gracias.				
				if($puntos <= $max_points) { // seroo churra XD ._. No alteraciones de javascript para sumar m�s de lo que se permite (? LOL ��
					// SUMAR PUNTOS AL POST
					db_exec([__FILE__, __LINE__], 'query', 'UPDATE p_posts SET post_puntos = post_puntos + '.(int)$puntos.' WHERE post_id = \''.(int)$post_id.'\'');
					// SUMAR PUNTOS AL DUE�O DEL POST
					db_exec([__FILE__, __LINE__], 'query', 'UPDATE u_miembros SET user_puntos = user_puntos + \''.(int)$puntos.'\' WHERE user_id = \''.(int)$data['post_user'].'\'');
					// RESTAR PUNTOS AL VOTANTE
					db_exec([__FILE__, __LINE__], 'query', 'UPDATE u_miembros SET user_puntosxdar = user_puntosxdar - \''.(int)$puntos.'\' WHERE user_id = \''.$tsUser->uid.'\'');
					// INSERTAR EN TABLA
					db_exec([__FILE__, __LINE__], 'query', 'INSERT INTO p_votos (tid, tuser, cant, type, date) VALUES (\''.(int)$post_id.'\', \''.$tsUser->uid.'\', \''.(int)$puntos.'\', \'1\', \''.time().'\')');
					// AGREGAR AL MONITOR
					$tsMonitor->setNotificacion(3, $data['post_user'], $tsUser->uid, $post_id, $puntos);
						  // ACTIVIDAD
						  $tsActividad->setActividad(3, $post_id, $puntos);
					// SUBIR DE RANGO
					$this->subirRango($data['post_user'], $post_id);
					//
					return '1: Puntos agregados!';					                  
				}else return '0: Voto no v&aacute;lido. No puedes dar '.$puntos.' puntos, s&oacute;lo se permiten '.$max_points .' <img src="http://i.imgur.com/doCpk.gif">';													
				} else return '0: Voto no v&aacute;lido. No puedes no dar puntos.';
			  } else return '0: Voto no v&aacute;lido. No puedes dar '.$puntos.' puntos, s&oacute;lo te quedan '.$tsUser->info['user_puntosxdar'].'.';
			} return '0: No es posible votar a un mismo post m&aacute;s de una vez.';
		  } else return '0: No puedes votar tu propio post.';			
		} else return '0: No tienes permiso para hacer esto.';			
		
	}	
	/*
		saveFavorito()
	*/
	function saveFavorito(){
		global $tsCore, $tsUser, $tsMonitor, $tsActividad;
		  # ANTIFLOOD
		//
		$post_id = $tsCore->setSecure($_POST['postid']);
		$fecha = (int) empty($_POST['reactivar']) ? time() : $tsCore->setSecure($_POST['reactivar']);
		/* DE QUIEN ES EL POST */
		$query = db_exec([__FILE__, __LINE__], 'query', 'SELECT post_user FROM p_posts WHERE post_id = \''.(int)$post_id.'\' LIMIT 1');
		$data = db_exec('fetch_assoc', $query);
		
		/*        ------       */
		if($data['post_user'] != $tsUser->uid){
			// YA LO TENGO?
			$my_favorito = db_exec('num_rows', db_exec([__FILE__, __LINE__], 'query', 'SELECT fav_id FROM p_favoritos WHERE fav_post_id = \''.(int)$post_id.'\' AND fav_user = \''.$tsUser->uid.'\' LIMIT 1'));
			if(empty($my_favorito)){
				 if(db_exec([__FILE__, __LINE__], 'query', 'INSERT INTO p_favoritos (fav_user, fav_post_id, fav_date) VALUES (\''.$tsUser->uid.'\', \''.(int)$post_id.'\', \''.$fecha.'\')')) {
					// AGREGAR AL MONITOR
					$tsMonitor->setNotificacion(1, $data['post_user'], $tsUser->uid, $post_id);
						  // ACTIVIDAD 
						  $tsActividad->setActividad(2, $post_id);
						  //
					return '1: Bien! Este post fue agregado a tus favoritos.';
				}
				else return '0: '.show_error('Error al ejecutar la consulta de la l&iacute;nea '.__LINE__.' de '.__FILE__.'.', 'db');
			} else return '0: Este post ya lo tienes en tus favoritos.';
		} else return '0: No puedes agregar tus propios post a favoritos.';
	}
	/*
		getFavoritos()
	*/
	function getFavoritos(){
		global $tsCore, $tsUser;
		//
		$query = db_exec([__FILE__, __LINE__], 'query', 'SELECT f.fav_id, f.fav_date, p.post_id, p.post_title, p.post_date, p.post_puntos, COUNT(p_c.c_post_id) as post_comments,  c.c_nombre, c.c_seo, c.c_img FROM p_favoritos AS f LEFT JOIN p_posts AS p ON p.post_id = f.fav_post_id LEFT JOIN p_categorias AS c ON c.cid = p.post_category LEFT JOIN p_comentarios AS p_c ON p.post_id = p_c.c_post_id && p_c.c_status = \'0\' WHERE f.fav_user = \''.$tsUser->uid.'\' AND p.post_status = \'0\' GROUP BY c_post_id');
		$data = result_array($query);
		
		//
		foreach($data as $fav){
			$favjson= [
				"fav_id" => $fav['fav_id'],
				"post_id" => $fav['post_id'],
				"titulo" => $fav['post_title'],
				"categoria" => $fav['c_seo'],
				"categoria_name" => $fav['c_nombre'],
				"imagen" => $fav['c_img'],
				"url" => $tsCore->settings['url'].'/posts/'.$fav['c_seo'].'/'.$fav['post_id'].'/'.$tsCore->setSEO($fav['post_title']).'.html',
				"fecha_creado" => $fav['post_date'],
				"fecha_creado_formato" => $tsCore->setHace($fav['post_date']),
				"fecha_creado_palabras" => $tsCore->setHace($fav['post_date'],true),
				"fecha_guardado" =>  $tsCore->setHace($fav['post_date']),
				"fecha_guardado_formato" => $tsCore->setHace($fav['post_date']),
				"fecha_guardado_palabras" => $tsCore->setHace($fav['fav_date'],true),
				"puntos" => $fav['post_puntos'],
				"comentarios" => $fav['post_comments']
			];
			$favoritos[] = json_encode($favjson, JSON_FORCE_OBJECT);
		}
		//

		return is_array($favoritos) ? join(',', $favoritos) : '';
	}
	/*
		delFavorito()
	*/
	function delFavorito(){
		global $tsCore, $tsUser;
		//
		$fav_id = $tsCore->setSecure($_POST['fav_id']);
		$query = db_exec([__FILE__, __LINE__], 'query', 'SELECT fav_post_id FROM p_favoritos WHERE fav_id = \''.(int)$fav_id.'\' AND fav_user = \''.$tsUser->uid.'\' LIMIT 1');
		$data = db_exec('fetch_assoc', $query);
		$is_myfav = db_exec('num_rows', $query);
		
		// ES MI FAVORITO?
		if(!empty($data['fav_post_id'])){
			 if(db_exec([__FILE__, __LINE__], 'query', 'DELETE FROM p_favoritos WHERE fav_id = \''.(int)$fav_id.'\' AND fav_user = \''.$tsUser->uid.'\'')){
				return '1: Favorito borrado.';
			} else return '0: No se pudo borrar.';
		} else return '0: No se pudo borrar, no es tu favorito.';
	}
	/*
		subirRango()
	*/
	function subirRango($user_id, $post_id = false){
		global $tsCore, $tsUser;
		// CONSULTA
		  $query = db_exec([__FILE__, __LINE__], 'query', 'SELECT u.user_puntos, u.user_rango, r.r_type FROM u_miembros AS u LEFT JOIN u_rangos AS r ON u.user_rango = r.rango_id WHERE u.user_id = \''.$user_id.'\' LIMIT 1');
		$data = db_exec('fetch_assoc', $query);
		
		// SI TIEN RANGO ESPECIAL NO ACTUALIZAMOS....
		  if(empty($data['r_type']) && $data['user_rango'] != 3) return true;
		  // SI SOLO SE PUEDE SUBIR POR UN POST
		  if(!empty($post_id) && $tsCore->settings['c_newr_type'] == 0) {
			 $query = db_exec([__FILE__, __LINE__], 'query', 'SELECT post_puntos FROM p_posts WHERE post_id = \''.(int)$post_id.'\' LIMIT 1');
				$puntos = db_exec('fetch_assoc', $query);
				
				// MODIFICAMOS
				$data['user_puntos'] = $puntos['post_puntos'];
		  }
		  //
		$puntos_actual = $data['user_puntos'];
		  $posts = db_exec('fetch_row', db_exec([__FILE__, __LINE__], 'query', 'SELECT COUNT(post_id) AS p FROM p_posts WHERE post_user = \''.(int)$user_id.'\' && post_status = \'0\''));
		$fotos = db_exec('fetch_row', db_exec([__FILE__, __LINE__], 'query', 'SELECT COUNT(foto_id) AS f FROM f_fotos WHERE f_user = \''.(int)$user_id.'\' && f_status = \'0\''));
		  $comentarios = db_exec('fetch_row', db_exec([__FILE__, __LINE__], 'query', 'SELECT COUNT(cid) AS c FROM p_comentarios WHERE c_user = \''.(int)$user_id.'\' && c_status = \'0\''));
		  
		// RANGOS
		$query = db_exec([__FILE__, __LINE__], 'query', 'SELECT rango_id, r_cant, r_type FROM u_rangos WHERE r_type > \'0\' ORDER BY r_cant');
		
		//
		while($rango = db_exec('fetch_assoc', $query)) 
		  {
			// SUBIR USUARIO
			if(!empty($rango['r_cant']) && $rango['r_type'] == 1 && $rango['r_cant'] <= $puntos_actual){
				$newRango = $rango['rango_id'];
			}elseif(!empty($rango['r_cant']) && $rango['r_type'] == 2 && $rango['r_cant'] <= $posts[0]){
				$newRango = $rango['rango_id'];
			}elseif(!empty($rango['r_cant']) && $rango['r_type'] == 3 && $rango['r_cant'] <= $fotos[0]){
				$newRango = $rango['rango_id'];
			}elseif(!empty($rango['r_cant']) && $rango['r_type'] == 4 && $rango['r_cant'] <= $comentarios[0]){
				$newRango = $rango['rango_id'];
			}
		}
		//HAY NUEVO RANGO?
		if(!empty($newRango) && $newRango != $data['user_rango']){
			//
			if(db_exec([__FILE__, __LINE__], 'query', 'UPDATE u_miembros SET user_rango = \''.$newRango.'\' WHERE user_id = \''.$user_id.'\' LIMIT 1')) return true;
		}
	}
	
	/*
		DarMedalla()
	*/
	function DarMedalla($post_id){
		//
		$data = db_exec('fetch_assoc', $query = db_exec([__FILE__, __LINE__], 'query', 'SELECT post_id, post_user, post_puntos, post_hits FROM p_posts WHERE post_id = \''.(int)$post_id.'\' LIMIT 1'));
		  
		#���#
		  $q1 = db_exec('fetch_row', db_exec([__FILE__, __LINE__], 'query', 'SELECT COUNT(follow_id) AS se FROM u_follows WHERE f_id = \''.(int)$post_id.'\' && f_type = \'2\''));
		  $q2 = db_exec('fetch_row', db_exec([__FILE__, __LINE__], 'query', 'SELECT COUNT(cid) AS c FROM p_comentarios WHERE c_post_id = \''.(int)$post_id.'\' && c_status = \'0\''));
		  $q3 = db_exec('fetch_row', db_exec([__FILE__, __LINE__], 'query', 'SELECT COUNT(fav_id) AS f FROM p_favoritos WHERE fav_post_id = \''.(int)$post_id.'\''));
		  $q4 = db_exec('fetch_row', db_exec([__FILE__, __LINE__], 'query', 'SELECT COUNT(did) AS d FROM w_denuncias WHERE obj_id = \''.(int)$post_id.'\' && d_type = \'1\''));
		  $q5 = db_exec('fetch_row', db_exec([__FILE__, __LINE__], 'query', 'SELECT COUNT(wm.medal_id) AS m FROM w_medallas AS wm LEFT JOIN w_medallas_assign AS wma ON wm.medal_id = wma.medal_id WHERE wm.m_type = \'2\' AND wma.medal_for = \''.(int)$post_id.'\''));
		  $q6 = db_exec('fetch_row', db_exec([__FILE__, __LINE__], 'query', 'SELECT COUNT(follow_id) AS sh FROM u_follows WHERE f_id = \''.(int)$post_id.'\' && f_type = \'3\''));
		// MEDALLAS
		$datamedal = result_array($query = db_exec([__FILE__, __LINE__], 'query', 'SELECT medal_id, m_cant, m_cond_post FROM w_medallas WHERE m_type = \'2\' ORDER BY m_cant DESC'));
		
		//		
		foreach($datamedal as $medalla){
			// DarMedalla
			if($medalla['m_cond_post'] == 1 && !empty($data['post_puntos']) && $medalla['m_cant'] > 0 && $medalla['m_cant'] <= $data['post_puntos']){
				$newmedalla = $medalla['medal_id'];
			}elseif($medalla['m_cond_post'] == 2 && !empty($q1[0]) && $medalla['m_cant'] > 0 && $medalla['m_cant'] <= $q1[0]){
				$newmedalla = $medalla['medal_id'];
			}elseif($medalla['m_cond_post'] == 3 && !empty($q2[0]) && $medalla['m_cant'] > 0 && $medalla['m_cant'] <= $q2[0]){
				$newmedalla = $medalla['medal_id'];
			}elseif($medalla['m_cond_post'] == 4 && !empty($q3[0]) && $medalla['m_cant'] > 0 && $medalla['m_cant'] <= $q3[0]){
				$newmedalla = $medalla['medal_id'];
			}elseif($medalla['m_cond_post'] == 5 && !empty($q4[0]) && $medalla['m_cant'] > 0 && $medalla['m_cant'] <= $q4[0]){
				$newmedalla = $medalla['medal_id'];
			}elseif($medalla['m_cond_post'] == 6 && !empty($data['post_hits']) && $medalla['m_cant'] > 0 && $medalla['m_cant'] <= $data['post_hits']){
				$newmedalla = $medalla['medal_id'];
			}elseif($medalla['m_cond_post'] == 7 && !empty($q5[0]) && $medalla['m_cant'] > 0 && $medalla['m_cant'] <= $q5[0]){
				$newmedalla = $medalla['medal_id'];
			}elseif($medalla['m_cond_post'] == 8 && !empty($q6[0]) && $medalla['m_cant'] > 0 && $medalla['m_cant'] <= $q6[0]){
				$newmedalla = $medalla['medal_id'];
			}
		//SI HAY NUEVA MEDALLA, HACEMOS LAS CONSULTAS
		if(!empty($newmedalla)){
		if(!db_exec('num_rows', db_exec([__FILE__, __LINE__], 'query', 'SELECT id FROM w_medallas_assign WHERE medal_id = \''.(int)$newmedalla.'\' AND medal_for = \''.(int)$post_id.'\''))){
		db_exec([__FILE__, __LINE__], 'query', 'INSERT INTO `w_medallas_assign` (`medal_id`, `medal_for`, `medal_date`, `medal_ip`) VALUES (\''.(int)$newmedalla.'\', \''.(int)$post_id.'\', \''.time().'\', \''.$_SERVER['REMOTE_ADDR'].'\')');
		db_exec([__FILE__, __LINE__], 'query', 'INSERT INTO u_monitor (user_id, obj_uno, obj_dos, not_type, not_date) VALUES (\''.(int)$data['post_user'].'\', \''.(int)$newmedalla.'\', \''.(int)$post_id.'\', \'16\', \''.time().'\')'); 
		db_exec([__FILE__, __LINE__], 'query', 'UPDATE w_medallas SET m_total = m_total + 1 WHERE medal_id = \''.(int)$newmedalla.'\'');}
		}
	  }	
	}
	 /*++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++*\
								BUSCADOR
	/*++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++*/
	 /*
		  getQuery()
	 */
	 function getQuery()
	 {
		  global $tsCore, $tsUser;
		  //
		  $q = $tsCore->setSecure($_GET['q']);
		  $c = intval($_GET['cat']);
		  $a = $tsCore->setSecure($_GET['autor']);
		  $e = $_GET['e'];
		  // ESTABLECER FILTROS
		  if($c > 0) $where_cat = 'AND p.post_category = \''.(int)$c.'\'';
		  if($e == 'tags') $search_on = 'p.post_tags';
		  else $search_on = 'p.post_title';
		  // BUSQUEDA
		  $w_search = 'AND MATCH('.$search_on.') AGAINST(\''.$q.'\' IN BOOLEAN MODE)';
		  // SELECCIONAR USUARIO
		  if(!empty($a)){
					 // OBTENEMOS ID
					 $aid = $tsUser->getUserID($a);
					 // BUSCAR LOS POST DEL USUARIO SIN CRITERIO DE BUSQUEDA
					 if(empty($q) && $aid > 0) $w_search = 'AND p.post_user = \''.(int)$aid.'\'';
					 // BUSCAMOS CON CRITERIO PERO SOLO LOS DE UN USUARIO
					 elseif($aid >= 1) $w_autor = 'AND p.post_user = \''.(int)$aid.'\'';
					 //
		  }
		  // PAGINAS
		  $query = db_exec([__FILE__, __LINE__], 'query', 'SELECT COUNT(p.post_id) AS total FROM p_posts AS p WHERE p.post_status = \'0\' '.$where_cat.' '.$w_autor.' '.$w_search.' ORDER BY p.post_date');
		  $total = db_exec('fetch_assoc', $query);
		  $total = $total['total'];
		  
		  $data['pages'] = $tsCore->getPagination($total, 12);
		  //
		  $query = db_exec([__FILE__, __LINE__], 'query', 'SELECT p.post_id, p.post_user, p.post_category, p.post_title, p.post_date, p.post_comments, p.post_favoritos, p.post_puntos, u.user_name, c.c_seo, c.c_nombre, c.c_img FROM p_posts AS p LEFT JOIN u_miembros AS u ON u.user_id = p.post_user LEFT JOIN p_categorias AS c ON c.cid = p.post_category WHERE p.post_status = \'0\' '.$where_cat.' '.$w_autor.' '.$w_search.' ORDER BY p.post_date DESC LIMIT '.$data['pages']['limit']);
		  $data['data'] = result_array($query);
		  
		  // ACTUALES
		  $total = explode(',',$data['pages']['limit']);
		  $data['total'] = ($total[0]) + count($data['data']);
		  //
		  return $data;
		  }
	 
}