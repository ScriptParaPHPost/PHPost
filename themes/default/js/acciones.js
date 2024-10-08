/* Box login */
function open_login_box(action){
	if($('#login_box').css('display') == 'block' && action!='open')
		close_login_box();
	else {
		$('#login_error,#login_cargando').css('display','none');
		$('.opciones_usuario').addClass('here');
		$('#login_box').fadeIn('fast');
		$('#nickname').focus();
	}
}
function close_login_box(){
	$('.opciones_usuario').removeClass('here');
	$('#login_box').slideUp('fast');
}
function ir_a_categoria(cat){
	if(cat!='root' && cat!='linea') {
		href = (cat==-2) ? 'posts/' : `posts/${cat}/`;
		location.href = `${global_data.url}/${href}`;
	}
}

function gget(data, sin_amp){
	var r = data + '=';
	if(!sin_amp) r = '&' + r;
	obj = (data === 'key') ? 'user_key' : data;
	if(global_data[obj] !== '') return r + global_data[obj];
	return '';
}
// Solo si es la tecla ENTER
keypress_intro = e => (e.keyCode === 13 || e.which === 13);

function onfocus_input(o){
	if($(o).val()==$(o).attr('title')){
		$(o).val('');
		$(o).removeClass('onblur_effect');
	}
}
function onblur_input(o){
	if($(o).val()==$(o).attr('title') || $(o).val()==''){
		$(o).val($(o).attr('title'));
		$(o).addClass('onblur_effect');
	}
}
var form_ff = 0;
//Cargo el formulario
function registro_load_form(){
   $('#loading').fadeIn(250);
   $.post(`${global_data.url}/registro-form.php`, h => {
		switch(h.charAt(0)){
			case '0': //Error
				mydialog.alert('Error', h.substring(3));
			break;
			case '1': //OK. Ya es miembro
				mydialog.faster({
		   		close: true,
		   		title: 'Registro',
		   		body: h.substring(3),
		   		buttons: {
		   			ok: {text: 'Crear cuenta', action: 'crearCuenta()' }
		   		}
		   	})
			break;
		}
      $('#loading').fadeOut(350);
   }).fail(() => {
		mydialog.error_500("registro.load_form("+data+")");
      $('#loading').fadeOut(350);
   })
}

function login_load_form(){
   $('#loading').fadeIn(250);
   $.post(global_data.url + '/login-form.php', response => {
   	mydialog.faster({
   		close: true,
   		title: 'Iniciar Sesión',
   		body: response,
   		buttons: {
   			ok: {text: 'Iniciar sesión', action: 'iniciarSesion()' }
   		}
   	})
      $('#loading').fadeOut(350);
   })
}

function bloquear(user, bloqueado, lugar, aceptar){
	if(!aceptar && bloqueado){
		mydialog.show();
		mydialog.title('Bloquear usuario');
		mydialog.body('&iquest;Realmente deseas bloquear a este usuario?');
		mydialog.buttons(true, true, 'SI', "bloquear('"+user+"', true, '"+lugar+"', true)", true, false, true, 'NO', 'close', true, true);
		mydialog.center();
		return;
	}
	if(bloqueado) mydialog.procesando_inicio('Procesando...', 'Bloquear usuario');
  	$('#loading').fadeIn(250);
  	let data = 'user=' + user + (bloqueado ? '&bloquear=1' : '') + gget('key');
	$.post(`${global_data.url}/bloqueos-cambiar.php`, data, h => {
		mydialog.alert('Bloquear Usuarios', h.substring(3));
      if(h.charAt(0) == 1){
    		switch(lugar){
    			case 'perfil':
    				if(bloqueado)
    					$('#bloquear_cambiar').html('Desbloquear').removeClass('bloquearU').addClass('desbloquearU').attr('href', "javascript:bloquear('"+user+"', false, '"+lugar+"')");
    				else
    					$('#bloquear_cambiar').html('Bloquear').removeClass('desbloquearU').addClass('bloquearU').attr('href', "javascript:bloquear('"+user+"', true, '"+lugar+"')");
    			break;
    			case 'respuestas':
    			case 'comentarios':
    				$('li.desbloquear_'+user)[(bloqueado ? 'show' : 'hide')]()
    				$('li.bloquear_'+user)[(bloqueado ? 'hide' : 'show')]()
    			break;
    			case 'mis_bloqueados':
    				if(bloqueado)
    					$('.bloquear_usuario_'+user).attr('title', 'Desbloquear Usuario').removeClass('bloqueadosU').addClass('desbloqueadosU').html('Desbloquear').attr('href', "javascript:bloquear('"+user+"', false, '"+lugar+"')");
    				else
    					$('.bloquear_usuario_'+user).attr('title', 'Bloquear Usuario').removeClass('desbloqueadosU').addClass('bloqueadosU').html('Bloquear').attr('href', "javascript:bloquear('"+user+"', true, '"+lugar+"')");
    			break;
            case 'mensajes':
    				if(bloqueado) $('#bloquear_cambiar').html('Desbloquear').attr('href', "javascript:bloquear('"+user+"', false, '"+lugar+"')");
    				else $('#bloquear_cambiar').html('Bloquear').attr('href', "javascript:bloquear('"+user+"', true, '"+lugar+"')");
                break;
    		}
      }
      $('#loading').fadeOut(350);
	}).fail(() => {
		mydialog.error_500("bloquear('"+user+"', '"+bloqueado+"', '"+lugar+"', true)");
      $('#loading').fadeOut(350);
	}).done(() => {
		mydialog.procesando_fin();
      $('#loading').fadeOut(350);
	});
}

function search_set(obj, typesearching) { 
   $('div.search-in > a').removeClass('search_active'); 
   $(obj).addClass('search_active');
   $('input[name="e"]').val(typesearching);  
   // GOOGLE ID
   var gid = $('form[name=top_search_box]').attr('gid');
   //Muestro/oculto los input google
	if(typesearching == 'google'){ 
      //Ahora es google
		$('form[name=top_search_box]').append('<input type="hidden" name="cx" value="' + gid + '" /><input type="hidden" name="cof" value="FORID:10" /><input type="hidden" name="ie" value="ISO-8859-1" />');
	} else $('input[name="cx"], input[name="cof"], input[name="ie"]').remove();
   // 
   $('#ibuscadorq').focus();
}

var notifica = {
	cache: {},
	retry: Array(),
	userMenuPopup: function (obj) {
		var id = $(obj).attr('userid');
		var cache_id = 'following_'+id, list = $(obj).children('ul');
		$(list).children('li.check').slideUp();
		if (this.cache[cache_id] == 1) {
			$(list).children('li.follow').slideUp();
			$(list).children('li.unfollow').slideDown();
		} else {
			$(list).children('li.unfollow').slideUp();
			$(list).children('li.follow').slideDown();
		}
	},
   userInMencionHandle: function(r){
		var x = r.split('-');
		if (x.length == 3 && x[0] == 0) {
        var fid = x[1];
			$('a.mf_' + fid +', a.mf_' + fid).each(() => $(this).toggle());
			$('.mft_' + fid).html(number_format(parseInt(x[2])));
        vcard_cache['mf' + fid] = '';
		} else if (x.length == 4) mydialog.alert('Notificaciones', x[3]);  
   },
	userMenuHandle: function (r) {
		var x = r.split('-');
		if (x.length == 3 && x[0] == 0) {
			var cache_id = 'following_'+x[1];
			notifica.cache[cache_id] = parseInt(x[0]);
			$('div.avatar-box').children('ul').hide();
		} else if (x.length == 4) mydialog.alert('Notificaciones', x[4]);
	},
	userInPostHandle: function (r) {
		var x = r.split('-');
		if (x.length == 3 && x[0] == 0) {
			$('a.follow_user_post, a.unfollow_user_post').toggle();
			$('div.metadata-usuario > span.nData.user_follow_count').html(number_format(parseInt(x[2])));
			notifica.userMenuHandle(r);
		} else if (x.length == 4) mydialog.alert('Notificaciones', x[3]);
	},
	userInMonitorHandle: function (r, obj) {
		var x = r.split('-');
		if (x.length == 3 && x[0] == 0) $(obj).fadeOut(() => { $(obj).remove(); });
		else if (x.length == 4) mydialog.alert('Notificaciones', x[3]);	
	},
	inPostHandle: function (r) {
		var x = r.split('-');
		if (x.length == 3 && x[0] == 0) {
			$('a.follow_post, a.unfollow_post').parent('li').toggle();
			$('ul.post-estadisticas > li > span.icons.monitor').html(number_format(parseInt(x[2])));
		} else if (x.length == 4) mydialog.alert('Notificaciones', x[3]);
	},
	inComunidadHandle: function (r) {
		var x = r.split('-');
		if (x.length == 3 && x[0] == 0) {
			$('a.follow_comunidad, a.unfollow_comunidad').toggle();
			$('li.comunidad_seguidores').html(number_format(parseInt(x[2]))+' Seguidores');
		} else if (x.length == 4) mydialog.alert('Notificaciones', x[3]);
	},
	temaInComunidadHandle: function (r) {
		var x = r.split('-');
		if (x.length == 3 && x[0] == 0) {
			$('div.followBox > a.follow_tema, a.unfollow_tema').toggle();
			$('span.tema_notifica_count').html(number_format(parseInt(x[2]))+' Seguidores');
		} else if (x.length == 4) mydialog.alert('Notificaciones', x[3]);
	},
	ruserInAdminHandle: function (r) {
		var x = r.split('-');
		if (x.length == 3 && x[0] == 0) $('.ruser'+x[1]).toggle();
		else if (x.length == 4) mydialog.alert('Notificaciones', x[3]);	
	},
	listInAdminHandle: function (r) {
		var x = r.split('-');
		if (x.length == 3 && x[0] == 0) {
			$('.list'+x[1]).toggle();
			$('.list'+x[1]+':first').parent('div').parent('li').children('div:first').fadeTo(0, $('.list'+x[1]+':first').css('display') == 'none' ? 0.5 : 1);
		} else if (x.length == 4) mydialog.alert('Notificaciones', x[3]);	
	},
	spamPostHandle: function (r) {
		var x = r.split('-');
		if (x.length == 2) mydialog.alert('Notificaciones', x[1]);
		else mydialog.close();
	},
	spamTemaHandle: function (r) {
		var x = r.split('-');
		if (x.length == 2) mydialog.alert('Notificaciones', x[1]);
		else mydialog.close();
	},
	ajax: function (param, cb, obj) {
		if ($(obj).hasClass('spinner')) return;
		notifica.retry.push(param);
		notifica.retry.push(cb);
		var error = param[0]!='action=count';
		$(obj).addClass('spinner');
      $('#loading').fadeIn(250);
		$.ajax({
			url: global_data.url + '/notificaciones-ajax.php', 
			type: 'post', 
			data: param.join('&') + gget('key'),
			success: function (r) {
				$(obj).removeClass('spinner');
				cb(r, obj);
            $('#loading').fadeOut(350);
			},
			error: function () {
				if (error) mydialog.error_500('notifica.ajax(notifica.retry[0], notifica.retry[1])');
            $('#loading').fadeOut(350);                
			}
		});
	},
	follow: function (type, id, cb, obj) {
		this.ajax(['action=follow', 'type='+type, 'obj='+id], cb, obj);
	},
	unfollow: function (type, id, cb, obj) {
		this.ajax(['action=unfollow', 'type='+type, 'obj='+id], cb, obj);
	},
	spam: function (id, cb) {
		this.ajax(['action=spam', 'postid='+id], cb);
	},
	c_spam: function (id, cb) {
		this.ajax(['action=c_spam', 'temaid='+id], cb);
	},
	sharePost: function (id) {
		mydialog.show();
		mydialog.title('Recomendar');
		mydialog.body('¿Quieres recomendar este post a tus seguidores?');
		mydialog.buttons(true, true, 'Recomendar', 'notifica.spam('+id+', notifica.spamPostHandle)', true, true, true, 'Cancelar', 'close', true, false);
		mydialog.center();
	},
	shareTema: function (id) {
		mydialog.show();
		mydialog.title('Recomendar');
		mydialog.body('¿Quieres recomendar este tema a tus seguidores?');
		mydialog.buttons(true, true, 'Recomendar', 'notifica.c_spam('+id+', notifica.spamTemaHandle)', true, true, true, 'Cancelar', 'close', true, false);
		mydialog.center();
	},
	last: function () {
		var c = parseInt($('#alerta_mon > a > span').html());
        mensaje.close();
		if ($('#mon_list').css('display') != 'none') {
			$('#mon_list').fadeOut();
			$('a[name=Monitor]').parent('li').removeClass('monitor-notificaciones');
		}
		else {
			if (($('#mon_list').css('display') == 'none' && c > 0) || typeof notifica.cache.last == 'undefined') {
				$('a[name=Monitor]').children('span').addClass('spinner');
				$('a[name=Monitor]').parent('li').addClass('monitor-notificaciones');
				$('#mon_list').slideDown();
				notifica.ajax(Array('action=last'), function (r) {
					notifica.cache['last'] = r;
					notifica.show();
				});
			}
			else notifica.show();
		}
	},
	check: function () {
		notifica.ajax(Array('action=count'), notifica.popup);
	},
	popup: function (r) {
		var c = parseInt($('#alerta_mon > a > span').html());
		if (r != c && r > 0) {
			if (r != 1) var not_total = ' notificaciones'; else var not_total = ' notificaci&oacute;n';
			if (!$('#alerta_mon').length) $('div.userInfoLogin > ul > li.monitor').append('<div class="alertas" id="alerta_mon"><a title="' + r + not_total + '"><span></span></a></div>');
			$('#alerta_mon > a > span').html(r);
			$('#alerta_mon').animate({ top: '-=5px' }, 100, null, () => { $('#alerta_mon').animate({ top: '+=5px' }, 100) });
		}
		else if (r == 0) $('#alerta_mon').remove();
	},
	show: function () {
		if (typeof notifica.cache.last != 'undefined') {
			$('#alerta_mon').remove();
			$('a[name=Monitor]').parent('li').addClass('monitor-notificaciones');
			$('a[name=Monitor]').children('span').removeClass('spinner');
			$('#mon_list').show().children('ul').html(notifica.cache.last);
		}
	},
	filter: function () {
		// Creamos un arreglo vacío
		var fid = []
		// Obtenemos todos los input del primer UL
		let inputs = $('#post-izquierda .categoriaList ul:first li input');
		inputs.map( (pos, input) => {
			// Añadimos solo las que estan activas
			if($(input).prop('checked')) fid.push(input.id)
		})
		$.post(global_data.url + '/notificaciones-filtro.php', { fid });
	},
   close: () => {
		$('#mon_list').hide();
		$('a[name=Monitor]').parent('li').removeClass('monitor-notificaciones');   
   }
	
}
/* Mensajes */

var mensaje = {
    cache: {},
    vars: Array(),
    // CREAR HTML
    form: function (){
         var html = '';
        if(this.vars['error']) html += '<div class="emptyData">' + this.vars['error'] + '</div><br style="clear:both">'
        html += '<div class="m-col1">Para:</div>'
        html += '<div class="m-col2"><input type="text" value="' + this.vars['to'] + '" maxlength="16" tabindex="0" size="20" id="msg_to" name="msg_to"/> <span style="font-size: 10px;">(Ingrese el nombre de usuario)</span></div><br style="clear:both" />'
        html += '<div class="m-col1">Asunto:</div>'
        html += '<div class="m-col2"><input type="text" value="' + this.vars['sub'] + '" maxlength="100" tabindex="0" size="50" id="msg_subject" name="msg_subject"/></div><br /><br style="clear:both"/>'
        html += '<div class="m-col1">Mensaje:</div>'
        html += '<div class="m-col2"><textarea tabindex="0" rows="10" id="msg_body" name="msg_body" style="height:100px; width:350px">' + this.vars['msg'] + '</textarea></div><br style="clear:both"/>'
        return html;                          
    },
    // FUNCIONES AUX
    checkform: function (h){
        if(parseInt(h) == 0)
            mensaje.enviar(1);
        else if(parseInt(h) == 1) {
            mensaje.nuevo(mensaje.vars['to'], mensaje.vars['sub'], mensaje.vars['msg'], 'No es posible enviarse mensajes a s&iacute; mismo.');
        } else if(parseInt(h) == 2) {
            mensaje.nuevo(mensaje.vars['to'], mensaje.vars['sub'], mensaje.vars['msg'], 'Este usuario no existe. Por favor, verif&iacute;calo.');
        }    
    },
    alert: function(h){
      mydialog.procesando_fin();
      mydialog.alert('Aviso','<div class="emptyData">' + h + '</div>');  
    },
    mostrar: function(show, obj){
        //
        $('.GBTabset a').removeClass('here');
        //
        if(show == 'all'){
            $('#mensajes div').show();
            $(obj).addClass('here');
        } else if(show == 'unread'){
            $('#mensajes div.GBThreadRow').hide();
            $('#mensajes table.unread').parent().show();
            $(obj).addClass('here');
        }
    },
    select: function(act){
        //
        var inputs = $('#mensajes .GBThreadRow :input');
        inputs.each(() => {
           if(act == 'all'){
            $(this).attr({checked: 'checked'});
           } else if(act == 'read'){
                if($(this).attr('class') != 'inread'){
                    $(this).attr({checked: 'checked'});
                } else $(this).attr({checked: ''});
           } else if(act == 'unread'){
                if($(this).attr('class') == 'inread'){
                    $(this).attr({checked: 'checked'});
                } else $(this).attr({checked: ''});                
           } else if(act == 'none'){
            $(this).attr({checked: ''});
           }
        });
    },
    modificar: function(act){
        var inputs = $('#mensajes .GBThreadRow :input');
        var ids = new Array();
        var i = 0;
        //
        inputs.each(() => {
            var este = $(this).attr('checked');
            //
            if(este != false){
                // AGREGAR EL ID
                ids[i] = $(this).val();
                i++;
                // PARA LOS ESTILOS
                var cid = $(this).val().split(':');
                // MARCAR LEIDO
                if(act == 'read'){
                    $('#' + cid[0]).removeClass('unread');
                    $(this).removeClass('inread');
                // MARCAR NO LEIDO
                } else if(act == 'unread'){
                    $('#' + cid[0]).addClass('unread');
                    $(this).addClass('inread');
                // ELIMINAR
                } else if(act == 'delete'){
                    $('#' + cid[0]).parent().remove();
                }
            }
        });
        // ENVIAR CAMBIOS
        if(ids.length > 0){
            var params = ids.join(',');
            mensaje.ajax('editar','ids=' + params + '&act=' + act,function(r){
                //
            });   
        }
    },
    eliminar: function(id,type){
        mensaje.ajax('editar','ids=' + id + '&act=delete',function(r){
            if(type == 1){
                var cid = id.split(':');
                $('#mp_' + cid[0]).remove();
             }else if(type == 2){
                location.href = global_data.url + '/mensajes/';
             }
        });
    },
    marcar: function(id, a, type, obj){
        var act = (a == 0) ? 'read' : 'unread';
        var show = (act == 'read') ? 'unread' : 'read';
        
        //
        mensaje.ajax('editar','ids=' + id + '&act=' + act,function(r){
            // CAMBIAR ENTRE LEIDO Y NO LEIDO
            if(type == 1){
                var cid = id.split(':');
                if(act == 'read')
                $('#mp_' + cid[0]).removeClass('unread');
                else 
                $('#mp_' + cid[0]).addClass('unread');
                //
                $(obj).parent().find('a').hide();
                $(obj).parent().find('.' + show).show();
             } else {
                location.href = global_data.url + '/mensajes/';
             }
        });
    },
    // POST
    ajax: function(action, params, fn){
        $('#loading').fadeIn(250);
        $.ajax({
    		type: 'POST',
    		url: global_data.url + '/mensajes-' + action + '.php',
    		data: params,
    		success: function(h){
                fn(h);
                $('#loading').fadeOut(350);                
    		}
    	});
    },
    // PREPARAR EL ENVIO
	nuevo: function (para, asunto, body, error){
        // GUARDAR
        this.vars['to'] = para;
        this.vars['sub'] = asunto;
        this.vars['msg'] = body;
        this.vars['error'] = error;
        //
        mydialog.procesando_fin();
		mydialog.show(true);
		mydialog.title('Nuevo mensaje');
		mydialog.body(this.form());
		mydialog.buttons(true, true, 'Enviar', '$(\'.wysibb-texarea\').sync(); mensaje.enviar(0)', true, true, true, 'Cancelar', 'close', true, false);
        var wbbOpt = { resize_maxheight: 170, buttons: "smilebox,|,bold,italic,underline,strike,sup,sub,|,img,video,link,|,removeFormat" }
        $('#msg_body').wysibb(wbbOpt);
        mydialog.center();
	},
    // ENVIAR...
    enviar: function (enviar){
        // DATOS
        this.vars['to'] = $('#msg_to').val();
        this.vars['sub'] = encodeURIComponent($('#msg_subject').val()); // FIX: 14/12/2014 - 1.1.000.9
        this.vars['msg'] = encodeURIComponent($('#msg_body').val()); // FIX: 14/12/2014 - 1.1.000.9
        // COMPROBAR
        if(enviar == 0){ // VERIFICAR...
            if(this.vars['to'] == '')
                mensaje.nuevo(mensaje.vars['to'], mensaje.vars['sub'], mensaje.vars['msg'], 'Por favor, especific&aacute; el destinatario.');
            if(this.vars['msg'] == '')
                mensaje.nuevo(mensaje.vars['to'], mensaje.vars['sub'], mensaje.vars['msg'], 'El mensaje esta vac&iacute;o.');
            //
            mydialog.procesando_inicio('Verificando...', 'Nuevo Mensaje');
            this.ajax('validar', 'para=' + this.vars['to'], mensaje.checkform);

        } else if(enviar == 1){
            mydialog.procesando_inicio('Enviando...', 'Nuevo Mensaje');
            // ENVIAR
            this.ajax('enviar', 'para=' + mensaje.vars['to'] + '&asunto=' + mensaje.vars['sub'] + '&mensaje=' + mensaje.vars['msg'], mensaje.alert);
        }
    },
    // RESPONDER
    responder: function(mp_id){
        this.vars['mp_id'] = $('#mp_id').val();
        this.vars['mp_body'] = encodeURIComponent($('#respuesta').bbcode()); // Fix: 06/05/2018 - 1.3
        if(this.vars['mp_body'] == '') {
            $('#respuesta').focus();
            return;
        }
        //
        this.ajax('respuesta','id=' + this.vars['mp_id'] + '&body=' + this.vars['mp_body'], function(h){
            $('#respuesta').val(''); // LIMPIAMOS
            $('.wysibb-body').html('');
            switch(h.charAt(0)){
                case '0':
                    mydialog.alert("Error", h.substring(3));
                break;
                case '1':
					$('#historial').append($(h.substring(3)).fadeIn('slow'));
                break;
            }
            $('#respuesta').focus();
        });
    },
	last: function () {
		var c = parseInt($('#alerta_mps > a > span').html());
        notifica.close();
        //
		if ($('#mp_list').css('display') != 'none') {
			$('#mp_list').hide();
			$('a[name=Mensajes]').parent('li').removeClass('monitor-notificaciones');
		}
		else {
			if (($('#mp_list').css('display') == 'none' && c > 0) || typeof mensaje.cache.last == 'undefined') {
				$('a[name=Mensajes]').children('span').addClass('spinner');
				$('a[name=Mensajes]').parent('li').addClass('monitor-notificaciones');
				$('#mp_list').show();
				mensaje.ajax('lista', '', function (r) {
					mensaje.cache['last'] = r;
					mensaje.show();
				});
			}
			else mensaje.show();
		}
	},
	popup: function (mps) {
		var c = parseInt($('#alerta_mps > a > span').html());
		if (mps != c && mps > 0) {
            if (mps != 1) var mps_total = ' mensajes'; else var mps_total = ' mensaje';
			if (!$('#alerta_mps').length) $('div.userInfoLogin > ul > li.mensajes').append('<div class="alertas" id="alerta_mps"><a title="' + mps + mps_total + '"><span></span></a></div>');
			$('#alerta_mps > a > span').html(mps);
			$('#alerta_mps').animate({ top: '-=5px' }, 100, null, () => { $('#alerta_mps').animate({ top: '+=5px' }, 100) });
		}
		else if (mps == 0) $('#alerta_mps').remove();
	},
	show: function () {
		if (typeof mensaje.cache.last != 'undefined') {
			$('#alerta_mps').remove();
			$('a[name=Mensajes]').parent('li').addClass('monitor-notificaciones');
			$('a[name=Mensajes]').children('span').removeClass('spinner');
			$('#mp_list').show().children('ul').html(mensaje.cache.last);
		}
	},
    close: () => {
        $('#mp_list').slideUp();
        $('a[name=Mensajes]').parent('li').removeClass('monitor-notificaciones');
    }
}

/* DENUNCIAS */
var denuncia = {
    nueva: function(type, obj_id, obj_title, obj_user){
        // PLANTILLA
		$('#loading').fadeIn(250); 
        $.ajax({
			type: 'POST',
			url: global_data.url + '/denuncia-' + type + '.php',
			data: 'obj_id=' + obj_id + '&obj_title=' + obj_title + '&obj_user=' + obj_user,
			success: function(h){
                denuncia.set_dialog(h, obj_id, type);
                $('#loading').fadeOut(350);                                 
			}
		});
    },
    set_dialog: function(html, obj_id, type){
        var d_title = 'Denunciar ' + type;
        // MYDIALOG
        mydialog.mask_close = false;
        mydialog.close_button = true;		                                        
		mydialog.show();
        mydialog.title(d_title);
		mydialog.body(html);
		mydialog.buttons(true, true, 'Enviar', "denuncia.enviar(" + obj_id + ", '" + type + "')", true, true, true, 'Cancelar', 'close', true, false);
		mydialog.center();
    },
    enviar: function(obj_id, type){
        var razon = $('select[name=razon]').val();
        var extras = $('textarea[name=extras]').val();
        //
        $('#loading').fadeIn(250);                         
		$.ajax({
			type: 'POST',
			url: global_data.url + '/denuncia-' + type + '.php',
			data: 'obj_id=' + obj_id + '&razon=' + razon + '&extras=' + extras,
			success: function(h){
                switch(h.charAt(0)){
                    case '0':
                        mydialog.alert("Error",'<div class="emptyData">' + h.substring(3) +  '</div>');
                    break;
                    case '1':
                        mydialog.alert("Bien", '<div class="emptyData">' + h.substring(3) + '</div>');
                    break;
                }
                $('#loading').fadeOut(350);                                                 
			}
		});
    }
}

/* AFILIACION */
const afiliado = {
	vars: Array(),
	nuevo: () => {
		$.get(global_data.url + '/afiliado-nuevo-form.php', form => {
			mydialog.faster({
				title: 'Nueva Afiliaci&oacute;n',
				body: form,
				buttons: {
					ok: { text: 'Enviar', action: 'afiliado.enviar(0)' },
					fail: { text: 'Cancelar', action: 'close' },
				}
			});
		})
	},
	enviar: () => {
		verifyInput('#aurl', 'La url no puede estar vacío.');
		verifyInput('#atitle', 'El titulo no puede estar vacío.');
		verifyInput('#atxt', 'La descripcion no puede estar vacío.');
		mydialog.procesando_inicio('Enviando...', 'Nueva Afiliaci&oacute;n');
		afiliado.enviando($('form[name="AFormInputs"]').serialize());
	},
	enviando: function(params){
		$('#loading').fadeIn(250); 
		$.post(global_data.url + '/afiliado-enviando.php', params, h => {
			mydialog.procesando_fin();
			switch(h.charAt(0)){
				case '0':
				case '2':
					let text = (h.charAt(0) == 2) ? 'Faltan datos' : 'La URL es incorrecta';
					$('#AFStatus > span').fadeOut().text(text).fadeIn();
				break;
				case '1':
					mydialog.body(h.substring(3));
					mydialog.buttons(true, true, 'Aceptar', 'close', true, true);
				break;
			}
			mydialog.center();
			$('#loading').fadeOut(350); 
		})
	},
	detalles: function(ref){
		$('#loading').fadeIn(250); 
		$.post(`${global_data.url}/afiliado-detalles.php`, { ref }, response => {
			mydialog.faster({
				title: 'Detalles',
				body: response,
				buttons: {
					ok: { text: 'Aceptar', action: 'close' }
				}
			});
		}) 
	}
};

/* IMAGENES */
var imagenes = {
   total: 0,
   move: '-250px',
   presentacion: () => {
      $('#imContent').animate({top: '0px'}, 1000, 'easeOutQuad', () => {
         $('#imContent').css({top: imagenes.move})
         var slm = $('#img_' + imagenes.total).html();
         for(var i = imagenes.total; i >= 0; i--) $('#img_' + i).html($('#img_' + (i - 1)).html());
         $('#img_0').html(slm);
         setTimeout("imagenes.presentacion()",5000);
      });
   }
}

// NEWS
var news = {
    total: 0,
    count: 1,
    slider: () => {
        if(news.total > 1){
            if(news.count < news.total) news.count++;
            else news.count = 1;
            //
            $('#top_news > li').hide();
            $('#new_' + news.count).fadeIn();
            // INFINITO :D
            setTimeout("news.slider()",7000);
        }
    }       
}

// READY
$(document).ready(() => {
   /* NOTICIAS */
   news.total = $('#top_news > li').length;
   news.slider();
   /* IMAGENES */
   imagenes.presentacion();

   const DisplayDropdown = [
		{id: '#mon_list', name: 'Monitor', func: notifica.last},
		{id: '#mp_list', name: 'Mensajes', func: mensaje.last}
	];
	$('body').on('click', function(e) {
		DisplayDropdown.map( ({ id, name, func }) => {
			if(
				$(id).css('display') !== 'none' && 
				$(e.target).closest(id).length === 0 && 
				$(e.target).closest(`a[name=${name}]`).length === 0
			) func.call();
		})
	});
	
	$('div.new-search > div.search-body > form > input[name=q]').on('focus', () => {
		if ($(this).val() == 'Buscar') $(this).val(''); 
		$(this).css('color', '#000');
	}).on('blur', () => {
		if ($.trim($(this).val()) == '') $(this).val('Buscar'); 
		$(this).css('color', '#999');
	});
	$('span.fb_share_no_count').each(() => {
		$(this).removeClass('fb_share_no_count');
		$('.fb_share_count_inner', this).html('0');
	});
	
});