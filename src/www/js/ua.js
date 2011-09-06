function moreinfo(bereich, object) {
  myHead = document.getElementById(bereich);

  if (myHead.style.display == "") {
    // zuklappen
    object.src = '{{ gfx }}/de_DE/t_uga/icon_plus.png';
    myHead.style.display = "none";
  } else {
    // aufklappen
    object.src = '{{ gfx }}/de_DE/t_uga/icon_minus.png';
    myHead.style.display = "";
  }
}

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
  // set up the options to be used for jqDock...
  var dockOptions =
    { align: 'middle' // horizontal menu, with expansion DOWN from a fixed TOP edge
    , size: 30
    , labels: 'bc'  // add labels (defaults to 'br')
    };
  // ...and apply...
  $('#header-menu').jqDock(dockOptions);
 
  // The following code attaches the ajax-detail toggle to the click
  // event of all detail links on the page.  
  $('a.building-detail-link').click(function (event) {
    var url = $(this).attr('href');
    toggleObjectDetails($(this), url, event);
  });
});

function open_page(url, opt){
  if (opt == 0) // current window
    window.location = url;
  else if (opt == 1) // new window
    window.open(url);
  else if (opt == 2) // background window
  {
    window.open(url);
    self.focus();
  }
}

function exportPopup(url) {
  newwindow=window.open(url,'name','height=400,width=500, resizable=yes, scrollbars=yes');
  if (window.focus) {newwindow.focus()}
  return false;
}