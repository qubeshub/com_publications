jQuery(document).ready(function() {

  String.prototype.nohtml = function () {
  	return this + (this.indexOf('?') == -1 ? '?' : '&') + 'no_html=1';
  };

  // Informational button
  $("a.modal-link").fancybox({
    maxWidth : 450
  });

  // Check the url to see which nav button to show as active
  $(window).on('load', function() {
    if (window.location.href.indexOf('browse') > -1) {
      $('.browse-link').addClass('active');
    } else if (window.location.href.indexOf('oer') > -1) {
      $('.oer-link').addClass('active');
    } else if(window.location.href.indexOf('submit') > -1) {
      $('.submit-link').addClass('active');
    } else if (window.location.href.indexOf('#') > -1) {
      // If user clicks to log in
      window.location = '/login';
    } else {
      $('.browse-link').addClass('active');
    }
  });

  // Mobile filtering
  var $mobileFilter = $('.mobile-filter'),
      $filterWrap = $('.page-filter');

  $(window).on('load resize', function() {
    if ($mobileFilter.is(':visible')) {
      $filterWrap.addClass('collapsed');
    } else {
      $filterWrap.removeClass('collapsed');
    }
  });

  $('.mobile-filter').click(function() {
    $('.page-filter').toggleClass('collapsed');
  });

  $('#submit-resource').fancybox({
    type: 'ajax',
    scrolling: true,
    autoSize: false,
    fitToView: true,
    titleShow: false,
    tpl: {
      wrap: '<div class="fancybox-wrap"><div class="fancybox-skin"><div class="fancybox-outer"><div id="sbox-content" class="fancybox-inner"></div></div></div></div>'
    },
    beforeLoad: function () {
      href = $(this).attr('href');
      $(this).attr('href', href.nohtml());
    }
  });

  //Sticky nav for Mobile
  const $navBar = $('.nav-page');
  const $subNavHeader = $('.sub');
  const $filterWrapper = $('.page-filter-wrapper');
  let scrollTop = 0;
  let windowTop = 0;

  $('.content-panel').on('resize scroll', function() {
    let $sticky = $navBar.offset().top;
    let $qubesHeaderHeight = $('.wrap-main').height();
    let $pageMenuHeight = $navBar.height();
    windowTop = $('.content-panel').scrollTop();

    if (windowTop > scrollTop) {
			scrollingDown = true;
		} else {
			scrollingDown = false;
		}
		scrollTop = windowTop;

    if ($mobileFilter.is(':visible')) {
      $('.page-filter-wrapper.sticky').css({'top': $qubesHeaderHeight + $pageMenuHeight + 'px'});
      if ($('.content-panel').scrollTop() > $sticky) {
        if (scrollingDown) {
          setTimeout(function() {
            $navBar.addClass('sticky');
            $filterWrapper.addClass('sticky');
          }, 300);
        }
      } else {
        $navBar.removeClass('sticky');
        $filterWrapper.removeClass('sticky');
      }
    } else {
      $navBar.removeClass('sticky');
      $filterWrapper.removeClass('sticky');
    }
  });
});
