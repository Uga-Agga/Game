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

jQuery(document).ready(function($){
  // set up the options to be used for jqDock...
  var dockOptions =
    { align: 'middle' // horizontal menu, with expansion DOWN from a fixed TOP edge
    , size: 30
    , labels: 'bc'  // add labels (defaults to 'br')
    };
  // ...and apply...
  $('#header-menu').jqDock(dockOptions);
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