$ = jQuery;
console.log('theme js');
var menu = $('#block-itrtheme-account-menu > .menu');
var topLevelMenuLinks = $('#block-itrtheme-account-menu > .menu > li > a')
$(topLevelMenuLinks).click(function(e) {
  var submenu = $(this).siblings('.menu');
  if($(submenu).length > 0) {
    e.preventDefault();
    console.log('has submenu');
    $(submenu).toggle();
    if($(submenu).css('display') == 'none') {
      $(this).parent('li').removeClass('open');
    } else {
      $(this).parent('li').addClass('open');
    }
  }
});
