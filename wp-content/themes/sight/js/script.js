jQuery.noConflict();
(function($) {
    $(function() {
        /*** new visitor ? **/
        var vists,
        $qElement = $("#newhere"),
        visits = localStorage.getItem('visits'),
        orgVisits = visits;
        
        if( visits && document.location.host != document.referrer.split("/")[2] ) {
            visits++;
            
        } else if (!visits) {
          visits = 1;
        }
        
        if (visits < 5) {
         //   $qElement.show( "fast" );
            $qElement.fadeIn(); 
        }
                
       if (orgVisits != visits)  {
           localStorage.setItem('visits', visits);
       }
        
        /*** Dropdown menu ***/
        
        var timeout    = 200;
        var closetimer = 0;
        var ddmenuitem = 0;

        function dd_open() {
            dd_canceltimer();
            dd_close();
            var liwidth = $(this).width();
            ddmenuitem = $(this).find('ul').css({'visibility': 'visible'});
            ddmenuitem.prev().addClass('dd_hover').parent().addClass('dd_hover');
        }

        function dd_close() {
            if(ddmenuitem) ddmenuitem.css('visibility', 'hidden').prev().removeClass('dd_hover').parent().removeClass('dd_hover');
        }

        function dd_timer() {closetimer = window.setTimeout(dd_close, timeout);
        }

        function dd_canceltimer() {
            if (closetimer) {
                window.clearTimeout(closetimer);
                closetimer = null;
            }
        }
        document.onclick = dd_close;

        $('#dd > li, #tdd > li').bind('mouseover', dd_open);
        $('#dd > li, #tdd > li').bind('mouseout',  dd_timer);

        $('#larr, #rarr').hide();
        $('.slideshow').hover(
            function(){
                $('#larr, #rarr').show();
            }, function(){
                $('#larr, #rarr').hide();
            }
        );

        /*** View mode ***/

        if ( $.cookie('mode') == 'grid' ) {
            grid_update();
        } else if ( $.cookie('mode') == 'list' ) {
            list_update();
        }

        $('#mode').toggle(
            function(){
                if ( $.cookie('mode') == 'grid' ) {
                    $.cookie('mode','list');
                    list();
                } else {
                    $.cookie('mode','grid');
                    grid();
                }
            },
            function(){
                if ( $.cookie('mode') == 'list') {
                    $.cookie('mode','grid');
                    grid();
                } else {
                    $.cookie('mode','list');
                    list();
                }
            }
        );

        function grid(){
            $('#mode').addClass('flip');
            $('#loop')
                .fadeOut('fast', function(){
                    grid_update();
                    $(this).fadeIn('fast');
                })
            ;
        }

        function list(){
            $('#mode').removeClass('flip');
            $('#loop')
                .fadeOut('fast', function(){
                    list_update();
                    $(this).fadeIn('fast');
                })
            ;
        }

        function grid_update(){
            $('#loop').addClass('grid').removeClass('list');
            $('#loop').find('.thumb img').attr({'width': '190', 'height': '190'});
            $('#loop').find('.post')
                .mouseenter(function(){
                    $(this)
                        .css('background-color','#FFEA97')
                        .find('.thumb').hide()
                        .css('z-index','-1');
                })
                .mouseleave(function(){
                    $(this)
                        .css('background-color','#f5f5f5')
                        .find('.thumb').show()
                        .css('z-index','1');
                });
            $('#loop').find('.post').click(function(){
                location.href=$(this).find('h2 a').attr('href');
            });
            $.cookie('mode','grid');
        }

        function list_update(){
            $('#loop').addClass('list').removeClass('grid');
            $('#loop').find('.post').removeAttr('style').unbind('mouseenter').unbind('mouseleave');
            $('#loop').find('.thumb img').attr({'width': '290', 'height': '290'});
            $.cookie('mode', 'list');
        }
		/*** tabbed widget ***/

  $("#tabs").tabs({ fx: { opacity: 'toggle' } });

		/**** Multi-col cats ****/
		
		$(".widget_categories ul").columns(2);

        /*** Ajax-fetching posts ***/

        $('#pagination a').live('click', function(e){
            e.preventDefault();
            $(this).addClass('loading').text('LOADING...');
            $.ajax({
                type: "GET",
                url: $(this).attr('href') + '#loop',
                dataType: "html",
                success: function(out){
                    result = $(out).find('#loop .post');
                    nextlink = $(out).find('#pagination a').attr('href');
                    $('#loop').append(result.fadeIn(300));
                    $('#pagination a').removeClass('loading').text('LOAD MORE');
                    if (nextlink != undefined) {
                        $('#pagination a').attr('href', nextlink);
                    } else {
                        $('#pagination').remove();
                    }
                    if ( $.cookie('mode') == 'grid' ) {
                        grid_update();
                    } else {
                        list_update();
                    }
                }
            });
        });

        /*** Misc ***/

        $('#comment, #author, #email, #url')
        .focusin(function(){
            $(this).parent().css('border-color','#888');
        })
        .focusout(function(){
            $(this).parent().removeAttr('style');
        });
        $('.rpthumb:last, .comment:last').css('border-bottom','none');
		
		
		/* selectbox */
		
        if ($("#sermon-filter").length > 0) {
           $("#sermon_speaker").selectbox();
           $("#sermon_format").selectbox();
           $("#sermon_series").selectbox();
           $("#sermon_topic").selectbox();
		   $("#bible_book").selectbox()
		   console.log("selectbox called");
        }
		
		// 125 Ads + Flickr Widget + 120x240 (Sidebar)

		$("#sidebar .ads-125 img, #sidebar .flickr_badge_image img, .ads-120x240 img").css({
				backgroundColor: "#f4f4f4"
			});
		/*$("#sidebar .ads-125 img, #sidebar .flickr_badge_image img, .ads-120x240 img").hover(function() {
			$(this).stop().animate({
				backgroundColor: "#333333"
				}, 300);
			},function() {
			$(this).stop().animate({
				backgroundColor: "#f4f4f4"
				}, 500);
		}); */

// Flickr Widget + 125 Ads (Footer)

		$("#footer .flickr_badge_image img, #footer .ads-125 img").css({
				backgroundColor: "#202020"
			});
		/*$("#footer .flickr_badge_image img, #footer .ads-125 img").hover(function() {
			$(this).stop().animate({
				backgroundColor: "#141414"
				}, 300);
			},function() {
			$(this).stop().animate({
				backgroundColor: "#202020"
				}, 500);
		});*/


		$(".tz_tab_widget .tab-thumb img, .tab-comments .avatar, .post .post-thumb img, #related-posts .post-thumb img").css({
				backgroundColor: "#ffffff"
			});
		/*$(".tz_tab_widget img.mini-thumbnail, .tab-comments .avatar, .post img.thumbnail, #related-posts img.thumbnail").hover(function() {
			$(this).stop().animate({
				backgroundColor: "#333333"
				}, 300);
			},function() {
			$(this).stop().animate({
				backgroundColor: "#ffffff"
				}, 500);
		});*/

// Tabbed Widget Tags
		$(".tz_tab_widget .tab-tags a").css({
				backgroundColor: "#ffffff",
				color: "#999999"
			});
		/*$(".tz_tab_widget .tab-tags a").hover(function() {
			$(this).stop().animate({
				backgroundColor: "#79DED8",
				color: "#fffffft"
				}, 300);
			},function() {
			$(this).stop().animate({
				backgroundColor: "#ffffff",
				color: "#999999"
				}, 500);
		});	*/
		
// fix products menu		
	
// Social Sharing

		$("#sharing li").css({
				opacity: 0.5
			});
		/*$("#sharing li").hover(function() {
			$(this).stop().animate({
				opacity: 1
				}, 100);
			},function() {
			$(this).stop().animate({
				opacity: 0.5
				}, 500);
		}); */
		
//  fitvids

	$("#content").fitVids({ ignore: '.audio_player'});
	$(".container").fitVids({ customSelector: "iframe[src^='http://embed.ted.com']"});
	
	// right click playlist
	 $(".wp-playlist").bind("contextmenu",function(e){
        e.preventDefault();
    }).append("<a id='poplife' href=''>New Window</a>");
    
    $("#poplife").click( function() {
        alert("popped");
        var w = window.open('', '', 'width=400,height=400,resizeable,scrollbars');
        w.document.write( $(".wp-playlist").html() );
        w.document.close(); // needed for chrome and safari

        
        // add in css, js, templatesu
    });
    
    
  //  $sharedaddy = $( ".sharedaddy" ).clone().addClass('inlined');
   // $sharedaddy.insertBefore(".sharedaddy");


    })
})(jQuery);

// glossary tooltips
var tooltip=function(){
	var id = 'tt';
	var top = 3;
	var left = 3;
	var maxw = 300;
	var speed = 10;
	var timer = 20;
	var endalpha = 95;
	var alpha = 0;
	var tt,t,c,b,h;
	var ie = document.all ? true : false;
	return{
		show:function(v,w){
			if(tt == null){
				tt = document.createElement('div');
				tt.setAttribute('id',id);
				t = document.createElement('div');
				t.setAttribute('id',id + 'top');
				c = document.createElement('div');
				c.setAttribute('id',id + 'cont');
				b = document.createElement('div');
				b.setAttribute('id',id + 'bot');
				tt.appendChild(t);
				tt.appendChild(c);
				tt.appendChild(b);
				document.body.appendChild(tt);
				tt.style.opacity = 0;
				tt.style.filter = 'alpha(opacity=0)';
				document.onmousemove = this.pos;
			}
			tt.style.display = 'block';
			c.innerHTML = v;
			tt.style.width = w ? w + 'px' : 'auto';
			if(!w && ie){
				t.style.display = 'none';
				b.style.display = 'none';
				tt.style.width = tt.offsetWidth;
				t.style.display = 'block';
				b.style.display = 'block';
			}
			if(tt.offsetWidth > maxw){tt.style.width = maxw + 'px'}
			h = parseInt(tt.offsetHeight) + top;
			clearInterval(tt.timer);
			tt.timer = setInterval(function(){tooltip.fade(1)},timer);
		},
		pos:function(e){
			var u = ie ? event.clientY + document.documentElement.scrollTop : e.pageY;
			var l = ie ? event.clientX + document.documentElement.scrollLeft : e.pageX;
			tt.style.top = (u - h) + 'px';
			tt.style.left = (l + left) + 'px';
		},
		fade:function(d){
			var a = alpha;
			if((a != endalpha && d == 1) || (a != 0 && d == -1)){
				var i = speed;
				if(endalpha - a < speed && d == 1){
					i = endalpha - a;
				}else if(alpha < speed && d == -1){
					i = a;
				}
				alpha = a + (i * d);
				tt.style.opacity = alpha * .01;
				tt.style.filter = 'alpha(opacity=' + alpha + ')';
			}else{
				clearInterval(tt.timer);
				if(d == -1){tt.style.display = 'none'}
			}
		},
		hide:function(){
			clearInterval(tt.timer);
			tt.timer = setInterval(function(){tooltip.fade(-1)},timer);
		}
	};
}();
