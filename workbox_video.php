<?php
    /*Plugin Name: Workbox Video from Vimeo & Youtube Plugin
    Author: Workbox Inc.
    Author URI: http://www.workbox.com/
    Plugin URI: http://blog.workbox.com/wordpress-video-gallery-plugin/
    Version: 2.3.5
    Description: The plugin allows to create a video gallery on any wordpress-generated page. 
	You can add videos from Youtube, Vimeo and Wistia by simply pasting the video URL. 
	Allows to control sort order of videos on the gallery page. Video galleries can be called on a page by using shortcodes now.
	This plugin is for advanced users. If you run into problems, please send us detailed notes about your set up and the errors and we'll do our best to get back to you.
	Spanish translation by Andrew Kurtis <a href="http://www.webhostinghub.com/">@WebHostingHub</a>
    == Copyright ==
    Copyright 2008-2013 Workbox Inc (email: support@workbox.com) 

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
    
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    
    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>*/

	define('WB_VID_DIR',dirname(__FILE__).'/');
	define('WB_VID_URL',WP_PLUGIN_URL.'/'.substr(WB_VID_DIR,strpos(WB_VID_DIR,'plugins')+8));
	define("WB_VIDEO_TABLE",'wb_video_VY');
	define("WB_VIDEO_GALLERIES_TABLE",'wb_video_galleries');
	define("WB_VIDEO_PAGE",'wb_video_VY');
	define("WB_VIDEO_OPTIONS_PAGE",'wb_video_VY_options');
	define("WB_VIDEO_GALLERIES_PAGE",'wb_video_VY_galleries');

	// activation, deactivation and uninstall hooks
	register_activation_hook( __FILE__, array('workbox_YV_video','activate') );
	register_deactivation_hook( __FILE__, array('workbox_YV_video','deactivate') );
	register_uninstall_hook( __FILE__, array( 'workbox_YV_video', 'uninstall' ) );
	
	// initialisation function for the administration part of the plugin
	add_action('admin_init',array('workbox_YV_video','init'));
	// shows menu in admin  area
	add_action('admin_menu', array('workbox_YV_video','menu'));
	// shows the functionality for the selected page
	add_action('the_content',array('workbox_YV_video','inpost'));
	add_shortcode( 'workbox_video_YV_list', array('workbox_YV_video','shortcode') );

	// add required JS and CSS files
	add_action('wp_enqueue_scripts', array('workbox_YV_video','add_js'));
	add_action('init', array('workbox_YV_video','add_style'));
	add_action('wp_head',array('workbox_YV_video','add_custom_style'));
	load_plugin_textdomain('workbox_video', false, basename( dirname( __FILE__ ) ) . '/languages' );
	function ap_action_init() {
		load_plugin_textdomain('workbox_video', false, dirname(plugin_basename(__FILE__)));
	}
	add_action('init', 'ap_action_init');
	
class workbox_YV_video {
    public function add_custom_style()
    {
	echo '
	<style>
	    .wb_video_pager {'.(get_option('class_wb_video_pager') != ''?get_option('class_wb_video_pager'):'width: 100%; clear: both;').'}
	    .wb_video_pager a {'.(get_option('class_wb_video_pager_a') != ''?get_option('class_wb_video_pager_a'):'').'}
	    .wb_video_container {'.(get_option('class_wb_video_container') != ''?get_option('class_wb_video_container'):'width: 100%; padding: 20px 0; display: inline-block;').'}
	    .wb_video_item {'.(get_option('class_wb_video_item') != ''?get_option('class_wb_video_item'):'clear: both;').'}
	    .wb_video_image_link {'.(get_option('class_wb_video_image_link') != ''?get_option('class_wb_video_image_link'):'float: left; padding: 0 20px 5px 0;').'}
	    .wb_video_image_img  {'.(get_option('class_wb_video_image_img') != ''?get_option('class_wb_video_image_img'):'').'}
	    .wb_video_title {'.(get_option('class_wb_video_title') != ''?get_option('class_wb_video_title'):'').'}
	    .wb_video_description {'.(get_option('class_wb_video_description') != ''?get_option('class_wb_video_description'):'').'}
	    .wb_video_icon {position:absolute; left:46px; top:33px; display:block; width:31px; height:27px; background:url('.WB_VID_URL.'ico-play.png) 0 0 no-repeat;}
		.wb_horizontal_container { clear: both; }
		.wb_horizontal_container .wb_video_item { float: left; clear: none; }
		.fancybox-wrap { overflow: visible!important; }
	</style>
	';
    }
    
    public function add_js() {
		wp_deregister_script( 'jquery' );
		wp_enqueue_script('jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js');
        wp_enqueue_script('fancybox', WB_VID_URL.'jquery.fancybox.js');
    }
    
    public function add_style() {
        wp_enqueue_style('fancybox',WB_VID_URL.'jquery.fancybox.css');
    }
	
	private function __createVideoTable() {
		global $wpdb;
		$sql = 'CREATE TABLE `'.WB_VIDEO_TABLE.'` (
					  `id` int(11) NOT NULL auto_increment,
					  `title` varchar(255) default NULL,
					  `url` varchar(255) default NULL,
					  `image` varchar(255) default NULL,
					  `code` text,
					  `description` text,
					  `is_live` int(1) default NULL,
					  `order_no` int(11) default NULL,
					  `gallery_id` int(11) default NULL,
					  PRIMARY KEY  (`id`)
					)
					';
		$wpdb->query($sql);
	}
	
	private function __createVideoGalleriesTable() {
		global $wpdb;
		$sql = 'CREATE TABLE `'.WB_VIDEO_GALLERIES_TABLE.'` (
					  `id` int(11) NOT NULL auto_increment,
					  `title` varchar(255) default NULL,
					  `description` text,
					  `post_id` int(11) default NULL,
					  `post_blog_id` int(11) default NULL,
					  `is_vertical` int(1) default NULL,
					  `is_live` int(1) default NULL,
					  `order_no` int(11) default NULL,
					  PRIMARY KEY  (`id`)
					)
					';
		$wpdb->query($sql);
	}
    
	//check tables in DB, if not exists, creates their
	public function checkTables() {
		global $wpdb;
		$hostPort = explode(':', DB_HOST);
		$host = '';
		$port = '';
		if (count($hostPort) > 0) {
			$host = $hostPort[0];
		}
		if (count($hostPort) > 1) {
			$port = $hostPort[1];
		}
		if ($host != '') {
			if ($port != '') {
				$mysqli = new mysqli($host, DB_USER, DB_PASSWORD, DB_NAME, $port);
			}
			else {
				$mysqli = new mysqli($host, DB_USER, DB_PASSWORD, DB_NAME);
			}
			if (!$mysqli->connect_errno) {
				$r = $mysqli->query('SELECT 1 FROM `'.WB_VIDEO_TABLE.'` WHERE 0');
				if (!$r) {
					self::__createVideoTable();
				}
				else {
					$r = $mysqli->query('select gallery_id from '.WB_VIDEO_TABLE.' limit 1');
					if (!$r) {
						$sql = 'alter table '.WB_VIDEO_TABLE.' add gallery_id int(11) after order_no';
						$wpdb->query($sql);
					}
				}
				$r = $mysqli->query('SELECT 1 FROM `'.WB_VIDEO_GALLERIES_TABLE.'` WHERE 0');
				if (!$r) {
					self::__createVideoGalleriesTable();
				}
				else {
					$r = $mysqli->query('select post_blog_id from '.WB_VIDEO_GALLERIES_TABLE.' limit 1');
					if (!$r) {
						$sql = 'alter table '.WB_VIDEO_GALLERIES_TABLE.' add post_blog_id int(11) after post_id';
						$wpdb->query($sql);
					}
					$r = $mysqli->query('select is_vertical from '.WB_VIDEO_GALLERIES_TABLE.' limit 1');
					if (!$r) {
						$sql = 'alter table '.WB_VIDEO_GALLERIES_TABLE.' add is_vertical int(1) after post_blog_id';
						$wpdb->query($sql);
					}
				}
			}
			else {
				self::__createVideoTable();
				self::__createVideoGalleriesTable();
			}
		}
		else {
			self::__createVideoTable();
			self::__createVideoGalleriesTable();
		}
	}
	
    // outputs the content then functionality is attached to specific page
    public function inpost($content) {
		global $wpdb, $posts;
		$post_id = get_the_ID();
		$flag = false;
		if ($post_id != get_option('wb_video_VY_page_id')) {
			$sql = 'select * from '.WB_VIDEO_GALLERIES_TABLE.' where is_live = 1 and (post_id = '.$post_id.' or post_blog_id='.$post_id.')';
			$list = $wpdb->get_results($sql);
			if (count($list > 0)) {
				$content .= $list[0]->description;
				$flag = true;
			}
		}
		else {
			$flag = true;
		}
		$html = '';
		if (isset($post_id) && get_option('wb_video_VY_fw') == 0 && $flag == true) {
			// call the output function
			$html .= self::__getContent(0);
		}
		return $content.$html;
    }
    
    /*public function shortcode() {
		$html = '';
		$html = self::__getContent(1);
		return $html;
    }*/
	
	public function shortcode($atts) {
		extract(shortcode_atts(array(
		  'gallery_name' => 1,
		), $atts));
		$html = self::__getContent(0, $gallery_name);
		return $html;
	}

    
    // use this function to output the functionality anywhere in the code
    public function showList() {
		$html = self::__getContent(2);
		return $html;
    }
    
	private function __getContent($show_type, $gallery_name = false) {
		global $wpdb, $posts, $count;
		if ($count == null) $count = 0;
		$count++;
		$post_id = get_the_ID();
		$page_len = intval(get_option('wb_video_VY_page_len'));
		//$total = $wpdb->get_var('select count(id) from '.WB_VIDEO_TABLE.' where is_live=1');
		if ($gallery_name != false) {
			$total = $wpdb->get_var('select count(a.id) from '.WB_VIDEO_TABLE.' a, '.WB_VIDEO_GALLERIES_TABLE.' b where a.is_live=1 and b.is_live=1 and b.title = \''.$gallery_name.'\' and a.gallery_id = b.id');
		}
		else {
			$total = $wpdb->get_var('select count(a.id) from '.WB_VIDEO_TABLE.' a, '.WB_VIDEO_GALLERIES_TABLE.' b where a.is_live=1 and b.is_live=1 and (b.post_id = '.$post_id.' or b.post_blog_id = '.$post_id.') and a.gallery_id = b.id');
		}
		$page_html = '';
		$sSQLLimit = '';
		// pages link
		if ($page_len > 0 && $total>$page_len) {
			$current_link = get_permalink();
			$aL = parse_url($current_link);
			$query = array();
			if ($_SERVER['QUERY_STRING'] != '')
			$query = explode ('&',$_SERVER['QUERY_STRING']);
			$aQueryTmp = array();
			foreach($query as &$item) {
				$s = explode('=',$item);
				$aQueryTmp[$s[0]] = isset($s[1])?$s[1]:'';
			}
			$new_url = $aL['scheme'].'://'.$aL['host'].$aL['path'];
			$page_html = '';
			$page_html.= '<div class="wb_video_pager">
			Page: ';
			$pages_count = ceil($total/$page_len);
			$current_page = isset($aQueryTmp['wb_video_page_id'])?intval($aQueryTmp['wb_video_page_id']):0;
			$current_page = max(0, min($current_page, $pages_count-1));
			$aPages = array();
			for($i = 0; $i<$pages_count; $i++) {
				$sStart = ($current_page == $i?'<span>[':'');
				$sEnd = ($current_page == $i?']<span>':'');
				$aQueryTmp['wb_video_page_id'] = $i;
				$aPages[] = '<a href="'.$new_url.'?'.http_build_query($aQueryTmp).'">'.$sStart.($i+1).$sEnd.'</a>';
			}
			$page_html.= implode(' | ', $aPages);
			$page_html.= '</div>';
			$sSQLLimit = ' limit '.($current_page*$page_len).', '.$page_len;
		}
		
		// get list
		if ($gallery_name != false) {
			$list = $wpdb->get_results('select a.*, b.post_id, b.post_blog_id, b.is_vertical from '.WB_VIDEO_TABLE.' a, '.WB_VIDEO_GALLERIES_TABLE.' b where a.is_live=1 and b.is_live=1 and b.title = \''.$gallery_name.'\' and a.gallery_id = b.id order by b.order_no, a.order_no desc'.$sSQLLimit);
		}
		else {
			$list = $wpdb->get_results('select a.*, b.post_id, b.post_blog_id, b.is_vertical from '.WB_VIDEO_TABLE.' a, '.WB_VIDEO_GALLERIES_TABLE.' b where a.is_live=1 and b.is_live=1 and (b.post_id = '.$post_id.' or b.post_blog_id = '.$post_id.') and a.gallery_id = b.id order by b.order_no, a.order_no desc'.$sSQLLimit);
		}
		
		// generate main HTML
		$html = $page_html;
			$html.= '<div class="wb_video_container">';
			$countInLine = htmlspecialchars(get_option('class_wb_video_count_in_line'));
			if ($countInLine == '') {
				$countInLine = 3;
			}
			$index = 1;
			//this flag for begin printing wb_horizontal_container
			$flagOfBegin = false;
			foreach($list as $k=>$item) {
				$class = ' class="wb_vertical_container"';
				if ($item->is_vertical == 0) {
					$class = ' class="wb_horizontal_container"';
				}
				if ($index == 1) {
					$html .= '<div'.$class.'>';
					$flagOfBegin = true;
				}
				$html.= '<div class="wb_video_item">';
				$html.= '<a href="#movie'.$count.'_'.$k.'" class="wb_video_image_link wbfancybox" style="position: relative;"><img src="'.$item->image.'" width="120" class="wb_video_image_img"><b class="wb_video_icon"></b></a>';
				if ($item->title) {
					if ($item->is_vertical == 0) {
						$html.= '<div class="wb_video_title"><a href="#movie'.$count.'_'.$k.'" class="wb_video_title wbfancybox">'.$item->title.'</a></div>';
					}
					else {
						$html.= '<a href="#movie'.$count.'_'.$k.'" class="wb_video_title wbfancybox">'.$item->title.'</a>';
					}
				}
				if ($item->description) {
					$html.= '<div class="wb_video_description">'.$item->description.'</div>';
				}
				$html.= '</div>';
				if ($flagOfBegin == true) {
					if (($index == $countInLine) && ($item->is_vertical == 0)) {
						$html.= '</div>';
						$index = 0;
					}
					$index++;
				}
			}
			if ( ($index <= $countInLine) && ($flagOfBegin == true) && ($index > 1) && ($item->is_vertical == 0) ) {
				$html.= '</div>';
			}
			else if ($item->is_vertical == 1) {
				$html.= '</div>';
			}
			$html.= '</div>';
		$html.= $page_html;
		
		foreach($list as $k=>$item) {
			$html.= '
			<div style="display: none;" id="movie'.$count.'_'.$k.'">'.$item->code.'</div>
			';
		}
		$html.= '
			<script language="JavaScript">
				var $ = jQuery.noConflict();
			$(function() {
			$(".wbfancybox").fancybox();
			});
			</script>
			';
		return $html;
	}

    

    

    public function activate() {
		// create tables
		self::checkTables();
    }

    

    public function deactivate() {

    }
	
	public function uninstall() {
		global $wpdb;
		$sql = "DROP TABLE IF EXISTS ".WB_VIDEO_TABLE;
		$wpdb->query($sql);
		$sql = "DROP TABLE IF EXISTS ".WB_VIDEO_GALLERIES_TABLE;
		$wpdb->query($sql);
    }

    

    public function menu() {
        global $wpdb;
		add_menu_page("Videos", __('Videos','workbox_video'), "administrator",WB_VIDEO_PAGE, array(__CLASS__,'get_list'),false);
        add_submenu_page(WB_VIDEO_PAGE, "Galleries", __("Galleries",'workbox_video'), "administrator",WB_VIDEO_GALLERIES_PAGE, array(__CLASS__,'get_galleries'));
		$sql = 'select * from '.WB_VIDEO_GALLERIES_TABLE.' where is_live=1 order by order_no';
		$list = $wpdb->get_results($sql);
		if (count($list) > 0) {
			foreach($list as $item) {
				add_submenu_page(WB_VIDEO_PAGE, $item->title, $item->title, "administrator", 'gallery_'.$item->id, array(__CLASS__,'getVideoByGallery'));
			}
		}
		add_submenu_page(WB_VIDEO_PAGE, "Options", __("Options",'workbox_video'), "administrator",WB_VIDEO_OPTIONS_PAGE, array(__CLASS__,'get_options'));
    }

    

    public function get_list()
    {
        if (isset($_GET['edit']))
        {
            echo self::__getEditPage();   
        }
        else
        {
            echo self::__getListPage();
        }
    }
    
    private function __getListPage() {
        global $wpdb, $posts;
        $list = $wpdb->get_results('select a.*, b.title as gallery_title, b.post_id, b.post_blog_id, b.id as bid from '.WB_VIDEO_TABLE.' a left join '.WB_VIDEO_GALLERIES_TABLE.' b on a.gallery_id = b.id order by a.id desc');
        $html = '';
        $html.= '
        <div class="wrap">
            <br>
            <h2>'.__('List of videos','workbox_video').' <a href="admin.php?page='.WB_VIDEO_PAGE.'&edit" class="add-new-h2">'.__('Add New','workbox_video').'</a></h2>
            ';
        if (count($list) > 0) {
			$current_url = parse_url($_SERVER['REQUEST_URI']);
			$html.= '
			<table class="widefat" cellspacing="0" style="width:100%;">
			<thead>
				<tr>
				<th class="manage-column" width="50">&nbsp;</th>
				<th class="manage-column">'.__('Video Title', 'workbox_video').'</th>
				<th class="manage-column">'.__('Video Gallery','workbox_video').'</th>
				<th class="manage-column" width="120">'.__('Video Thumbnail','workbox_video').'</th>
				'.//<th class="manage-column" width="100">Sort</th>
				'</tr>
			</thead>
			<tbody>';
			foreach($list as $item) {
				$tr_style = 'style="background-color: #'.($item->is_live?"ccffcc":"ffcccc").'"';
				$color_border = !$item->is_live?'#fff':'#ccc';
				$html.='<tr class="iedit" '.$tr_style.'>';
				$html.= '<td valign="middle" align="center" style="border-right: 1px dotted '.$color_border.'"><a href="admin.php?page='.WB_VIDEO_PAGE.'&edit&id='.$item->id.'">'.__('edit','workbox_video').'</a></td>';
				$html.= '<td valign="middle" style="border-right: 1px dotted '.$color_border.'">'.$item->title.'</td>';
				$titleGallery = '';
				$flag = true;
				if ( (get_the_title($item->post_id) == '') && (get_the_title($item->post_blog_id) == '') ) {
					$flag = false;
				}
				if ($flag == false) {
					$titleGallery = $item->gallery_title.' ('.__('not attached','workbox_video').')';
				}
				else {
					$title1 = get_the_title($item->post_id);
					$title2 = get_the_title($item->post_blog_id);
					$i = 0;
					if ($title1 != '') {
						$i++;
					}
					if ($title2 != '') {
						$i++;
					}
					$title = $title1;
					if ($i > 1) {
						$title .= ', ';
					}
					$title .= $title2;
					$titleGallery = '<a href="admin.php?page=gallery_'.$item->bid.'">'.$item->gallery_title.'('.$title.')</a>';
				}
				$html.= '<td valign="middle" style="border-right: 1px dotted '.$color_border.'">'.$titleGallery.'</td>';
				$html.= '<td valign="middle" style="border-right: 1px dotted '.$color_border.'"><img src="'.$item->image.'" width="120"></td>';
				//$html.= '<td valign="middle" align="center"><a href="admin.php?page='.WB_VIDEO_PAGE.'&move=down&id='.$item->id.'">Up</a> / <a href="admin.php?page='.WB_VIDEO_PAGE.'&move=up&id='.$item->id.'">Down</a></td>';
				$html.='</tr>';
			}
			$html.='</tbody></table>';
        }
        else {
            $html.= '<br><br>'.__('Currently there are no records','workbox_video');
        }
        $html.= '</div>';
        echo $html;
    }
    
    private function __getEditPage() {
        global $wpdb, $wb_form_info, $wp_version;
        
        $item_id = isset($_GET['id'])?intval($_GET['id']):0;
        
	
        $html = '';
        
        $html.= '
        <div class="wrap"><form action="" method="post" name="edit_form" enctype="multipart/form-data">
        <h2>'.($item_id?__('Edit Record','workbox_video').': '.$wb_form_info->title:__('Add New Video','workbox_video')).'</h2>
        <script language="JavaScript">
			function doCancel() {
				window.location.href="admin.php?page='.WB_VIDEO_PAGE.'";    
			}
			function doDelete() {
				oForm = document.forms.edit_form;
				
				if (confirm("'.__('Do you really want to delete this record?','workbox_video').'"))
				{
				oForm.action_name.value = "delete";
				oForm.action = "";
				oForm.target = "_self";
				oForm.submit();
				}
			}
			
			function doSave() {
				oForm = document.forms.edit_form;
				
				oForm.action = "";
				oForm.target = "_self";
				oForm.action_name.value = "save";
				oForm.submit();
			}
        </script>
            ';
            $html.= '
            <div class="metabox-holder has-right-sidebar" id="poststuff">
		<div class="inner-sidebar" style="display:block">
		    <div class="postbox">
			<h3 class="hndle"><span>'.__('Publish','workbox_video').'</span></h3>
			    <div id="submitpost" class="submitbox">
                                <div id="major-publishing-actions">';
				if ($item_id>0) {
				    $html.='<div id="delete-action">
					<a href="JavaScript:" onclick="doDelete()" class="submitdelete deletion">'.__('Delete','workbox_video').'</a>
				    </div>';
				}  
				    $html.='
                                    <div id="publishing-action">
					<a href="JavaScript:" onclick="doCancel();">'.__('back to list','workbox_video').'</a>&nbsp;&nbsp;&nbsp;
					<input type="button" onclick="doSave()" value="'.__('Publish','workbox_video').'" class="button-primary" >
				    </div>
				    <div class="clear"></div>
				</div>
			    </div>
			</div>
		    </div>
		    <div id="post-body">
			<div id="post-body-content">
				<input type="hidden" name="id" value="'.intval($item_id).'">
				<input type="hidden" name="action_name" value="">
			    ';
	///////////////////////////////////////////
		$gallery_id = self::_get('gallery_id',$wb_form_info);
        $html.= '
                <table border="0" width="100%">
                    <tr>
                        <td width="30%" align="right"><b>'.__('Video Name','workbox_video').'</td>
                        <td width="70%">
							<input type="text" name="title" value="'.self::_get('title',$wb_form_info).'" style="width:100%">    
						</td>
                    </tr>
					<tr>
                        <td width="30%" align="right"><b>'.__('Video Gallery','workbox_video').'</td>
						<td width="70%">
						<select name="gallery_id" style="width:100%;">
                            <option value="0">'.__('not attached to any gallery','workbox_video').'</option>
                        ';
						$sql = 'select * from '.WB_VIDEO_GALLERIES_TABLE.' order by order_no';
						$rows = $wpdb->get_results($sql);
						if (count($rows) > 0) {
							foreach($rows as $item) {
								$selected = '';
								if (isset($_GET['gallery'])) {
									if ($_GET['gallery'] == $item->id) {
										$selected = 'selected';
									}
								}
								else 
								if ($item->id == $gallery_id) {
									$selected = 'selected';
								}
								if ($item->is_live == 1) {
									$html.='<option value="'.$item->id.'" '.$selected.'>'.$item->title.'</option>';
								}
								else {
									$html.='<option value="'.$item->id.'" '.$selected.'>'.$item->title.' ('.__('Gallery is not active','workbox_video').')</option>';
								}
							}
						}
                        $html.='
                        </select>
						</td>
                    </tr>
					<tr>
                        <td width="30%" align="right"><b>'.__('Video URL','workbox_video').'</td>
                        <td width="70%">
							<input type="text" name="url" value="'.self::_get('url',$wb_form_info).'" style="width:100%">    
						</td>
                    </tr>
					<tr>
                        <td width="30%" align="right" valign="top"><br><br><b>'.__('Video Description','workbox_video').'</td>
						<td width="70%">
						';
						if ( version_compare( $wp_version, '3.3', '>=' ) ) {
							ob_start();
							wp_editor($wb_form_info->description,'description', array('textarea_name'=>'description'));
							$html.= '<div id="poststuff" >'.ob_get_clean().'</div>';
						}
						else
						{
							ob_start();
							the_editor($wb_form_info->description,'description');
							$str = '<div id="poststuff" >'.ob_get_clean().'</div>';
							$html.= str_replace('<textarea','<textarea style="width:100%" ', $str);
						}
						
						$html.= '
						</td>
                    </tr>
					<tr>
                        <td width="30%" align="right"><b>'.__('Show video?','workbox_video').'</td>
                        <td width="70%">
							<input type="checkbox" name="is_live" value="1" '.(self::_get('is_live',$wb_form_info,1) == 1?'checked':'').'>    
						</td>
                    </tr>
                </table>';
		$html.= '
			</div>
		    </div>
		    <br class="clear">
			</div></form>
			</div>';
        $html.= '</div>';
        echo $html;
    }
	
	public function getVideoByGallery() {
		global $wpdb, $posts;
		if (isset($_GET['page'])) {
			$a = explode('_',$_GET['page']);
			if (count($a) > 1) {
				$gallery_id = $a[1];
			}
			else {
				$gallery_id = 0;
			}
		}
		else {
			$gallery_id = 0;
		}
		$list = $wpdb->get_results('select a.*, b.title as gallery_title from '.WB_VIDEO_TABLE.' a, '.WB_VIDEO_GALLERIES_TABLE.' b where a.gallery_id = b.id and gallery_id = '.$gallery_id.' order by a.order_no desc');
        $html = '';
		$rows = $wpdb->get_results('select * from '.WB_VIDEO_GALLERIES_TABLE.' where id='.$gallery_id.' and is_live=1');
        if (count($rows) > 0) {
			$html.= '
			<div class="wrap">
            <br>
			<h2>'.__('List of videos in','workbox_video').' '.$rows[0]->title.'<a href="admin.php?page='.WB_VIDEO_PAGE.'&edit&gallery='.$gallery_id.'" class="add-new-h2">'.__('Add New','workbox_video').'</a></h2>';
		}
		else {
			$html.= '
			<div class="wrap">
            <br>
			<h2>'.__('Error','workbox_video').'</h2>';
		}
            //<h2>List of videos <a href="admin.php?page='.WB_VIDEO_PAGE.'&edit" class="add-new-h2">Add New</a></h2>
        if (count($list)>0) {
			$current_url = parse_url($_SERVER['REQUEST_URI']);
			$html.= '
				<table class="widefat" cellspacing="0" style="width:100%;">
				<thead>
					<tr>
					<th class="manage-column" width="50">&nbsp;</th>
					<th class="manage-column">'.__('Video Title', 'workbox_video').'</th>
					<th class="manage-column" width="120">'.__('Video Thumbnail','workbox_video').'</th>
					<th class="manage-column" width="100">'.__('Sort','workbox_video').'</th>
					</tr>
				</thead>
				<tbody>';
			foreach($list as $item) {
				$tr_style = 'style="background-color: #'.($item->is_live?"ccffcc":"ffcccc").'"';
				$color_border = !$item->is_live?'#fff':'#ccc';
				$html.='<tr class="iedit" '.$tr_style.'>'; 
				$html.= '<td valign="middle" align="center" style="border-right: 1px dotted '.$color_border.'"><a href="admin.php?page='.WB_VIDEO_PAGE.'&edit&id='.$item->id.'">'.__('edit','workbox_video').'</a></td>';
				$html.= '<td valign="middle" style="border-right: 1px dotted '.$color_border.'">'.$item->title.'</td>';
				$html.= '<td valign="middle" style="border-right: 1px dotted '.$color_border.'"><img src="'.$item->image.'" width="120"></td>';
				$html.= '<td valign="middle" align="center"><a href="admin.php?page=gallery_'.$gallery_id.'&move=down&id='.$item->id.'">'.__('Up','workbox_video').'</a> / <a href="admin.php?page=gallery_'.$gallery_id.'&move=up&id='.$item->id.'">'.__('Down','workbox_video').'</a></td>';
				$html.='</tr>';
			}
			$html.='</tbody></table>';
        }
        else {
            $html.= '<br><br>'.__('Currently there are no records','workbox_video');
        }
        $html.= '</div>';
        echo $html;
	}
	
    public function get_galleries() {
		global $wpdb, $posts;
		$html = '';
		if (isset($_GET['edit'])) {
            global $wpdb, $wb_form_info, $wp_version;
			$item_id = isset($_GET['id'])?intval($_GET['id']):0;
			$html.= '
			<div class="wrap"><form action="" method="post" name="edit_form" enctype="multipart/form-data">
            <h2>'.($item_id?__('Edit Record','workbox_video').': '.$wb_form_info->title:__('Add New Gallery','workbox_video')).'</h2>
            <script language="JavaScript">
                function doCancel() {
					window.location.href="admin.php?page='.WB_VIDEO_GALLERIES_PAGE.'";    
				}
				function doDelete() {
					oForm = document.forms.edit_form;
					if (confirm("'.__('Do you really want to delete this record?','workbox_video').'")) {
						oForm.action_name.value = "delete";
						oForm.action = "";
						oForm.target = "_self";
						oForm.submit();
					}
				}
				function doSave() {
					oForm = document.forms.edit_form;
					oForm.action = "";
					oForm.target = "_self";
					oForm.action_name.value = "save";
					oForm.submit();
				}
            </script>
            ';
            $html.= '
            <div class="metabox-holder has-right-sidebar" id="poststuff">
			<div class="inner-sidebar" style="display:block">
		    <div class="postbox">
			<h3 class="hndle"><span>'.__('Publish','workbox_video').'</span></h3>
			    <div id="submitpost" class="submitbox">
                    <div id="major-publishing-actions">';
				if ($item_id>0) {
				    $html.='<div id="delete-action">
					<a href="JavaScript:" onclick="doDelete()" class="submitdelete deletion">'.__('Delete','workbox_video').'</a>
				    </div>';
				}  
				$html.='
                    <div id="publishing-action">
					<a href="JavaScript:" onclick="doCancel();">'.__('back to list','workbox_video').'</a>&nbsp;&nbsp;&nbsp;
					<input type="button" onclick="doSave()" value="'.__('Publish','workbox_video').'" class="button-primary" >
				    </div>
				    <div class="clear"></div>
					</div>
			    </div>
			</div>
		    </div>
		    <div id="post-body">
				<div id="post-body-content">
				<input type="hidden" name="id" value="'.intval($item_id).'">
				<input type="hidden" name="action_name" value="">
			    ';
			$pages = get_pages();
			$args = array(  
				'numberposts' => 100
			); 
			$posts = get_posts($args);
			$html.= '
                <table border="0" width="100%">
                    <tr>
                        <td width="30%" align="right"><b>'.__('Gallery Name','workbox_video').': </td>
                        <td width="70%">
							<input type="text" name="title" value="'.self::_get('title',$wb_form_info).'" style="width:100%">    
						</td>
                   </tr>
					<tr>
                        <td width="30%" align="right"><b>'.__('Gallery Page','workbox_video').': </td>
                        <td width="70%">
						<select name="post_id" style="width:350px;">
                            <option value="0">'.__('not attached to any page','workbox_video').'</option>
                        ';
						$flag = false;
						if ($item_id != 0) {
							$sql = 'select * from '.WB_VIDEO_GALLERIES_TABLE.' where id = '.$item_id.' and is_live=1';
							$rows = $wpdb->get_results($sql);
							if (count($rows) > 0) {
								$flag = true;
							}
						}
                        foreach($pages as $item) {
							$selected = '';
							if ($flag == true) {
								foreach($rows as $row) {
									if ($row->post_id == $item->ID) {
										$selected = 'selected';
									}
								}
							}
                            $html.='<option value="'.$item->ID.'" '.$selected.'>'.$item->post_title.'</option>';
                        }
                        $html.='
                        </select>
						</td>
                    </tr>
					<tr>
                        <td width="30%" align="right"><b>'.__('Gallery Post Page','workbox_video').': </td>
                        <td width="70%">
						<select name="post_blog_id" style="width:350px;">
                            <option value="0">'.__('not attached to any post page','workbox_video').'</option>
                        ';
						$flag = false;
						if ($item_id != 0) {
							$sql = 'select * from '.WB_VIDEO_GALLERIES_TABLE.' where id = '.$item_id.' and is_live=1';
							$rows = $wpdb->get_results($sql);
							if (count($rows) > 0) {
								$flag = true;
							}
						}
                        foreach($posts as $item) {
							$selected = '';
							if ($flag == true) {
								foreach($rows as $row) {
									if ($row->post_blog_id == $item->ID) {
										$selected = 'selected';
									}
								}
							}
                            $html.='<option value="'.$item->ID.'" '.$selected.'>'.$item->post_title.'</option>';
                        }
                        $html.='
                        </select>
						</td>
                    </tr>
					<tr>
                        <td width="30%" align="right" valign="top"><br><br><b>'.__('Gallery Description','workbox_video').': </td>
						<td width="70%">
					';
			if ( version_compare( $wp_version, '3.3', '>=' ) ) {
			    ob_start();
			    wp_editor($wb_form_info->description,'description', array('textarea_name'=>__('description','workbox_video')));
			    $html.= '<div id="poststuff" >'.ob_get_clean().'</div>';
			}
			else {
			    ob_start();
			    the_editor($wb_form_info->description,'description');
			    $str = '<div id="poststuff" >'.ob_get_clean().'</div>';
			    $html.= str_replace('<textarea','<textarea style="width:100%" ', $str);
			}
			
			$html.= '
						</td>
					</tr>
					<tr>
                        <td width="30%" align="right"><b>'.__('Stack videos vertically?','workbox_video').'</td>
                        <td width="70%">
							<input type="checkbox" name="is_vertical" value="1" '.(self::_get('is_vertical',$wb_form_info,1) == 1?'checked':'').'>    
						</td>
                    </tr>
					<tr>
                        <td width="30%" align="right"><b>'.__('Is Live?','workbox_video').'</td>
                        <td width="70%">
							<input type="checkbox" name="is_live" value="1" '.(self::_get('is_live',$wb_form_info,1) == 1?'checked':'').'>    
						</td>
                    </tr>
                </table>
				';
			$html.= '
				</div>
		    </div>
		    <br class="clear">
			</div></form>
			</div>';
			$html.= '</div>';   
        }
        else {
			$list = $wpdb->get_results('select * from '.WB_VIDEO_GALLERIES_TABLE.' order by order_no');
			$html.= '
			<div class="wrap">
				<br>
				<h2>'.__('Gallery List','workbox_video').' <a href="admin.php?page='.WB_VIDEO_GALLERIES_PAGE.'&edit" class="add-new-h2">'.__('Add New','workbox_video').'</a></h2>
				';	
			if (count($list)>0) {
				$current_url = parse_url($_SERVER['REQUEST_URI']);
				$html.= '
				<table class="widefat" cellspacing="0" style="width:100%;">
				<thead>
					<tr>
						<th class="manage-column" width="50">&nbsp;</th>
						<th class="manage-column">'.__('Gallery Title','workbox_video').'</th>
						<th class="manage-column" width="120">'.__('Gallery Page','workbox_video').'</th>
						<th class="manage-column" width="120">'.__('Gallery Post Page','workbox_video').'</th>
						<th class="manage-column" width="100">'.__('Sort','workbox_video').'</th>
					</tr>
				</thead>
				<tbody>';
				foreach($list as $item) {
					$tr_style = 'style="background-color: #'.($item->is_live?"ccffcc":"ffcccc").'"';
					$color_border = !$item->is_live?'#fff':'#ccc';
					$html.='<tr class="iedit" '.$tr_style.'>';
					$html.= '<td valign="middle" align="center" style="border-right: 1px dotted '.$color_border.'"><a href="admin.php?page='.WB_VIDEO_GALLERIES_PAGE.'&edit&id='.$item->id.'">'.__('edit','workbox_video').'</a></td>';
					$html.= '<td valign="middle" style="border-right: 1px dotted '.$color_border.'">'.$item->title.'</td>';
					$html.= '<td valign="middle" style="border-right: 1px dotted '.$color_border.'">'.get_the_title($item->post_id).'</td>';
					$html.= '<td valign="middle" style="border-right: 1px dotted '.$color_border.'">'.get_the_title($item->post_blog_id).'</td>';
					$html.= '<td valign="middle" align="center"><a href="admin.php?page='.WB_VIDEO_GALLERIES_PAGE.'&move=up&id='.$item->id.'">'.__('Up','workbox_video').'</a> / <a href="admin.php?page='.WB_VIDEO_GALLERIES_PAGE.'&move=down&id='.$item->id.'">'.__('Down','workbox_video').'</a></td>';
					$html.='</tr>';
				}
				$html.='</tbody></table>';
			}
			else {
				$html.= '<br><br>'.__('Currently there are no records','workbox_video');
			}
			$html.= '</div>';
        }
		echo $html;
	}

    public function get_options() {
        $_wb_video_VY_fw = get_option('wb_video_VY_fw');
        $pages = get_pages();
        $_wb_video_VY_page_id = intval(get_option('wb_video_VY_page_id'));
        $_wb_video_VY_page_len = intval(get_option('wb_video_VY_page_len'));
        
        
        $html = '';
        $html.= '
        <br>
        <h2>'.__('Workbox Video Plugin Options','workbox_video').'</h2>';
        
        if (isset($_GET['updated']))
        {
            $html.= '<h3 style="color:red">'.__('Settings updated!','workbox_video').'</h3>';
        }
        
        $html.= '<script language="JavaScript">
            function wb_setVisible(value)
            {
                switch(value)
                {
                    case 0:
                        document.getElementById("wb_video_VY_fw1").style.display = "";
                        document.getElementById("wb_video_VY_fw2").style.display = "none";
                        document.getElementById("wb_video_VY_fw3").style.display = "none";
                        break;
                    
                    case 1:
                        document.getElementById("wb_video_VY_fw1").style.display = "none";
                        document.getElementById("wb_video_VY_fw2").style.display = "";
                        document.getElementById("wb_video_VY_fw3").style.display = "none";
                        break;
                        
                    case 2:
                        document.getElementById("wb_video_VY_fw1").style.display = "none";
                        document.getElementById("wb_video_VY_fw2").style.display = "none";
                        document.getElementById("wb_video_VY_fw3").style.display = "";
                        break;
                    default:
                        document.getElementById("wb_video_VY_fw1").style.display = "";
                        document.getElementById("wb_video_VY_fw2").style.display = "none";
                        document.getElementById("wb_video_VY_fw3").style.display = "none";
                }
            }
        </script>
        <form name="workbox_video_options_form" method="post">
            <input type="hidden" name="_workbox_edit_video_VY_options_attempt" value="Yes">
            <table border="0">
                <tr>
                    <td width="200" align="right"><b>'.__('__Functionality type__','workbox_video').':</b></td>
                    <td>
                        <input type="radio" name="wb_video_VY_fw" onclick="wb_setVisible(0)" value="0" '.( $_wb_video_VY_fw == 0?'checked':'').' id="wb_video_fw_1"> <label for="wb_video_fw_1">'.__('Select a page','workbox_video').'</label>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <input type="radio" name="wb_video_VY_fw" onclick="wb_setVisible(1)" value="1" '.( $_wb_video_VY_fw == 1?'checked':'').' id="wb_video_fw_2"> <label for="wb_video_fw_2">'.__('Use a ShortCode','workbox_video').'</label>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <input type="radio" name="wb_video_VY_fw" onclick="wb_setVisible(2)" value="2" '.( $_wb_video_VY_fw == 2?'checked':'').' id="wb_video_fw_3"> <label for="wb_video_fw_3">'.__('Use a PHP function','workbox_video').'</label>
                    </td>
                </tr>
            </table>
            
            <table border="0" id="wb_video_VY_fw1" style="display: none;">
                './*<tr>
                    <td width="200" align="right"><b>Select a Page:</b></td>
                    <td>
                        <select name="wb_video_VY_page_id" style="width:350px;">
                            <option value="0">not attached to any page</option>
                        ';
                        foreach($pages as $item)
                        {
                            $html.='<option value="'.$item->ID.'" '.( $_wb_video_VY_page_id == $item->ID?"selected":"").'>'.$item->post_title.'</option>';
                        }
                        $html.='
                        </select>
                    </td>
                </tr>*/
            '</table>
            
            <table border="0" id="wb_video_VY_fw2" style="display: none;">
                <tr>
                    <td width="200" align="right"><b>'.__('Using a shortcode','workbox_video').':</b></td>
                    <td>
                        '.__('Use a shortcode [workbox_video_YV_list gallery_name="Test Post Gallery"] where Test Post Gallery is the name of the gallery you want to use on the page.','workbox_video').'
                    </td>
                </tr>
            </table>
            
            <table border="0" id="wb_video_VY_fw3" style="display: none;">
                <tr>
                    <td width="200" align="right"><b>'.__('Using a function','workbox_video').':</b></td>
                    <td>
                        '.__('Use a echo workbox_YV_video::showList(); to show a list of videos','workbox_video').'
                    </td>
                </tr>
            </table>
            ';
            
            $html.= '
	    <table border="0" width="90%">
                <tr>
                    <td width="200" align="right"><b>'.__('Show videos on page','workbox_video').'</b><br><i>'.__('0 - no pagination','workbox_video').'</i>:</td>
                    <td>
                        <input type="text" name="wb_video_VY_page_len" value="'.$_wb_video_VY_page_len.'" size="10">
                    </td>
                </tr>
		
		<tr>
                    <td colspan="2"><br><b>'.__('CSS Options (leave the field empty for default value)','workbox_video').'</b></td>
                </tr>
		
		<tr>
                    <td width="200" align="right"><b>'.__('Pages counter DIV container','workbox_video').':</b><br>(.wb_video_pager)<br>&nbsp;</td>
                    <td>
                        <input type="text" name="class_wb_video_pager" value="'.htmlspecialchars(get_option('class_wb_video_pager')).'" style="width: 100%;">
			<br>
			<i>'.__('Default value','workbox_video').': width: 100%; clear: both;</i>
			<br><br>
                    </td>
                </tr>
		<tr>
                    <td width="200" align="right"><b>'.__('Pages counter DIV container link','workbox_video').':</b><br>(.wb_video_pager a)<br>&nbsp;</td>
                    <td>
                        <input type="text" name="class_wb_video_pager_a" value="'.htmlspecialchars(get_option('class_wb_video_pager_a')).'" style="width: 100%;">
			<br>
			<i>'.__('Default value','workbox_video').': none</i>
			<br><br>
                    </td>
                </tr>
		<tr>
                    <td width="200" align="right"><b>'.__('Main container DIV','workbox_video').':</b><br>(.wb_video_container)<br>&nbsp;</td>
                    <td>
                        <input type="text" name="class_wb_video_container" value="'.htmlspecialchars(get_option('class_wb_video_container')).'" style="width: 100%;">
			<br>
			<i>'.__('Default value','workbox_video').': width: 100%; padding: 20px 0;</i>
			<br><br>
                    </td>
                </tr>
		<tr>
                    <td width="200" align="right"><b>'.__('Specific item container DIV','workbox_video').':</b><br>(.wb_video_item)<br>&nbsp;</td>
                    <td>
                        <input type="text" name="class_wb_video_item" value="'.htmlspecialchars(get_option('class_wb_video_item')).'" style="width: 100%;">
			<br>
			<i>'.__('Default value','workbox_video').': clear: both;</i>
			<br><br>
                    </td>
                </tr>
		<tr>
                    <td width="200" align="right"><b>'.__('Image link A','workbox_video').':</b><br>(.wb_video_image_link)<br>&nbsp;</td>
                    <td>
                        <input type="text" name="class_wb_video_image_link" value="'.htmlspecialchars(get_option('class_wb_video_image_link')).'" style="width: 100%;">
			<br>
			<i>'.__('Default value','workbox_video').': float: left; padding: 0 20px 20px 0;</i>
			<br><br>
                    </td>
                </tr>
		<tr>
                    <td width="200" align="right"><b>'.__('Image','workbox_video').':</b><br>(.wb_video_image_img)<br>&nbsp;</td>
                    <td>
                        <input type="text" name="class_wb_video_image_img" value="'.htmlspecialchars(get_option('class_wb_video_image_img')).'" style="width: 100%;">
			<br>
			<i>'.__('Default value','workbox_video').': none</i>
			<br><br>
                    </td>
                </tr>
		<tr>
                    <td width="200" align="right"><b>'.__('Video title link A','workbox_video').':</b><br>(.wb_video_title)<br>&nbsp;</td>
                    <td>
                        <input type="text" name="class_wb_video_title" value="'.htmlspecialchars(get_option('class_wb_video_title')).'" style="width: 100%;">
			<br>
			<i>Default value: none</i>
			<br><br>
                    </td>
                </tr>
			<tr>
                    <td width="200" align="right"><b>'.__('Video description container DIV','workbox_video').':</b><br>(.wb_video_description)<br>&nbsp;</td>
                    <td>
                        <input type="text" name="class_wb_video_description" value="'.htmlspecialchars(get_option('class_wb_video_description')).'" style="width: 100%;">
			<br>
			<i>Default value: none</i>
			<br><br>
                    </td>
            </tr>
			<tr>
				<td width="200" align="right"><b>'.__('Count of video in line','workbox_video').':</b></td>
				<td><input type="text" name="class_wb_video_count_in_line" value="'.htmlspecialchars(get_option('class_wb_video_count_in_line')).'" style="width: 100%;"></td>
			</tr>
            <tr>
                <td width="200" align="right">&nbsp;</td>
                <td>
					<input type="submit" value="'.__('Update Options','workbox_video').'">
                </td>
            </tr>
            </table>
        </form>
        <script language="JavaScript">
            wb_setVisible('.$_wb_video_VY_fw.');
        </script>
        ';
        echo $html;
    }

    public function init() {
        global $wpdb;
		self::checkTables();
        if (isset($_POST['_workbox_edit_video_VY_options_attempt'])) {
            // edit options ettempt
            if (isset($_POST['wb_video_VY_fw'])) {
				update_option('wb_video_VY_fw',$_POST['wb_video_VY_fw']);
            }
            
            if (isset($_POST['wb_video_VY_page_id'])) {
				update_option('wb_video_VY_page_id',intval($_POST['wb_video_VY_page_id']));
            }
            
            if (isset($_POST['wb_video_VY_page_len'])) {
				update_option('wb_video_VY_page_len',intval($_POST['wb_video_VY_page_len']));
            }
	    //////////////////////////////////////////////////////////////////////////////////////////
			if (isset($_POST['class_wb_video_pager'])) {
				update_option('class_wb_video_pager',($_POST['class_wb_video_pager']));
            }
	    
			if (isset($_POST['class_wb_video_pager_a'])) {
				update_option('class_wb_video_pager_a',($_POST['class_wb_video_pager_a']));
            }
	    
			if (isset($_POST['class_wb_video_container'])) {
				update_option('class_wb_video_container',($_POST['class_wb_video_container']));
            }
	    
			if (isset($_POST['class_wb_video_item'])) {
				update_option('class_wb_video_item',($_POST['class_wb_video_item']));
            }
	    
			if (isset($_POST['class_wb_video_image_link'])) {
				update_option('class_wb_video_image_link',($_POST['class_wb_video_image_link']));
            }
	    
			if (isset($_POST['class_wb_video_image_img'])) {
				update_option('class_wb_video_image_img',($_POST['class_wb_video_image_img']));
            }
	    
			if (isset($_POST['class_wb_video_title'])) {
				update_option('class_wb_video_title',($_POST['class_wb_video_title']));
            }
	    
			if (isset($_POST['class_wb_video_description'])) {
				update_option('class_wb_video_description',($_POST['class_wb_video_description']));
            }
			
			if (isset($_POST['class_wb_video_count_in_line'])) {
				update_option('class_wb_video_count_in_line',($_POST['class_wb_video_count_in_line']));
            }
            wp_redirect('admin.php?page='.WB_VIDEO_OPTIONS_PAGE.'&updated');
            die();
        }
        
		if (isset($_GET['move']) && isset($_GET['page']) && $_GET['page'] == WB_VIDEO_GALLERIES_PAGE) {
			// move items
				$dir = $_GET["move"] == 'up'?'up':'down';
				$item_id = intval($_GET['id']);
				
			$order_no = $wpdb->get_var('select order_no from '.WB_VIDEO_GALLERIES_TABLE.' where id='.$item_id);
			
				// get swapped item data
				$row = $wpdb->get_row("select id, order_no from ".WB_VIDEO_GALLERIES_TABLE." where order_no".($dir=="up"?"<":">").$order_no." order by order_no ".($dir=="up"?"desc":"asc")." limit 1");
				if (count($row) == 1){
					$wpdb->update(WB_VIDEO_GALLERIES_TABLE, array("order_no"=>$row->order_no),array("id"=>$item_id));
					$wpdb->update(WB_VIDEO_GALLERIES_TABLE, array("order_no"=>$order_no),array("id"=>$row->id));
				}
			
			wp_redirect('admin.php?page='.WB_VIDEO_GALLERIES_PAGE);
				die();
		}
		$galleryPage = "";
		$galleryId = 0;
		if (isset($_GET['page'])) {
			$galleryGet = explode("_",$_GET['page']);
			if (count($galleryGet) > 1) {
				$galleryPage = $galleryGet[0];
				$galleryId = $galleryGet[1];
			}
		}
		if (isset($_GET['move']) && $galleryPage == "gallery") {
			// move items
			$dir = $_GET["move"] == 'up'?'up':'down';
			$item_id = intval($_GET['id']);  
			$order_no = $wpdb->get_var('select order_no from '.WB_VIDEO_TABLE.' where gallery_id='.$galleryId.' and id='.$item_id);
			// get swapped item data
			$row = $wpdb->get_row("select id, order_no from ".WB_VIDEO_TABLE." where gallery_id=".$galleryId." and order_no".($dir=="up"?"<":">").$order_no." order by order_no ".($dir=="up"?"desc":"asc")." limit 1");
			if (count($row) == 1){
				$wpdb->update(WB_VIDEO_TABLE, array("order_no"=>$row->order_no),array("id"=>$item_id));
				$wpdb->update(WB_VIDEO_TABLE, array("order_no"=>$order_no),array("id"=>$row->id));
			}
			wp_redirect('admin.php?page='.$_GET['page']);
			die();
		}
	
        if (isset($_GET['edit']) && isset($_GET['page']) && $_GET['page'] == WB_VIDEO_PAGE) {
            $GLOBALS['wb_form_info'] = array();
			$item_id = 0;
            if (isset($_GET['id'])) {
                $row = $wpdb->get_row('select * from '.WB_VIDEO_TABLE.' where id='.intval($_GET['id']));
                if ($row) {
                    $GLOBALS['wb_form_info'] = $row;
					$item_id = intval($_GET['id']);
                }
                else {
                    wp_redirect('admin.php?page='.WB_VIDEO_PAGE);
                    die();
                }
            }
            if (!empty($_POST)) {
				if (isset($_POST['action_name']) && $_POST['action_name'] == 'save') {
					$saveArray = array(
					'title'=>stripcslashes(self::_getPost("title")),
					'image'=>'',
					'url'=>stripcslashes(self::_getPost("url")),
					'gallery_id'=>stripcslashes(self::_getPost("gallery_id")),
					'description'=>stripcslashes(self::_getPost("description")),
					'is_live'=>intval(self::_getPost("is_live")),
					);
					
					
					// now check the URL
					$pos = strpos($saveArray['url'], 'http://');
					$posS = strpos($saveArray['url'], 'https://');
					if( $pos === false && $posS === false) {
						$saveArray['url'] = 'http://' . $saveArray['url'];
					}
					
					$media_source = explode('/', $saveArray['url']);
					$media_source = explode('.', $media_source[2]);
					
					if ((($media_source[0] == 'www') && ($media_source[1] == 'vimeo')) || ($media_source[0] == 'vimeo')) {
					// vimeo
					
					$vimeo_key = explode('.com/', $saveArray['url']);
					$vimeo_key = explode('?', $vimeo_key[1]);
					// get info
					$data = @json_decode(file_get_contents('http://vimeo.com/api/v2/video/'.$vimeo_key[0].'.json'));
					
					$thumb = '';
					$width = 0;
					$height = 0;
					if (isset($data[0]->thumbnail_small))
					{
						$thumb = $data[0]->thumbnail_small;
						$width = intval($data[0]->width);
						$height = intval($data[0]->height);
					}    
					$saveArray['image'] = $thumb;
					$saveArray['code'] = '<iframe src="http://player.vimeo.com/video/'.$vimeo_key[0].'?title=0&amp;byline=0&amp;portrait=0&amp;color=6fde9f" width="'.$width.'" height="'.$height.'" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';
					}
					else if ((($media_source[0] == 'www') && ($media_source[1] == 'youtube')) || ($media_source[0] == 'youtu') )
					{
						if(strpos($saveArray['url'], "&v") || strpos($saveArray['url'], "?v")) {
							$youtube_key = explode('/', $saveArray['url']);
							$youtube_key = explode('v=', $youtube_key[3]);
							$youtube_key = explode('&', $youtube_key[1]);
						} else {
							$youtube_key = explode('?', $saveArray['url']);
							$youtube_key[0] = substr($youtube_key[0], -11);
						}
							
						$thumb = 'http://i.ytimg.com/vi/'.$youtube_key[0].'/default.jpg';
						$width = 560;
						$height = 349;
						
						$saveArray['image'] = $thumb;
						$saveArray['code'] = '<iframe width="'.$width.'" height="'.$height.'" src="http://www.youtube.com/embed/'.$youtube_key[0].'?rel=0" frameborder="0" allowfullscreen></iframe>';
					}
					else if ( (($media_source[1] == 'wistia')) || ($media_source[0] == 'wistia') ) {
						$url = 'http://fast.wistia.com/oembed?url='.$saveArray['url'].'&width=640&height=480';  
						$ch = curl_init();      
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   
						curl_setopt($ch, CURLOPT_URL, $url);   
						$result = curl_exec($ch);  
						curl_close($ch);
						$wistia = json_decode($result);
						$saveArray['image'] = $wistia->{'thumbnail_url'};
						$saveArray['code'] = $wistia->{'html'};
					}
					if ($item_id == 0)
					{
					$new_order_no = intval($wpdb->get_var('select max(order_no)+1 as pnum from '.WB_VIDEO_TABLE.' where gallery_id = '.$saveArray['gallery_id']));
					$saveArray['order_no'] = $new_order_no;
					$wpdb->insert(WB_VIDEO_TABLE,$saveArray);
					$item_id = $wpdb->insert_id;
					}
					else
					{
					$wpdb->update(WB_VIDEO_TABLE,$saveArray,array("id"=>$item_id));
					}
					
					wp_redirect('admin.php?page='.WB_VIDEO_PAGE);
                    die();
				}
		
				if (isset($_POST['action_name']) && $_POST['action_name'] == 'delete') {
					if ($item_id>0) {
						$wpdb->query("DELETE FROM ".WB_VIDEO_TABLE." WHERE id=".$item_id);
					}
					wp_redirect('admin.php?page='.WB_VIDEO_PAGE);
					die();
				}
            }
        }	
		//for galleries
		if (isset($_GET['edit']) && isset($_GET['page']) && $_GET['page'] == WB_VIDEO_GALLERIES_PAGE) {
            $GLOBALS['wb_form_info'] = array();
			$item_id = 0;
            if (isset($_GET['id'])) {
                $row = $wpdb->get_row('select * from '.WB_VIDEO_GALLERIES_TABLE.' where id='.intval($_GET['id']));
                if ($row) {
					$GLOBALS['wb_form_info'] = $row;
					$item_id = intval($_GET['id']);
                }
                else {
                    wp_redirect('admin.php?page='.WB_VIDEO_GALLERIES_PAGE);
                    die();
                }
            }
            if (!empty($_POST)) {
                if (isset($_POST['action_name']) && $_POST['action_name'] == 'save') {
					$saveArray = array(
						'title'=>stripcslashes(self::_getPost("title")),
						'post_id'=>stripcslashes(self::_getPost("post_id")),
						'post_blog_id'=>stripcslashes(self::_getPost("post_blog_id")),
						'description'=>stripcslashes(self::_getPost("description")),
						'is_live'=>intval(self::_getPost("is_live")),
						'is_vertical'=>intval(self::_getPost("is_vertical")),
					);
					if ($item_id == 0) {
						$new_order_no = intval($wpdb->get_var('select max(order_no)+1 as pnum from '.WB_VIDEO_GALLERIES_TABLE));
						$saveArray['order_no'] = $new_order_no;
						$wpdb->insert(WB_VIDEO_GALLERIES_TABLE,$saveArray);
						$item_id = $wpdb->insert_id;
					}
					else {
						$wpdb->update(WB_VIDEO_GALLERIES_TABLE,$saveArray,array("id"=>$item_id));
					}
					wp_redirect('admin.php?page='.WB_VIDEO_GALLERIES_PAGE);
					die();
				}
				if (isset($_POST['action_name']) && $_POST['action_name'] == 'delete') {
					if ($item_id>0) {
						$wpdb->query("DELETE FROM ".WB_VIDEO_GALLERIES_TABLE." WHERE id=".$item_id);
					}
					wp_redirect('admin.php?page='.WB_VIDEO_GALLERIES_PAGE);
					die();
				}
			}
        }
    }

    
    private function _get($value, $container, $default = '', $correct = true) {
		if (isset($container->$value))
			return $correct?htmlspecialchars($container->$value):$container->$value;
		else
			return $default;
    }
    
    private function _getPost($value, $default = '') {
		if (isset($_POST[$value])) {
			return $_POST[$value];
		}
		else {
			return $default;
		}
    }
    
    private function getText($content) {
		$content = wpautop(convert_chars($content));
		$content = preg_replace('/\[iframe([^\]]*)\]/mis','<iframe $1></iframe>',$content);
		return $content;
    }
}

?>