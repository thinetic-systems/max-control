
<h2>Listado de aulas</h2>



<table class="bDataTable"> 
    <tr> 
        <td> 
        <form id="aulas" action="{$urlform}" method="post"> 
          <input type='text' name='Filter' id='Filter' value="{$filter}" /> 
          <input type='submit' name='button' value="Buscar" title="Buscar" /> 
          <input type='submit' name='button' value="Actualizar archivos PXE" title="Actualizar archivos PXE" /> 
          <input type='submit' name='button' value="Limpiar archivos PXE" title="Limpiar archivos PXE" /> 
        </form>
        </td> 
    </tr> 
</table> 


<table class='dataTable'> 
    <thead> 
     <tr>
      <th class=''>Nombre</th> 
      <th class=''>Arranque por defecto</th>
      <th class=''>Cambiar</th>
     </tr>
    </thead>
 
 
    <tbody> 
      {foreach from=$aulas item=u}
      <tr class='border' id="{$u->safecn()}"> 
      {if $u->teacher_in_aula()}
        <td class='tcenter'><span>{$u->cn}</span></td> 
        <td class='tcenter'><span>{$u->getBoot()}</span></td>
        <td class='tcenter'>
            <span>
            <a href="{$urleditar}/{$u->cn}"><img src="{$baseurl}/img/edit-table.gif" alt="editar" /></a>
            </span>
        </td>
      </tr>
      {/if}
      {/foreach}

    </tbody> 
</table> 




{*
{if $DEBUG}
{debug}
{/if}
*}