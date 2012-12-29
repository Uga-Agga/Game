DEBUG = 'on';

!function ($) {

  $(function(){
    $(document).on('click', 'a', function(event){
      event.preventDefault();

      var url = $(this).attr('href');
/* Direkte Urls. einfach folgen! */
      if ($(this).is('.absolute')) {
        ua_log('fix url redirect: '+url);
        window.location.replace(url);
/* Neues Fenster */
      } else if ($(this).is('.new-window')) {
        ua_log('Open new Window: '+url);
        window.open(url);
/* Nachladen der Erweiterungen/Froschungen... Infos */
      } else if ($(this).is('.object-detail-link')) {
        var rowDetails = $('#'+$(this).attr('data-id')+'_details');
        rowDetails.parent().toggle();
        if (rowDetails.html().length === 0) {
          rowDetails.html('<div class="text-center">Bitte warten. Seite wird geladen...<br /><img src="images/ajax-loader.gif" title="" alt="" /></div>');
          ua_log('load ajax object detail: '+url);
          $.get(url+'&method=ajax', function(data){rowDetails.html(data);});
        }
/* Öffnen des Exports Fenster */
      } else if ($(this).is('.export-link')) {
        ua_log('Open export Window: '+url);
        $('#export-dialog').dialog({autoOpen:false,height: 600,width: 800,modal: true,resizable: true,buttons: {'Schließen': function(){$( this ).dialog('close');}}});
        $('#export-dialog').dialog('open').html('Exportiere Daten...').load(url+'&method=ajax');
/* Tab Links */
      } else if ($(this).is('.tab-switch') || $(this).is('.dropdown-toggle')) {
        if (url.length > 1) {
          ua_log('switch tab: '+url);
          $('#status-msg').hide();
          if(url!=window.location) {
            window.history.pushState({path:url}, '', $(this).attr('href'));
          }
        }
/* Einfach Ignorieren ;) */
      } else if ($(this).is('.nolink')) {
        
/* Alles Andere als Ajax anfrage behandeln! */
      } else  {
        if ($(this).attr('data-reask') == 'true') {
          appendModal('modal-reask', $(this).attr('data-reask-header'), $(this).attr('data-reask-msg'), url);
          $('#modal-reask').modal({keyboard: true, backdrop: false});
          return;
        }
    
        $('#loader').show(); $('#content').hide();
        if ($(this).attr('data-post') == 'true') {
          ua_log('Load Ajax post Content: '+url);
          $.post(url+'&method=ajax', { postConfirm: "true" }, function(data) {
            var useJson = true;
            try {var json = jQuery.parseJSON(data);} catch(e) {useJson = false;}
            if (useJson === true) {parseJson(json);} else {updateContent(data);}
          });
        } else {
          ua_log('Load Ajax get Content: '+url);
          $.ajax({
            url: url+'&method=ajax',
            cache: false,
            dataType: 'html',
            success: function(data) {
              var useJson = true;
              try {var json = jQuery.parseJSON(data);} catch(e) {useJson = false;}
              if (useJson === true) {parseJson(json);} else {updateContent(data);}
            },
          });
          if(url!=window.location){window.history.pushState({path:url}, '', $(this).attr('href'));}
        }
      }
    });

    $(document).on('submit', 'form', function(e) {
      ua_log('submit ajax form: '+$(this).attr('id'));
      e.preventDefault();
      var options = {success: updateContent};
      $(this).ajaxSubmit(options); 
    });

    $(document).on("click", ".box_toggle", function(e){$(this).css('display', 'none');$('#'+$(this).attr('id')+'_content').slideDown("slow");});
    $(document).on("click", ".show_hide", function(e){$('#'+$(this).attr('id')+'_content').toggle("fast");});
    $(document).on("hover", ".change_mouseover", function(){$(this).css('cursor', 'pointer');}).on("mouseout", ".change_mouseover", function() {$(this).css('cursor', 'default');});
    
    $(document).on({mouseover: function() {$('div#warpoints').show();},mouseleave: function() {$('div#warpoints').hide();}}, "div#warpoints_info");
    $(document).on("mousemove", "div#warpoints_info", function(e) {$("div#warpoints").css('top', e.pageY + 10).css('left', e.pageX + 20);});
    
    $(document).on("click", ".load_max", function(){if ($('#'+$(this).attr('id')+'_input').val() === ''){$('#'+$(this).attr('id')+'_input').val($(this).context.innerHTML);} else {$('#'+$(this).attr('id')+'_input').val('');}});
    
    $(document).ready(function() {
      // jqDock
      var dockOptions = {align: 'middle', size: 30, labels: 'bc'};
      $('#header-middle-menu-item').jqDock(dockOptions);

      $('.tooltip-show').tooltip();
      parseTabs();
      parseAutocomplete();
      parseCountdown();
    });
    
    function appendModal(id, title, msg, href) {
      var hide = (href === false) ? 'hide' : '';
      removeModal(id);
      $('body').append('<div id="'+id+'" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="messageModalLabel" aria-hidden="true"><div class="modal-header"><h3 id="messageModalLabel">'+title+'</h3></div><div class="modal-body"><p id="messageModalMsg">'+msg+'</p></div><div id="messageModalFooter" class="modal-footer '+hide+'"><button class="btn" data-dismiss="modal" aria-hidden="true">Schließen</button><a href="'+href+'" class="btn btn-primary" data-dismiss="modal" aria-hidden="true" data-post="true">Bestätigen</a></div></div>');
    }
    
    function removeModal(id) {
      $('#'+id).remove();
    }
    
    function updateContent(data) {
      $('#content').html($(data).find('#content').html());
      $('#farmpoints').html($(data).find('#farmpoints').html());
      $('#warpoints_info').html($(data).find('#warpoints_info').html());
      document.title = $(data).filter('title').text();
      $('.tooltip-show').tooltip();
      parseTabs();
      parseAutocomplete();
      parseCountdown();
      $('#loader').hide(); $('#content').show();
    }

    function parseAutocomplete() {
      $($('#content').html()).find('.autocomplete').each(function(i) {
        $('#'+$(this).attr('id')).autocomplete({source: 'json.php?modus='+$(this).attr('data-source'), minLength: 1});
      });
    }
    function parseCountdown() {
      /* parse countdown */
      $($('#content').html()).find('.timer').each(function(i) {
        var endTime = new Date(); endTime.setTime($(this).attr('data-endtime') * 1000);
        var serverTime = new Date($(this).attr('data-servertime'));
        $('#'+$(this).attr('id')).countdown({until: endTime, expiryText: '<span class="bold" style="color: red;">Fertig!</span>', compact: true, description: '', serverSync: serverTime});
      });
    }
    function parseTabs() {
      var anchor = $.url().fsegment(1);
      if (anchor.length > 1 && $('#'+anchor).length > 0) {
        $('#mainTab a[href="#'+anchor+'"]').tab('show');}
    }
    
    function parseJson(json) {
      if (json.mode === 'finish') {
        appendModal('modal-logout', json.title, json.msg, false);
        $('#modal-logout').modal({keyboard: false, backdrop: 'static'});
      }
    }
    
    $(function(){$("#slider").slider({range: "max",min: 940,max: 1440,value: 940,slide: function( event, ui ) {$('.container').css('width', ui.value + 'px');$('.span-content-middle').css('width', ui.value-306 + 'px');}});$( "#amount" ).val( $( "#slider-range-max" ).slider( "value" ) );});
    function ua_log(out){if(DEBUG==='on'){console.log(out);}}
})

}(window.jQuery)