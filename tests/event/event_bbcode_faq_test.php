<?php
/**
*
* Advanced BBCode Box
*
* @copyright (c) 2015 Matt Friedman
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace vse\abbc3\tests\event;

require_once dirname(__FILE__) . '/../../../../../includes/functions.php';
require_once dirname(__FILE__) . '/../../../../../includes/functions_content.php';

class event_bbcode_faq_test extends event_listener_base
{
	public function setUp()
	{
		parent::setUp();

		global $config, $phpbb_container, $phpbb_root_path, $phpEx;
		$config = new \phpbb\config\config(array());

		$lang_loader = new \phpbb\language\language_file_loader($phpbb_root_path, $phpEx);
		$lang = new \phpbb\language\language($lang_loader);
		$lang->add_lang('abbc3', 'vse/abbc3');
		$this->user = new \phpbb\user($lang, '\phpbb\datetime');

		// This is needed to set up the s9e text formatter services
		$this->get_test_case_helpers()->set_s9e_services($phpbb_container);
	}

	/**
	 * Data set for test_display_custom_bbcodes and test_allow_custom_bbcodes
	 *
	 * @return array Test data
	 */
	public function add_bbcode_faq_data()
	{
		return array(
			array('HELP_BBCODE_BLOCK_OTHERS', true),
			array('', false),
			array('HELP_BBCODE_BLOCK_INTRO', false),
		);
	}

	/**
	 * Test the add_bbcode_faq event is calling add_bbcode_faq()
	 * and updating the template arrays in the correct position.
	 *
	 * @dataProvider add_bbcode_faq_data
	 */
	public function test_add_bbcode_faq($block_name, $expected)
	{
		$this->set_listener();

		if ($expected)
		{
			$this->template->expects($this->at(0))
				->method('assign_block_vars')
				->with('faq_block', array(
					'BLOCK_TITLE'	=> 'ABBC3_FAQ_TITLE',
					'SWITCH_COLUMN'	=> false,
				))
			;

			$this->template->expects($this->at(1))
				->method('assign_block_vars')
				->with('faq_block.faq_row', array(
					'FAQ_QUESTION'	=> 'ABBC3_FONT_HELPLINE',
					'FAQ_ANSWER'	=> 'ABBC3_FAQ_ANSWER',
				))
			;
		}
		else
		{
			$this->template->expects($this->never())
				->method('assign_block_vars');
		}

		$dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
		$dispatcher->addListener('core.help_manager_add_block_after', array($this->listener, 'add_bbcode_faq'));

		$event_data = array('block_name');
		$event = new \phpbb\event\data(compact($event_data));
		$dispatcher->dispatch('core.help_manager_add_block_after', $event);
	}
}