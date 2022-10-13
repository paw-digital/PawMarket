	jQuery(document).ready(function($) {
		// Header fixed and Back to top button
		$(window).scroll(function() {
			if ($(this).scrollTop() > 100) {
				$('.back-to-top').fadeIn('slow');
				$('#header').addClass('header-fixed');
			} else {
				$('.back-to-top').fadeOut('slow');
				$('#header').removeClass('header-fixed');
			}
		});
		$('.back-to-top').click(function() {
			$('html, body').animate({
				scrollTop: 0
			}, 1500, 'easeInOutExpo');
			return false;
		});

		// Initiate superfish on nav menu
		$('.nav-menu').superfish({
			animation: {
				opacity: 'show'
			},
			speed: 400
		});

		// Mobile Navigation
		if ($('#nav-menu-container').length) {
			var $mobile_nav = $('#nav-menu-container').clone().prop({
				id: 'mobile-nav'
			});
			$mobile_nav.find('> ul').attr({
				'class': '',
				'id': ''
			});
			$('body').append($mobile_nav);
			$('body').prepend('<button type="button" id="mobile-nav-toggle"><i class="fa fa-bars"></i></button>');
			$('body').append('<div id="mobile-body-overly"></div>');
			$('#mobile-nav').find('.menu-has-children').prepend('<i class="fa fa-chevron-down"></i>');

			$(document).on('click', '.menu-has-children i', function(e) {
				$(this).next().toggleClass('menu-item-active');
				$(this).nextAll('ul').eq(0).slideToggle();
				$(this).toggleClass("fa-chevron-up fa-chevron-down");
			});

			$(document).on('click', '#mobile-nav-toggle', function(e) {
				$('body').toggleClass('mobile-nav-active');
				$('#mobile-nav-toggle i').toggleClass('fa-times fa-bars');
				$('#mobile-body-overly').toggle();
			});

			$(document).click(function(e) {
				var container = $("#mobile-nav, #mobile-nav-toggle");
				if (!container.is(e.target) && container.has(e.target).length === 0) {
					if ($('body').hasClass('mobile-nav-active')) {
						$('body').removeClass('mobile-nav-active');
						$('#mobile-nav-toggle i').toggleClass('fa-times fa-bars');
						$('#mobile-body-overly').fadeOut();
					}
				}
			});
		}
		else if ($("#mobile-nav, #mobile-nav-toggle").length) {
			$("#mobile-nav, #mobile-nav-toggle").hide();
		}
		  
	});
	
	
	// QR Authentication
	let qr_watch_time = 0;
	let qr_last_call = Math.floor(Date.now() / 1000);
	function watch_qr_auth(hash)
	{
		// Ease up on the checks after some time
		let interval = 5000;
		if(qr_watch_time/1000 > 60)
			interval = 8000;
		if(qr_watch_time/1000 > 120)
			interval = 15000;
		if(qr_watch_time/1000 > 600)
			interval = 60000;
		if(qr_watch_time/1000 > 1200)
			return;
		
		if(Math.floor(Date.now() / 1000) - qr_last_call < 5)
		{
			setTimeout(function() {watch_qr_auth(hash)}, interval);
			return;
		}
		
		$.ajax({
				"method": "POST",
				"url": "login/qr_auth",
				"data": {
				"hash": hash
			},
			"success": function (response) {
				if (response.result == 'success') {
					document.location = '/';
				}
				else
				{
					setTimeout(function() {watch_qr_auth(hash)}, interval);
				}
			},
			"error": function (error) {
				$("#error_message").html('An error occurred, trying again.');
				setTimeout(function() {watch_qr_auth(hash)}, interval);
			},
			"dataType": "JSON"
		});
		
		qr_watch_time += (interval + 10000);
		qr_last_call = Math.floor(Date.now() / 1000);
	}
	
	$(function() {
		if(typeof(qr_auth_hash) != 'undefined')
		{
			watch_qr_auth(qr_auth_hash);
		}
	});
	
	if(typeof(load_after) != 'undefined')
	{
		$(function() {
			load_after();
		});
	}