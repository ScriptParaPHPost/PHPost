<div class="boxy-title">
	 <h3>Administrar Temas</h3>
</div>
<div id="res" class="boxy-content">
{if $tsSave}<div class="mensajes  ok">Tus cambios han sido guardados.</div>{/if}
	{if $tsAct == ''}
		<table class="admin_table">
			<thead>
				<th>Vista previa</th>
				<th>Nombre</th>
				<th>Opciones</th>
		  	</thead>
		  	<tbody>
		  		{foreach from=$tsTemas item=tema}
				<tr>
					<td width="150"><img src="{$tema.t_url}/screenshot.png" width="150" height="100" /></td>
					<td><strong><u>{$tema.t_name}</u></strong></td>
					<td class="admin_actions">
						<a href="{$tsConfig.url}/admin/temas?act=editar&tid={$tema.tid}"><img src="{$tsConfig.assets}/images/icons/editar.png" title="Editar este tema"/></a>
					 	{if $tsConfig.tema_id == $tema.tid}
							<a onclick="return false;"><img src="{$tsConfig.assets}/images/icons/yes.png" title="Este tema est&aacute; en uso" /></a>
					 	{else}
							<a href="{$tsConfig.url}/admin/temas?act=usar&tid={$tema.tid}&tt={$tema.t_name}"><img src="{$tsConfig.assets}/images/icons/theme.png" title="Usar este tema" /></a>
							{if $tema.tid != 1}
								<a href="{$tsConfig.url}/admin/temas?act=borrar&tid={$tema.tid}&tt={$tema.t_name}"><img src="{$tsConfig.assets}/images/icons/close.png" title="Borrar este tema" /></a>
							{/if}
					 	{/if}
					</td>
				</tr>
				{/foreach}
		 	</tbody>
	 	</table>
	 	<hr />
	 	<input type="button"  onclick="location.href = '{$tsConfig.url}/admin/temas?act=nuevo'"value="Instalar nuevo tema" class="mBtn btnOk" style="margin-left:280px;">
	{elseif $tsAct == 'editar'}
	 	<form action="" method="post" id="admin_form" autocomplete="off">
			<label for="ai_name">Nombre del tema:</label> <input type="text" id="ai_name" name="name" value="{$tsTema.t_name}" size="30" disabled="disabled"/> Por copyright no se pude modificar.<br class="spacer" />
		  	<label for="ai_url">Url completa del tema:</i></label> <input type="text" id="ai_url" name="url" value="{$tsTema.t_url}" size="30" /><br class="spacer" />
			<label for="ai_path">Nombre de la carpeta donde esta el tema:<br /><i>{$tsConfig.url}/themes/</i></label> <input type="text" id="ai_path" name="path" value="{$tsTema.t_path}" size="30" />
		  	<hr />
		  <label>&nbsp;</label> <input type="submit" value="Guardar tema" name="save" class="mBtn btnOk">
	 	</form>
	{elseif $tsAct == 'usar' || $tsAct == 'borrar'}
	 	<form action="" method="post" id="admin_form" autocomplete="off">
			<h3 align="center">{$tt}</h3>
			<label>&nbsp;</label> <input type="submit" name="confirm" value="{if $tsAct == 'usar'}Confirmar el cambio de{else}Continuar borrando este{/if} tema &raquo;" class="mBtn btnOk">
		  	{if $tsAct == 'borrar'}<p align="center">Te recordamos que debes borrar la carpeta del Tema manualmente en el servidor.</p>{/if}
	 	</form>
	{elseif $tsAct == 'nuevo'}
	 	{if $tsError}<div class="mensajes  error">{$tsError}</div>{/if}
	 	<form action="" method="post" id="admin_form" autocomplete="off">
			<label for="ai_path">Nombre de la carpeta donde esta el tema a instalar:<br /><i>{$tsConfig.url}/themes/</i></label> <input type="text" id="ai_path" name="path" size="30" />
		  	<hr />
		  	<label>&nbsp;</label> <input type="submit" value="Instalar tema" class="mBtn btnOk">
	 	</form>
	{/if}
</div>