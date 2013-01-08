/* ua.js -
 * Copyright (c) 2012-2013 David Unger <unger-dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version. 
 */
DEBUG = 'on';

!function ($) {
  $(function(){
    $(document).on('click', 'a', function(e){
      e.preventDefault();

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
            window.history.pushState($(this).attr('href'), '', $(this).attr('href'));
          }
        }
/* Einfach Ignorieren ;) */
      } else if ($(this).is('.nolink')) {
        return;
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
            }
          });
          window.history.pushState($(this).attr('href'), '', $(this).attr('href'));
        }
      }
    });

    $(document).on('click', "button", function(e){
      ua_log('Clicked Button '+$(this).attr("id"));
      if ($(this).attr("id") === undefined) {return;}

      e.preventDefault();
      var form = $(this).parents("form");
      var data = {button: $(this).attr("id")};
      if ($(this).is('.btn-map')) {
        form.ajaxSubmit({data: data, success: function (data) {
          $('#map-view').html(data);
          if($('#messageAjax').html() !== null) {$('#messageDisplay').html($('#messageAjax').html());$('#messageDisplay').show();}
          $('#caveName').val('');$('#xCoord').val('');$('#yCoord').val('');$("#targetCaveID option[value='-1']").attr('selected',true);
          $('#mapSliderHori').slider('value', getMapSliderValue('xCoord'));
          $('#mapSliderHori2').slider('value', getMapSliderValue('xCoord'));
          $('#mapSliderVerti').slider('value', getMapSliderValue('yCoord'));
          $('#mapSliderVerti2').slider('value', getMapSliderValue('yCoord'));
        }});
      } else {
        window.history.pushState(form.attr('action'), '', form.attr('action'));
        form.ajaxSubmit({data: data, success: updateContent});
      }
    });

    $(document).on('submit', 'form', function(e) {
      ua_log('submit form: '+$(this).attr('id'));

      if ($(this).is('.paypal')) {
        $("form").attr("target", "_blank");
      } else {
        e.preventDefault();
        alert('Achtung. Das Absenden der Buttons ist so unerwünscht!');
      }
    });

    $(document).on('click', '.box_toggle', function(e){$(this).css('display', 'none');$('#'+$(this).attr('id')+'_content').slideDown("slow");});
    $(document).on('click', '.show_hide', function(e){$('#'+$(this).attr('id')+'_content').toggle("fast");});
    $(document).on('hover', '.change_mouseover', function(){$(this).css('cursor', 'pointer');}).on("mouseout", ".change_mouseover", function() {$(this).css('cursor', 'default');});
    $(document).on("click", "input.check-all", function(e){ $(this).parents('form:eq(0)').find(':checkbox').attr('checked', this.checked);});

    $(document).on({mouseover: function() {$('div#warpoints').show();},mouseleave: function() {$('div#warpoints').hide();}}, "div#warpoints_info");
    $(document).on('mousemove', 'div#warpoints_info', function(e) {$("div#warpoints").css('top', e.pageY + 10).css('left', e.pageX + 20);});

    $(document).on('click', '.load_max', function(){if ($('#'+$(this).attr('id')+'_input').val() === ''){$('#'+$(this).attr('id')+'_input').val($(this).context.innerHTML);} else {$('#'+$(this).attr('id')+'_input').val('');}});

    $(document).on('click', '.clickmax', function(e) {if ($(this).attr('data-max') === '' || $(this).attr('data-max-id') === '') {return;}if ($('#'+$(this).attr('data-max-id')).val() == $(this).attr('data-max')) {$('#'+$(this).attr('data-max-id')).val('');} else {$('#'+$(this).attr('data-max-id')).val($(this).attr('data-max'));}});
    $(document).on('dblclick', '.dblclickmax', function(e) {if ($(this).attr('data-max') === '' || $(this).attr('data-max-id') === '') {return;}if ($('#'+$(this).attr('data-max-id')).val() == $(this).attr('data-max')) {$('#'+$(this).attr('data-max-id')).val('');} else {$('#'+$(this).attr('data-max-id')).val($(this).attr('data-max'));}});

    $(document).on('change input', '.change-movement', function(e) {updateMovement();});
    $(document).on('click', '.update-movement', function(e) {updateMovement();});
    $(document).on('click', '#selctAllUnits', function(e) {var unitData;try {unitData = jQuery.parseJSON($('#unitData').html());}catch(e) {ua_log('Fehler beim einlesen der Einheiten');return false;}for (var unit in unitData) {if($(this).attr('checked')) {$('#unit_'+unitData[unit].unit_id).val(unitData[unit].maxUnitCount);} else {$('#unit_'+unitData[unit].unit_id).val('');}}updateMovement();});
    $(document).on('change', '.move-select-bookmark', function(e) {if ($(this).val() !== '-1') {$('#targetXCoord').enable(false);$('#targetYCoord').enable(false);$('#targetCaveName').enable(false);$('#targetXCoord').val($(this).find(":selected").attr('data-xCoord'));$('#targetYCoord').val($(this).find(":selected").attr('data-yCoord'));$('#targetCaveName').val($(this).find(":selected").attr('data-caveName'));} else {$('#targetXCoord').enable(true);$('#targetYCoord').enable(true);$('#targetCaveName').enable(true);$('#targetXCoord').val('');$('#targetYCoord').val('');$('#targetCaveName').val('');}});

    function updateMovement() {
      var movementData;var unitData;var resouceData;

      try {movementData = jQuery.parseJSON($('#movementData').html());unitData = jQuery.parseJSON($('#unitData').html());resouceData = jQuery.parseJSON($('#resouceData').html());}catch(e) {ua_log('Fehler beim einlesen der Movement/Einheiten/Resourcen');return false;}

      var countAll = 0;var sizeAll = 0;var speedFactor = 0;var arealAttackAll = 0;var attackRateAll = 0;var rangeAttackAll = 0;var unitRations=0;
      for (var unit in unitData) {
        var unitID = unitData[unit].unit_id;
        var amount = parseInt($('#unit_'+unitID).val(), 10);

        if (amount > 0) {
          countAll += amount;
          sizeAll += (unitData[unit].size * amount);
          arealAttackAll += (unitData[unit].arealAttack * amount);
          attackRateAll += (unitData[unit].attackRate * amount);
          rangeAttackAll += (unitData[unit].rangeAttack * amount);
          unitRations += (unitData[unit].foodCost * amount);
          if (speedFactor === 0){speedFactor = Math.max(speedFactor, unitData[unit].speedFactor);}
          for (var resource in resouceData) {resouceData[resource].amount += (unitData[unit].encumbrance[resource].load * amount);}
        }
      }

      for (var resourceVal in resouceData) {$('#resource_'+resourceVal+'_max').html(resouceData[resourceVal].amount);$('#resource_'+resourceVal+'_max').attr('data-max', resouceData[resourceVal].amount);$('#resource_'+resourceVal).attr('data-max', resouceData[resourceVal].amount);}
      $('#countAll').html(countAll);$('#sizeAll').html(sizeAll);$('#speedFactorAll').html(speedFactor);$('#arealAttackAll').html(arealAttackAll);$('#attackRateAll').html(attackRateAll);$('#rangeAttackAll').html(rangeAttackAll);

      var movementID = $('input[name=movementID]:checked').val();
      if (movementData.movements[movementID] !== undefined && speedFactor !== 0 && $('#targetYCoord').val() && $('#targetXCoord').val()) {
        var xCoord = movementData.dim_x - Math.abs(Math.abs(parseInt($('#targetYCoord').val(), 10) - movementData.currentX) - movementData.dim_x);
        var yCoord = movementData.dim_y - Math.abs(Math.abs(parseInt($('#targetXCoord').val(), 10) - movementData.currentY) - movementData.dim_y);
        var distance = Math.ceil(Math.sqrt(xCoord*xCoord + yCoord*yCoord));
        var duration = Math.ceil(Math.sqrt(xCoord*xCoord + yCoord*yCoord) * movementData.minutesPerCave * speedFactor * movementData.movements[movementID].speedfactor);

        var tmpdist = 0;var i = 0;
        if(distance > 15){distance = distance - 15;tmpdist = 15;if(Math.floor(distance/5)<11)tmpdist += (distance % 5) * (1-0.1*Math.floor(distance/5));for(i = 1; i <= Math.floor( distance / 5) && i < 11; i++) {tmpdist += 5*(1-0.1*(i-1));}}else{tmpdist = distance;}
        var food = Math.ceil(movementData.minutesPerCave * speedFactor * movementData.movements[movementID].speedfactor * tmpdist * unitRations * movementData.foodfactor * movementData.movements[movementID].foodfactor);

        var speed = (movementData.movements[movementID].speedfactor * speedFactor);

        $('#duration').html(duration+' Min');$('#food').html(food+' '+resouceData[movementData.foodID].name);$('#speed').html(speed);
      } else {
        $('#duration').html('- Min');$('#food').html('- '+resouceData[movementData.foodID].name);$('#speed').html('0');
      }
    }

    $(document).ready(function() {
      // set page width
      var pageWidth = getLastRange(false);if (pageWidth) {ua_log('set site width from cookie');$('.container').css('width', pageWidth+'px');$('.span-content-middle').css('width', pageWidth-306+'px');$('#amount').val(pageWidth);}

      // jqDock
      var dockOptions = {align: 'middle', size: 30, labels: 'bc'};$('#header-middle-menu-item').jqDock(dockOptions);

      $(function(){$("#pageSilder").slider({range: "max",min: 940,max: 1440,value: getLastRange(true),slide: function( event, ui ) {$.cookie('page_width', ui.value);$('.container').css('width', ui.value+'px');$('.span-content-middle').css('width', ui.value-306+'px');}});});

      $('.tooltip-show').tooltip();
      $('.popover').popover();
      reParseContent();
    });
    
    function appendModal(id, title, msg, href) {var hide = (href === false) ? 'hide' : '';removeModal(id);$('body').append('<div id="'+id+'" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="messageModalLabel" aria-hidden="true"><div class="modal-header"><h3 id="messageModalLabel">'+title+'</h3></div><div class="modal-body"><p id="messageModalMsg">'+msg+'</p></div><div id="messageModalFooter" class="modal-footer '+hide+'"><button class="btn" data-dismiss="modal" aria-hidden="true">Schließen</button><a href="'+href+'" class="btn btn-primary" data-dismiss="modal" aria-hidden="true" data-post="true">Bestätigen</a></div></div>');}
    function removeModal(id) {$('#'+id).remove();}
    
    function updateContent(data) {
      ua_log('update content....');
      $('#content').html($(data).find('#content').html());
      $('#farmpoints').html($(data).find('#farmpoints').html());
      $('#warpoints_info').html($(data).find('#warpoints_info').html());
      $('#message_icon').attr('src', $(data).find('img#message_icon').attr('src'));
      document.title = $(data).filter('title').text();
      $('.tooltip-show').tooltip();
      reParseContent();
      $('#loader').hide(); $('#content').show();
    }

    function reParseContent() {
      $($('#content').html()).find('.autocomplete').each(function(i) {
        $('#'+$(this).attr('id')).autocomplete({source: 'json.php?modus='+$(this).attr('data-source'), minLength: 1});
      });

      /* parse countdown */
      $($('#content').html()).find('.timer').each(function(i) {
        var endTime = new Date(); endTime.setTime($(this).attr('data-endtime') * 1000);
        var serverTime = new Date($(this).attr('data-servertime'));
        $('#'+$(this).attr('id')).countdown({until: endTime, expiryText: '<span class="bold" style="color: red;">Fertig!</span>', compact: true, description: '', serverSync: serverTime});
      });

      var anchor = $.url().fsegment(1);
      if (anchor.length > 1 && $('#'+anchor).length > 0) {$('#mainTab a[href="#'+anchor+'"]').tab('show');}

      $(function(){$('#mapSliderHori').slider({range: "min", value: getMapSliderValue('xCoord'), min: MAP_MIN_X, max: MAP_MAX_X, slide: function( event, ui ){$('#mapSliderHori2').slider('value', ui.value);getMap(ui.value, Math.abs($('#mapSliderVerti').slider('value')));}});});
      $(function(){$('#mapSliderHori2').slider({range: "min", value: getMapSliderValue('xCoord'), min: MAP_MIN_X, max: MAP_MAX_X, slide: function( event, ui ) {$('#mapSliderHori').slider('value', ui.value);getMap(ui.value, Math.abs($('#mapSliderVerti').slider('value')));}});});
      $(function(){$('#mapSliderVerti').slider({orientation: "vertical", range: "max", value: getMapSliderValue('yCoord'), min: MAP_MAX_Y, max: MAP_MIN_Y, slide: function( event, ui ) {$('#mapSliderVerti2').slider('value', ui.value);getMap($('#mapSliderHori').slider('value'), Math.abs(ui.value));}});});
      $(function(){$('#mapSliderVerti2').slider({orientation: "vertical", range: "max", value: getMapSliderValue('yCoord'), min: MAP_MAX_Y, max: MAP_MIN_Y, slide: function( event, ui ) {$('#mapSliderVerti').slider('value', ui.value);getMap($('#mapSliderHori').slider('value'), Math.abs(ui.value));}});});
    }
    function getMapSliderValue(type) {if (type === 'xCoord') {return $('#map-queryX').html();} else if (type === 'yCoord') {return $('#map-queryY').html()*-1;}}

    function getMap(xCoord, yCoord) {
      ua_log('Load map Content: main.php?modus=map&method=ajax&xCoord='+xCoord+'&yCoord='+yCoord);

      $.ajax({
        url: 'main.php?modus=map_region&method=ajax&xCoord='+xCoord+'&yCoord='+yCoord,
        cache: false,
        dataType: 'html',
        success: function(data) {
          var useJson = true;
          try {var json = jQuery.parseJSON(data);} catch(e) {useJson = false;}
          if (useJson === true) {parseJson(json);} else {
            $('#map-view').html(data);
            if($('#messageAjax').html() !== null) {$('#messageDisplay').html($('#messageAjax').html());$('#messageDisplay').show();}
            $('#caveName').val('');$('#xCoord').val('');$('#yCoord').val('');$("#targetCaveID option[value='-1']").attr('selected',true);
          }
        }
      });
    }

    function parseJson(json) {
      if (json.mode === 'finish') {
        appendModal('modal-logout', json.title, json.msg, false);
        $('#modal-logout').modal({keyboard: false, backdrop: 'static'});
      }
    }

    function getLastRange(slider) {
      var pageWidth = $.cookie('page_width');
      if ($.isNumeric(pageWidth) && pageWidth > 940 && pageWidth < 1441) {
        return pageWidth;
      } else {
        if (slider) {return 940;}else{return 0;}
      }
    }

    function ua_log(out){if(DEBUG==='on'){console.log(out);}}
})
}(window.jQuery)