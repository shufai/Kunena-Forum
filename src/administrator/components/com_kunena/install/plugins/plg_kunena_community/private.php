<?php
/**
 * Kunena Plugin
 *
 * @package         Kunena.Plugins
 * @subpackage      Community
 *
 * @copyright       Copyright (C) 2008 - 2016 Kunena Team. All rights reserved.
 * @license         http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/
defined('_JEXEC') or die();

class KunenaPrivateCommunity extends KunenaPrivate
{
	protected $loaded = false;

	protected $params = null;

	/**
	 * KunenaPrivateCommunity constructor.
	 *
	 * @param $params
	 *
	 * @since Kunena
	 */
	public function __construct($params)
	{
		$this->params = $params;
		CFactory::load('libraries', 'messaging');
	}

	/**
	 * @param $userid
	 *
	 * @return string
	 * @since Kunena
	 */
	protected function getOnClick($userid)
	{
		if (!$this->loaded)
		{
			// PM popup requires JomSocial css to be loaded from selected template
			$config   = CFactory::getConfig();
			$document = JFactory::getDocument();
			$document->addStyleSheet('components/com_community/assets/window.css');
			$document->addStyleSheet('components/com_community/templates/' . $config->get('template') . '/css/style.css');
			$this->loaded = true;
		}

		return ' onclick="' . CMessaging::getPopup($userid) . '"';
	}

	/**
	 * @param $userid
	 *
	 * @return string
	 * @since Kunena
	 */
	protected function getURL($userid)
	{
		return "javascript:void(0)";
	}

	/**
	 * @param $text
	 *
	 * @return string
	 * @since Kunena
	 */
	public function getInboxLink($text)
	{
		if (!$text)
		{
			$text = JText::_('COM_KUNENA_PMS_INBOX');
		}

		return '<a href="' . CRoute::_('index.php?option=com_community&view=inbox') . '" rel="follow">' . $text . '</a>';
	}

	/**
	 * @return string
	 * @since Kunena
	 */
	public function getInboxURL()
	{
		return CRoute::_('index.php?option=com_community&view=inbox');
	}
}
