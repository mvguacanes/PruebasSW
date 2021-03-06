<?php
/* Copyright (C) 2009 Regis Houssin	<regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *		\file       htdocs/theme/phones/iphone/theme/default/default.css.php
 *		\brief      Fichier de style CSS du theme Iphone default
 *		\version    $Id$
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1'); // We need to use translation files to know direction
if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');

require_once("../../../../../master.inc.php");

// Define css type
header('Content-type: text/css');
// Important: Avoid page request by browser and dynamic build at
// each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

?>

body {
	position: relative;
	margin: 0;
	-webkit-text-size-adjust: none;
	min-height: 416px;
	font-family: helvetica,sans-serif;
	background: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/background.png'; ?>),
				url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/menutouched.png'; ?>) no-repeat;
	-webkit-touch-callout: none;
}
.center {
	margin: auto;
	display: block;
}
img {
	border: 0;
}
a:hover span.arrow {
	background-position: 0 -13px!important;
}
#topbar {
	position: relative;
	left: 0;
	top: 0;
	height: 44px;
	width: auto;
	background: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/header.png'; ?>) repeat;
	margin-bottom: 13px;
}
#title {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	padding: 0 10px;
	text-align: center;
	text-overflow: ellipsis;
	white-space: nowrap;
	overflow: hidden;
	height: 44px;
	line-height: 44px;
	font-weight: bold;
	color: #FFF;
	text-shadow: rgba(0,0,0,0.6) 0 -1px 0;
	font-size: 16pt;
}
#content {
	width: 100%;
	position: relative;
	min-height: 250px;
	margin-top: 10px;
	height: auto;
	z-index: 0;
	overflow: hidden;
}
#footer {
	text-align: center;
	position: relative;
	margin: 20px 10px 0;
	height: auto;
	width: auto;
	bottom: 10px;
}
#footer a, #footer {
	text-decoration: none;
	font-size: 9pt;
	color: #4C4C4C;
	text-shadow: #FFF 0 1px 0;
}
.pageitem {
	-webkit-border-radius: 8px;
	background-color: #fff;
	border: #878787 solid 1px;
	font-size: 12pt;
	overflow: hidden;
	padding: 0;
	position: relative;
	display: block;
	height: auto;
	width: auto;
	margin: 3px 9px 17px;
	list-style: none;
}
.textbox {
	padding: 5px 9px;
	position: relative;
	overflow: hidden;
	border-top: 1px solid #878787;
}
.textbox p {
	margin-top: 2px;
	color: #000;
	margin-bottom: 2px;
	text-align: justify;
}
.textbox img {
	max-width: 100%;
}
.textbox ul {
	margin: 3px 0 3px 0;
	list-style: circle!important;
}
.textbox li {
	margin: 0!important;
}
.pageitem li:first-child {
	border-top: 0;
}
li.menu, li.form {
	position: relative;
	list-style-type: none;
	display: block;
	height: 43px;
	overflow: hidden;
	border-top: 1px solid #878787;
	width: auto;
}
ul.pageitem li:first-child:hover, .pageitem li:first-child a, li.form:first-child input[type=radio], li.form:first-child select, li.form:first-child input[type=submit], li.form:first-child button, li.form:first-child input[type=reset] {
	-webkit-border-top-left-radius: 8px 8px;
	-webkit-border-top-right-radius: 8px 8px;
}
ul.pageitem li:last-child:hover, .pageitem li:last-child a, li.form:last-child input[type=radio], li.form:last-child select, li.form:last-child input[type=submit], li.form:last-child button, li.form:last-child input[type=reset] {
	-webkit-border-bottom-left-radius: 8px 8px;
	-webkit-border-bottom-right-radius: 8px 8px;
}
li.menu:hover {
	background: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/menutouched.png'; ?>) repeat-x #0274ee;
}
li.menu a:hover span.name, li.menu a:hover span.comment, li.store:hover .starcomment, li.store:hover .name, li.store:hover .comment, body.list li.withimage a:hover .comment {
	color: #fff;
}
li.menu a:hover span.comment {
	color: #CCF;
}
li.menu a {
	display: block;
	height: 43px;
	width: auto;
	text-decoration: none;
}
li.menu a img {
	width: auto;
	height: 32px;
	margin: 5px 0 0 5px;
	float: left;
}
li.menu span.name {
	margin: 11px 0 0 7px;
	width: auto;
	color: #000;
	font-weight: bold;
	font-size: 17px;
	text-overflow: ellipsis;
	overflow: hidden;
	max-width: 75%;
	white-space: nowrap;
	float: left;
}
li.menu span.comment {
	margin: 11px 30px 0 0;
	width: auto;
	color: #000;
	font-size: 17px;
	text-overflow: ellipsis;
	overflow: hidden;
	max-width: 75%;
	white-space: nowrap;
	float: right;
	color: #324f85;
}
li.menu span.arrow, li.store span.arrow, body.musiclist span.arrow, body.list span.arrow {
	position: absolute;
	width: 8px!important;
	height: 13px!important;
	right: 10px;
	top: 15px;
	margin: 0!important;
	background: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/arrow.png'; ?>) 0 0 no-repeat;
}
li.store {
	height: 90px;
	border-top: #878787 solid 1px;
	overflow: hidden;
	position: relative;
}
li.store a {
	width: 100%;
	height: 90px;
	display: block;
	background: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/storebg.png'; ?>) left top no-repeat;
	text-decoration: none;
	position: absolute;
}
li.store:hover {
	background: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/storetouched.png'; ?>) repeat-x #0274ee;
}
li.store .image {
	position: absolute;
	left: 0;
	top: 0;
	height: 90px;
	width: 90px;
	display: block;
	background: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/storetouched.png'; ?>) no-repeat;
}
li:first-child.store .image, li.store:first-child a {
	-webkit-border-top-left-radius: 8px 8px;
}
li:last-child.store .image, li.store:last-child a {
	-webkit-border-bottom-left-radius: 8px 8px;
}
li.store .name {
	font-size: 15px;
	white-space: nowrap;
	margin: 5px 0 0 95px;
	display: block;
	overflow: hidden;
	color: #000;
	max-width: 60%;
	text-overflow: ellipsis;
	font-weight: bold;
	white-space: nowrap;
	text-overflow: ellipsis;
}
li.store .comment, body.list li.withimage .comment {
	font-size: 12px;
	color: #7f7f7f;
	margin: 16px 0 0 95px;
	display: block;
	width: 60%;
	font-weight: bold;
	white-space: nowrap;
	text-overflow: ellipsis;
	overflow: hidden;
}
li.store .arrow, body.list li.withimage .arrow {
	top: 39px!important;
}
li.store .stars {
	margin: 6px 0 0 95px;
}
li.store .starcomment {
	position: absolute;
	left: 165px;
	top: 56px;
	font-size: 12px;
	color: #7f7f7f;
	font-weight: lighter;
}
.graytitle {
	position: relative;
	font-weight: bold;
	font-size: 17px;
	right: 20px;
	left: 9px;
	color: #4C4C4C;
	text-shadow: #FFF 0 1px 0;
	padding: 1px 0 3px 8px;
}
.header {
	display: block;
	font-weight: bold;
	color: rgb(73,102,145);
	font-size: 12pt;
	margin-bottom: 6px;
	line-height: 14pt;
}
body.musiclist div#content {
	width: auto;
	margin: -29px auto auto -40px;
}
body.musiclist div#content ul {
	background: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/musiclistbg.png'; ?>) repeat;
}
body.musiclist div#content ul li {
	list-style: none;
	height: 44px;
	width: auto;
	border-bottom: 1px solid #e6e6e6;
	position: relative;
}
body.musiclist div#content ul li a {
	text-decoration: none;
	color: #000;
	width: 100%!important;
	height: 100%;
	display: block;
}
body.musiclist ul li .number, body.musiclist .name, body.musiclist .time {
	display: inline-block;
	height: 44px;
	font-weight: bold;
	font-size: large;
	width: 44px;
	text-align: center;
	line-height: 46px;
}
body.musiclist ul li .name {
	margin-left: 0;
	width: auto!important;
	font-size: medium;
	padding-left: 5px;
	border-left: solid 1px #e6e6e6;
}
body.musiclist ul li .time {
	color: #848484;
	font-size: medium;
	margin-left: 4px;
	width: auto!important;
	font-weight: normal;
}
body.musiclist {
	background-image: none!important;
	background-color: #cbcccf;
}
body.musiclist ul li span.name {
	text-overflow: ellipsis;
	overflow: hidden;
	white-space: nowrap;
	max-width: 62%;
}
body.list ul li.title {
	background: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/listheader.gif'; ?>) repeat-x;
	height: 22px!important;
	width: 100%;
	color: #fff;
	font-weight: bold;
	font-size: 16px;
	text-shadow: gray 0 1px 0;
	line-height: 22px;
	padding-left: 20px;
	border-bottom: none!important;
}
body.list ul {
	background-color: #fff;
	width: 100%;
	overflow: hidden;
	padding: 0;
	margin: 0;
}
body.list div#content li {
	height: 40px;
	border-bottom: 1px solid #e1e1e1;
	list-style: none;
}
body.list {
	background-color: #fff;
	background-image: none!important;
}
body.list div#footer {
	margin-top: 24px!important;
}
body.list div#content li a {
	padding: 9px 0 0 20px;
	font-size: large;
	font-weight: bold;
	position: relative;
	display: block;
	color: #000;
	text-decoration: none;
	height: 32px;
}
body.list div#content li a span.name {
	text-overflow: ellipsis;
	overflow: hidden;
	max-width: 93%;
	white-space: nowrap;
	display: block;
}
body.list div#content li a:hover {
	color: #fff;
}
body.list div#content li a:hover {
	background: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/menutouched.png'; ?>) repeat-x;
}
body.list div#content {
	margin-top: -13px!important;
}
body.list ul img {
	width: 90px;
	height: 90px;
	position: absolute;
	left: 0;
	top: 0;
}
body.list li.withimage {
	height: 90px!important;
}
body.list li.withimage span.name {
	margin: 13px 0 0 90px;
	text-overflow: ellipsis;
	overflow: hidden;
	max-width: 63%!important;
	white-space: nowrap;
}
body.list li.withimage .comment {
	margin: 10px auto auto 90px !important;
	max-width: 63%!important;
}
body.list li.withimage a, body.list li.withimage:hover a {
	height: 81px!important;
	background: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/storebg.png'; ?>) left top no-repeat!important;
}
body.list li.withimage:hover {
	background-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/storetouched.png'; ?>);
}
.confirm_screen {
	position: absolute;
	bottom: 0;
	-webkit-transform: translate(0,100%);
	-webkit-transition-property: -webkit-transform;
	background-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/popup-bg.png'; ?>);
	opacity: 0;
}
.confirm_screenopen {
	position: absolute;
	opacity: 0.8;
	overflow: hidden;
	bottom: -100%;
	width: 100%;
	background: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/popup-bg.png'; ?>) repeat-x #4e545f;
	-webkit-transition-duration: 0.8s;
	-webkit-transition-property: -webkit-transform;
	-webkit-transform-style: preserve-3d;
	-webkit-transform: translate(0,0);
	text-align: center;
	z-index: 99999;
}
.confirm_screenopenfull {
	position: absolute;
	opacity: 0.8;
	overflow: hidden;
	bottom: -100%;
	width: 100%;
	background: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/popup-bg.png'; ?>) repeat-x #4e545f;
	-webkit-transition-duration: 0.8s;
	-webkit-transition-property: -webkit-transform;
	-webkit-transform-style: preserve-3d;
	-webkit-transform: translate(0,45px);
	text-align: center;
	z-index: 99999;
}
.confirm_screenclose {
	-webkit-transition-duration: 1.2s;
	-webkit-transition-property: -webkit-transform;
	-webkit-transform-style: preserve-3d;
	position: absolute;
	opacity: 0.8;
	overflow: hidden;
	bottom: -100%;
	width: 100%;
	background: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/popup-bg.png'; ?>) repeat-x #545A67;
	text-align: center;
	-webkit-transform: translate(0,150%);
}
.confirm_screenopen span, .confirm_screenclose span, .confirm_screenopenfull span, .confirm_screenclosefull span {
	margin: 10px 0 20px;
	font-size: 17px;
	color: #fff;
	width: 100%;
	height: 10px;
	text-shadow: rgba(0,0,0,1) 0 -1px 0;
	display: block;
}
.popup {
	position: absolute;
	bottom: 0;
	width: 100%;
	left: 0;
	z-index: 9999;
}
.cover {
	width: 100%;
	position: absolute;
	top: 0;
	z-index: 9998;
	opacity: 0.4;
	left: 0;
	background-color: #000;
}
.nocover {
	opacity: 0;
	background-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/bluebutton.png'; ?>),
					url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/darkredbutton.png'; ?>);
}
#frame a {
	text-decoration: none;
	display: block;
	width: 90%;
	margin-left: auto;
	margin-right: auto;
	margin-bottom: -15px;
	margin-top: 0;
}
#frame span.black, #frame span.red, #frame span.gray {
	display: block;
	height: 46px;
	border-width: 0 14px;
	width: auto;
	background-repeat: no-repeat;
	line-height: 46px;
	font-size: large;
	opacity: 1;
	font-weight: bolder;
	white-space: nowrap;
	text-overflow: ellipsis;
	overflow: hidden;
	font-family: Arial,Helvetica,sans-serif;
}
#frame span.black {
	-webkit-border-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/graybutton.png'; ?>) 0 14 0 14;
	color: #fff;
}
#frame span.red {
	-webkit-border-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/redbutton.png'; ?>) 0 14 0 14;
	color: #fff;
}
#frame span.gray {
	-webkit-border-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/whitebutton.png'; ?>) 0 14 0 14;
	color: #000;
	text-shadow: white 0 1px 0;
}
#frame a:last-child {
	margin-bottom: 20px!important;
}
#frame a:hover span.black, #frame a:hover span.gray {
	-webkit-border-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/bluebutton.png'; ?>) 0 14 0 14;
	color: #fff;
	text-shadow: rgba(0,0,0,1) 0 -1px 0;
}
#frame a:hover span.red {
	-webkit-border-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/darkredbutton.png'; ?>) 0 14 0 14;
}
#leftnav, #leftbutton {
	position: absolute;
	font-size: 12px;
	left: 9px;
	top: 7px;
	font-weight: bold;
}
#leftnav, #leftbutton, #rightnav, #rightbutton {
	z-index: 5000;
}
#leftnav a, #rightnav a, #leftbutton a, #rightbutton a {
	display: block;
	color: #fff;
	text-shadow: rgba(0,0,0,0.6) 0 -1px 0;
	line-height: 30px;
	height: 30px;
	text-decoration: none;
}
#leftnav img, #rightnav img {
	margin-top: 4px;
}
#leftnav a:first-child {
	z-index: 2;
	-webkit-border-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/navleft.png'; ?>) 0 5 0 13;
	border-width: 0 5px 0 13px;
	-webkit-border-top-left-radius: 16px;
	-webkit-border-bottom-left-radius: 16px;
	-webkit-border-top-right-radius: 6px;
	-webkit-border-bottom-right-radius: 6px;
	width: auto;
}
#leftnav a {
	-webkit-border-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/navlinkleft.png'; ?>) 0 5 0 13;
	z-index: 3;
	margin-left: -4px;
	border-width: 0 5px 0 13px;
	padding-right: 4px;
	-webkit-border-top-left-radius: 16px;
	-webkit-border-bottom-left-radius: 16px;
	-webkit-border-top-right-radius: 6px;
	-webkit-border-bottom-right-radius: 6px;
	float: left;
}
#rightnav, #rightbutton {
	position: absolute;
	font-size: 12px;
	right: 9px;
	top: 7px;
	font-weight: bold;
}
#rightnav a {
	-webkit-border-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/navlinkright.png'; ?>) 0 13 0 5;
	z-index: 3;
	margin-right: -4px;
	border-width: 0 13px 0 5px;
	padding-left: 4px;
	-webkit-border-top-left-radius: 6px;
	-webkit-border-bottom-left-radius: 6px;
	float: right;
	-webkit-border-top-right-radius: 16px;
	-webkit-border-bottom-right-radius: 16px;
}
#rightnav a:first-child {
	z-index: 2;
	-webkit-border-top-left-radius: 6px;
	-webkit-border-bottom-left-radius: 6px;
	-webkit-border-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/navright.png'; ?>) 0 13 0 5;
	border-width: 0 13px 0 5px;
	-webkit-border-top-right-radius: 16px;
	-webkit-border-bottom-right-radius: 16px;
}
#leftbutton a, #rightbutton a {
	-webkit-border-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/navbutton.png'; ?>) 0 5 0 5;
	border-width: 0 5px;
	-webkit-border-radius: 6px;
}
.rssxpresschannel {
	font-family: helvetica,sans-serif;
	border: none;
}
.rssxpresschtitle {
	text-align: center;
}
.rssxpresschdesc {
	color: #000;
	text-align: center;
	border-bottom: 1px solid #000;
	padding-bottom: 5px;
}
.rssxpressittitle {
	display: block;
	font-size: 12pt;
	background: #fff;
	margin: 5px 0 2px;
}
.rssxpressittitle a {
	text-decoration: none!important;
	font-weight: bold;
	color: rgb(73,102,145);
	line-height: 10pt;
}
.rssxpressitdesc {
	background: #fff;
	font-size: 12pt;
}
.rssxpressdivider {
	display: none;
}
li.form input[type=text], li.form input[type=password], li.form input[type=search] {
	border-width: 7px 7px;
	font-weight: normal;
	border-color: white;
	height: 10px;
	-webkit-border-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/textfield.png'; ?>) 7 7 7 7;
	-webkit-appearance: none;
	line-height: 8px;
	font-size: 18px;
	margin: auto;
	display: block;
	position: relative;
	width: 90%;
}
li.form input[type=submit] {
	width: 100%;
	background: none;
	border: 0px;
	color: #000;
	margin-top: -5px;
	margin-bottom: -5px;
	font-weight: bold;
	font-size: 17px;
}
.form {
	padding: 5px 8px 0 5px;
	height: 37px!important;
	position: relative;
	overflow: hidden;
}
li.form .narrow textarea, li.form .narrow input[type=text], li.form .narrow input[type=checkbox], li.form .narrow input[type=password], li.form .narrow input[type=search] {
	width: 40%!important;
	border-width: 7px 7px;
	height: 10px;
	-webkit-border-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/textfield.png'; ?>) 7 7 7 7;
	-webkit-appearance: none;
	line-height: 8px;
	font-size: 18px;
	font-weight: normal;
	border-color: white;
	margin: 0!important;
	position: absolute;
	right: -17px;
}
li.form span.narrow, li.form span.check {
	width: 90%!important;
	display: block;
	position: relative;
	margin: auto;
}
li.form .name {
	width: 55%!important;
	white-space: nowrap;
	text-overflow: ellipsis;
	position: absolute;
	margin: 6px 0 0 7px;
	color: #000;
	font-weight: bold;
	font-size: 17px;
	overflow: hidden;
	left: -17px;
}
li.form .check .name {
	width: 70%!important;
}
li.form input[type=radio] {
	width: 100%;
	height: 42px;
	display: block;
	margin: -5px -8px 0 -5px;
	-webkit-appearance: none;
	border: 0;
	-webkit-border-radius: 0;
	position: relative;
	background: transparent;
	position: absolute;
}
span.radio {
	width: 16px;
	height: 30px;
	background: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/check.png'; ?>) no-repeat;
	display: block;
	position: absolute;
	right: 5px;
	overflow: visible;
	z-index: 1;
}
span.checkbox {
	width: 94px;
	height: 27px;
	background: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/checkbox.png'; ?>) no-repeat;
	display: block;
	position: absolute;
	right: -14px;
	top: 2px;
}
input[type=checkbox] {
	display: none;
}
select {
	height: 40px;
	opacity: 0;
	position: absolute;
	width: 100%;
	margin: -5px 0 0 -5px;
	-webkit-border-radius: 0;
}
.form .choice .name {
	left: 1.7%;
	width: 87%!important;
}
span.select {
	z-index: 1;
	position: absolute;
	white-space: nowrap;
	text-overflow: ellipsis;
	margin: 6px 0 0 7px;
	color: #000;
	font-weight: bold;
	font-size: 17px;
	overflow: hidden;
	max-width: 87%;
}
.form .arrow {
	background: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/more.png'; ?>) no-repeat;
	width: 13px;
	height: 8px;
	position: absolute;
	right: 8px;
	top: 18px;
	margin: 0!important;
}
input[type=submit], button, input[type=button], input[type=reset] {
	background: transparent;
	width: 100%;
	height: 40px;
	left: 0px;
	position: absolute;
	top: 6px;
	display: block;
	-webkit-border-radius: 0;
	line-height: 40px;
}
button, input[type=button], input[type=reset] {
	top: 0px!important;
	border: none;
	color: black;
	font-weight: bold;
	font-size: 17px;
	-webkit-appearance: none;
}
.textbox textarea {
	min-height: 50px;
	margin: 3px auto 4px auto;
	position: relative;
	-webkit-border-image: url(<?php echo DOL_URL_ROOT.'/theme/phones/iphone/theme/default/img/textfield.png'; ?>') 7 7 7 7;
	padding: 3px 0 0 0;
	line-height: 18px;
	left: -2px;
	font-size: 18px;
	font-weight: normal;
	width: 97%;
	display: block;
	border-width: 7px 7px;
}
ul li.hidden {
	display: none;
}
ul li.autolisttext {
	text-align: center;
}
body.musiclist ul li.autolisttext {
	line-height: 44px!important;
}
ul li.autolisttext a:hover {
	background-image: none!important;
	color: black!important;
}
