<?php
/* 
Plugin Name: Coaching Scorecard Maker
Plugin URI: http://www.BuildAThrivingPractice.com/coaching-scorecard-maker
Version: v1.0
Author: <a href="http://www.BuildAThrivingPractice.com">Build A Thriving Practice</a> | <a href="http://www.BuildAThrivingPractice.com/coaching-scorecard-maker">Documentation and Help</a> | <a href="http://www.BuildAThrivingPractice.com/coaching-scorecard-maker/builder">Help Building Your Scorecard</a>
Description: A simple coaching scorecard and analysis generator.
*/

/*  
License: 
Copyright (c) 2011 Effexis and BuildAThrivingPractice.com

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, All warranties, conditions, representations and indemnities, whether 
express, implied, statutory or otherwise, including without limitation, warranties 
as to satisfactory quality, being error or virus free, being secure, fitness for a particular purpose, 
merchantability, correctness, reliability or uninterrupted use are hereby overridden, 
excluded and disclaimed.

IN NO EVENT UNLESS REQUIRED BY APPLICABLE LAW OR AGREED TO IN WRITING
WILL ANY COPYRIGHT HOLDER, OR ANY OTHER PARTY WHO MAY MODIFY AND/OR
REDISTRIBUTE THE PROGRAM AS PERMITTED ABOVE, BE LIABLE TO YOU FOR DAMAGES,
INCLUDING ANY GENERAL, SPECIAL, INCIDENTAL OR CONSEQUENTIAL DAMAGES ARISING
OUT OF THE USE OR INABILITY TO USE THE PROGRAM (INCLUDING BUT NOT LIMITED
TO LOSS OF DATA OR DATA BEING RENDERED INACCURATE OR LOSSES SUSTAINED BY
YOU OR THIRD PARTIES OR A FAILURE OF THE PROGRAM TO OPERATE WITH ANY OTHER
PROGRAMS), EVEN IF SUCH HOLDER OR OTHER PARTY HAS BEEN ADVISED OF THE
POSSIBILITY OF SUCH DAMAGES.

*/

// Pre-2.6 compatibility
if ( ! defined( 'WP_CONTENT_URL' ) )
      define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( ! defined( 'WP_CONTENT_DIR' ) )
      define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) )
      define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) )
      define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );

add_action('plugins_loaded',array('BTPCoachingScorecardMaker','start'));

if (!class_exists("BTPCoachingScorecardMaker")) {
	class BTPCoachingScorecardMaker {
		// Get the classname for the plugin
		function classname() { return get_class($this); }
		
		function start() {
			global $BTPCSM_MainInstance;
			if (!isset($BTPCSM_MainInstance)) {
				if (version_compare(PHP_VERSION, '5.0.0', '<')) {
					$BTPCSM_MainInstance = &new BTPCoachingScorecardMaker();
				} else {
					$BTPCSM_MainInstance= new BTPCoachingScorecardMaker();
				}
			}
		} // start

		var $version = "1.1.0.0";
		var $docURL = "http://www.BuildAThrivingPractice.com/coaching-scorecard-maker/";
		var $itemRoot = "CSMItem";

		// Various path variables
		var $pluginURL = "";
		var $pluginPath = "";
		
		var $currentGroup = null;

		var $totalScore;
		var $groupScores;
		var $itemScores;

		// Constructor
		function BTPCoachingScorecardMaker() {
			// Get plugin paths
			$this->pluginPath = WP_PLUGIN_DIR . "/" . plugin_basename(dirname(__FILE__));
			$this->pluginURL = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__));
			
			// Add appropriate actions and filters to Wordpress
			
			if (is_admin()) {
				//add_action('admin_menu', array(&$this, 'AddAdminPages'));
			}
			else {
				//Actions
				add_action('wp_print_styles', array(&$this, 'AddStyles'));
				
				// Shortcodes for the scorecard page
				add_shortcode('csmheadline', array(&$this,'SCHeadline'));
				add_shortcode('csmcardstart', array(&$this,'SCCardStart'));
				add_shortcode('csmcardend', array(&$this,'SCCardEnd'));
				add_shortcode('csmtitlerow', array(&$this,'SCTitleRow'));
				add_shortcode('csmgroupstart', array(&$this,'SCGroupStart'));
				add_shortcode('csmgroupend', array(&$this,'SCGroupEnd'));
				add_shortcode('csmitem', array(&$this,'SCItem'));

				// shortcodes for the analysis page
				add_shortcode('csmstartanalysis', array(&$this,'SCStartAnalysis'));
				add_shortcode('csmtotalscore', array(&$this,'SCTotalScore'));
				add_shortcode('csmtotalrange', array(&$this,'SCTotalRange'));
				add_shortcode('csmgroupscore', array(&$this,'SCGroupScore'));
				add_shortcode('csmgrouprange', array(&$this,'SCGroupRange'));
				add_shortcode('csmitemscore', array(&$this,'SCItemScore'));
				add_shortcode('csmitemrange', array(&$this,'SCItemRange'));
			}
		}

		function SCHeadline($atts, $content = null) {
			extract( shortcode_atts( array(
				), $atts ) );
	
			return '<p class="csm-scorecard-headline">' . do_shortcode($content) . '</p>';
		}

		// Outputs the <form> containing the scorecard
		function SCCardStart($atts, $content = null) {
			extract( shortcode_atts( array(
				'name' => 'Scorecard',
				'action' => null,
				), $atts ) );

			if (is_null($action)) {
				return '<p>csmcardstart shortcode needs a valid action argument</p>';
			}
			else
			{
				return '<form name="' . esc_attr($name) . '" method="post" action="' . esc_url($action) .'"> <table class="csm-main-table">';
			}
		}

		// Outputs the submit and ending </table> </form> for the scorecard
		function SCCardEnd($atts, $content = null) {
			extract( shortcode_atts( array(
				'text' => "Get Your Score",
				'credit' => null
			), $atts ) );

			$submit_out = '<center><input class="csm-card-submit" type="submit" name="cmdSubmit" value="'. esc_attr($text) . '"></center>';
			
			if (!is_null($credit))	{
				$submit_out .= '<center><p class="csm-credit">Powered By <a href="http://www.BuildAThrivingPractice.com/coaching-scorecard-maker">Coaching Scorecard Maker</a></p></center>';
			}

			return '<tr class="csm-row"><td class="csm-card-submit-column" colspan="2">' . $submit_out . '</td></tr></table></form>';
		}

		// Outputs a title row for the scorecard
		function SCTitleRow($atts, $content = null) {
			extract( shortcode_atts( array(
				'scoreColumn' => 'Score'
				), $atts ) );
			
			if (is_null($content)) {
				return '<p>Missing clossing [/csmtitlerow] shortcode</p>';
			}


			return '<tr class="csm-row"><td class="csm-title-item-column"><p class="csm-title-item">' . 
				do_shortcode($content) . '</p></td><td class="csm-title-score-column">' .
				esc_html($scoreColumn) . '</td></tr>';
		}

		// Start a new group of related items
		function SCGroupStart($atts, $content = null) {
			extract( shortcode_atts( array(
				'group' => null
				), $atts ) );
	
			$table_class = "csm-group-start";

			// set the current group for all items			
			$this->currentGroup = $group;
			
			if (!is_null($content)) {
				return '<tr class="csm-row"><td colspan="2" class="' . $table_class . '"><p class="csm-group-title">' . do_shortcode($content) . '</p></td></tr>';
			}
			else {
				return '<tr class="csm-row"><td colspan="2" class="' . $table_class . '"></td></tr>';
			}
		}

		function SCGroupEnd($atts, $content = null) {
			extract( shortcode_atts( array(
				), $atts ) );

			return '';
		}

		// [csmitem] shortcode implementation
		function SCItem($atts, $content = null) {
			extract( shortcode_atts( array(
				// name of the group for the item (can be null)
				'group' => null,
				// name of the item (must be defined)
				'item' => null
				), $atts ) );

			if (is_null($content)) {
				return '<p>Missing clossing [/csmitem] shortcode</p>';
			}

			if (is_null($group)) {
				// use the default current group is there is one
				$group = $this->currentGroup;
				// if the current group is null, fall back to G1
				if (is_null($group)) { $group = "G1"; }
			}

			if (is_null($item)) {
				return '<p>Missing item argument for csitem shortcode</p>';
			}

			return '<tr class="csm-row"><td class="csm-card-item-column"><p class="csm-card-item-description">' .
				do_shortcode($content) . '</p></td><td class="csm-card-score-column"><input class="csm-card-score-input" name="' . $this->itemRoot . '[' .
				esc_attr($group) . '][' . esc_attr($item) . ']" size="5" type="text"></td></tr>';
		}

		// start the analysis page by getting the quiz answers into the correct array
		// [csmstartanalysis] shortcode implementation
		function SCStartAnalysis($atts, $content = null) {
			extract( shortcode_atts( array(
				'min_score' => 1,
				'max_score' => 5,
				'debug_analysis' => null,
				), $atts ) );

			// reset the score
			$this->totalScore = 0;

			$min_score = intval($min_score);
			$max_score = intval($max_score);
			
			// reset the group scores
			$this->groupScores = array();

			$scout = "";
			
			if (!is_null($debug_analysis)) {
				$debug_analysis = true;
			}
			else {
				$debug_analysis = false;
			}
			
			if ($debug_analysis) {
				$scout = $scout . '<p>Debug Analysis... Min Score: '	. esc_html($min_score) . ', Max Score: ' . esc_html($max_score) . '</p>';
			}
			
			// compute the score
			$scout = $scout . $this->ComputeScore($min_score,$max_score,$debug_analysis);

			return $scout;
		}
		
		// Compute the score using the post & get data
		function ComputeScore($minScore,$maxScore,$debug_analysis) {
			$scout = "";
			$groups = $_POST[$this->itemRoot];
			if (isset($groups) && is_array($groups)) {
				// process the groups array one group at a time
				foreach ($groups as $group => $items) {
					if ($debug_analysis) {
						$scout = $scout . '<p>Analyze Group: ' . esc_html($group) . ' </p>';
					}
					if (isset($items) && is_array($items)) {
						// initialize the group score
						$this->groupScores[$group] = 0;
						// process the items one item at a time
						foreach ($items as $item => $item_value) {
							
							if (isset($item_value)) {
								// the numeric value from 1 to 5
								$item_score = max($minScore,min($maxScore,intval($item_value)));
							}
							else { $item_score = $minScore; }
								
							// add it to the total score
							$this->totalScore += $item_score;

							// add it to the group score for the appropriate group
							$this->groupScores[$group] = $this->groupScores[$group] + $item_score;
							// save it as the item score
							$this->itemScores[$item] = $item_score;

							if ($debug_analysis) {
								$scout = $scout . '<p>&nbsp;&nbsp;Analyze Item: ' . esc_html($item) . ' with score ' . esc_html($item_score) . '</p>';
							}
						} // foreach item	
					} // $items is valid
					if ($debug_analysis) {
						$scout = $scout . '<p>Group ' . esc_html($group) . ' Score = ' . esc_html($this->groupScores[$group]) . '</p>';
					}
				} // foreach group
			} // $groups is valid

			if ($debug_analysis) {
				$scout = $scout . '<p>Total Score = ' . esc_html($this->totalScore) . '</p>';
			}
			
			return $scout;
		} // ComputeScores

		// display the total score value
		function SCTotalScore($atts, $content = null) {
			extract( shortcode_atts( array(
				), $atts ) );

			return esc_html($this->totalScore);
		}

		// start the display of the item if the total score is in the given range
		function SCTotalRange($atts, $content = null) {
			extract( shortcode_atts( array(
				'start' => null,
				'end' => null,
				), $atts ) );

			if (is_null($content)) {
				return '<p>Missing clossing [/csmtotalrange] shortcode</p>';
			}

			if (is_null($start) && is_null($end)) {
				return '<p>Need at least one of start or end arguments to be set</p>';
			}
			
			return $this->DisplayIfScoreInRange($this->totalScore,$start,$end,$content);
		}
		
		// display the total score value
		function SCGroupScore($atts, $content = null) {
			extract( shortcode_atts( array(
				'group' => 'G1'
				), $atts ) );

			$score = 0;
			$score = $this->groupScores[$group];
			if (is_null($score)) {
				$score = 0;	
			}
			
			return esc_html($score);
		}

		// start the display of the item if the total score is in the given range
		function SCGroupRange($atts, $content = null) {
			extract( shortcode_atts( array(
				'group' => 'G1',
				'start' => null,
				'end' => null,
				), $atts ) );

			if (is_null($content)) {
				return '<p>Missing clossing [/csmgrouprange] shortcode</p>';
			}

			if (is_null($start) && is_null($end)) {
				return '<p>Need at least one of start or end arguments to be set</p>';
			}
			
			$score = $this->groupScores[$group];
			if (is_null($score)) {
				$score = 0;	
			}
			
			return $this->DisplayIfScoreInRange($score,$start,$end,$content);
		}

		// display the total score value
		function SCItemScore($atts, $content = null) {
			extract( shortcode_atts( array(
				'item' => null
				), $atts ) );

			if (is_null($item)) {
				return '<p>Missing item argument in csmitemscore</p>';
			}

			$score = $this->itemScores[$item];
			if (is_null($score)) {
				$score = 0;	
			}
			
			return esc_html($score);
		}

		// start the display of the item if the total score is in the given range
		function SCItemRange($atts, $content = null) {
			extract( shortcode_atts( array(
				'item' => null,
				'start' => null,
				'end' => null,
				), $atts ) );

			if (is_null($content)) {
				return '<p>Missing clossing [/csmitemrange] shortcode</p>';
			}
			
			if (is_null($item)) {
				return '<p>Missing item argument in csmitemrange</p>';
			}

			if (is_null($start) && is_null($end)) {
				return '<p>Need at least one of start or end arguments to be set in csmitemrange</p>';
			}
			
			$score = $this->itemScores[$item];
			if (is_null($score)) {
				$score = 0;	
			}
			
			return $this->DisplayIfScoreInRange($score,$start,$end,$content);
		}
		
		// Display the content if the score is in the given range
		function DisplayIfScoreInRange($score, $start, $end, $content) {
			if (is_null($end)) {
				if ($score >= $start) {
					return do_shortcode($content);
				}
				else {
					return '';
				}
			}
			else if (is_null($start)) {
				if ($score <= $end) {
					return do_shortcode($content);
				}
				else {
					return '';
				}
			}
			else {
				if ($score >= $start && $score <= $end) {
					return do_shortcode($content);
				}
				else {
					return '';
				}
			}
		}

		// add header code to include the CSS stylesheet for the plugin
		function AddStyles() {
			// check for a custom CSS file first
			$customStyleUrl = get_stylesheet_directory_uri() . '/coaching-scorecard-maker.css';
			$customStyleFile = get_stylesheet_directory() . '/coaching-scorecard-maker.css';
			
			if (file_exists($customStyleFile)) {
				wp_register_style('BTPCoachingScorecardMarkerStyle', $customStyleUrl);
				wp_enqueue_style( 'BTPCoachingScorecardMarkerStyle');
			}
			else {				
				$myStyleUrl = $this->pluginURL . '/css/coaching-scorecard-maker.css';
				$myStyleFile = $this->pluginPath . '/css/coaching-scorecard-maker.css';
				if ( file_exists($myStyleFile) ) {
					wp_register_style('BTPCoachingScorecardMarkerStyle', $myStyleUrl);
					wp_enqueue_style( 'BTPCoachingScorecardMarkerStyle');
				}
			}
		}
	} //End Class BTPCoachingScorecardMaker
} // class doesn't exist

?>