(function($) {
  var menu = $('#block-itrtheme-account-menu > .menu');
  var topLevelMenuLinks = $('#block-itrtheme-account-menu > .menu > li > a')
  $(topLevelMenuLinks).click(function(e) {
    var submenu = $(this).siblings('.menu');
    if($(submenu).length > 0) {
      e.preventDefault();
      $(submenu).toggle();
      if($(submenu).css('display') == 'none') {
        $(this).parent('li').removeClass('open');
      } else {
        $(this).parent('li').addClass('open');
      }
    }
  });
  
  $(document).ready(function() {
    // tabs
    $('.itr-tabs-menu li[data-itr-menu-item]').click(function(e) {
      e.preventDefault();
      var tabIndex = $(this).attr('data-itr-tab-index');
      $('.itr-tab-content .itr-tab').removeClass('current');
      $('li[data-itr-menu-item]').removeClass('current');
      $(this).addClass('current');
      $('.itr-tab[data-itr-tab-index="' + tabIndex + '"]').addClass('current');
    });
  });  
})(jQuery);