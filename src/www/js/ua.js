DEBUG = 'on';

$(document).on('click', 'a', function(event){
  event.preventDefault();

  var url = $(this).attr('href');
  if ($(this).is('.absolute')) {
    ua_log('fix url redirect: '+url);
    window.location.replace(url);
  } else if ($(this).is('.new-window')) {
    ua_log('Open new Window: '+url);
    window.open(url);
  } else if ($(this).is('.object-detail-link')) {
    var parent = $(this).parents('tr.object-row').next();
    parent.toggle();
    if (parent.children().html().length === 0) {
      parent.children().html('<div class="text-center">Bitte warten. Seite wird geladen...<br /><img src="images/ajax-loader.gif" title="" alt="" /></div>');
      ua_log('load ajax object detail: '+url);
      $.get(url+'&method=ajax', function(data){parent.children().html(data);});
    }
  } else if ($(this).is('.export-link')) {
    ua_log('Open export Window: '+url);
    $('#export-dialog').dialog({autoOpen:false,height: 600,width: 800,modal: true,resizable: true,buttons: {'Schließen': function(){$( this ).dialog('close');}}});
    $('#export-dialog').dialog('open').html('Exportiere Daten...').load(url+'&method=ajax');
  } else {
    ua_log('Load Ajax Content: '+url);
    $('#loader').show(); $('#content').hide();
    $.get(url+'&method=ajax', function(data) {
      try {
        var json = jQuery.parseJSON(data);
        if (json.mode === 'finish') {
          $("#overlay").show();
          $('#loader').html('<div class="text-center">'+json.msg+'</div>');
          $('#loader').attr('title', json.title);
          $('#loader').dialog({width: 600, height: 200});
        }
      } catch(e) {
        $('#loader').dialog(); $('#loader').dialog('destroy');
        $('#content').html($(data).find('#content').html());
        $('#farmpoints').html($(data).find('#farmpoints').html());
        $('#warpoints_info').html($(data).find('#warpoints_info').html());
        document.title = $(data).filter('title').text();
        $('#loader').hide(); $('#content').show(); 
      }
    });
    if(url!=window.location){window.history.pushState({path:url}, '', $(this).attr('href'));}
  }
});

$(document).on("click", ".box_toggle", function(event){$(this).css('display', 'none');$('#'+$(this).attr('id')+'_content').slideDown("slow");});
$(document).on("hover", ".change_mouseover", function(){$(this).css('cursor', 'pointer');}).on("mouseout", ".change_mouseover", function() {$(this).css('cursor', 'default');});

$(document).on({mouseover: function() {$('div#warpoints').show();},mouseleave: function() {$('div#warpoints').hide();}}, "div#warpoints_info");
$(document).on("mousemove", "div#warpoints_info", function(e) {$("div#warpoints").css('top', e.pageY + 10).css('left', e.pageX + 20);});

$(document).on("click", ".load_max", function(){if ($('#'+$(this).attr('id')+'_input').val() === ''){$('#'+$(this).attr('id')+'_input').val($(this).context.innerHTML);} else {$('#'+$(this).attr('id')+'_input').val('');}});

$(document).ready(function() {
  $(".nav1").accessibleTabs({tabhead: 'h4', fx:'fadeIn', saveState:true, cssClassAvailable:true, autoAnchor:false});
  $(".nav2").accessibleTabs({wrapperClass: 'content2', currentClass: 'current2', tabhead: 'h5', tabbody: '.tabbody2', fx:'fadeIn', cssClassAvailable:true, autoAnchor:false});

  // jqDock
  var dockOptions = {align: 'middle', size: 30, labels: 'bc'};
  $('#ua-head-menu-item').jqDock(dockOptions);
});

function ua_log(out){if(DEBUG==='on'){console.log(out);}}