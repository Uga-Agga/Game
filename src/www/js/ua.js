/** this method is used in the improvement and defense screens
 to load more details from the server and show and hide the
 corresponding element. Assumes a specific layout of the rows;
 namely, there is a row containing the "header" and the links,
 this row is followed by another row, that should be toggled,
 and this row contains one element td.object-details that
 should be populated with the html from the server.*/
function toggleObjectDetails(a, url, event) {
  var element = a.parents('tr.object-row').next();
  var content = element.contents('td.object-details');
  if (content.children().length == 0) {
    content.html('Loading...')
    .load(url+"&method=ajax");
  }
  element.toggle();
  event.preventDefault();
}

function displayExportDialog(a, url, event) {
  
    // prepare dialog window
    $('#export-dialog').dialog({
        autoOpen:false,
        height: 600,
        width: 800,
        modal: true,
        resizable: true,
        buttons: {
          Schließen: function() {
            $(this).dialog('close');
          }
        }
    });
    
    $('#export-dialog').dialog('open').html("Exportiere Daten...").load(url+"&method=ajax");

}

wmtt = null;
document.onmousemove = updateWMTT;
function updateWMTT(e) {
  if (wmtt != null && wmtt.style.display == 'block') {
    x = (e.pageX ? e.pageX : window.event.x) + wmtt.offsetParent.scrollLeft - wmtt.offsetParent.offsetLeft;
    y = (e.pageY ? e.pageY : window.event.y) + wmtt.offsetParent.scrollTop - wmtt.offsetParent.offsetTop;
    wmtt.style.left = (x + 10) + "px";
    wmtt.style.top   = (y + 10) + "px";
  }
}
function showWMTT(id) {
  wmtt = document.getElementById(id);
  wmtt.style.display = "block";
}
function hideWMTT() {
  wmtt.style.display = "none";
}

$(document).ready(function() {
  $('a.new-window').click(function (event) {
    window.open($(this).attr('href'));
    return false;
  });

  // The following code attaches the ajax-detail toggle to the click
  // event of all detail links on the page.  
  $('a.object-detail-link').click(function (event) {
    var url = $(this).attr('href');
    toggleObjectDetails($(this), url, event);
  });

  $('.box_toggle').click(function(){
    $(this).css('display', 'none');
    $('#'+$(this).attr('id')+'_content').slideDown("slow");
  });

  $('.box_show_hide').click(function(){
    $('#'+$(this).attr('id')+'_content').toggle("fast");
  });

  $('.change_mouseover').hover(
    function() { $(this).css('cursor', 'pointer'); },
    function() { $(this).css('cursor', 'default'); }
  );

  //function for tutorial form dropdown
  function tutorial() {
    if ($(".ua-tutorial-box").is(":hidden")){
      $(".ua-tutorial-box").slideDown("slow");
    }
    else{
      $(".ua-tutorial-box").slideUp("slow");
    }
  }

  //run contact form when any contact link is clicked
  $(".ua-tutorial-button").click(function(){tutorial()});

  //display export popup
  $('a.export-link').click(function (event) {
    var url = $(this).attr('href');
    displayExportDialog(this, url, event);
    event.preventDefault();
  });

  // set up the options to be used for jqDock...
  var dockOptions =
    { align: 'middle' // horizontal menu, with expansion DOWN from a fixed TOP edge
    , size: 30
    , labels: 'bc'  // add labels (defaults to 'br')
    };
  // ...and apply...
  $('#ua-head-menu-item').jqDock(dockOptions);

  var tabs = $(".jquery_tabs").accessibleTabs({
    tabhead: 'h6',
    fx:'fadeIn',
    saveState:true
  });
});