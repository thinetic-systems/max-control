
<h2 id="title">Importador en proceso</h2>


<div class='note'>

<div id="stop" style="width:120px;float:right;">
    <form action='{$urlstop}' method='post'> 
    <input type="submit" value="Detener importación"/>
    </form>
</div>

<ul style="font-size:18px;">
    <li>Fecha y hora de importación: {$status.date}</li>
    <li id="doneDate" style="display:none;">Fecha y hora de finalización: &nbsp;<span id="doneDateValue"></span></li>
    <li>Número total de cuentas a importar: <span id="total">{$status.number}</span></li>
    <li>Número de cuentas ya importadas: <span id="done">{$status.done}</span></li>
</ul>

<div id="progress_bar">
    <div class="percent" id="percentValue">0%</div>
    <div style="height: 20px; width: 680px; margin: 5px 0; background-color: #d49292; -moz-border-radius: 5px; border-radius: 5px">
        <div id="progressValue" style="height: 20px; background-color: rgb(221, 41, 40); border-top-left-radius: 5px 5px; border-top-right-radius: 5px 5px; border-bottom-right-radius: 5px 5px; border-bottom-left-radius: 5px 5px; width: 0%; ">
        </div>
    </div>
</div>

<div class="warning" id="finished" style="display:none;width: 625px;">
<h2>Terminado</h2>
<form action='{$urldelete}' method='post'> 
Para hacer una nueva importación pulse en <input type="submit" value="Borrar información de importación"/>
</form>
</div>

<div id="error" style="display:none;">
<h2>Mensajes de error</h2>
<pre id="error_messages" style="width: 660px;"></pre>
</div>

<div id="info" style="display:none;">
<h2>Mensajes de información</h2>
<pre id="info_messages" style="width: 660px;"></pre>
</div>


</div>

<script type="text/javascript">
    var ajaxurl="{$baseurl}/index.php?ajax=1";
    var percent=0;
</script>

{literal}
<script type="text/javascript">
<!--
$(document).ready(function() {
    update_progressbar();
    setInterval('update_progressbar()', 1500);
});

function update_progressbar() {
    $.ajax({
      type: "POST",
      url: ajaxurl,
      data: "accion=importprogress",
      dataType: 'json',
      success: function(data) {
          percent=parseInt(data.done/$('#total')[0].innerHTML*100);
          $('#done')[0].innerHTML=data.done;
          if(percent > 100) {
            percent=100;
            $('#done')[0].innerHTML=$('#total')[0].innerHTML;
          }
          $('#percentValue')[0].innerHTML=percent + "%";
          $('#progressValue')[0].style.width=percent + "%";
          
          if(percent > 99) {
            $('#finished')[0].style.display='';
            $('#title')[0].innerHTML="Importador finalizado";
            $('#stop')[0].style.display='none';
            $('#doneDate')[0].style.display='';
            $('#doneDateValue')[0].innerHTML=data.doneDateValue;
            if(data.timeNeeded)
                $('#doneDateValue')[0].innerHTML+=" <small>("+data.timeNeeded+")</small>";
          }
          
          $('#info_messages')[0].innerHTML=data.info;
          $('#error_messages')[0].innerHTML=data.error;
          if (data.info != '') {
            $('#info')[0].style.display='';
          }
          if (data.error != '') {
            $('#error')[0].style.display='';
          }
      }
    });
}
-->
</script>
{/literal}
