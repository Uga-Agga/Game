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
      if ($(this).attr('href') === undefined) {return;}

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
        $('#modalLabel').html('Export');
        $('#modalLabelClose').show();
        $('#modalFooter').hide();
        $('#modal').modal({keyboard: true, backdrop: true});
        $('#modal').children('div.modal-body').html('Daten werden geladen...').load(url+'&method=ajax');
/* Öffnen des Detail Fenster */
      } else if ($(this).is('.popup-detail')) {
        ua_log('Open export Window: '+url);
        if ($(this).attr('data-title')) {$('#modalLabel').html($(this).attr('data-title'));} else {$('#modalLabel').html('Details');}
        $('#modalLabelClose').show();
        $('#modalFooter').hide();
        $('#modal').modal({keyboard: true, backdrop: true});
        $('#modal').children('div.modal-body').html('Daten werden geladen...').load(url+'&method=ajax');
/* Tab Links */
      } else if ($(this).is('.tab-switch') || $(this).is('.dropdown-toggle')) {
        if (url.length > 1) {
          ua_log('switch tab: '+url);
          $('#status-msg').hide();
          $('#mainTab a[href="'+url+'"]').tab('show');
          if(url!=window.location) {
            var urlHistory = $.url().attr('path')+"?"+$.url().attr('query')+$(this).attr('href');
            pushState(urlHistory);
          }
        }
/* Einfach Ignorieren ;) */
      } else if ($(this).is('.nolink')) {
        return;
/* Alles Andere als Ajax anfrage behandeln! */
      } else  {
        if ($(this).attr('data-reask') == 'true') {
          $('#modalLabel').html($(this).attr('data-reask-header'));
          $('#modalLabelClose').show();
          $('#modalFooterHref').attr('href', url);
          $('#modalFooter').show();
          $('#modal').modal({keyboard: true, backdrop: false});
          $('#modal').children('div.modal-body').html($(this).attr('data-reask-msg'));
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
          pushState($(this).attr('href'));
        }
      }
    });

    window.addEventListener('load', function() {
      setTimeout(function() {
        window.addEventListener('popstate', function() {
          ua_log('please go back');
          var url = $.url(window.history.state);

          $.ajax({
            url: url.attr('path')+'?'+url.attr('query')+'&method=ajax#'+url.fsegment(1),
            cache: false,
            dataType: 'html',
            success: function(data) {
              var useJson = true;
              try {var json = jQuery.parseJSON(data);} catch(e) {useJson = false;}
              if (useJson === true) {parseJson(json);} else {updateContent(data);}
            }
          });
        });
      }, 0);
    });

    $(document).on('click', "button", function(e){
      ua_log('Clicked Button '+$(this).attr("id"));
      if ($(this).attr("id") === undefined) {return;}

      e.preventDefault();
      var form = $(this).parents('form');

      if (form.attr('data-reask') == 'true') {
        $('#modalLabel').html(form.attr('data-reask-header'));
        $('#modalLabelClose').show();
        $('#modalFooterHref').attr('href', form.attr('action')+'&'+form.serialize());
        $('#modalFooter').show();
        $('#modal').modal({keyboard: true, backdrop: true});
        $('#modal').children('div.modal-body').html(form.attr('data-reask-msg'));
        return;
      }

      var data = {button: $(this).attr("id")};
      if ($(this).is('.btn-map')) {
        form.ajaxSubmit({data: data, success: function (data) {
          $('#map-view').html(data);
          if($('#messageAjax').html() !== null) {$('#messageDisplay').html($('#messageAjax').html());$('#messageDisplay').show();}
          $('#caveName').val('');$('#xCoord').val('');$('#yCoord').val('');$("#targetCaveID option[value='-1']").attr('selected',true);

          var xCoord = getMapSliderValue('xCoord');
          var yCoord = getMapSliderValue('yCoord');
          $('#mapSliderHori').slider({value: xCoord});
          $('#mapSliderHori2').slider({value: xCoord});
          $('#mapSliderVerti').slider({value: yCoord});
          $('#mapSliderVerti2').slider({value: yCoord});
        }});
      } else {
        pushState(form.attr('action'));
        form.ajaxSubmit({data: data, success: updateContent, clearForm: true, resetForm:true, cache:false});
      }
    });

    $(document).on('submit', 'form', function(e) {
      ua_log('submit form: '+$(this).attr('id'));

      if ($(this).is('.paypal')) {
        $("form").attr("target", "_blank");
      } else {
        e.preventDefault();
        $('#'+$(this).attr('id')+'Submit').click();
      }
    });

    $(document).on('click', '.box_toggle', function(e){$(this).css('display', 'none');$('#'+$(this).attr('id')+'_content').slideDown("slow");});
    $(document).on('click', '.show_hide', function(e){$('#'+$(this).attr('id')+'_content').toggle("fast");});
    $(document).on('hover', '.change_mouseover', function(){$(this).css('cursor', 'pointer');}).on("mouseout", ".change_mouseover", function() {$(this).css('cursor', 'default');});
    $(document).on('click', 'input.check-all', function(e){ $(this).parents('form:eq(0)').find(':checkbox').attr('checked', this.checked);});

    $(document).on({mouseover: function() {$('div#warpoints').show();},mouseleave: function() {$('div#warpoints').hide();}}, "div#warpoints_info");
    $(document).on('mousemove', 'div#warpoints_info', function(e) {$("div#warpoints").css('top', e.pageY + 10).css('left', e.pageX + 20);});

    $(document).on('click', '.load_max', function(){if ($('#'+$(this).attr('id')+'_input').val() === ''){$('#'+$(this).attr('id')+'_input').val($(this).context.innerHTML);} else {$('#'+$(this).attr('id')+'_input').val('');}});

    $(document).on('click', '.clickmax', function(e) {if ($(this).attr('data-max') === '' || $(this).attr('data-max-id') === '') {return;}if ($('#'+$(this).attr('data-max-id')).val() == $(this).attr('data-max')) {$('#'+$(this).attr('data-max-id')).val('');} else {$('#'+$(this).attr('data-max-id')).val($(this).attr('data-max'));}});
    $(document).on('dblclick', '.dblclickmax', function(e) {if ($(this).attr('data-max') === '' || $(this).attr('data-max-id') === '') {return;}if ($('#'+$(this).attr('data-max-id')).val() == $(this).attr('data-max')) {$('#'+$(this).attr('data-max-id')).val('');} else {$('#'+$(this).attr('data-max-id')).val($(this).attr('data-max'));}});

    $(document).on('change input', '.change-movement', function(e) {updateMovement();});
    $(document).on('click', '.update-movement', function(e) {updateMovement();});
    $(document).on('click', '#selctAllUnits', function(e) {var unitData;try {unitData = jQuery.parseJSON($('#unitData').html());}catch(e) {ua_log('Fehler beim einlesen der Einheiten');return false;}for (var unit in unitData) {if($(this).attr('checked')) {$('#unit_'+unitData[unit].unit_id).val(unitData[unit].maxUnitCount);} else {$('#unit_'+unitData[unit].unit_id).val('');}}updateMovement();});
    $(document).on('change', '.move-select-bookmark', function(e) {if ($(this).val() !== '-1') {$('#targetXCoord').enable(false);$('#targetYCoord').enable(false);$('#targetCaveName').enable(false);$('#targetXCoord').val($(this).find(":selected").attr('data-xCoord'));$('#targetYCoord').val($(this).find(":selected").attr('data-yCoord'));$('#targetCaveName').val($(this).find(":selected").attr('data-caveName'));} else {$('#targetXCoord').enable(true);$('#targetYCoord').enable(true);$('#targetCaveName').enable(true);$('#targetXCoord').val('');$('#targetYCoord').val('');$('#targetCaveName').val('');}updateMovement();});

    $(document).on('click', '.show-tutorial', function(){showTutorialModal();});

    $(document).on('dblclick', '.jm_actions', function(e) {$(this).parent().css('top', '');$(this).parent().css('left', '');});

    $('#modal').on('hidden', function () {ua_log('close modal');});
    $('#modal').on('show', function () {ua_log('show modal');});

    $(document).on('mousemove', '#minimapImg', function(e){
      // Fix Firefox "bug"
      if(typeof e.offsetX === "undefined" || typeof e.offsetY === "undefined") {
         var targetOffset = $(e.target).offset();
         e.offsetX = e.pageX - targetOffset.left;
         e.offsetY = e.pageY - targetOffset.top;
      }
      var xCoord = Math.abs(parseInt(e.offsetX/12, 10))+1;
      var yCoord = Math.abs(parseInt(e.offsetY/12, 10))+1;

      var data = $('#minimapData').html();
      try {var mapData = jQuery.parseJSON(data);} catch(e) {ua_log('Fehler beim parsen der minimap daten');return;}
      $('#minimapInfo').html(mapData[xCoord][yCoord].title);

      $('#minimapInfo').css({'top': e.pageY+20,'left': e.pageX+20});
    });
    $(document).on({mouseover: function() {$('#minimapInfo').show();},mouseleave: function() {$('#minimapInfo').hide();}}, "#minimapImg");

    $(document).on('click', '#minimapImg', function(e) {
      if(typeof e.offsetX === "undefined" || typeof e.offsetY === "undefined") {
         var targetOffset = $(e.target).offset();
         e.offsetX = e.pageX - targetOffset.left;
         e.offsetY = e.pageY - targetOffset.top;
      }
      var xCoord = Math.abs(parseInt(e.offsetX/12, 10))+1;
      var yCoord = Math.abs(parseInt(e.offsetY/12, 10))+1;

      var url="main.php?modus=map&xCoord="+xCoord+"&yCoord="+yCoord
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
      pushState(url);
    });

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

      if ($('#maxUnitsSize').html() !== null) {
        if (sizeAll > $('#maxUnitsSize').html()) {
          $('#labelMaxUnitsSize').css("color", "#FF0000");
        } else {
          $('#labelMaxUnitsSize').css("color", "#000000");
        }
      }

      for (var resourceVal in resouceData) {$('#resource_'+resourceVal+'_max').html(resouceData[resourceVal].amount);$('#resource_'+resourceVal+'_max').attr('data-max', resouceData[resourceVal].amount);$('#resource_'+resourceVal).attr('data-max', resouceData[resourceVal].amount);}
      $('#countAll').html(countAll);$('#sizeAll').html(sizeAll);$('#speedFactorAll').html(speedFactor);$('#arealAttackAll').html(arealAttackAll);$('#attackRateAll').html(attackRateAll);$('#rangeAttackAll').html(rangeAttackAll);

      var movementID = $('input[name=movementID]:checked').val();
      if (movementData.movements[movementID] !== undefined && speedFactor !== 0 && $('#targetYCoord').val() && $('#targetXCoord').val()) {
        var xCoord = movementData.dim_x - Math.abs(Math.abs(parseInt($('#targetXCoord').val(), 10) - movementData.currentX) - movementData.dim_x);
        var yCoord = movementData.dim_y - Math.abs(Math.abs(parseInt($('#targetYCoord').val(), 10) - movementData.currentY) - movementData.dim_y);
        var distance = Math.ceil(Math.sqrt(xCoord*xCoord + yCoord*yCoord));

        var duration = Math.ceil(Math.sqrt(xCoord*xCoord + yCoord*yCoord) * movementData.minutesPerCave * speedFactor * movementData.movements[movementID].speedfactor);

        var tmpdist = 0;var i = 0;
        if(distance > 15){distance = distance - 15;tmpdist = 15;if(Math.floor(distance/5)<11)tmpdist += (distance % 5) * (1-0.1*Math.floor(distance/5));for(i = 1; i <= Math.floor( distance / 5) && i < 11; i++) {tmpdist += 5*(1-0.1*(i-1));}}else{tmpdist = distance;}
        var food = Math.ceil(movementData.minutesPerCave * speedFactor * movementData.movements[movementID].speedfactor * tmpdist * unitRations * movementData.foodfactor * movementData.movements[movementID].foodfactor);

        var speed = (movementData.movements[movementID].speedfactor * speedFactor);

        $('#duration').html(TimeString(duration));$('#food').html(food+' '+resouceData[movementData.foodID].name);$('#speed').html(speed);
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
      $('#modal').modal({show: false, keyboard: true, backdrop: true});

      $('.jm_chat-content').draggable({handle: '.jm_actions'});

      reParseContent();
    });

    function updateContent(data) {
      ua_log('update content....');
      $('#content').html($(data).find('#content').html());
      $('#farmpoints').html($(data).find('#farmpoints').html());
      $('#warpoints_info').html($(data).find('#warpoints_info').html());
      $('#region-info').html($(data).find('#region-info').html());
      $('#message_icon').attr('src', $(data).find('#message_icon').attr('src'));
      $('#servertime').html($(data).find('#servertime').html());

      $('#tutorialDataOpen').html($(data).find('#tutorialDataOpen').html());
      $('#tutorialDataFinish').html($(data).find('#tutorialDataFinish').html());
      $('#tutorialDataUrl').html($(data).find('#tutorialDataUrl').html());
      $('#tutorialDataHeader').html($(data).find('#tutorialDataHeader').html());
      $('#tutorialDataBody').html($(data).find('#tutorialDataBody').html());

      document.title = $(data).filter('title').text();
      reParseContent();
      $('#loader').hide();$('#content').show();
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

      $('.tooltip-show').tooltip();

      var anchor = $.url().fsegment(1);
      if (anchor.length > 1 && $('#'+anchor).length > 0) {$('#mainTab a[href="#'+anchor+'"]').tab('show');}

      if ($('#tutorialDataOpen').html() !== null) {
        if ($('#tutorialDataOpen').html() === 'true') {
          showTutorialModal();
        } else {
          $('#modal').modal('hide');
        }
      } else {
        $('#modal').modal('hide');
      }

      var xCoord = getMapSliderValue('xCoord');
      var yCoord = getMapSliderValue('yCoord');
      $(function(){$('#mapSliderHori').slider({range: "min", value: xCoord, min: MAP_MIN_X, max: MAP_MAX_X, slide: function( event, ui ){$('#mapSliderHori2').slider({value: ui.value});getMap(ui.value, Math.abs($('#mapSliderVerti').slider('value')));}});});
      $(function(){$('#mapSliderHori2').slider({range: "min", value: xCoord, min: MAP_MIN_X, max: MAP_MAX_X, slide: function( event, ui ) {$('#mapSliderHori').slider({value: ui.value});getMap(ui.value, Math.abs($('#mapSliderVerti').slider('value')));}});});
      $(function(){$('#mapSliderVerti').slider({orientation: "vertical", range: "max", value: yCoord, min: MAP_MAX_Y, max: MAP_MIN_Y, slide: function( event, ui ) {$('#mapSliderVerti2').slider({value: ui.value});getMap($('#mapSliderHori').slider('value'), Math.abs(ui.value));}});});
      $(function(){$('#mapSliderVerti2').slider({orientation: "vertical", range: "max", value: yCoord, min: MAP_MAX_Y, max: MAP_MIN_Y, slide: function( event, ui ) {$('#mapSliderVerti').slider({value: ui.value});getMap($('#mapSliderHori').slider('value'), Math.abs(ui.value));}});});
    }
    function getMapSliderValue(type) {if (type === 'xCoord') {return $('#map-queryX').html();} else if (type === 'yCoord') {return $('#map-queryY').html()*-1;}}

    function showTutorialModal() {
      if ($('#tutorialDataHeader').html() === '' || $('#tutorialDataHeader').html() === null || $('#tutorialDataBody').html() === '' || $('#tutorialDataBody').html() === null) {
        $('#modal').modal('hide');
        return;
      }

      $('#modalLabel').html($('#tutorialDataHeader').html());
      $('#modalLabelClose').show();

      if ($('#tutorialDataFinish').html() === 'true') {
        $('#modalFooterHref').attr('href', $('#tutorialDataUrl').text());
        $('#modalFooter').show();
      } else {
        $('#modalFooter').hide();
      }
      $('#modal').children('div.modal-body').html($('#tutorialDataBody').html());
      $('#modal').modal('show');
    }

    function getMap(xCoord, yCoord) {
      if (xCoord < 1) xCoord = 1;
      if (yCoord < 1) yCoord = 1;
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
      if (json.mode === 'finish') {$('#loader').hide();$('#modalLabel').html(json.title);$('#modalLabelClose').hide();$('#modalFooter').hide();$('#modal').modal({keyboard: false, backdrop: 'static'});$('#modalBody').css('text-align', 'center');$('#modal').children('div.modal-body').html(json.msg);}
    }

    function getLastRange(slider) {
      var pageWidth = $.cookie('page_width');
      if ($.isNumeric(pageWidth) && pageWidth > 940 && pageWidth < 1441) {
        return pageWidth;
      } else {
        if (slider) {return 940;}else{return 0;}
      }
    }

  function TimeString(duration) {
    var time = duration * 60;
    var hours = Math.floor(time/3600);
    var minutes = Math.floor((time%3600)/60);
    if(!hours) return minutes+" Min";
    var text = duration + " Min ("+hours+" Std";
    if (minutes) text = text + " "+((minutes<10)?"0":"")+minutes+" Min";
    text = text + ")";
    return text;
  }

  function pushState(url) {
    if ($.browser.msie && $.browser.version <= 9) {
      return;
    }
    if (navigator && navigator.userAgent && navigator.userAgent != null) {
      var strUserAgent = navigator.userAgent.toLowerCase();
      var arrMatches = strUserAgent.match(/(iphone|ipod|ipad)/);
      if (arrMatches) return;
    }

    window.history.pushState(null, null, url);
  }

  function ua_log(out){if(DEBUG==='on'&&typeof console !== "undefined"&&typeof console.log !== "undefined"){console.log(out);}}
})
}(window.jQuery)