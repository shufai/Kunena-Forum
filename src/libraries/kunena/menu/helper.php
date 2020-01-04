<?php
/**
 * Kunena Component
 *
 * @package       Kunena.Framework
 * @subpackage    Forum.Menu
 *
 * @copyright     Copyright (C) 2008 - 2020 Kunena Team. All rights reserved.
 * @copyright     Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license       https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link          https://www.kunena.org
 **/
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Multilanguage;

/**
 * Class KunenaMenuHelper
 *
 * @since   Kunena 6.0
 */
abstract class KunenaMenuHelper
{
	/**
	 * @return  void
	 *
	 * @since   Kunena 6.0
	 */
	public static function cleanCache()
	{
		$cache = Factory::getCache();
		$cache->clean('mod_menu');
	}

	/**
	 * Get a list of the menu items (taken from Joomla 2.5.1).
	 * This only method need to be used only in frontend part
	 *
	 * @see     modules/mod_menu/helper.php
	 *
	 * @param   Joomla\Registry\Registry  $params  The module options.
	 *
	 * @return   array
	 *
	 * @since    Kunena 6.0
	 *
	 * @throws   Exception
	 */
	public static function getList(&$params)
	{
		$app  = Factory::getApplication();
		$menu = $app->getMenu();

		// Get active menu item
		$base   = self::getBase($params);
		$user   = Factory::getApplication()->getIdentity();
		$levels = $user->getAuthorisedViewLevels();
		asort($levels);
		$key   = 'menu_items' . $params . implode(',', $levels) . '.' . $base->id;
		$cache = Factory::getCache('mod_menu', '');

		if ($cache->contains($key))
		{
			$items = $cache->get($key);
		}
		else
		{
			$path           = $base->tree;
			$start          = (int) $params->get('startLevel');
			$end            = 0;
			$showAll        = 1;
			$items          = $menu->getItems('menutype', $params->get('menutype'));
			$hidden_parents = array();
			$lastitem       = 0;

			if ($items)
			{
				foreach ($items as $i => $item)
				{
					$item->parent = false;

					if (isset($items[$lastitem]) && $items[$lastitem]->id == $item->parent_id && $item->getParams()->get('menu_show', 1) == 1)
					{
						$items[$lastitem]->parent = true;
					}

					if (($start && $start > $item->level)
						|| ($end && $item->level > $end)
						|| (!$showAll && $item->level > 1 && !in_array($item->parent_id, $path))
						|| ($start > 1 && !in_array($item->tree[$start - 2], $path)))
					{
						unset($items[$i]);
						continue;
					}

					// Exclude item with menu item option set to exclude from menu modules
					if (($item->getParams()->get('menu_show', 1) == 0) || in_array($item->parent_id, $hidden_parents))
					{
						$hidden_parents[] = $item->id;
						unset($items[$i]);
						continue;
					}

					$item->deeper     = false;
					$item->shallower  = false;
					$item->level_diff = 0;

					if (isset($items[$lastitem]))
					{
						$items[$lastitem]->deeper     = ($item->level > $items[$lastitem]->level);
						$items[$lastitem]->shallower  = ($item->level < $items[$lastitem]->level);
						$items[$lastitem]->level_diff = ($items[$lastitem]->level - $item->level);
					}

					$lastitem     = $i;
					$item->active = false;
					$item->flink  = $item->link;

					// Reverted back for CMS version 2.5.6
					switch ($item->type)
					{
						case 'separator':
							break;

						case 'heading':
							// No further action needed.
							break;

						case 'url':
							if ((strpos($item->link, 'index.php?') === 0) && (strpos($item->link, 'Itemid=') === false))
							{
								// If this is an internal Joomla link, ensure the Itemid is set.
								$item->flink = $item->link . '&Itemid=' . $item->id;
							}
							break;

						case 'alias':
							$item->flink = 'index.php?Itemid=' . $item->getParams()->get('aliasoptions');
							break;

						default:
							$item->flink = 'index.php?Itemid=' . $item->id;
							break;
					}

					if ((strpos($item->flink, 'index.php?') !== false) && strcasecmp(substr($item->flink, 0, 4), 'http'))
					{
						$item->flink = Route::_($item->flink, true, $item->getParams()->get('secure'));
					}
					else
					{
						$item->flink = Route::_($item->flink);
					}

					// We prevent the double encoding because for some reason the $item is shared for menu modules and we get double encoding
					// when the cause of that is found the argument should be removed
					$item->title          = htmlspecialchars($item->title, ENT_COMPAT, 'UTF-8', false);
					$item->anchor_css     = htmlspecialchars($item->getParams()->get('menu-anchor_css', ''), ENT_COMPAT, 'UTF-8', false);
					$item->anchor_title   = htmlspecialchars($item->getParams()->get('menu-anchor_title', ''), ENT_COMPAT, 'UTF-8', false);
					$item->anchor_rel     = htmlspecialchars($item->getParams()->get('menu-anchor_rel', ''), ENT_COMPAT, 'UTF-8', false);
					$item->menu_image     = $item->getParams()->get('menu_image', '') ?
						htmlspecialchars($item->getParams()->get('menu_image', ''), ENT_COMPAT, 'UTF-8', false) : '';
					$item->menu_image_css = htmlspecialchars($item->getParams()->get('menu_image_css', ''), ENT_COMPAT, 'UTF-8', false);
				}

				if (isset($items[$lastitem]))
				{
					$items[$lastitem]->deeper     = (($start ?: 1) > $items[$lastitem]->level);
					$items[$lastitem]->shallower  = (($start ?: 1) < $items[$lastitem]->level);
					$items[$lastitem]->level_diff = ($items[$lastitem]->level - ($start ?: 1));
				}
			}

			$cache->store($items, $key);
		}

		return $items;
	}

	/**
	 * Get base menu item.
	 *
	 * @param   Joomla\Registry\Registry $params  The module options.
	 *
	 * @return  object
	 *
	 * @since    3.0.2
	 *
	 * @throws   Exception
	 */
	public static function getBase(&$params)
	{
		// Get base menu item from parameters
		if ($params->get('base'))
		{
			$base = Factory::getApplication()->getMenu()->getItem($params->get('base'));
		}
		else
		{
			$base = false;
		}

		// Use active menu item if no base found
		if (!$base)
		{
			$base = self::getActive($params);
		}

		return $base;
	}

	/**
	 * Get active menu item.
	 *
	 * @param   Joomla\Registry\Registry $params  The module options.
	 *
	 * @return  object
	 *
	 * @since    3.0.2
	 *
	 * @throws   Exception
	 */
	public static function getActive(&$params)
	{
		$menu = Factory::getApplication()->getMenu();

		return $menu->getActive() ?: self::getDefault();
	}

	/**
	 * Get default menu item (home page) for current language.
	 *
	 * @return  object
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	public static function getDefault()
	{
		$menu = Factory::getApplication()->getMenu();
		$lang = Factory::getLanguage();

		// Look for the home menu
		if (Multilanguage::isEnabled())
		{
			return $menu->getDefault($lang->getTag());
		}
		else
		{
			return $menu->getDefault();
		}
	}
}
