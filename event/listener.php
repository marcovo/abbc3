<?php
/**
*
* Advanced BBCode Box
*
* @copyright (c) 2013 Matt Friedman
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace vse\abbc3\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{
	/** @var \vse\abbc3\core\bbcodes_parser */
	protected $bbcodes_parser;

	/** @var \vse\abbc3\core\bbcodes_display */
	protected $bbcodes_display;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var string phpBB root path */
	protected $root_path;

	/** @var string Extension root path */
	protected $ext_root_path;

	/** @var string default width of bbvideo */
	protected $bbvideo_width;

	/** @var string default height of bbvideo */
	protected $bbvideo_height;

	/**
	 * Constructor
	 *
	 * @param \vse\abbc3\core\bbcodes_parser  $bbcodes_parser
	 * @param \vse\abbc3\core\bbcodes_display $bbcodes_display
	 * @param \phpbb\controller\helper        $helper
	 * @param \phpbb\template\template        $template
	 * @param \phpbb\user                     $user
	 * @param string                          $root_path
	 * @param string                          $ext_root_path
	 * @param string                          $bbvideo_width
	 * @param string                          $bbvideo_height
	 * @access public
	 */
	public function __construct(\vse\abbc3\core\bbcodes_parser $bbcodes_parser, \vse\abbc3\core\bbcodes_display $bbcodes_display, \phpbb\controller\helper $helper, \phpbb\template\template $template, \phpbb\user $user, $root_path, $ext_root_path, $bbvideo_width, $bbvideo_height)
	{
		$this->bbcodes_parser = $bbcodes_parser;
		$this->bbcodes_display = $bbcodes_display;
		$this->helper = $helper;
		$this->template = $template;
		$this->user = $user;
		$this->root_path = $root_path;
		$this->ext_root_path = $ext_root_path;
		$this->bbvideo_width = $bbvideo_width;
		$this->bbvideo_height = $bbvideo_height;
	}

	/**
	 * Assign functions defined in this class to event listeners in the core
	 *
	 * @return array
	 * @static
	 * @access public
	 */
	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup'							=> 'load_language_on_setup',

			// functions_content events
			'core.modify_text_for_display_before'		=> 'parse_bbcodes_before',
			'core.modify_text_for_display_after'		=> 'parse_bbcodes_after',

			// functions_display events
			'core.display_custom_bbcodes'				=> 'setup_custom_bbcodes',
			'core.display_custom_bbcodes_modify_sql'	=> 'custom_bbcode_modify_sql',
			'core.display_custom_bbcodes_modify_row'	=> 'display_custom_bbcodes',

			// message_parser events
			'core.modify_format_display_text_after'		=> 'parse_bbcodes_after',
			'core.modify_bbcode_init'					=> 'allow_custom_bbcodes',
		);
	}

	/**
	 * Load common files during user setup
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'vse/abbc3',
			'lang_set' => 'abbc3',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}

	/**
	 * Alter BBCodes before they are processed by phpBB
	 *
	 * This is used to change old/malformed ABBC3 BBCodes to a newer structure
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function parse_bbcodes_before($event)
	{
		$event['text'] = $this->bbcodes_parser->pre_parse_bbcodes($event['text'], $event['uid']);
	}

	/**
	 * Alter BBCodes after they are processed by phpBB
	 *
	 * This is used on ABBC3 BBCodes that require additional post-processing
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function parse_bbcodes_after($event)
	{
		$event['text'] = $this->bbcodes_parser->post_parse_bbcodes($event['text']);
	}

	/**
	 * Modify the SQL array to gather custom BBCode data
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function custom_bbcode_modify_sql($event)
	{
		$sql_ary = $event['sql_ary'];
		$sql_ary['SELECT'] .= ', b.bbcode_group';
		$sql_ary['ORDER_BY'] = 'b.bbcode_order, b.bbcode_id';
		$event['sql_ary'] = $sql_ary;
	}

	/**
	 * Setup custom BBCode variables
	 *
	 * @return null
	 * @access public
	 */
	public function setup_custom_bbcodes()
	{
		$this->template->assign_vars(array(
			'ABBC3_USERNAME'			=> $this->user->data['username'],
			'ABBC3_BBCODE_ICONS'		=> $this->ext_root_path . 'images/icons',
			'ABBC3_BBVIDEO_HEIGHT'		=> $this->bbvideo_height,
			'ABBC3_BBVIDEO_WIDTH'		=> $this->bbvideo_width,

			'UA_ABBC3_BBVIDEO_WIZARD'	=> $this->helper->route('vse_abbc3_bbcode_wizard', array('mode' => 'bbvideo')),
			'UA_ABBC3_URL_WIZARD'		=> $this->helper->route('vse_abbc3_bbcode_wizard', array('mode' => 'url')),
		));
	}

	/**
	 * Alter custom BBCodes display
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function display_custom_bbcodes($event)
	{
		$event['custom_tags'] = $this->bbcodes_display->display_custom_bbcodes($event['custom_tags'], $event['row']);
	}

	/**
	 * Set custom BBCodes permissions
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function allow_custom_bbcodes($event)
	{
		$event['bbcodes'] = $this->bbcodes_display->allow_custom_bbcodes($event['bbcodes'], $event['rowset']);
	}
}
