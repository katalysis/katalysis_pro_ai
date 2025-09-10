<?php 
	
// Site variables
$siteName = 'Katalysis Pro AI';

// Theme
$current = PageTheme::getSiteTheme();
$themePath =  $current-> getThemeURL();

// Actual full site url (this will need to change for testing server etc.)
$siteUrl = BASE_URL;

// Public facing website url
$publicUrl = BASE_URL;

// Theme variables
$kFont1 = 'Helvetica, Arial, sans-serif';
$kFont2 = 'Helvetica, Arial, sans-serif';
$kCol1 = '#064b8d';
$kCol1Light = '#7abeff';
$kCol1Lightest ='#e7eff7';
$kCol1Dark = '#0e3152';
$kColVib = '#b5995c';
$kColLink = '#0b267e';
$kStylePara = 'text-align:left;font-family:'. $kFont1.';font-size:15px;padding-bottom:8px!important;color:#000000;line-height:135%;';
$kStyleH3 = 'text-align:left;font-family:'. $kFont1.';font-size:20px;margin-top:0;padding-bottom:8px!important;font-weight:bold;color:'. $kColVib .';line-height:135%;';

$header = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="format-detection" content="telephone=no" /> <!-- disable auto telephone linking in iOS -->
        <title>'. $siteName .'</title>
        <style type="text/css">
            /* RESET STYLES */
            body, #bodyTable, #bodyCell, #bodyCell{height:100% !important; margin:0; padding:0; width:100% !important;font-family:'. $kFont1 .';}
            table{border-collapse:collapse;}
            table[id=bodyTable] {width:100%!important;margin:auto;max-width:600px!important;color:#7A7A7A;font-weight:normal;}
            img, a img{border:0; outline:none; text-decoration:none;height:auto; line-height:100%;}
            a {text-decoration:none !important;border-bottom: 1px solid;}
            h1, h2, h3, h4, h5, h6 {font-family:'. $kFont1 .';color:'. $kCol1Dark.'; font-weight:normal; font-size:20px; line-height:125%; text-align:Left; letter-spacing:normal;margin-top:0;margin-right:0;margin-bottom:10px;margin-left:0;padding-top:0;padding-bottom:0;padding-left:0;padding-right:0;}

            /* CLIENT-SPECIFIC STYLES */
            .ReadMsgBody{width:100%;} .ExternalClass{width:100%;} /* Force Hotmail/Outlook.com to display emails at full width. */
            .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div{line-height:100%;} /* Force Hotmail/Outlook.com to display line heights normally. */
            table, td{mso-table-lspace:0pt; mso-table-rspace:0pt;} /* Remove spacing between tables in Outlook 2007 and up. */
            #outlook a{padding:0;} /* Force Outlook 2007 and up to provide a "view in browser" message. */
            img{-ms-interpolation-mode: bicubic;display:block;outline:none; text-decoration:none;} /* Force IE to smoothly render resized images. */
            body, table, td, p, a, li, blockquote{-ms-text-size-adjust:100%; -webkit-text-size-adjust:100%; font-weight:normal!important;} /* Prevent Windows- and Webkit-based mobile platforms from changing declared text sizes. */
            .ExternalClass td[class="ecxflexibleContainerBox"] h3 {padding-top: 10px !important;} /* Force hotmail to push 2-grid sub headers down */
            
             p {margin:0; padding:0; margin-bottom:0;}

            /* /\/\/\/\/\/\/\/\/ TEMPLATE STYLES /\/\/\/\/\/\/\/\/ */

            /* ========== Page Styles ========== */
            h1{display:block;font-size:26px;font-style:normal;font-weight:normal;line-height:100%;}
            h2{display:block;font-size:20px;font-style:normal;font-weight:normal;line-height:120%;}
            h3{display:block;font-size:17px;font-style:normal;font-weight:normal;line-height:110%;margin-bottom;8px;}
            h4{display:block;font-size:18px;font-style:italic;font-weight:normal;line-height:100%;}
			h1 a, h2 a, h3 a {color:'. $kColLink.';}
		    a:hover, a:active, a:visited  {color:'. $kColLink.' !important;}
			h1 a:active, h2 a:active, h3 a:active, h1 a:visited, h2 a:visited,  h3 a:visited {color:'. $kColLink.' !important;} 
            
            
            .flexibleImage{height:auto;}
            .linkRemoveBorder{border-bottom:0 !important;}
            table[class=flexibleContainerCellDivider] {padding-bottom:0 !important;padding-top:0 !important;}
		    table[id=bodyTable] {color:#000000;font-weight:normal;}

            body, #bodyTable, .body, #bodyCell, #bodyCell {background-color:#FFFFFF;font-family:'. $kFont1 .';}
            #emailHeader{border-bottom: solid 3px '. $kCol1.';}
            #emailBody{background-color:#FFFFFF;}
            #emailFooter{background-color:'. $kCol1Dark.';}
            .textContent, .textContentLast {color:#000000;font-weight:normal;font-size:16px; line-height:125%; text-align:Left;}
            .textContent a, .textContentLast a{color:'. $kColLink.'; text-decoration:underline;}
            .nestedContainer{background-color:#F8F8F8; border:1px solid #CCCCCC;}
            .imageContentText {margin-top: 10px;line-height:0;}
            .imageContentText a {line-height:0;}
            #invisibleIntroduction {display:none !important;} /* Removing the introduction text from the view */
            
            /*FRAMEWORK HACKS & OVERRIDES */
            span[class=ios-color-hack] a {color:#275100!important;text-decoration:none!important;} /* Remove all link colors in IOS (below are duplicates based on the color preference) */
            span[class=ios-color-hack2] a {color:#205478!important;text-decoration:none!important;}
            span[class=ios-color-hack3] a {color:#8B8B8B!important;text-decoration:none!important;}
            
            .a[href^="tel"], a[href^="sms"] {text-decoration:none!important;color:'. $kColLink.'!important;pointer-events:none!important;cursor:default!important;}
            .mobile_link a[href^="tel"], .mobile_link a[href^="sms"] {text-decoration:none!important;color:'. $kColLink.'!important;pointer-events:auto!important;cursor:default!important;}
            
            /* User message panel link styling - force white links in blue panels */
            .user-message a, .user-panel a {color: white !important;}
            div[style*="background: #007bff"] a {color: white !important;}

            /* MOBILE STYLES */
            @media only screen and (max-width: 480px){
                /*////// CLIENT-SPECIFIC STYLES //////*/
                body{width:100% !important; min-width:100% !important;} /* Force iOS Mail to render the email at full width. */

                /* FRAMEWORK STYLES */
                table[id="emailHeader"], table[id="emailBody"], table[id="emailFooter"], table[class="flexibleContainer"] {width:100% !important;}
                td[class="flexibleContainerBox"], td[class="flexibleContainerBox"] table {display: block;width: 100%;text-align: left;}
                td[class="imageContent"] img {height:auto !important; width:100% !important; max-width:100% !important;}
                img[class="flexibleImage"]{height:auto !important; width:100% !important;max-width:100% !important;}
                img[class="flexibleImageSmall"]{height:auto !important; width:auto !important;}

            }

            /*  CONDITIONS FOR ANDROID DEVICES ONLY
            *   http://developer.android.com/guide/webapps/targeting.html
            *   http://pugetworks.com/2011/04/css-media-queries-for-targeting-different-mobile-devices/ ;
            =====================================================*/

            @media only screen and (-webkit-device-pixel-ratio:.75){
            /* Put CSS for low density (ldpi) Android layouts in here */
            }

            @media only screen and (-webkit-device-pixel-ratio:1){
            /* Put CSS for medium density (mdpi) Android layouts in here */
            }

            @media only screen and (-webkit-device-pixel-ratio:1.5){
            /* Put CSS for high density (hdpi) Android layouts in here */
            }
            /* end Android targeting */

            /* CONDITIONS FOR IOS DEVICES ONLY
            =====================================================*/
            @media only screen and (min-device-width : 320px) and (max-device-width:568px) {

            }
            /* end IOS targeting */
            

	  	</style>
        
        <!--[if mso 12]>
            <style type="text/css">
                .flexibleContainer{display:block !important; width:100% !important;}
            </style>
        <![endif]-->
        <!--[if mso 14]>
            <style type="text/css">
                .flexibleContainer{display:block !important; width:100% !important;}
            </style>
        <![endif]-->
    </head>
    <body bgcolor="#FFFFFF" leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
        <center style="background-color:#FFFFFF;">
        	<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="bodyTable" style="table-layout: fixed;max-width:100% !important;width: 100% !important;min-width: 100% !important;">
            	<tr>
                	<td align="center" valign="top" id="bodyCell" cellpadding="20" style="padding:20px;">
                        <!-- EMAIL CONTAINER // -->
                    	<table bgcolor="#FFFFFF"  border="0" cellpadding="0" cellspacing="0" width="600" id="emailBody">
							<!-- MODULE ROW // -->
							<tr>
                            	<td align="center" valign="top">
                                	<!-- CENTERING TABLE // -->
                                 	<table border="0" cellpadding="0" cellspacing="0" width="100%"  bgcolor="#FFFFFF" id="emailHeader" style="border-bottom: solid 1px #CFCFCF;">
                                    	<tr>
                                        	<td align="center" valign="top">
                                            	<!-- FLEXIBLE CONTAINER // -->
                                            	<table border="0" cellpadding="0" cellspacing="0" width="600" class="flexibleContainer">
                                                	<tr>
                                                    	<td align="center" valign="top" width="600" class="flexibleContainerCell">

                                                            <!-- CONTENT TABLE // -->
                                                            <table border="0" cellspacing="0" width="100%">
                                                                <tr>
                                                                    <td align="left" valign="top">
                                                                        <img alt="Katalysis Net" width="300" border="0" style="display: block; border: 0px;width:100%;max-width:300px;margin:20px auto 20px 0;" src="'.$siteUrl.'/packages/katalysis_pro_ai/mail/images/email-logox2.png">
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                            <!-- // CONTENT TABLE -->

                                                        </td>
                                                    </tr>
                                                </table>
                                                <!-- // FLEXIBLE CONTAINER -->
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- // CENTERING TABLE -->
                                </td>
                            </tr>
                            <!-- // MODULE ROW -->
                            <tr>
				        	<td align="center" valign="top">
				            	<table border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#FFFFFF">
				                	<tr>
				                    	<td align="left" valign="top">
				                        	<table border="0" cellpadding="0" cellspacing="0" width="600" class="flexibleContainer">
				                            	<tr>
				                                	<td align="left" valign="top" width="600" class="flexibleContainerCell" style="padding:20px 0;">';

                        $footer = '					</td>
				                                </tr>
				                            </table>
				                        </td>
				                    </tr>
				                </table>
				            </td>
				        </tr>
						<tr>
						<td>
						<table bgcolor="#FFF" border="0" cellpadding="0" cellspacing="0" width="600" id="emailFooter" style="border-top: solid 1px #CFCFCF;">
			                <tr>
			                    <td align="center" valign="top">
			                        <!-- CENTERING TABLE // -->
			                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
			                            <tr>
			                                <td align="center" valign="top">
			                                    <!-- FLEXIBLE CONTAINER // -->
			                                    <table border="0" cellpadding="0" cellspacing="0" width="600" class="flexibleContainer">
			                                        <tr>
			                                            <td align="center" valign="top" width="600" class="flexibleContainerCell">
			                                                <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
			                                                    <tr>
                                                                    <td align="left" valign="top" bgcolor="white" style="padding:20px 0">
                                                                    
			                                                            <p style="font-family:'.$kFont1.';font-size:13px;text-align:left;line-height:120%;width:100%;">
			                                                            	<a  style="color:'.$kColLink.'" href="'.$siteUrl.'/privacy-policy/"><span style="color:'.$kColLink.'">Privacy Policy</span></a>
			                                                            </p>
		
			                                                        </td>
			                                                    </tr>
			                                                </table>
			                                            </td>
			                                        </tr>
			                                    </table>
			                                    <!-- // FLEXIBLE CONTAINER -->
			                                </td>
			                            </tr>
			                        </table>
			                        <!-- // CENTERING TABLE -->
			                    </td>
			                </tr>
			            </table>
			            <!-- // END -->
                    </td>
                </tr>
            </table>
        </center>
    </body>
</html>';
