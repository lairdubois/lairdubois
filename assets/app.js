import 'jquery-form';
import 'jquery-ui';
import 'jquery-ui/ui/widgets/sortable';
import 'jquery-ui/ui/effect';
import 'jquery-ui/ui/effects/effect-highlight';
import 'jquery-ui/ui/widgets/droppable';
import 'jquery-ui/ui/disable-selection';
import 'devbridge-autocomplete';
import 'jquery-textcomplete';
import 'jquery-lazy';
import 'jquery-sticky';
import 'jquery.scrollto';
import 'jscroll';
import 'bootstrap';
import 'eonasdan-bootstrap-datetimepicker/src/js/bootstrap-datetimepicker';
import 'uikit';
import 'uikit/src/js/components/notify';
import 'readmore.js'
import 'blueimp-file-upload';
import 'emojionearea';

import moment from 'moment';
global.moment = moment;

// Old vendors not working properly with webpack encore1
import './js/bootstrap-markdown/bootstrap-markdown-v1';
import './js/modernizr/modernizr-custom';
import './js/masonry/masonry.pkgd';
import './js/infinite/collections';

// Internal libs
import './js/ladb/jquery.ladb.appendscrolltotopbtn';
import './js/ladb/jquery.ladb.counters.js'
import './js/ladb/jquery.ladb.hrefajax.js'
import './js/ladb/jquery.ladb.textcompletify.js'
import './js/ladb/jquery.ladb.sharebuttonsbuilder.js'
import './js/ladb/jquery.ladb.scrollto.js'
import './js/ladb/jquery.ladb.appendscrolltotopbtn.js'
import './js/ladb/jquery.ladb.autopager.js'
import './js/ladb/jquery.ladb.maparea.js'
import './js/ladb/jquery.ladb.smartsearch.js'
import './js/ladb/jquery.ladb.topbartranslucent.js'
import './js/ladb/jquery.ladb.commentwidget.js'
import './js/ladb/jquery.ladb.votewidget.js'
import './js/ladb/jquery.ladb.reviewwidget.js'
import './js/ladb/jquery.ladb.feedbackwidget.js'
import './js/ladb/jquery.ladb.boxlinkclick.js'

// LADB Common
import LADBCommon from './js/ladb/ladb-common';
global.LADBCommon = LADBCommon;

// LADB Pictures
import LADBPictures from './js/ladb/ladb-pictures';
global.LADBPictures = LADBPictures;

// LADB Reports
import LADBReports from './js/ladb/ladb-reports';
global.LADBReports = LADBReports;

// LADB WebPush
import LADBWebPush from './js/ladb/ladb-webpush';
global.LADBWebPush = LADBWebPush;

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.less';

// start the Stimulus application
import './bootstrap';