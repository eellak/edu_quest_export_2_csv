<?php

/**
 * ellak - FOSS in Unis questionary export to CSV format plugin
 *
 * @package     none
 * @author      David Bromoiras
 * @copyright   2017 Your Name or Company Name
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: FOSS in Unis questionary export to CSV format plugin
 * Description: Produce the opendata csv file that derives from the edu-quest results.
 * Version:     0.1
 * Author:      David Bromoiras
 * Author URI:  https://www.anchor-web.gr
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txtd
 *

  /* PLUGIN DOCUMENTATION AT https://team.ellak.gr/projects/sites/wiki/Greek-edu-fos/ */

register_activation_hook(__FILE__, 'edu_quest_export_2_csv_activation');
register_activation_hook(__FILE__, 'ellak_edu_quest_export_2_csv');

function edu_quest_export_2_csv_activation(){
	if (! wp_next_scheduled('edu_quest_hourly_event')){
		wp_schedule_event(time(), 'hourly', 'edu_quest_hourly_event');
	}
}

add_action('edu_quest_hourly_event', 'ellak_edu_quest_export_2_csv');

function ellak_edu_quest_export_2_csv(){
	$args=array('post_type' => 'edu_quest_post_type', 'posts_per_page' => -1, 'post_status' => 'publish');
	$quest_q=new WP_Query($args);
	$custom_base_dir=get_stylesheet_directory();
		
    
	if($quest_q->have_posts()){

		// create the csv file
		$res_file=fopen($custom_base_dir.'/assets/files/edu_quest_opendata.csv', 'w');
		
		// write the header line of the csv with the column titles
		$csv_header_line='applicant_name,applicant_surname,applicant_email,applicant_position,institution,department,course,software,software_url,lab_name,lab_activity,lab_activity_description,lab_head,lab_website,graduate_title'.PHP_EOL;
		fwrite($res_file, $csv_header_line);

		while($quest_q->have_posts()){
			$quest_q->the_post();
			if(isset($csv_line)){unset($csv_line);}
			$csv_line=array();
			
			// the title of each post, being identical to the email field, thus 
			// redundant, is removed from the csv completely.
			// $csv_line[]=get_the_title();
			$csv_line[]='"'.get_field('edu_quest_applicant_name').'"';
			$csv_line[]='"'.get_field('edu_quest_applicant_surname').'"';
			$csv_line[]='"'.get_field('edu_quest_applicant_email').'"';
			$position=get_field('edu_quest_applicant_position');
			switch ($position){
				case 'didaktiko':
					$csv_line[]='Διδακτικό Ερευνητικό Προσωπικό';
					break;
				case 'dioikitiko':
					$csv_line[]='Διοικητικό Προσωπικό';
					break;
				case 'ergastiriako':
					$csv_line[]='Εργαστηριακό Προσωπικό - Βοηθοί και Επιστημονικοί Συνεργάτες';
					break;
				case 'meta':
					$csv_line[]='Μεταπτυχιακός φοιτητής';
					break;
				case 'fititis':
					$csv_line[]='Φοιτητής/τρια';
					break;
				default:
					$csv_line[]='';
					break;
			}
			$csv_line[]='"'.get_field('edu_quest_institution').'"';
			$csv_line[]='"'.get_field('edu_quest_department').'"';
			$csv_line[]='"'.get_field('edu_quest_course').'"';
			$csv_line[]='"'.get_field('edu_quest_software').'"';
			
			$sw_url=get_field('edu_quest_software_url');
			if(preg_match('~(^http://|^https://|^\"http://|^\"https://)~', $sw_url)==0){
				$csv_line[]='http://'.$sw_url;
				update_post_meta(get_the_ID(), 'edu_quest_software_url', 'http://'.$sw_url);
			}
			else{
				$csv_line[]=$sw_url;
			}
			$csv_line[]='"'.get_field('edu_quest_lab_name').'"';
			$csv_line[]='"'.get_field('edu_quest_lab_activity').'"';
			$csv_line[]='"'.get_field('edu_quest_lab_activity_description').'"';
			$csv_line[]='"'.get_field('edu_quest_lab_head').'"';
			$csv_line[]='"'.get_field('edu_quest_lab_website').'"';
			$csv_line[]='"'.get_field('edu_quest_graduate_title').'"';
			
			// checking to see if last line of the csv. if yes, do not append a
			// line break
			if(($quest_q->current_post +1) == ($quest_q->post_count)){
				fwrite($res_file, implode(',', $csv_line));
			}
			else{
				fwrite($res_file, implode(',', $csv_line).PHP_EOL);
			}
			
			// check and replace url if http://is absent
			
			//$sw_url=get_post_meta(get_the_ID(), 'edu_quest_software_url');
			//if(!preg_match("/(^http:\/\/|^https:\/\/)/", $sw_url)){
				//$csv_line[]='http://'.$sw_url;
				//update_post_meta(get_the_ID(), 'http://'.$sw_url);
			//}
			//else{
				//update_post_meta(get_the_ID(), $sw_url);
			//}
		}
		
		fclose($res_file);
		date_default_timezone_set('Europe/Athens');
		$date_string=date('D, d M Y H:i:s', time());
		update_post_meta(6735, 'edu_quest_update_date', $date_string);
	}
}

register_deactivation_hook(__FILE__, 'edu_quest_event_deactivation');

function edu_quest_event_deactivation() {
	wp_clear_scheduled_hook('edu_quest_hourly_event');
}

/* Add monthly scheduling interval */
//function ellak_add_my_scheduling_intervals($schedules){
//    //error_log('check 3-4');
//    $schedules['ellak_weekly']=array('interval'=>604800, 'display'=>__('Once weekly'));
//    $schedules['ellak_monthly']=array('interval'=>2635200, 'display'=>__('Once monthly'));
//    return $schedules;
//}
//add_filter('cron_schedules', 'ellak_add_my_scheduling_intervals');

//register_activation_hook(__FILE__, 'ellak_activate_contributors_synch');
///* Monthly schedule the synching of files with posts */
//function ellak_activate_contributors_synch(){
//    if(! wp_next_scheduled('ellak_monthly_synch_contributors')){
//        
//        if(!wp_schedule_event(time()+60, '1min', 'ellak_monthly_synch_contributors'))
//                error_log('check 1-2');
//    }
//}
//add_action('ellak_monthly_synch_contributors', 'ellak_github_contributors_synch_posts');
//
//register_deactivation_hook(__FILE__, 'ellak_deactivate_contributors_synch');
//function ellak_deactivate_contributors_synch() {
//	wp_clear_scheduled_hook('ellak_monthly_synch_contributors');
//}
