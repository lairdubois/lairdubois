/**
 * ! jQuery.cornerSlider | (c) 2015 reshetech.co.il
 */

(function ($, document, window) {
    $.fn.cornerSlider = function (options) {

        var settings = $.extend({
            // where to show
            showAtScrollingHeight: 1300,
            elemToPresent: "#presentSlider",

            // effect
            directionEffect: "right",
            speedEffect: 300,

            // margin
            bottom: 6,
            right: 6,
            left: 6,
            top: 6,

            // the 'cornerSliderHide' cookie is generated
            // when a user chooses to close the slider
            cookieName: 'cornerSliderHide',
            cookieValue: 'hidden',
            cookieDomain: '',
            cookieMinutesToExpiry: 15,


            // callback functions that the user can use.
            onShow: function () {
            },
            onHide: function () {
            },
            onClose: function () {
            }
        }, options);


        /**
         * the 'cornerSlider' element
         */
        var cornerSliderElem = $(this),
            cornerSliderElemWidth = cornerSliderElem.outerWidth(),
            cornerSliderElemHeight = cornerSliderElem.outerHeight(),
            direction = "right",

        // true if the user chooses to close the element.	
            flagClose = false;


        /**
         * @desc  set any cookie with javascript
         * @param string name    - the cookie name
         * @param string value   - the cookie value
         * @param string minutes - the number of minutes to expiry
         */
        function setCookie(name, value, minutes) {
            var expires,
                domain,
                minutes = parseInt(minutes);

            minutes = (minutes > 0) ? minutes : null;

            if (minutes) {
                domain = (settings.cookieDomain != '') ? '; domain=.' + settings.cookieDomain : '';

                var date = new Date();
                date.setTime(date.getTime() + (minutes * 60 * 1000));
                expires = '; expires=' + date.toGMTString();
                document.cookie = name + '=' + value + expires + domain + '; path=/';
            }
        }


        /**
         * @desc   check whether a cookie exists
         * @param  string cName - the cookie name
         * @return bool         - whether the cookie exists
         */
        function getCookieValue(cName) {
            var cStart, cEnd, cookieVal;

            if (document.cookie.length < 1) return null;

            cStart = document.cookie.indexOf(cName + '=');

            if (cStart < 0)  return null;

            cStart = cStart + cName.length + 1;

            cEnd = document.cookie.indexOf(';', cStart);

            if (cEnd == -1) cEnd = document.cookie.length;

            cookieVal = unescape(document.cookie.substring(cStart, cEnd));

            if (cookieVal.length > 1) return cookieVal;
        }


        /**
         * @desc   checks whether to show the element
         * @return bool - whether to show the element
         */
        function isAllowedCornerSlider() {

            if (flagClose) return false;

            var cookieValue = getCookieValue(settings.cookieName);

            return cookieValue == null || cookieValue != settings.cookieValue;
        }


        /**
         * @desc  display the element
         * @param obj elem - the 'cornerSlider' object
         */
        function cornerSliderAppear(elem) {
            elem.removeClass('hide').addClass('shown').stop();

            if (settings.directionEffect == 'right') {
                elem.animate({'right': settings.right}, settings.speedEffect, function () {});
            }
            else if (direction == 'bottom') {
                elem.animate({'bottom': settings.bottom}, settings.speedEffect, function () {});
            }
            else {
                elem.animate({'left': settings.left}, settings.speedEffect, function () {});
            }

            // callback
            settings.onShow.call(elem);
        }


        /**
         * @desc  hide the element
         * @param obj  elem  - the 'cornerSlider' object
         * @param int  width - the element width
         * @param bool close - whether the user chose to hide the element
         */
        function cornerSliderDisAppear(elem, width, close) {
            elem.stop();

            if (settings.directionEffect == 'right') {
                elem.animate({'right': -width}, settings.speedEffect, function () {
                    elem.removeClass('shown').addClass('hide');
                });
            }
            else if (direction == 'bottom') {
                height = cornerSliderElemHeight;

                elem.animate({'bottom': -height}, settings.speedEffect, function () {
                    elem.removeClass('shown').addClass('hide');
                });
            }
            else {
                elem.animate({'left': -width}, settings.speedEffect, function () {
                    elem.removeClass('shown').addClass('hide');
                });
            }

            // callbacks
            if (close) {
                settings.onClose.call(elem);
            } else {
                settings.onHide.call(elem);
            }
        }

        /**
         * hide or show the cornerSlider on window scroll.
         */
        $(window).scroll(function () {
            var scrollTopInt = parseInt($(window).scrollTop());
            var winHeight = parseInt(window.innerHeight);
            var h1 = scrollTopInt + winHeight;
            var h2 = $(settings.elemToPresent).length == 0 ? settings.showAtScrollingHeight : parseInt($(settings.elemToPresent).offset().top);

            if (h1 > h2) {
                if (cornerSliderElem.hasClass('hide') && isAllowedCornerSlider(settings.cookieName)) {
                    cornerSliderAppear(cornerSliderElem);
                }
            } else if (cornerSliderElem.hasClass('shown')) {
                cornerSliderDisAppear(cornerSliderElem, cornerSliderElemWidth, false);
            }
        });

        /**
         * motion directions and starting position
         * are initialized here.
         */
        (function init() {
            width = cornerSliderElemWidth;
            height = cornerSliderElemHeight;
            direction = (settings.directionEffect == 'left' || settings.directionEffect == 'right') ? settings.directionEffect : "bottom";

            // listen to the closing button
            cornerSliderElem.find('.close,.close-area').on('click', function () {
                flagClose = true;
                cornerSliderDisAppear(cornerSliderElem, cornerSliderElemWidth, true);
                setCookie(settings.cookieName, settings.cookieValue, settings.cookieMinutesToExpiry);
            });

            // The element needs to have a hide class.
            cornerSliderElem.addClass("hide");

            cornerSliderElem.css({'bottom': settings.bottom});

            if (direction == 'left')
                cornerSliderElem.css({'left': -width, 'right': 'auto'});
            else if (direction == 'right')
                cornerSliderElem.css({'right': -width, 'left': 'auto'});
            else {

                if (direction == 'bottom')
                    cornerSliderElem.css({'bottom': -height});

                if (settings.directionEffect == 'bottom left')
                    cornerSliderElem.css({'left': settings.left, 'right': 'auto'});
                else if (settings.directionEffect == 'bottom center')
                    cornerSliderElem.css({'margin-right': 'auto', 'margin-left': 'auto', 'right': 0, 'left': 0});
                else if (settings.directionEffect == 'bottom right')
                    cornerSliderElem.css({'right': settings.right, 'left': 'auto'});
                else
                    cornerSliderElem.css({'right': settings.right, 'left': 'auto'});
            }
        }());
    };
}(jQuery, document, window));